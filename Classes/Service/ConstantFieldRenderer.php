<?php
declare(strict_types=1);

namespace JambageCom\Tsparser\Service;

/**
 * Core rendering engine for TypoScript Constant Editor fields in TYPO3 v14.
 * Completely dynamic, decoupling fields from fixed extension prefixes.
 */
final class ConstantFieldRenderer
{
    /**
     * Renders the HTML number input field using dynamic dot-separated keys.
     * Fully compatible with the native TYPO3 v14 activation state mechanics.
     *
     * @param array<string, mixed> $params
     * @param bool $allowEmpty
     * @return string
     */
    public function renderField(array $params, bool $allowEmpty): string
    {
        // FIX: Re-establish the correct variable retrieval from the parameters map
        $pureFieldName = (string)($params['fieldName'] ?? $params['name'] ?? '');
        $currentValue = (string)($params['fieldValue'] ?? $params['value'] ?? '');

        // Determine the dynamic prefix context from the given parameters definition map
        // If the core provides a full path in 'name', we use it, otherwise fall back dynamically
        if (str_contains($pureFieldName, '.')) {
            $constantKey = $pureFieldName;
        } else {
            // Fetch configuration context if available, otherwise determine extension scope safely
            $prefix = (string)($params['fieldDefinition']['conf']['prefix'] ?? 'plugin.tt_products.');
            $constantKey = rtrim($prefix, '.') . '.' . $pureFieldName;
        }

        if ($currentValue === '') {
            $currentValue = $allowEmpty ? '' : '0';
        }

        // Build flat naming structure dynamically using the resolved key path
        $fullHtmlName = sprintf('data[%s]', $constantKey);
        $fullCheckName = sprintf('check[%s]', $constantKey);

        $escapedName = htmlspecialchars($fullHtmlName, ENT_QUOTES, 'UTF-8');
        $escapedCheckName = htmlspecialchars($fullCheckName, ENT_QUOTES, 'UTF-8');
        $escapedValue = htmlspecialchars($currentValue, ENT_QUOTES, 'UTF-8');
        $requiredAttribute = $allowEmpty ? '' : ' required';

        // Enforces "checked" as the hidden field value to satisfy core activation filters natively
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
