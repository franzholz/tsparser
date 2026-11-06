<?php
declare(strict_types=1);

namespace JambageCom\Tsparser\Service;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Core rendering engine for TypoScript Constant Editor fields in TYPO3 v14.
 */
final class ConstantFieldRenderer
{
    /**
     * Renders the HTML number input field using flat dot-separated keys.
     */
    public function renderField(array $params, bool $allowEmpty): string
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;

        // TYPO3 v14 übergibt bei User-Fields meist den vollen Pfad in 'name' oder 'fieldName'
        $pureFieldName = (string)($params['fieldName'] ?? $params['name'] ?? '');
        $currentValue = (string)($params['fieldValue'] ?? $params['value'] ?? '');

        // Dynamischer Schutz: Falls 'plugin.tt_products.' bereits im Namen enthalten ist,
        // nutzen wir ihn direkt. Ansonsten fügen wir das Präfix hinzu.
        if (str_starts_with($pureFieldName, 'plugin.tt_products.')) {
            $constantKey = $pureFieldName;
        } else {
            $constantKey = 'plugin.tt_products.' . ltrim($pureFieldName, '.');
        }

        // Wenn wir uns im POST-Lifecycle befinden, holen wir den live gesendeten Wert ab
        if ($request instanceof ServerRequestInterface && $request->getMethod() === 'POST') {
            $parsedBody = $request->getParsedBody();

            if (isset($parsedBody['data']) && is_array($parsedBody['data'])) {
                if (isset($parsedBody['data'][$constantKey])) {
                    $currentValue = (string)$parsedBody['data'][$constantKey];
                }
            }
        }

        if ($currentValue === '') {
            $currentValue = $allowEmpty ? '' : '0';
        }

        // Exakt das HTML-Format generieren, das Ihre funktionierende saveAction erwartet:
        $fullHtmlName = sprintf('data[%s]', $constantKey);
        $fullCheckName = sprintf('check[%s]', $constantKey);

        $escapedName = htmlspecialchars($fullHtmlName, ENT_QUOTES, 'UTF-8');
        $escapedCheckName = htmlspecialchars($fullCheckName, ENT_QUOTES, 'UTF-8');
        $escapedValue = htmlspecialchars($currentValue, ENT_QUOTES, 'UTF-8');
        $requiredAttribute = $allowEmpty ? '' : ' required';

        return sprintf(
            '<div class="form-control-wrap">' .
            '<input type="hidden" name="%1$s" value="1" />' .
            '<input type="number" name="%2$s" value="%3$s" class="form-control form-control-clearable" min="0" step="1"%4$s />' .
            '</div>',
            $escapedCheckName,
            $escapedName,
            $escapedValue,
            $requiredAttribute
        );
    }
}
