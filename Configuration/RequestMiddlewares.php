<?php
return [
    'backend' => [
        'jambage-com/tsparser/form-interception' => [
            'target' => \JambageCom\Tsparser\Middleware\ConstantEditorMiddleware::class,
            'after' => [
                'typo3/cms-backend/authentication',
            ],
        ],
    ],
];
