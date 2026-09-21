<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\ValueObject\PhpVersion;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;

/**
 * If FQDNs are imported globally at a later date, it must be ensured that
 * extended domain models of the EXT:news extension are excluded. To check
 * which files this affects, you can look in the configuration of the PhpCsFixer.
 * @see https://docs.typo3.org/p/georgringer/news/main/en-us/Tutorials/ExtendNews/ProxyClassGenerator/Index.html
 */
return RectorConfig::configure()
    ->withPaths([
        getcwd() . '/Classes',
        getcwd() . '/Configuration',
        getcwd() . '/Tests',
        getcwd() . '/ext_localconf.php',
    ])
    ->withImportNames(false, true, false)
    ->withPhpVersion(PhpVersion::PHP_82)
    ->withSets([
        LevelSetList::UP_TO_PHP_82,
        Typo3LevelSetList::UP_TO_TYPO3_13,
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);
