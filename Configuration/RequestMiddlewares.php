<?php
defined('TYPO3') or die();

return [
    'backend' => [
        'tsparser/constant-sanitizer-middleware' => [
            'target' => \JambageCom\Tsparser\Middleware\ConstantEditorActionSanitizerMiddleware::class,
            'before' => [
                // Läuft ganz früh an, noch bevor das Backend-Routing den Controller sucht!
                'typo3/cms-backend/routing',
            ],
        ],
    ],
];
