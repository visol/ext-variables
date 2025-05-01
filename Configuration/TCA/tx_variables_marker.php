<?php

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$enableRte = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('variables', 'enableRte');

return [
    'ctrl' => [
        'title' => 'LLL:EXT:variables/Resources/Private/Language/locallang_db.xlf:tx_variables_marker',
        'label' => 'marker',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'dividers2tabs' => true,
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'default_sortby' => 'marker',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'marker,replacement',
        'iconfile' => 'EXT:variables/Resources/Public/Icons/tx_variables_marker.png'
    ],
    'types' => [
        '1' => [
            'showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource,
                    marker, replacement,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;paletteHidden,
                    --palette--;;paletteAccess,'
        ],
    ],
    'palettes' => [
        'paletteAccess' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access',
            'showitem' => '
                starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel,
                endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel
            ',
        ],
        'paletteHidden' => [
            'showitem' => '
                hidden
            ',
        ],
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => ['type' => 'language']
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => 0],
                ],
                'default' => 0,
                'foreign_table' => 'tx_variables_marker',
                'foreign_table_where' => 'AND tx_variables_marker.pid=###CURRENT_PID### AND tx_variables_marker.sys_language_uid IN (-1,0)',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        't3ver_label' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.versionLabel',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
            ],
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
            ],
        ],
        'starttime' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel',
            'config' => [
                'type' => 'datetime',
                'size' => 16,
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'endtime' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel',
            'config' => [
                'type' => 'datetime',
                'size' => 16,
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'marker' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:variables/Resources/Private/Language/locallang_db.xlf:tx_variables_marker.marker',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'alphanum_x,upper',
                'required' => true //TODO: Restore uniqueInPid. See https://forge.typo3.org/issues/83572
            ],
        ],
        'replacement' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:variables/Resources/Private/Language/locallang_db.xlf:tx_variables_marker.replacement',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 4,
                'eval' => 'trim',
                'required' => true
            ],
            'defaultExtras' => $enableRte ? 'richtext:rte_transform[mode=ts_css]' : '',
        ],
    ]
];
