<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * TSParser extension class to t3lib_TStemplate
 *
 * $Id$
 * Contains functions for the TS module in TYPO3 backend
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author	Franz Holzinger <franz@ttproducts.de>
 */



/**
 * TSParser extension class to t3lib_TStemplate
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tsparser
 */
class ux_t3lib_tsparser_ext extends t3lib_tsparser_ext {

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$http_post_vars: ...
	 * @param	[type]		$http_post_files: ...
	 * @param	[type]		$theConstants: ...
	 * @param	[type]		$tplRow: ...
	 * @return	[type]		...
	 */
	public function ext_procesInput($http_post_vars, $http_post_files, $theConstants, $tplRow) {
		$data = $http_post_vars['data'];
		$check = $http_post_vars['check'];
		$copyResource = $http_post_vars['_copyResource'];
		$Wdata = $http_post_vars['Wdata'];
		$W2data = $http_post_vars['W2data'];
		$W3data = $http_post_vars['W3data'];
		$W4data = $http_post_vars['W4data'];
		$W5data = $http_post_vars['W5data'];

		if (is_array($data)) {
			foreach ($data as $key => $var) {
				if (isset($theConstants[$key])) {
					if ($this->ext_dontCheckIssetValues || isset($check[$key])) { // If checkbox is set, update the value
						list($var) = explode(LF, $var); // exploding with linebreak, just to make sure that no multiline input is given!
						$typeDat = $this->ext_getTypeData($theConstants[$key]['type']);
						switch ($typeDat['type']) {
							case 'int':
								if ($typeDat['paramstr']) {
									$var =
										(class_exists('t3lib_utility_Math') ?
											t3lib_utility_Math::forceIntegerInRange($var, $typeDat['params'][0], $typeDat['params'][1]) :
											t3lib_div::intInRange($var, $typeDat['params'][0], $typeDat['params'][1])
										);
								} else {
									$var = intval($var);
								}
							break;
							case 'int+':
							case 'eint+':
								if ($typeDat['type'] == 'int+' || strlen($var)) {
									$var = max(0, intval($var));
								}
								break;
							case 'color':
								$col = array();
								if ($var && !t3lib_div::inList($this->HTMLcolorList, strtolower($var))) {
									$var = preg_replace('/[^A-Fa-f0-9]*/', '', $var);
									$useFullHex = strlen($var) > 3;

									$col[] = HexDec(substr($var, 0, 1));
									$col[] = HexDec(substr($var, 1, 1));
									$col[] = HexDec(substr($var, 2, 1));

									if ($useFullHex) {
										$col[] = HexDec(substr($var, 3, 1));
										$col[] = HexDec(substr($var, 4, 1));
										$col[] = HexDec(substr($var, 5, 1));
									}

									$var = substr('0' . DecHex($col[0]), -1) . substr('0' . DecHex($col[1]), -1) . substr('0' . DecHex($col[2]), -1);
									if ($useFullHex) {
										$var .= substr('0' . DecHex($col[3]), -1) . substr('0' . DecHex($col[4]), -1) . substr('0' . DecHex($col[5]), -1);
									}

									$var = '#' . strtoupper($var);
								}
							break;
							case 'comment':
								if ($var) {
									$var = '#';
								} else {
									$var = '';
								}
							break;
							case 'wrap':
								if (isset($Wdata[$key])) {
									$var .= '|' . $Wdata[$key];
								}
							break;
							case 'offset':
								if (isset($Wdata[$key])) {
									$var = intval($var) . ',' . intval($Wdata[$key]);
									if (isset($W2data[$key])) {
										$var .= ',' . intval($W2data[$key]);
										if (isset($W3data[$key])) {
											$var .= ',' . intval($W3data[$key]);
											if (isset($W4data[$key])) {
												$var .= ',' . intval($W4data[$key]);
												if (isset($W5data[$key])) {
													$var .= ',' . intval($W5data[$key]);
												}
											}
										}
									}
								}
							break;
							case 'boolean':
								if ($var) {
									$var = $typeDat['paramstr'] ? $typeDat['paramstr'] : 1;
								}
							break;
							case 'file':
								if (!$this->ext_noCEUploadAndCopying) {
									if ($http_post_files['upload_data']['name'][$key] && $http_post_files['upload_data']['tmp_name'][$key] != 'none') {
										$var = $this->upload_copy_file(
											$typeDat,
											$tplRow,
											trim($http_post_files['upload_data']['name'][$key]),
											$http_post_files['upload_data']['tmp_name'][$key]
										);
									}
									if ($copyResource[$key]) {
										$var = $this->upload_copy_file(
											$typeDat,
											$tplRow,
											basename($copyResource[$key]),
											$copyResource[$key]
										);
									}
								}
							break;
						}

						if ($this->ext_printAll || strcmp($theConstants[$key]['value'], $var)) {
							$this->ext_putValueInConf($key, $var); // Put value in, if changed.
						}
						unset($check[$key]); // Remove the entry because it has been "used"
					} else {
						$this->ext_removeValueInConf($key);
					}
				}
			}
		}

			// Remaining keys in $check indicates fields that are just clicked "on" to be edited. Therefore we get the default value and puts that in the template as a start...
		if (!$this->ext_dontCheckIssetValues && is_array($check)) {
			foreach ($check as $key => $var) {
				if (isset($theConstants[$key])) {
					$dValue = $theConstants[$key]['default_value'];
					$this->ext_putValueInConf($key, $dValue);
				}
			}
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tsparser/xclass/class.user_t3lib_tsparser_ext.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tsparser/xclass/class.user_t3lib_tsparser_ext.php']);
}

?>