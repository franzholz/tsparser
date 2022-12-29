<?php
namespace JambageCom\Tsparser\TypoScript\Parser;


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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Parser for TypoScript constant configuration lines and values
 * like "# cat=content/cText/1; type=; label= Bodytext font: This is the font face used for text!"
 * for display of the constant editor and extension settings configuration
 */
class ConstantConfigurationParser extends \TYPO3\CMS\Core\TypoScript\Parser\ConstantConfigurationParser
{

    /**
     * Builds a configuration array from each line (option) of the config file.
     * Helper method for getConfigurationPreparedForView()
     *
     * @param array $configurationOption config file line representing one setting
     * @return array
     */
    protected function buildConfigurationArray(array $configurationOption): array
    {
        $hierarchicConfiguration = [];
        if (str_starts_with((string)$configurationOption['type'], 'user')) {
            $configurationOption = $this->extractInformationForConfigFieldsOfTypeUser($configurationOption);
        } elseif (str_starts_with((string)$configurationOption['type'], 'options')) {
            $configurationOption = $this->extractInformationForConfigFieldsOfTypeOptions($configurationOption);
        }
        $languageService = $this->getLanguageService();
        if (is_string($configurationOption['label'])) {
            $translatedLabel = $languageService->sL($configurationOption['label']);
            if ($translatedLabel) {
                $configurationOption['label'] = $translatedLabel;
            }
        }

    // new begin
            // Call processing function for the labels
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tsparser']['configurationParser']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tsparser']['configurationParser'])
        ) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tsparser']['configurationParser'] as $extensionKey => $classRef) {
                $hookObject= GeneralUtility::makeInstance($classRef);
                if (
                    is_object($hookObject) &&
                    method_exists($hookObject, 'buildConfigurationArray')
                ) {
                    $_params =
                        [
                            'configurationOption' => $configurationOption,
                            'extensionKey' => $extensionKey
                        ];
                    $hookObject->buildConfigurationArray($_params, $this);
                }
            }
            $configurationOption = $_params['configurationOption'];
        }
    // new end

        $configurationOption['labels'] = GeneralUtility::trimExplode(':', $configurationOption['label'], false, 2);
        $configurationOption['subcat_name'] = ($configurationOption['subcat_name'] ?? false) ?: '__default';
        $hierarchicConfiguration[$configurationOption['cat']][$configurationOption['subcat_name']][$configurationOption['name']] = $configurationOption;
        return $hierarchicConfiguration;
    }
}

