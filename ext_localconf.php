<?php
if (!defined ('TYPO3_MODE')) {
	die('Access denied.');
}

if (
	defined('TYPO3_version') &&
	version_compare(TYPO3_version, '6.0.0', '>=')
) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\TypoScript\\ExtendedTemplateService'] =
		array(
			'className' => 'JambageCom\\Tsparser\\TypoScript\\ExtendedTemplateService',
		);
}

