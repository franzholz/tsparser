<?php

defined('TYPO3') || die('Access denied.');

call_user_func(function (): void {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Tstemplate\Controller\ConstantEditorController::class] =
        [
            'className' => \JambageCom\Tsparser\Tstemplate\Controller\ConstantEditorController::class
        ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\TypoScript\Parser\ConstantConfigurationParser::class] =
        [
            'className' => \JambageCom\Tsparser\TypoScript\Parser\ConstantConfigurationParser::class
        ];
});
