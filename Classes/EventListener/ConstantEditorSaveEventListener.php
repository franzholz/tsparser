<?php

declare(strict_types=1);

namespace JambageCom\Tsparser\EventListener;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\DataHandling\Event\BeforeProcessDatamapEvent;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Log\LogManager;

class ConstantEditorSaveEventListener
{
    private LoggerInterface $logger;

    public function __construct(LogManager $logManager)
    {
        // Logger sauber über Dependency Injection / Services.yaml holen
        $this->logger = $logManager->getLogger(__CLASS__);
    }

    public function __invoke(BeforeProcessDatamapEvent $event): void
    {
        // 1. PSR-7 ServerRequest sicher aus den Globals extrahieren
        $request = $GLOBALS['Request'] ?? null;
        if (!$request instanceof ServerRequest) {
            return;
        }

        // 2. Parsed Body (entspricht den validierten POST-Daten) holen
        $parsedBody = $request->getParsedBody();
        if (!is_array($parsedBody)) {
            return;
        }

        $data = $parsedBody['data'] ?? [];
        $check = $parsedBody['check'] ?? [];

        // 3. Debugging über die TYPO3 PSR-3 Logger API (Exakter Key aus dem Log-Schnitt)
        $constantKey = 'plugin.tt_products.maxW_single';

        if (isset($data[$constantKey])) {
            $this->logger->debug(sprintf('PSR-7 Request Data für %s: %s', $constantKey, $data[$constantKey]));
        } else {
            $this->logger->debug(sprintf('PSR-7 Request Data für %s: NICHT VORHANDEN', $constantKey));
        }

        $checkValue = $check[$constantKey] ?? 'FEHLT';
        $this->logger->debug(sprintf('PSR-7 Request Check für %s: %s', $constantKey, $checkValue));

        // 4. Manuelle Rettung der Daten
        // Wenn der Core 'int+' beim Speichern verwirft, injizieren wir die Daten
        // hier direkt in den DataHandler-Prozess über das $event.
        if (isset($data[$constantKey]) && $checkValue === 'checked') {
            $datamap = $event->getDatamap();

            // Wir prüfen, ob der DataHandler gerade sys_template verarbeitet
            if (isset($datamap['sys_template']) && is_array($datamap['sys_template'])) {
                foreach ($datamap['sys_template'] as $id => $fields) {
                    // Falls der Core den Wert filtert, erzwingen wir die Speicherung.
                    // TYPO3 v14 verarbeitet geänderte Konstanten im DataHandler
                    // primär über das Feld 'constants'.
                    if (isset($fields['constants'])) {
                        $currentConstants = $fields['constants'];

                        // Einfache Ersetzung oder Ergänzung des geänderten Wertes im TS-String
                        $pattern = '/(' . preg_quote($constantKey, '/') . '\s*=\s*)[^\r\n]*/';
                        if (preg_match($pattern, $currentConstants)) {
                            $datamap['sys_template'][$id]['constants'] = preg_replace($pattern, '${1}' . $data[$constantKey], $currentConstants);
                        } else {
                            $datamap['sys_template'][$id]['constants'] .= "\n" . $constantKey . ' = ' . $data[$constantKey];
                        }
                    } else {
                        // Fallback, falls das Feld in der Datamap noch nicht initialisiert war
                        $datamap['sys_template'][$id]['constants'] = $constantKey . ' = ' . $data[$constantKey];
                    }

                    $this->logger->debug(sprintf(
                        'Tsparser Rettung: %s (%s) wurde manuell in sys_template [%s] constants injiziert.',
                        $constantKey,
                        $data[$constantKey],
                        $id
                    ));
                }

                // Aktualisierte Datamap zurück an das Event übergeben
                $event->setDatamap($datamap);
            }
        }
    }
}
