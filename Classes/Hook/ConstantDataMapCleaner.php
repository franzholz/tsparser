<?php
namespace JambageCom\Tsparser\Hook;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DataHandling\DataHandler;

class ConstantDataMapCleaner
{
    /**
     * Dieser Hook wird vom Core zwingend aufgerufen, sobald der DataHandler
     * im ConstantEditorController instanziiert wird.
     */
    public function __construct()
    {
        $logFile = Environment::getPublicPath() . '/log/tsparser.log';

        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if ($request === null) {
            return;
        }

        $parsedBody = $request->getParsedBody();
        $data = $parsedBody['data'] ?? [];
        $check = $parsedBody['check'] ?? [];
        $modified = false;

        if (is_array($data) && count($data) > 0) {
            foreach ($data as $key => $value) {
                // Wir zwingen jedes fehlende Haken-Flag im Request auf 'checked',
                // BEVOR der Controller updateTemplateConstants ausführt.
                if ($value !== '' && (!isset($check[$key]) || $check[$key] !== 'checked')) {
                    $check[$key] = 'checked';
                    $modified = true;
                }
            }

            if ($modified) {
                $parsedBody['check'] = $check;
                // Den korrigierten Stream zurück in den globalen v14-Kernel injizieren
                $GLOBALS['TYPO3_REQUEST'] = $request->withParsedBody($parsedBody);

                file_put_contents($logFile, sprintf(
                    "[%s] [TSPARSER-CONSTRUCTOR-FIX] Request-Flags erfolgreich erzwungen für maxW_single: %s\n",
                    date('Y-m-d H:i:s'),
                    $data['plugin.tt_products.maxW_single'] ?? 'n/a'
                ), FILE_APPEND);
            }
        }
    }
}
