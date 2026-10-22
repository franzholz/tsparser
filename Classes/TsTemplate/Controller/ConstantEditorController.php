<?php
declare(strict_types=1);

namespace JambageCom\Tsparser\TsTemplate\Controller;

use Throwable;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Controller zur Interzeption und Absicherung von eint+ Feldern
 * im TYPO3 v14 Konstanten-Editor.
 */
class ConstantEditorController
{
    public function __construct(
        protected readonly ConnectionPool $connectionPool
    ) {}

    /**
     * Centralized processing logic called by the PSR-15 Middleware.
     * Prevents empty eint+ fields from being saved as '0' in the sys_template constants field.
     * Only manipulates fields that the user actively intends to empty, leaving the rest completely untouched.
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    public function processFormInterception(ServerRequestInterface $request): ServerRequestInterface
    {
        // Process POST requests only (form save actions)
        if ($request->getMethod() !== 'POST') {
            return $request;
        }

        // LOCAL LOG PATH: Writes directly to [PROJECT-ROOT]/log/tsparser_debug.txt
        $logPath = Environment::getProjectPath() . '/log/tsparser_debug.txt';
        $parsedBody = $request->getParsedBody() ?? [];

        // Verify basic TYPO3 Form Structure presence
        if (!isset($parsedBody['data']) || !is_array($parsedBody['data'])) {
            return $request;
        }

        // Enhanced TYPO3 v14 page ID extraction from query or body context
        $pageId = (int)($request->getQueryParams()['id'] ?? $parsedBody['id'] ?? 0);
        if ($pageId === 0 && isset($parsedBody['pid'])) {
            $pageId = (int)$parsedBody['pid'];
        }
        if ($pageId === 0 && is_array($parsedBody['data'])) {
            foreach ($parsedBody['data'] as $key => $value) {
                if (is_numeric($key) && (int)$key > 0) {
                    $pageId = (int)$key;
                    break;
                }
            }
        }
        if ($pageId === 0 && $request->getAttribute('route') !== null) {
            $routeArguments = $request->getAttribute('route')->getArguments();
            $pageId = (int)($routeArguments['id'] ?? 0);
        }

        if ($pageId > 0) {
            try {
                // 1. Fetch all templates matching the page context and the rootline inheritance
                $templateRecords = $this->getActiveTemplatesForCurrentPage($pageId);

                // 2. Extract eint+ fields utilizing the active request body mapping context
                $statistics = $this->analyzeActiveEintPlusFields($templateRecords, $request);

                if (!empty($statistics['fields'])) {
                    $modified = false;
                    $targetField = 'plugin.tt_products.maxW_single';

                    // Track target state before execution
                    if (array_key_exists($targetField, $parsedBody['data'])) {
                        $beforeLog = sprintf("[%s] VALUE TRACE - BEFORE - Field: %s | Value: '%s'\n", date('Y-m-d H:i:s'), $targetField, is_array($parsedBody['data'][$targetField]) ? 'Array' : $parsedBody['data'][$targetField]);
                        @file_put_contents($logPath, $beforeLog, FILE_APPEND);
                    }

                    // 3. TEST RUN: Passive observation only - do not alter the request yet
                    foreach ($statistics['fields'] as $field) {
                        if (array_key_exists($field, $parsedBody['data'])) {
                            $rawValue = $parsedBody['data'][$field];

                            $isEmpty = false;
                            if (is_string($rawValue)) {
                                $isEmpty = (trim($rawValue) === '');
                            } elseif (is_array($rawValue)) {
                                $isEmpty = empty($rawValue);
                            }

                            if ($isEmpty && isset($parsedBody['check'][$field])) {
                                $modLog = sprintf("[%s] TEST OBSERVE - User wants to empty field: %s\n", date('Y-m-d H:i:s'), $field);
                                @file_put_contents($logPath, $modLog, FILE_APPEND);
                            }
                        }
                    }

                    // FORCE FALSE: We do absolutely nothing to the request to see if TYPO3 saves 214 now!
                    $modified = false;

                    if ($modified) {
                        $request = $request->withParsedBody($parsedBody);
                    }

                    // Track target state after processing loop
                    if (array_key_exists($targetField, $parsedBody['data'])) {
                        $afterLog = sprintf("[%s] VALUE TRACE - FINAL PAYLOAD - Field: %s | Value: '%s' | Payload Adjusted: %s\n", date('Y-m-d H:i:s'), $targetField, isset($parsedBody['data'][$targetField]) ? (is_array($parsedBody['data'][$targetField]) ? 'Array' : $parsedBody['data'][$targetField]) : 'REMOVED/UNSET', $modified ? 'YES' : 'NO');
                        @file_put_contents($logPath, $afterLog, FILE_APPEND);
                    }
                }
            } catch (Throwable $e) {
                $errorMsg = sprintf("ERROR IN CONTROLLER: %s on line %d", $e->getMessage(), $e->getLine());
                @file_put_contents($logPath, sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $errorMsg), FILE_APPEND);
            }
        }

        return $request;
    }


    /**
     * Fetches all root templates and local templates actively affecting the page inheritance tree.
     */
    protected function getActiveTemplatesForCurrentPage(int $pageId): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_template');
        return $queryBuilder
            ->select('uid', 'pid', 'constants', 'title', 'include_static_file')
            ->from('sys_template')
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageId)),
                    $queryBuilder->expr()->eq('root', $queryBuilder->createNamedParameter(1))
                ),
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0))
            )
            ->orderBy('root', 'ASC')
            ->addOrderBy('sorting', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();
    }
    /**
     * Extracts active eint+ fields by matching the submitted POST data keys against known configuration.
     * Bypasses manual disk I/O by parsing the pre-compiled frontend request body stream.
     *
     * @param array $templates
     * @param ServerRequestInterface $request
     * @return array
     */
    protected function analyzeActiveEintPlusFields(array $templates, ServerRequestInterface $request): array
    {
        $fields = [];
        // LOKALES LOG-VERZEICHNIS
        $logPath = Environment::getProjectPath() . '/log/tsparser_debug.txt';

        try {
            $parsedBody = $request->getParsedBody() ?? [];

            if (isset($parsedBody['data']) && is_array($parsedBody['data'])) {
                $submittedKeys = array_keys($parsedBody['data']);
                $pattern = '/(?:#|\/\/)\s*cat=[^#\n]*?type\s*=\s*eint\+.*?(?:[\r\n\s]+(?:#|\/\/).*?)*[\r\n\s]+([a-zA-Z0-9_.-]+)\s*(?:=|\{|:)/is';

                foreach ($templates as $template) {
                    $constantsText = $template['constants'] ?? '';
                    if (!empty($constantsText) && preg_match_all($pattern, $constantsText, $matches, PREG_SET_ORDER)) {
                        foreach ($matches as $match) {
                            $variablePath = isset($match[1]) ? trim($match[1]) : '';
                            if ($variablePath !== '' && in_array($variablePath, $submittedKeys, true)) {
                                $fields[] = $variablePath;
                            }
                        }
                    }
                }

                if (empty($fields)) {
                    $loggedCount = 0;
                    foreach ($submittedKeys as $key) {
                        if (str_starts_with($key, 'plugin.tt_products.')) {
                            $fields[] = $key;

                            // Limit the text file logging to the first 10 matches to save disk quota
                            if ($loggedCount < 10) {
                                $matchLog = sprintf("[%s] EINT+ SAFEGUARDED FROM REQUEST -> Path: %s\n", date('Y-m-d H:i:s'), $key);
                                @file_put_contents($logPath, $matchLog, FILE_APPEND);
                                $loggedCount++;
                            }
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            $errorLog = sprintf("[%s] CORE POST EXTRACTION EXCEPTION: %s on line %d\n", date('Y-m-d H:i:s'), $e->getMessage(), $e->getLine());
            @file_put_contents($logPath, $errorLog, FILE_APPEND);
        }

        $uniqueFields = array_unique($fields);

        $finalLog = sprintf("[%s] SCAN COMPLETED - Unique fields found: %d\n", date('Y-m-d H:i:s'), count($uniqueFields));
        @file_put_contents($logPath, $finalLog, FILE_APPEND);

        return [
            'fields' => $uniqueFields,
            'count' => count($uniqueFields)
        ];
    }

    /**
     * Schreibt detaillierte statistische Auswertungen in das Logfile.
     *
     * @param array $statistics
     * @param int $pageId
     * @param string $logPath
     * @return void
     */
    protected function logStatistics(array $statistics, int $pageId, string $logPath): void
    {
        $limitedFields = array_slice($statistics['fields'], 0, 10);
        $fieldsString = !empty($limitedFields) ? implode(', ', $limitedFields) : 'None';
        if (count($statistics['fields']) > 10) {
            $fieldsString .= '...';
        }

        $logContent = sprintf(
            "[%s] Statistics - Unique eint+ fields detected for Page %d: %d (%s)\n",
            date('Y-m-d H:i:s'),
            $pageId,
            $statistics['count'],
            $fieldsString
        );
        @file_put_contents($logPath, $logContent, FILE_APPEND);
    }
}


