<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

(static function ($extKey) {
    ExtensionManagementUtility::addLLrefForTCAdescr('tx_variables_marker', 'EXT:variables/Resources/Private/Language/locallang_csh_tx_variables_marker.xlf');
    ExtensionManagementUtility::allowTableOnStandardPages('tx_variables_marker');

    ExtensionManagementUtility::addStaticFile($extKey, 'Configuration/TypoScript', 'Content Variables');
})('variables');
