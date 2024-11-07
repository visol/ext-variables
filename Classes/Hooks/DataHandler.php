<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with TYPO3 source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Sinso\Variables\Hooks;

use Sinso\Variables\Utility\CacheKeyUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataHandler
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * Flushes the cache if a marker record was edited.
     */
    public function clearCachePostProc(array $params, \TYPO3\CMS\Core\DataHandling\DataHandler $that): void
    {
        if (
            ($params['table'] !== 'tx_variables_marker')
            || !isset($params['uid'])
        ) {
            return;
        }

        // TODO: Prüfen, was passiert, wenn der Marker-Key nicht angepasst wird, oder ein Element gelöscht oder hidden wird
        $marker = $that->datamap[$params['table']][$params['uid']]['marker'] ?? null;

        if ($marker === null || $marker === '') {
            $marker = $this->findDeletedVariableMarkerByUid($params['uid']);
            if ($marker === null || $marker === '') {
                return;
            }
        }

        $cacheTagsToFlush = [];
        $cacheTagsToFlush[] = CacheKeyUtility::getCacheKey($marker);

        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        foreach ($cacheTagsToFlush as $cacheTag) {
            $cacheManager->flushCachesInGroupByTag('pages', $cacheTag);
        }
    }

    protected function findDeletedVariableMarkerByUid(int $uid): ?string
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_variables_marker');
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->select('marker')
            ->from('tx_variables_marker')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', 1),
            )
            ->executeQuery()
            ->fetchOne();
    }
}
