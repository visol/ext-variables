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
            'typo3' => '12.4.99',
            'php' => '8.1.0-8.3.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ]
];
