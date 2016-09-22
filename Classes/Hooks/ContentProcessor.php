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

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

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

        // Replace content
        $content = str_replace(array_keys($markers), array_values($markers), $content);

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
        $rows = $frontendController->cObj->getRecords($table, [
            'selectFields' => 'marker, replacement',
            'pidInList' => implode(',', $parentPages),
        ]);

        $markers = [];
        foreach ($rows as $row) {
            $marker = '{{' . $row['marker'] . '}}';
            $markers[$marker] = $row['replacement'];
        }

        return $markers;
    }

}
