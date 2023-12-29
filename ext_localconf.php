<?php
defined('TYPO3') || die('Access denied.');

call_user_func(function (): void
{
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\TypoScript\ExtendedTemplateService::class] =
        [
            'className' => \JambageCom\Tsparser\TypoScript\ExtendedTemplateService::class
        ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\TypoScript\Parser\ConstantConfigurationParser::class] =
        [
            'className' => \JambageCom\Tsparser\TypoScript\Parser\ConstantConfigurationParser::class
        ];
});
