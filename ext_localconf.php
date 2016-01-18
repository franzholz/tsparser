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
} else {
	$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tsparser_ext.php'] =
		t3lib_extMgm::extPath('tsparser') . 'xclass/class.user_t3lib_tsparser_ext.php';
}

