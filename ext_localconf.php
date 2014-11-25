<?php
if (!defined ('TYPO3_MODE')) {
	die('Access denied.');
}

if (
	TYPO3_MODE == 'BE' &&
	t3lib_extMgm::isLoaded('div2007')
) {
	if (class_exists('tx_div2007_core')) {
		$typoVersion = tx_div2007_core::getTypoVersion();
	} else { // workaround for bug #55727
		$result = FALSE;
		$callingClassName = '\\TYPO3\\CMS\\Core\\Utility\\VersionNumberUtility';
		if (
			class_exists($callingClassName) &&
			method_exists($callingClassName, 'convertVersionNumberToInteger')
		) {
			$result = call_user_func($callingClassName . '::convertVersionNumberToInteger', TYPO3_version);
		} else if (
			class_exists('t3lib_utility_VersionNumber') &&
			method_exists('t3lib_utility_VersionNumber', 'convertVersionNumberToInteger')
		) {
			$result = t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version);
		} else if (
			class_exists('t3lib_div') &&
			method_exists('t3lib_div', 'int_from_ver')
		) {
			$result = t3lib_div::int_from_ver(TYPO3_version);
		}

		$typoVersion = $result;
	}

	if ($typoVersion >= '6000000') {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\TypoScript\\ExtendedTemplateService'] =
			array(
				'className' => 'JambageCom\\Tsparser\\TypoScript\\ExtendedTemplateService',
			);
	} else {
		$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tsparser_ext.php'] =
			t3lib_extMgm::extPath('tsparser') . 'xclass/class.user_t3lib_tsparser_ext.php';
	}
}

?>
