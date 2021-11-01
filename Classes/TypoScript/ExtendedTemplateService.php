<?php
namespace JambageCom\Tsparser\TypoScript;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */


use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;


/**
 * TSParser extension class to \TYPO3\CMS\Core\TypoScript\ExtendedTemplateService
 * Contains functions for the TS module in TYPO3 backend
 */

class ExtendedTemplateService extends \TYPO3\CMS\Core\TypoScript\ExtendedTemplateService {

    /**
     * Process input
     *
     * @param array $http_post_vars
     * @param array $http_post_files (not used anymore)
     * @param array $theConstants
     * @param array $tplRow Not used
     */
    public function ext_procesInput($http_post_vars, $http_post_files, $theConstants, $tplRow)
    {
        $data = $http_post_vars['data'];
        $check = $http_post_vars['check'];
        $Wdata = $http_post_vars['Wdata'] ?? [];
        $W2data = $http_post_vars['W2data'] ?? [];
        $W3data = $http_post_vars['W3data'] ?? [];
        $W4data = $http_post_vars['W4data'] ?? [];
        $W5data = $http_post_vars['W5data'] ?? [];
        if (is_array($data)) {
            foreach ($data as $key => $var) {
                if (isset($theConstants[$key])) {
                    // If checkbox is set, update the value
                    if ($this->ext_dontCheckIssetValues || isset($check[$key])) {
                        // Exploding with linebreak, just to make sure that no multiline input is given!
                        [$var] = explode(LF, $var);
                        $typeDat = $this->ext_getTypeData($theConstants[$key]['type']);
                        switch ($typeDat['type']) {
                            case 'int':
                                if ($typeDat['paramstr']) {
                                    $var = MathUtility::forceIntegerInRange((int)$var, $typeDat['params'][0], $typeDat['params'][1]);
                                } else {
                                    $var = (int)$var;
                                }
                                break;
                            case 'int+':
                            case 'eint+':
                                if ($typeDat['type'] == 'int+' || strlen($var)) {
                                    $var = max(0, (int)$var);
                                }
                                break;
                            case 'color':
                                $col = [];
                                if ($var) {
                                    $var = preg_replace('/[^A-Fa-f0-9]*/', '', $var) ?? '';
                                    $useFulHex = strlen($var) > 3;
                                    $col[] = (int)hexdec($var[0]);
                                    $col[] = (int)hexdec($var[1]);
                                    $col[] = (int)hexdec($var[2]);
                                    if ($useFulHex) {
                                        $col[] = (int)hexdec($var[3]);
                                        $col[] = (int)hexdec($var[4]);
                                        $col[] = (int)hexdec($var[5]);
                                    }
                                    $var = substr('0' . dechex($col[0]), -1) . substr('0' . dechex($col[1]), -1) . substr('0' . dechex($col[2]), -1);
                                    if ($useFulHex) {
                                        $var .= substr('0' . dechex($col[3]), -1) . substr('0' . dechex($col[4]), -1) . substr('0' . dechex($col[5]), -1);
                                    }
                                    $var = '#' . strtoupper($var);
                                }
                                break;
                            case 'comment':
                                if ($var) {
                                    $var = '';
                                } else {
                                    $var = '#';
                                }
                                break;
                            case 'wrap':
                                if (isset($Wdata[$key])) {
                                    $var .= '|' . $Wdata[$key];
                                }
                                break;
                            case 'offset':
                                if (isset($Wdata[$key])) {
                                    $var = (int)$var . ',' . (int)$Wdata[$key];
                                    if (isset($W2data[$key])) {
                                        $var .= ',' . (int)$W2data[$key];
                                        if (isset($W3data[$key])) {
                                            $var .= ',' . (int)$W3data[$key];
                                            if (isset($W4data[$key])) {
                                                $var .= ',' . (int)$W4data[$key];
                                                if (isset($W5data[$key])) {
                                                    $var .= ',' . (int)$W5data[$key];
                                                }
                                            }
                                        }
                                    }
                                }
                                break;
                            case 'boolean':
                                if ($var) {
                                    $var = $typeDat['paramstr'] ?: 1;
                                }
                                break;
                        }
                        if ($this->ext_printAll || (string)$theConstants[$key]['value'] !== (string)$var) {
                            // Put value in, if changed.
                            $this->ext_putValueInConf($key, $var);
                        }
                        // Remove the entry because it has been "used"
                        unset($check[$key]);
                    } else {
                        $this->ext_removeValueInConf($key);
                    }
                }
            }
        }
        // Remaining keys in $check indicates fields that are just clicked "on" to be edited.
        // Therefore we get the default value and puts that in the template as a start...
        if (!isset($this->ext_dontCheckIssetValues) || !$this->ext_dontCheckIssetValues) {
            foreach ($check ?? [] as $key => $var) {
                if (isset($theConstants[$key])) {
                    $dValue = $theConstants[$key]['default_value'];
                    $this->ext_putValueInConf($key, $dValue);
                }
            }
        }
    }
}
