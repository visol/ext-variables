<?php

namespace Sinso\Variables\Service;

use Ramsey\Collection\Set;
use Sinso\Variables\Domain\Model\Marker;
use Sinso\Variables\Domain\Model\MarkerCollection;
use Sinso\Variables\Hooks\MarkersProcessorInterface;
use Sinso\Variables\Utility\CacheKeyUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class VariablesService
{
    public const MAXIMUM_LOOP_COUNT = 100;

    protected Set $cacheTags;
    protected array $usedMarkerKeys = [];

    protected ?ExtensionConfiguration $extensionConfiguration = null;
    protected ?TypoScriptFrontendController $typoScriptFrontendController = null;
    protected ?MarkerCollection $markerCollection = null;
    protected ?array $markerKeys = null;
    protected ?string $markerRegexp = null;

    public function __construct()
    {
        $this->cacheTags = new Set('string');
    }

    public function initialize(
        ExtensionConfiguration $extensionConfiguration = null,
        TypoScriptFrontendController $typoScriptFrontendController = null,
    ): void {
        if (!$typoScriptFrontendController) {
            $typoScriptFrontendController = $this->getTypoScriptFrontendController();
        }

        if (!$extensionConfiguration) {
            $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        }

        $this->extensionConfiguration = $extensionConfiguration;
        $this->typoScriptFrontendController = $typoScriptFrontendController;
        $this->markerCollection = $this->getMarkers();
        $this->markerKeys = $this->markerCollection->getMarkerKeys();
        $this->markerRegexp = '/(' . implode('|', $this->markerKeys) . ')/'; // TODO Use preg_quote
    }

    /**
     * Iterates over a structure (array, object) and replaces markers in every string found.
     *
     * @param mixed $structure
     *
     * @return void
     * @throws \Exception
     */
    public function replaceMarkersInStructureAndAdjustCaching(
        mixed &$structure
    ): void {
        if ($this->markerCollection === null) {
            throw new \Exception('Markers not initialized. Please run initialize() first.', 1726241619);
        }
        $this->replaceMarkersInStructure($structure);
        $this->setCacheTagsAndLifetimeInTsfe();
    }

    /**
     * Iterates over a structure (array, object) and replaces markers in every string found.
     *
     * @throws \Exception
     */
    protected function replaceMarkersInStructure(mixed &$structure): void
    {
        if (is_null($structure) || is_bool($structure) || is_int($structure) || is_float($structure) || $structure instanceof \UnitEnum) {
            return;
        }

        if (is_string($structure)) {
            $this->replaceMarkersInText($structure);
            return;
        }

        if (is_array($structure) || is_object($structure)) {
            foreach ($structure as &$subStructure) {
                $this->replaceMarkersInStructure($subStructure);
            }
            return;
        }

        throw new \Exception(sprintf('Unsupported type "%s" in structure', gettype($structure)), 1725955598);
    }

    protected function replaceMarkersInText(string &$text): void
    {
        $loops = 0;
        while (preg_match($this->markerRegexp, $text) && $loops++ < self::MAXIMUM_LOOP_COUNT) {
            foreach ($this->markerCollection as $marker) {
                $newContent = str_replace(
                    $marker->getMarkerWithBrackets(),
                    $marker->replacement,
                    $text
                );

                if ($newContent === $text) {
                    continue;
                }

                // Assign a cache key associated with the marker
                $this->cacheTags->add(CacheKeyUtility::getCacheKey($marker->getMarkerWithBrackets()));
                $this->usedMarkerKeys[] = $marker->key;
                $text = $newContent;
            }
        }

        // Remove all markers (avoids empty entries)
        if ($this->extensionConfiguration->get('variables', 'removeUnreplacedMarkers')) {
            $text = preg_replace('/{{.*?}}/', '', $text);
        }
    }

    /**
     * Returns the markers available in the current root line.
     */
    protected function getMarkers(): MarkerCollection
    {
        $pids = array_map(static function ($page) {
            return $page['uid'];
        }, $this->typoScriptFrontendController->rootLine);

        if (!empty($this->typoScriptFrontendController->tmpl->setup['plugin.']['tx_variables.']['persistence.']['storagePid'])) {
            $pids[] = (int)$this->typoScriptFrontendController->tmpl->setup['plugin.']['tx_variables.']['persistence.']['storagePid'];
        }

        $table = 'tx_variables_marker';
        $rows = $this->typoScriptFrontendController->cObj->getRecords(
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

    protected function setCacheTagsAndLifetimeInTsfe(): void
    {
        $this->usedMarkerKeys = array_unique($this->usedMarkerKeys);

        $minLifetime = min(
            $this->getSmallestLifetimeForMarkers($this->usedMarkerKeys),
            $this->typoScriptFrontendController->page['cache_timeout'] ?: PHP_INT_MAX
        );

        $this->typoScriptFrontendController->page['cache_timeout'] = $minLifetime;

        if (count($this->cacheTags) > 0) {
            $this->typoScriptFrontendController->addCacheTags($this->cacheTags->toArray());
        }
    }

    public function getSmallestLifetimeForMarkers(array $usedMarkerKeys): int
    {
        $tableName = 'tx_variables_marker';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName)->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        // Code heavily inspired by:
        // \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getFirstTimeValueForRecord
        $now = (int)$GLOBALS['ACCESS_TIME'];
        // Max value possible to keep an int \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->realPageCacheContent ($timeOutTime = $GLOBALS['EXEC_TIME'] + $cacheTimeout;)
        $result = PHP_INT_MAX - $GLOBALS['EXEC_TIME'];
        $timeFields = [];
        $timeConditions = $queryBuilder->expr()->orX();
        foreach (['starttime', 'endtime'] as $field) {
            if (isset($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'][$field])) {
                $timeFields[$field] = $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'][$field];
                $queryBuilder->addSelectLiteral(
                    'MIN('
                    . 'CASE WHEN '
                    . $queryBuilder->expr()->lte(
                        $timeFields[$field],
                        $queryBuilder->createNamedParameter($now, \PDO::PARAM_INT)
                    )
                    . ' THEN NULL ELSE ' . $queryBuilder->quoteIdentifier($timeFields[$field]) . ' END'
                    . ') AS ' . $queryBuilder->quoteIdentifier($timeFields[$field])
                );
                $timeConditions->add(
                    $queryBuilder->expr()->gt(
                        $timeFields[$field],
                        $queryBuilder->createNamedParameter($now, \PDO::PARAM_INT)
                    )
                );
            }
        }

        // if starttime or endtime are defined, evaluate them
        if (!empty($timeFields)) {
            // find the timestamp, when the current page's content changes the next time
            $queryBuilder
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->in('marker', $queryBuilder->createNamedParameter($usedMarkerKeys, Connection::PARAM_STR_ARRAY)),
                    $timeConditions
                );
            $row = $queryBuilder
                ->execute()
                ->fetch();

            if ($row) {
                foreach ($timeFields as $timeField => $_) {
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

    public function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }
}
