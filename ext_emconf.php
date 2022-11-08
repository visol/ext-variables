<?php

/***********************************************************************
 * Extension Manager/Repository config file for ext "variables".
 ***********************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'Content Variables',
    'description' => 'Search and replace strings recursively after page generation using user-managed replacement definitions.',
    'category' => 'misc',
    'version' => '3.0.0',
    'state' => 'beta',
    'author' => 'Xavier Perseguers',
    'author_email' => 'xavier@causal.ch',
    'author_company' => 'Swisscom (Schweiz) AG',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-9.5.99',
            'php' => '7.0.0-7.2.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ]
];
