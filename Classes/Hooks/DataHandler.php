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

use Sinso\Variables\Domain\Model\Marker;
use Sinso\Variables\Utility\CacheKeyUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

class DataHandler
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly CacheManager $cacheManager,
    ) {
    }

    /**
     * Flushes the cache if a marker record was edited.
     */
    public function clearCachePostProc(array $params, \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler): void
    {
        $marker = $this->getMarkerFromHook($params, $dataHandler);

        if (!$marker instanceof \Sinso\Variables\Domain\Model\Marker) {
            return;
        }

        $cacheTagToFlush = CacheKeyUtility::getCacheKey(
            $marker->getMarkerWithBrackets()
        );

        $this->cacheManager->flushCachesInGroupByTag('pages', $cacheTagToFlush);
    }

    protected function getMarkerFromHook(array $params, \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler): ?Marker
    {
        if (
            ($params['table'] !== 'tx_variables_marker')
            || !isset($params['uid'])
        ) {
            return null;
        }

        $marker = $dataHandler->datamap[$params['table']][$params['uid']]['marker'] ?? null;

        if (!$marker) {
            $marker = $this->findVariableMarkerByUidEventIfHiddenOrDeleted($params['uid']);
        }

        if (!$marker) {
            return null;
        }

        return new Marker(
            uid: $params['uid'],
            key: $marker,
            replacement: '', // value doesn't matter here
        );
    }

    protected function findVariableMarkerByUidEventIfHiddenOrDeleted(int $uid): ?string
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_variables_marker');
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->select('marker')
            ->from('tx_variables_marker')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchOne();
    }
}
