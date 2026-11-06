<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "tsparser".
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'More Constants Editor Types',
    'description' => 'This extension adds custom rendering services to the native TypoScript Constant Editor to support enhanced data types.',
    'category' => 'be',
    'author' => 'Franz Holzinger',
    'author_email' => 'franz@ttproducts.de',
    'state' => 'stable',
    'author_company' => 'jambage.com',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.5.99',
            'typo3' => '13.4.0-14.3.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
