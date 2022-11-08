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
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataHandler
{
    /**
     * Flushes the cache if a marker record was edited.
     */
    public function clearCachePostProc(array $params): void
    {
        if (
            isset($params['table'])
            && isset($params['uid'])
            && $params['table'] === 'tx_variables_marker'
        ) {
            $cacheTagsToFlush = [];
            if (isset($params['marker'])) {
                $cacheTagsToFlush[] = CacheKeyUtility::getCacheKey(params['marker']);
            }

            $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
            foreach ($cacheTagsToFlush as $cacheTag) {
                $cacheManager->flushCachesInGroupByTag('pages', $cacheTag);
            }
        }
    }

}
