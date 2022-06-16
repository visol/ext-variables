<?php
/*
 * This file is part of the Sinso/Variables project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

namespace Sinso\Variables\Hooks;

use Doctrine\DBAL\Connection;
use Sinso\Variables\Utility\CacheKeyUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class ContentProcessor
{
    /**
     * Dynamically replaces variables by user content.
     */
    public function replaceContent(array &$parameters, TypoScriptFrontendController $parentObject): void
    {
        $content = $parameters['pObj']->content;

        $markers = $this->getMarkers($parameters['pObj']);
        $markerKeys = array_keys($markers);
        $markerRegexp = '/(' . implode('|', $markerKeys) . ')/';

        $usedMarkerKeys = [];
        $cacheTags = [];
        $loops = 0;
        while (preg_match($markerRegexp, $content) && $loops++ < 100) {
            foreach ($markerKeys as $markerKey) {
                $newContent = str_replace($markerKey, $markers[$markerKey]['replacement'], $content);
                if ($newContent !== $content) {
                    // Assign a cache key associated with the marker
                    $cacheTags[] = CacheKeyUtility::getCacheKey($markerKey);
                    $usedMarkerKeys[] = $markers[$markerKey]['markerKey'];
                    $content = $newContent;
                }
            }
        }

        $usedMarkerKeys = array_unique($usedMarkerKeys);

        $minLifetime = min(
            $this->getSmallestLifetimeForMarkers($usedMarkerKeys),
            $parentObject->page['cache_timeout'] ?: PHP_INT_MAX
        );

        $parentObject->page['cache_timeout'] = $minLifetime;

        if (count($cacheTags) > 0) {
            $parameters['pObj']->addCacheTags($cacheTags);
        }

        $parameters['pObj']->content = $content;
    }

    protected function getSmallestLifetimeForMarkers(array $usedMarkerKeys): int
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

    /**
     * Returns the markers available in the current root line.
     */
    protected function getMarkers(TypoScriptFrontendController $frontendController): array
    {
        $table = 'tx_variables_marker';
        $parentPages = array_map(function ($page) {
            return $page['uid'];
        }, $frontendController->rootLine);

        if (!empty($frontendController->tmpl->setup['plugin.']['tx_variables.']['persistence.']['storagePid'])) {
            $parentPages[] = (int)$frontendController->tmpl->setup['plugin.']['tx_variables.']['persistence.']['storagePid'];
        }

        $rows = $frontendController->cObj->getRecords($table, [
            'selectFields' => 'marker, replacement',
            'pidInList' => implode(',', $parentPages),
            'orderBy' => 'uid ASC',
        ]);

        $markers = [];
        foreach ($rows as $row) {
            $marker = '{{' . $row['marker'] . '}}';
            $markers[$marker] = [
                'uid' => $row['uid'],
                'marker' => $marker,
                'markerKey' => $row['marker'],
                'replacement' => $row['replacement'],
            ];
        }

        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['variables']['postProcessMarkers'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['variables']['postProcessMarkers'] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (!($hookObj instanceof \Sinso\Variables\Hooks\MarkersProcessorInterface)) {
                    throw new \RuntimeException($classRef . ' does not implement ' . \Sinso\Variables\Hooks\MarkersProcessorInterface::class, 1512391205);
                }
                $hookObj->postProcessMarkers($markers);
            }
        }

        // Sort markers
        ksort($markers);

        return $markers;
    }

}
