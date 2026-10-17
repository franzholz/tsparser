<?php
return [
    'backend' => [
        'tsparser/constants-interceptor' => [
            'target' => \JambageCom\Tsparser\Middleware\ConstantEditorInterceptor::class,
            'before' => [
                'typo3/cms-backend/endpoint-dispatcher',
            ],
        ],
    ],
];
