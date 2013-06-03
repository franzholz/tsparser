<?php
if (!defined ('TYPO3_MODE')) {
	die('Access denied.');
}

if (TYPO3_MODE == 'BE') {

	$typoVersion = (
		class_exists('t3lib_utility_VersionNumber') ?
			t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) :
			t3lib_div::int_from_ver(TYPO3_version)
	);

	if ($typoVersion >= '6000000') {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\TypoScript\\ExtendedTemplateService'] = array(
		'className' => 'JambageCom\\Tsparser\\TypoScript\\ExtendedTemplateService',
		);
	} else {
		$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tsparser_ext.php'] = t3lib_extMgm::extPath('tsparser') . 'xclass/class.user_t3lib_tsparser_ext.php';
	}
}

?>
