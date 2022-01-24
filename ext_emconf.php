<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "tsparser".
 ***************************************************************/


$EM_CONF[$_EXTKEY] = [
    'title' => 'More Constants Editor Types',
    'description' => 'This extension adds more types to the Constants Editor. This is a patch for TYPO3.',
    'category' => 'be',
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'author_company' => 'jambage.com',
    'version' => '0.4.1',
    'constraints' => [
        'depends' => [
            'php' => '7.2.0-8.1.99',
            'typo3' => '8.7.0-11.5.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];

