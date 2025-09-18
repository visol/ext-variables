<?php

namespace Sinso\Variables\Service;

use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Collection\Set;
use Sinso\Variables\Domain\Model\Marker;
use Sinso\Variables\Domain\Model\MarkerCollection;
use Sinso\Variables\Hooks\MarkersProcessorInterface;
use Sinso\Variables\Utility\CacheKeyUtility;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class VariablesService
{
    public const MAXIMUM_LOOP_COUNT = 100;

    /**
     * @var Set<string>
     */
    protected Set $cacheTags;
    /**
     * @var string[]
     */
    protected array $usedMarkerKeys = [];

    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly Context $context,
        private readonly ConnectionPool $connectionPool,
    ) {
        $this->cacheTags = new Set('string');
    }

    /**
     * Iterates over a structure (array, object) and replaces markers in every string found.
     *
     *
     * @throws \Exception
     */
    public function replaceMarkersInStructureAndAdjustCaching(
        ServerRequestInterface $request,
        mixed &$structure
    ): void {
        $this->replaceMarkersInStructure($this->getMarkers($request), $structure);
        $this->setCacheTags($request);
    }

    /**
     * Iterates over a structure (array, object) and replaces markers in every string found.
     *
     * @throws \Exception
     */
    private function replaceMarkersInStructure(
        MarkerCollection $markerCollection,
        mixed &$structure
    ): void {
        if (is_null($structure) || is_bool($structure) || is_int($structure) || is_float($structure) || $structure instanceof \UnitEnum) {
            return;
        }

        if (is_string($structure)) {
            $this->replaceMarkersInText($markerCollection, $structure);
            return;
        }

        if (is_iterable($structure)) {
            foreach ($structure as &$subStructure) {
                $this->replaceMarkersInStructure($markerCollection, $subStructure);
            }
            return;
        }

        throw new \Exception(sprintf('Unsupported type "%s" in structure', gettype($structure)), 1725955598);
    }

    private function replaceMarkersInText(
        MarkerCollection $markerCollection,
        string &$text
    ): void {
        $markerRegexp = '/(' . implode('|', array_map('preg_quote', $markerCollection->getMarkerKeys())) . ')/';

        $loops = 0;

        while (preg_match($markerRegexp, $text) && $loops++ < self::MAXIMUM_LOOP_COUNT) {
            foreach ($markerCollection as $marker) {
                $newContent = str_replace(
                    $marker->getMarkerWithBrackets(),
                    $marker->replacement,
                    $text
                );

                if ($newContent === $text) {
                    continue;
                }

                // Assign a cache key associated with the marker
                $this->cacheTags->add(
                    CacheKeyUtility::getCacheKey(
                        $marker->getMarkerWithBrackets()
                    )
                );
                $this->usedMarkerKeys[] = $marker->key;
                $text = $newContent;
            }
        }

        $this->usedMarkerKeys = array_unique($this->usedMarkerKeys);

        // Remove all markers (avoids empty entries)
        if ($this->extensionConfiguration->get('variables', 'removeUnreplacedMarkers')) {
            $text = preg_replace('/{{.*?}}/', '', $text);
        }
    }

    /**
     * Returns the markers available in the current root line.
     */
    private function getMarkers(
        ServerRequestInterface $request,
    ): MarkerCollection {
        $pids = array_map(static function ($page) {
            return $page['uid'];
        }, $request->getAttribute('frontend.page.information')?->getRootLine() ?? []);

        $storagePid = $this->getStoragePidFromTypoScript($request);
        if (is_int($storagePid)) {
            $pids[] = $storagePid;
        }

        $table = 'tx_variables_marker';
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $rows = $contentObjectRenderer->getRecords(
            $table,
            [
                'selectFields' => 'marker, replacement',
                'pidInList' => implode(',', $pids),
                'orderBy' => 'uid ASC',
            ]
        );

        $markers = new MarkerCollection();
        foreach ($rows as $row) {
            $markers->add(
                new Marker(
                    uid: $row['uid'],
                    key: $row['marker'],
                    replacement: $row['replacement'],
                )
            );
        }

        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['variables']['postProcessMarkers'] ?? null)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['variables']['postProcessMarkers'] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (!($hookObj instanceof MarkersProcessorInterface)) {
                    throw new \RuntimeException($classRef . ' does not implement ' . MarkersProcessorInterface::class, 1512391205);
                }
                $hookObj->postProcessMarkers($markers);
            }
        }
        return $markers;
    }

    private function setCacheTags(
        ServerRequestInterface $request,
    ): void {
        if (count($this->cacheTags) === 0) {
            return;
        }

        $cacheCollector = $request->getAttribute('frontend.cache.collector');
        if (($cacheCollector instanceof CacheDataCollector) === false) {
            return;
        }

        $cacheTags = array_map(function (string $tag): CacheTag {
            return new CacheTag($tag);
        }, $this->cacheTags->toArray());

        $cacheCollector->addCacheTags(...$cacheTags);
    }

    public function getLifetime(): int
    {
        return $this->getNearestTimestampForMarkers() - $this->context->getPropertyFromAspect('date', 'timestamp');
    }

    /**
     * Get the nearest timestamp in the future when changes for Markers should happen.
     * This respects starttime and endtime.
     * The result will be used to calculate the maximal caching duration
     */
    private function getNearestTimestampForMarkers(): int
    {
        // Max value possible to keep an int \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->realPageCacheContent ($timeOutTime = $GLOBALS['EXEC_TIME'] + $cacheTimeout;)
        $result = PHP_INT_MAX;

        $tableName = 'tx_variables_marker';
        $queryBuilder = $this->connectionPool->getConnectionForTable($tableName)->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        // Code heavily inspired by:
        // \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getFirstTimeValueForRecord
        $now = (int)$this->context->getPropertyFromAspect('date', 'timestamp');
        $timeFields = [];
        $timeConditions = $queryBuilder->expr()->or();
        foreach (['starttime', 'endtime'] as $field) {
            if (isset($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'][$field])) {
                $timeFields[$field] = $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'][$field];
                $queryBuilder->addSelectLiteral(
                    'MIN('
                    . 'CASE WHEN '
                    . $queryBuilder->expr()->lte(
                        $timeFields[$field],
                        $queryBuilder->createNamedParameter($now, Connection::PARAM_INT)
                    )
                    . ' THEN NULL ELSE ' . $queryBuilder->quoteIdentifier($timeFields[$field]) . ' END'
                    . ') AS ' . $queryBuilder->quoteIdentifier($timeFields[$field])
                );
                $timeConditions->with(
                    $queryBuilder->expr()->gt(
                        $timeFields[$field],
                        $queryBuilder->createNamedParameter($now, Connection::PARAM_INT)
                    )
                );
            }
        }

        // if starttime or endtime are defined, evaluate them
        if ($timeFields !== []) {
            // find the timestamp, when the current page's content changes the next time
            $queryBuilder
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->in('marker', $queryBuilder->createNamedParameter($this->usedMarkerKeys, Connection::PARAM_STR_ARRAY)),
                    $timeConditions
                );
            $row = $queryBuilder
                ->executeQuery()
                ->fetchAssociative();

            if ($row) {
                foreach (array_keys($timeFields) as $timeField) {
                    // if a MIN value is found, take it into account for the
                    // cache lifetime we have to filter out start/endtimes < $now,
                    // as the SQL query also returns rows with starttime < $now
                    // and endtime > $now (and using a starttime from the past
                    // would be wrong)
                    if ($row[$timeField] !== null && (int)$row[$timeField] > $now) {
                        $result = min($result, (int)$row[$timeField]);
                    }
                }
            }
        }

        return $result;
    }

    private function getStoragePidFromTypoScript(ServerRequestInterface $request): ?int
    {
        $typoScript = $request->getAttribute('frontend.typoscript');
        if (($typoScript instanceof FrontendTypoScript) === false) {
            return null;
        }

        $typoScriptArray = $typoScript->getSetupArray();
        $lookUpPath = 'plugin./tx_variables./persistence./storagePid';

        if (ArrayUtility::isValidPath($typoScriptArray, $lookUpPath) === false) {
            return null;
        }

        $storagePid = ArrayUtility::getValueByPath($typoScriptArray, $lookUpPath);
        if (is_numeric($storagePid) === false) {
            return null;
        }

        return (int)$storagePid;
    }
}
