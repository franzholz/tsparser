<?php
declare(strict_types=1);

namespace JambageCom\Tsparser\UserFunction;

use JambageCom\Tsparser\Service\ConstantFieldRenderer;

/**
 * Entry point for rendering the 'eint+' TypoScript constant type.
 * Allows the field to accept and store empty values.
 */
final class RenderEintPlus
{
    /**
     * @param ConstantFieldRenderer $fieldRenderer The shared rendering core service
     */
    public function __construct(
        private readonly ConstantFieldRenderer $fieldRenderer
    ) {}

    /**
     * Dispatches the parameters to the core renderer engine with allowEmpty set to true.
     *
     * @param array<string, mixed> $params Parameters provided by the TYPO3 core engine
     * @return string Generated HTML layout for the backend input field
     */
    public function render(array $params): string
    {
        return $this->fieldRenderer->renderField($params, true);
    }
}
