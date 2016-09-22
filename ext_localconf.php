<?php
defined('TYPO3_MODE') || die();

$boot = function ($_EXTKEY) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'][$_EXTKEY] =
        \Sinso\Variables\Hooks\ContentProcessor::class . '->replaceContent';
};

$boot($_EXTKEY);
unset($boot);
