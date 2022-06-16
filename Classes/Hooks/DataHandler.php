<?php
/*
 * This file is part of the Sinso/Variables project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

namespace Sinso\Variables\Hooks;

use Sinso\Variables\Utility\CacheKeyUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Cache\CacheManager;

class DataHandler
{

    /**
     * Flushes the cache if a marker record was edited.
     */
    public function clearCachePostProc(array $params): void
    {
        if (isset($params['table']) && $params['table'] === 'tx_variables_marker') {
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
