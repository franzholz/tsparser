<?php
defined('TYPO3_MODE') || die('Access denied.');

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\TypoScript\\ExtendedTemplateService'] =
    array(
        'className' => 'JambageCom\\Tsparser\\TypoScript\\ExtendedTemplateService',
    );

