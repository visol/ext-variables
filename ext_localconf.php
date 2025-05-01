<?php

use Sinso\Variables\Hooks\ContentProcessor;
use Sinso\Variables\Hooks\DataHandler;

defined('TYPO3') || die();

(static function ($extKey) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'][$extKey]
        = ContentProcessor::class . '->replaceContent';

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][$extKey . '_clearcache']
        = DataHandler::class . '->clearCachePostProc';
})('variables');
