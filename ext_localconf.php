<?php
defined('TYPO3_MODE') || die('Access denied.');

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\TypoScript\\ExtendedTemplateService'] =
    [
        'className' => 'JambageCom\\Tsparser\\TypoScript\\ExtendedTemplateService',
    ];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\TypoScript\\Parser\\ConstantConfigurationParser'] =
    [
        'className' => 'JambageCom\\Tsparser\\TypoScript\\Parser\\ConstantConfigurationParser'
    ];


