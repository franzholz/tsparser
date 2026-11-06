<?php
declare(strict_types=1);

namespace JambageCom\Tsparser\Service;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Core rendering engine for TypoScript Constant Editor fields in TYPO3 v14.
 * Includes a highly targeted diagnostic probe limited strictly to plugin.tt_products.PIDinfo.
 */
final class ConstantFieldRenderer
{
    /**
     * Renders the HTML number input field and inspects incoming runtime parameters.
     *
     * @param array<string, mixed> $params
     * @param bool $allowEmpty
     * @return string
     */
    public function renderField(array $params, bool $allowEmpty): string
    {
        $pureFieldName = (string)($params['fieldName'] ?? $params['name'] ?? '');
        $currentValue = (string)($params['fieldValue'] ?? $params['value'] ?? '');

        // Clean potential prefix duplicates
        if (str_starts_with($pureFieldName, 'plugin.tt_products.')) {
            $pureFieldName = substr($pureFieldName, 19);
        }

        // --- HIGHLY TARGETED DIAGNOSTIC PROBE (STRIKT REDUZIERT AUF PIDinfo) ---
        if ($pureFieldName === 'PIDinfo') {
            $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
            $parsedBody = $request instanceof ServerRequestInterface ? $request->getParsedBody() : [];
            $logFile = (class_exists(\TYPO3\CMS\Core\Core\Environment::class) ? \TYPO3\CMS\Core\Core\Environment::getProjectPath() : dirname(__DIR__, 4)) . '/log/tsparse.log';

            // Wir extrahieren isoliert nur die Werte, die für dieses Feld relevant sind,
            // um den massiven Log-Overflow zu verhindern:
            $filteredData = isset($parsedBody['data']['plugin.tt_products.PIDinfo']) ? ['plugin.tt_products.PIDinfo' => $parsedBody['data']['plugin.tt_products.PIDinfo']] : [];
            $filteredCheck = isset($parsedBody['check']['plugin.tt_products.PIDinfo']) ? ['plugin.tt_products.PIDinfo' => $parsedBody['check']['plugin.tt_products.PIDinfo']] : [];

            $logOutput = "========================================================================\n";
            $logOutput .= "TIMESTAMP: " . date('Y-m-d H:i:s') . "\n";
            $logOutput .= "TARGET FIELD LIFE-CYCLE: plugin.tt_products.PIDinfo\n";
            $logOutput .= "REQUEST METHOD: " . ($request instanceof ServerRequestInterface ? $request->getMethod() : 'UNKNOWN') . "\n";
            $logOutput .= "--- PARAMS PASSED BY TYPO3 CORE --- \n" . print_r($params, true) . "\n";
            $logOutput .= "--- REDUZIERTER POST BODY (NUR PIDinfo) --- \n";
            $logOutput .= "  data => " . print_r($filteredData, true);
            $logOutput .= "  check => " . print_r($filteredCheck, true);
            $logOutput .= "========================================================================\n";

            @file_put_contents($logFile, $logOutput, FILE_APPEND);
        }
        // --- ENDE LOG-SONDE ---

        $constantKey = 'plugin.tt_products.' . $pureFieldName;

        $fullHtmlName = sprintf('data[%s]', $constantKey);
        $fullCheckName = sprintf('check[%s]', $constantKey);

        if ($currentValue === '') {
            $currentValue = $allowEmpty ? '' : '0';
        }

        $escapedName = htmlspecialchars($fullHtmlName, ENT_QUOTES, 'UTF-8');
        $escapedCheckName = htmlspecialchars($fullCheckName, ENT_QUOTES, 'UTF-8');
        $escapedValue = htmlspecialchars($currentValue, ENT_QUOTES, 'UTF-8');
        $requiredAttribute = $allowEmpty ? '' : ' required';

        // CRITICAL FIX: Value changed from "1" to "checked" to pass the native core verification filter
        return sprintf(
            '<div class="form-control-wrap">' .
            '<input type="hidden" name="%1$s" value="checked" />' .
            '<input type="number" name="%2$s" value="%3$s" class="form-control form-control-clearable" min="0" step="1"%4$s />' .
            '</div>',
            $escapedCheckName,
            $escapedName,
            $escapedValue,
            $requiredAttribute
        );
    }
}
