<?php
if (!defined ('TYPO3_MODE'))	die ('Access denied.');

$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tsparser_ext.php'] = t3lib_extMgm::extPath('tsparser') . 'xclass/class.user_t3lib_tsparser_ext.php';

?>
