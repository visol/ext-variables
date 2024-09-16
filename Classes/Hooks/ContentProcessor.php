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

use Sinso\Variables\Service\VariablesService;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class ContentProcessor
{
    protected VariablesService $variablesService;

    public function __construct()
    {
        $this->variablesService = GeneralUtility::makeInstance(VariablesService::class);
    }

    /**
     * Dynamically replaces variables by user content.
     */
    public function replaceContent(array &$parameters, TypoScriptFrontendController $parentObject): void
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $this->variablesService->initialize($extensionConfiguration, $parentObject);
        $this->variablesService->replaceMarkersInStructureAndAdjustCaching($parentObject->content);
    }
}
