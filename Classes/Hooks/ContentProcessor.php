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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Hooks for \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController.
 *
 * @category    Hooks
 * @package     tx_variables
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @copyright   2016 Causal Sàrl
 * @license     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ContentProcessor
{

    /**
     * Dynamically replaces variables by user content.
     *
     * @param array $parameters
     * @param TypoScriptFrontendController $parentObject
     * @return void
     */
    public function replaceContent(array &$parameters, TypoScriptFrontendController $parentObject)
    {
        $content = $parameters['pObj']->content;

        $markers = $this->getMarkers($parameters['pObj']);
        $markerKeys = array_keys($markers);
        $markerRegexp = '/(' . implode('|', $markerKeys) . ')/';

        $cacheTags = [];
        $loops = 0;
        while (preg_match($markerRegexp, $content) && $loops++ < 100) {
            foreach ($markerKeys as $markerKey) {
                $newContent = str_replace($markerKey, $markers[$markerKey]['replacement'], $content);
                if ($newContent !== $content) {
                    // Assign a cache key associated with the marker
                    $cacheTags[] = 'tx_variables_uid_' . $markers[$markerKey]['uid'];
                    $content = $newContent;
                }
            }
        }

        if (count($cacheTags) > 0) {
            $parameters['pObj']->addCacheTags($cacheTags);
        }

        $parameters['pObj']->content = $content;
    }

    /**
     * Returns the markers available in the current root line.
     *
     * @param TypoScriptFrontendController $frontendController
     * @return array
     */
    protected function getMarkers(TypoScriptFrontendController $frontendController)
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
        ]);

        $markers = [];
        foreach ($rows as $row) {
            $marker = '{{' . $row['marker'] . '}}';
            $markers[$marker] = [
                'uid' => $row['uid'],
                'marker' => $marker,
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
