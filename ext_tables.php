<?php
defined('TYPO3_MODE') || die();

$boot = function ($_EXTKEY) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_variables_marker', 'EXT:variables/Resources/Private/Language/locallang_csh_tx_variables_marker.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_variables_marker');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'Content Variables');
};

$boot($_EXTKEY);
unset($boot);
