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
    'author_company' => 'jambage.com',
    'version' => '0.14.0',
    'constraints' => [
        'depends' => [
            'php' => '8.0.0-8.5.99',
            'typo3' => '13.4.0-14.3.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'patch_install_tool' => '0.1.0-0.1.99',
        ],
    ],
];
