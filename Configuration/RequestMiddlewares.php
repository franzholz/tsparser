<?php
return [
    'backend' => [
        'jambage-com/tsparser/form-interception' => [
            'target' => \JambageCom\Tsparser\Middleware\ConstantEditorMiddleware::class,
            'before' => [
                'typo3/cms-backend/backend-routing',
            ],
        ],
    ],
];
