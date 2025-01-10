<?php

namespace JambageCom\Tsparser\Tstemplate\Controller;

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

class ConstantEditorController extends \TYPO3\CMS\Tstemplate\Controller\ConstantEditorController
{
    private function updateTemplateConstants(ServerRequestInterface $request, array $constantDefinitions, string $rawTemplateConstants): ?array
    {
        $rawTemplateConstantsArray = explode(LF, $rawTemplateConstants);
        $constantPositions = $this->calculateConstantPositions($rawTemplateConstantsArray);

        $parsedBody = $request->getParsedBody();
        $data = $parsedBody['data'] ?? null;
        $check = $parsedBody['check'] ?? [];

        $valuesHaveChanged = false;
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (!isset($constantDefinitions[$key])) {
                    // Ignore if there is no constant definition for this constant key
                    continue;
                }
                if (!isset($check[$key]) || ($check[$key] !== 'checked' && isset($constantPositions[$key]))) {
                    // Remove value if the checkbox is not set, indicating "value to be dropped from template"
                    $rawTemplateConstantsArray = $this->removeValueFromConstantsArray($rawTemplateConstantsArray, $constantPositions, $key);
                    $valuesHaveChanged = true;
                    continue;
                }
                if ($check[$key] !== 'checked') {
                    // Don't process if this value is not set
                    continue;
                }
                $constantDefinition = $constantDefinitions[$key];
                switch ($constantDefinition['type']) {
                    case 'int':
                        $min = $constantDefinition['typeIntMin'] ?? PHP_INT_MIN;
                        $max = $constantDefinition['typeIntMax'] ?? PHP_INT_MAX;
                        $value = (string)MathUtility::forceIntegerInRange((int)$value, (int)$min, (int)$max);
                        break;
                    case 'int+':
                    case 'eint+':
                        if ($constantDefinition['type'] == 'int+' || strlen($value)) {
                            $min = $constantDefinition['typeIntMin'] ?? 0;
                            $max = $constantDefinition['typeIntMax'] ?? PHP_INT_MAX;
                            $value = (string)MathUtility::forceIntegerInRange((int)$value, (int)$min, (int)$max);
                        }
                        break;
                    case 'color':
                        $col = [];
                        if ($value) {
                            $value = preg_replace('/[^A-Fa-f0-9]*/', '', $value) ?? '';
                            $useFulHex = strlen($value) > 3;
                            $col[] = (int)hexdec($value[0]);
                            $col[] = (int)hexdec($value[1]);
                            $col[] = (int)hexdec($value[2]);
                            if ($useFulHex) {
                                $col[] = (int)hexdec($value[3]);
                                $col[] = (int)hexdec($value[4]);
                                $col[] = (int)hexdec($value[5]);
                            }
                            $value = substr('0' . dechex($col[0]), -1) . substr('0' . dechex($col[1]), -1) . substr('0' . dechex($col[2]), -1);
                            if ($useFulHex) {
                                $value .= substr('0' . dechex($col[3]), -1) . substr('0' . dechex($col[4]), -1) . substr('0' . dechex($col[5]), -1);
                            }
                            $value = '#' . strtoupper($value);
                        }
                        break;
                    case 'comment':
                        if ($value) {
                            $value = '';
                        } else {
                            $value = '#';
                        }
                        break;
                    case 'wrap':
                        if (($data[$key]['left'] ?? false) || $data[$key]['right']) {
                            $value = $data[$key]['left'] . '|' . $data[$key]['right'];
                        } else {
                            $value = '';
                        }
                        break;
                    case 'offset':
                        $value = rtrim(implode(',', $value), ',');
                        if (trim($value, ',') === '') {
                            $value = '';
                        }
                        break;
                    case 'boolean':
                        if ($value) {
                            $value = ($constantDefinition['trueValue'] ?? false) ?: '1';
                        }
                        break;
                }
                if ((string)($constantDefinition['value'] ?? '') !== (string)$value) {
                    // Put value in, if changed.
                    $rawTemplateConstantsArray = $this->addOrUpdateValueInConstantsArray($rawTemplateConstantsArray, $constantPositions, $key, $value);
                    $valuesHaveChanged = true;
                }
            }
        }
        if ($valuesHaveChanged) {
            return $rawTemplateConstantsArray;
        }
        return null;
    }
}
