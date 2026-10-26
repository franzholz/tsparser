<?php
declare(strict_types=1);

namespace JambageCom\Tsparser\EventListener;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DataHandling\Event\BeforeProcessDatamapEvent;
use JambageCom\Tsparser\TypoScript\AST\Visitor\CustomConstantTypeVisitor;

class ConstantEditorActionSanitizer
{
    private string $logPath;

    public function __construct()
    {
        $this->logPath = Environment::getPublicPath() . '/log/tsparser.log';
    }

    public function onBeforeProcessDatamap(BeforeProcessDatamapEvent $event): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $datamap = $event->getDatamap();

        // Nur ausführen, wenn Modifikationen an sys_template vorgenommen werden
        if (!isset($datamap['sys_template']) || !is_array($datamap['sys_template'])) {
            return;
        }

        file_put_contents($this->logPath, sprintf("[%s] [EVENT-DATAHANDLER MATCH] BeforeProcessDatamapEvent gefangen!\n", $timestamp), FILE_APPEND);

        // 1. EXTRACT EINT+ FIELDS FROM THE VISITOR
        $eintPlusKeys = [];
        try {
            if (class_exists(CustomConstantTypeVisitor::class)) {
                $visitor = new CustomConstantTypeVisitor();
                if (method_exists($visitor, 'getEintPlusFields')) {
                    $eintPlusKeys = $visitor->getEintPlusFields();
                    file_put_contents($this->logPath, sprintf("[%s] [EVENT-DATAHANDLER AST] Visitor geladen. Keys: %d\n", $timestamp, count($eintPlusKeys)), FILE_APPEND);
                }
            }
        } catch (\Throwable $e) {
            file_put_contents($this->logPath, sprintf("[%s] [EVENT-DATAHANDLER AST ERROR] %s\n", $timestamp, $e->getMessage()), FILE_APPEND);
        }

        $datamapModified = false;

        // 2. Schleife über die Datamap-Einträge von sys_template
        foreach ($datamap['sys_template'] as $id => $fields) {
            if (!isset($fields['constants']) || !is_string($fields['constants'])) {
                continue;
            }

            $lines = explode(LF, $fields['constants']);
            $cleanedLines = [];

            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                if (str_contains($line, '=')) {
                    [$tsKey, $tsValue] = explode('=', $line, 2);
                    $trimmedKey = trim($tsKey);
                    $trimmedValue = trim($tsValue);

                    // Abgleich gegen Visitor oder Namensmuster
                    $isEintPlus = in_array($trimmedKey, $eintPlusKeys, true);
                    if (!$isEintPlus && (str_contains($trimmedKey, 'eint') || str_contains($trimmedKey, 'tt_products'))) {
                        $isEintPlus = true;
                    }

                    // Wenn geleert oder auf 0 -> restlos löschen!
                    if ($isEintPlus && ($trimmedValue === '' || $trimmedValue === '0')) {
                        file_put_contents($this->logPath, sprintf("[%s] [EVENT-DATAHANDLER PURGE] Entferne Feld: %s\n", $timestamp, $trimmedKey), FILE_APPEND);
                        $datamapModified = true;
                        continue; // Zeile überspringen
                    }
                }

                $cleanedLines[] = $line;
            }

            if ($datamapModified) {
                $datamap['sys_template'][$id]['constants'] = implode(LF, $cleanedLines);
            }
        }

        // 3. Zurückschreiben über die offizielle Event-Methode von TYPO3 v14
        if ($datamapModified) {
            $event->setDatamap($datamap);
            file_put_contents($this->logPath, sprintf("[%s] [EVENT-DATAHANDLER SUCCESS] Constants über PSR-14 Event bereinigt!\n", $timestamp), FILE_APPEND);
        }
    }
}
