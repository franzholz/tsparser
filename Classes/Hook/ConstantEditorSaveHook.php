<?php
declare(strict_types=1);

namespace JambageCom\Tsparser\Hook;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Standard hook to inject validated user-type values directly into the core configuration array before database compilation.
 */
final class ConstantEditorSaveHook
{
    /**
     * Intercepts the core parameter array by reference to persist custom template values.
     */
    public function processSave(array &$params): void
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!$request instanceof ServerRequestInterface || $request->getMethod() !== 'POST') {
            return;
        }

        $parsedBody = $request->getParsedBody();
        if (!is_array($parsedBody) || !isset($parsedBody['data']) || !is_array($parsedBody['data'])) {
            return;
        }

        // Loop through all variables submitted in the request
        foreach ($parsedBody['data'] as $rawKey => $value) {
            $stringKey = (string)$rawKey;

            // Normalize points and underscores (e.g. plugin_tt_products_maxW_single -> plugin.tt_products.maxW_single)
            $normalizedKey = str_replace(['_', 'data.'], ['.', ''], $stringKey);

            if (str_contains($normalizedKey, 'plugin.tt_products.')) {
                $valueStr = trim((string)$value);

                $intValue = filter_var($valueStr, FILTER_VALIDATE_INT);
                $cleanValue = ($intValue !== false && $intValue >= 0) ? (string)$intValue : '0';

                // Force the injection directly into the core configuration parameters by reference
                foreach (['constants', 'configuration', 'elements'] as $arrayKey) {
                    if (isset($params[$arrayKey]) && is_array($params[$arrayKey])) {
                        $params[$arrayKey][$normalizedKey] = $cleanValue;
                        // Also support dot-replaced keys just in case
                        $params[$arrayKey][$stringKey] = $cleanValue;
                    }
                }
            }
        }
    }
}
