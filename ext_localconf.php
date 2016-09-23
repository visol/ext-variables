<?php
defined('TYPO3_MODE') || die();

$boot = function ($_EXTKEY) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'][$_EXTKEY] =
        \Sinso\Variables\Hooks\ContentProcessor::class . '->replaceContent';

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][$_EXTKEY . '_clearcache'] =
        \Sinso\Variables\Hooks\DataHandler::class . '->clearCachePostProc';
};

$boot($_EXTKEY);
unset($boot);
