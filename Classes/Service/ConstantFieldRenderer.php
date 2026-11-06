<?php
declare(strict_types=1);

namespace JambageCom\Tsparser\Service;

/**
 * Core rendering engine for TypoScript Constant Editor fields in TYPO3 v14.
 * Completely cleaned from temporary diagnostic logging and optimized for production.
 */
final class ConstantFieldRenderer
{
    /**
     * Renders the HTML number input field using flat dot-separated keys.
     * Fully compatible with the native TYPO3 v14 activation state mechanics.
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

        // CRITICAL FIX: Enforces "checked" as the hidden field value to pass core filters natively
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
