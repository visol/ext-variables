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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hooks for \TYPO3\CMS\Core\DataHandling\DataHandler.
 *
 * @category    Hooks
 * @package     tx_variables
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   2016 Causal Sàrl
 * @license     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
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
            $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
            $cacheManager->flushCachesInGroupByTag('pages', ['tx_variables_uid_' . $params['uid']]);
        }
    }

}
