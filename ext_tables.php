<?php
defined('TYPO3') || die();

(function ($extKey) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_variables_marker', 'EXT:variables/Resources/Private/Language/locallang_csh_tx_variables_marker.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_variables_marker');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($extKey, 'Configuration/TypoScript', 'Content Variables');
})('variables');
