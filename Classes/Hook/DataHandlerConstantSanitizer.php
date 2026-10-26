<?php
declare(strict_types=1);

namespace JambageCom\Tsparser\Hook;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use JambageCom\Tsparser\TypoScript\AST\Visitor\CustomConstantTypeVisitor;

/**
 * Class DataHandlerConstantSanitizer
 *
 * Intercepts the TYPO3 DataHandler process to purge empty eint+ and tt_products
 * constants before they are written to the sys_template database table.
 */
class DataHandlerConstantSanitizer
{
    private string $logPath;

    public function __construct()
    {
        $this->logPath = Environment::getPublicPath() . '/log/tsparser.log';
    }

    /**
     * Core DataHandler hook executing before database operations start.
     *
     * @param DataHandler $dataHandler The executing Core DataHandler instance
     */
    public function processDatamap_beforeStart(DataHandler $dataHandler): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $datamap = $dataHandler->datamap;

        // Abort immediately if no sys_template payload is tracked in the current queue
        if (!isset($datamap['sys_template']) || !is_array($datamap['sys_template'])) {
            return;
        }

        file_put_contents($this->logPath, sprintf("[%s] [V14-HOOK HIT] Successfully entered via Services.yaml tag registration!\n", $timestamp), FILE_APPEND);

        // 1. EXTRACT EINT+ TARGET KEYS FROM VISITOR
        $eintPlusKeys = [];
        try {
            if (class_exists(CustomConstantTypeVisitor::class)) {
                $visitor = new CustomConstantTypeVisitor();
                if (method_exists($visitor, 'getEintPlusFields')) {
                    $eintPlusKeys = $visitor->getEintPlusFields();
                    file_put_contents($this->logPath, sprintf("[%s] [V14-AST] Visitor keys resolved: %d\n", $timestamp, count($eintPlusKeys)), FILE_APPEND);
                }
            }
        } catch (\Throwable $e) {
            file_put_contents($this->logPath, sprintf("[%s] [V14-AST ERROR] %s\n", $timestamp, $e->getMessage()), FILE_APPEND);
        }

        $datamapModified = false;

        // 2. ITERATE THROUGH SYS_TEMPLATE ENTRIES IN DATA MAP
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

                // Parse TypoScript assignment lines
                if (str_contains($line, '=')) {
                    [$tsKey, $tsValue] = explode('=', $line, 2);
                    $trimmedKey = trim($tsKey);
                    $trimmedValue = trim($tsValue);

                    // Match against parsed visitor rules or fallback name conventions
                    $isEintPlus = in_array($trimmedKey, $eintPlusKeys, true);
                    if (!$isEintPlus && (str_contains($trimmedKey, 'eint') || str_contains($trimmedKey, 'tt_products'))) {
                        $isEintPlus = true;
                    }

                    // If target field is emptied or reset to 0, completely purge the line from stream
                    if ($isEintPlus && ($trimmedValue === '' || $trimmedValue === '0')) {
                        file_put_contents($this->logPath, sprintf("[%s] [V14-PURGE] Dropping field assignment: %s\n", $timestamp, $trimmedKey), FILE_APPEND);
                        $datamapModified = true;
                        continue; // Skip appending this line to delete it
                    }
                }

                $cleanedLines[] = $line;
            }

            if ($datamapModified) {
                $datamap['sys_template'][$id]['constants'] = implode(LF, $cleanedLines);
            }
        }

        // 3. RE-INJECT MODIFIED DATA STRUCT BACK TO LIVE DATAHANDLER
        if ($datamapModified) {
            $dataHandler->datamap = $datamap;
            file_put_contents($this->logPath, sprintf("[%s] [V14-HOOK SUCCESS] Payload updated and synchronised successfully!\n", $timestamp), FILE_APPEND);
        }
    }
}
