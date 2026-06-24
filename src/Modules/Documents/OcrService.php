<?php

declare(strict_types=1);

namespace App\Modules\Documents;

final class OcrService
{
    public static function extractText(string $filePath): string
    {
        $config = require ROOT_PATH . '/config/app.php';
        $tesseract = $config['tesseract_path'];
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'pdf') {
            $text = self::extractPdfText($filePath);
            if (trim($text) !== '') {
                return $text;
            }
            $png = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ocr_' . uniqid() . '.png';
            $cmd = sprintf('pdftoppm -singlefile -png %s %s 2>nul', escapeshellarg($filePath), escapeshellarg(substr($png, 0, -4)));
            @exec($cmd);
            $pngFile = substr($png, 0, -4) . '.png';
            if (is_file($pngFile)) {
                $text = self::runTesseract($tesseract, $pngFile);
                @unlink($pngFile);
                return $text;
            }
        }

        if (in_array($ext, ['png', 'jpg', 'jpeg', 'tiff', 'bmp'], true)) {
            return self::runTesseract($tesseract, $filePath);
        }

        return self::extractPdfText($filePath);
    }

    private static function runTesseract(string $binary, string $imagePath): string
    {
        $outBase = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tess_' . uniqid();
        $cmd = sprintf(
            '%s %s %s -l fra+ara --psm 6 2>nul',
            escapeshellcmd($binary),
            escapeshellarg($imagePath),
            escapeshellarg($outBase)
        );
        @exec($cmd);
        $txtFile = $outBase . '.txt';
        if (!is_file($txtFile)) {
            return '';
        }
        $text = file_get_contents($txtFile) ?: '';
        @unlink($txtFile);
        return $text;
    }

    private static function extractPdfText(string $filePath): string
    {
        if (!class_exists('\Smalot\PdfParser\Parser')) {
            $autoload = ROOT_PATH . '/vendor/autoload.php';
            if (is_file($autoload)) {
                require_once $autoload;
            }
        }
        if (class_exists('\Smalot\PdfParser\Parser')) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                return $pdf->getText();
            } catch (\Throwable) {
                return '';
            }
        }

        $output = [];
        @exec('pdftotext ' . escapeshellarg($filePath) . ' - 2>nul', $output);
        return implode("\n", $output);
    }

    public static function processDocument(int $documentId): void
    {
        $doc = DocumentRepository::find($documentId);
        if (!$doc) {
            return;
        }

        DocumentRepository::updateStatus($documentId, 'processing');

        try {
            $text = self::extractText($doc['file_path']);
            $docType = TemplateExtractor::classify($text, $doc['original_name']);
            $extracted = TemplateExtractor::extract($docType, $text);
            $source = 'template';

            $required = ['masse_salariale', 'salaire_base', 'ca_services', 'irg_acompte_base'];
            $missing = empty($extracted) || !self::hasRequired($extracted, $required);

            if ($missing) {
                $llm = \App\Modules\AI\OpenRouterClient::extractFields($text, $docType, $extracted);
                if ($llm) {
                    $extracted = array_merge($extracted, $llm);
                    $source = empty($extracted) ? 'llm' : 'mixed';
                }
            }

            if (!isset($extracted['entry_type'])) {
                $extracted['entry_type'] = in_array($docType, ['g50', 'g12', 'facture'], true) ? 'sales' : 'payroll';
            }

            $confidence = self::confidence($extracted);
            DocumentRepository::saveOcrResult($documentId, $text, $extracted, $confidence, $source);
            DocumentRepository::updateDocType($documentId, $docType, 'awaiting_review');
            self::applyAiGedMeta($documentId, $doc, $text);
        } catch (\Throwable $e) {
            DocumentRepository::updateStatus($documentId, 'failed');
            throw $e;
        }
    }

    private static function hasRequired(array $data, array $fields): bool
    {
        foreach ($fields as $f) {
            if (!empty($data[$f])) {
                return true;
            }
        }
        return false;
    }

    private static function confidence(array $extracted): float
    {
        $keys = ['masse_salariale', 'salaire_base', 'period_year', 'period_month', 'irg_acompte_base', 'ca_services'];
        $found = 0;
        foreach ($keys as $k) {
            if (!empty($extracted[$k])) {
                $found++;
            }
        }
        return round(min(95, 40 + $found * 12), 2);
    }

    private static function applyAiGedMeta(int $documentId, array $doc, string $text): void
    {
        if (!\App\Modules\Admin\SettingsService::bool('auto_ai_classify')) {
            return;
        }
        if (strlen(trim($text)) < 30) {
            return;
        }
        $secteur = null;
        if (!empty($doc['client_id'])) {
            $client = \App\Core\Database::fetchOne('SELECT secteur FROM clients WHERE id = ?', [$doc['client_id']]);
            $secteur = $client['secteur'] ?? null;
        }
        $meta = \App\Modules\AI\AiAutomationService::classifyDocument($doc['original_name'], $text, $secteur);
        if (!$meta) {
            return;
        }
        \App\Core\Database::query(
            'UPDATE documents SET category = ?, title = ?, doc_type = ?, notes = CONCAT(COALESCE(notes,\'\'), ?) WHERE id = ?',
            [
                $meta['category'],
                $meta['title'],
                $meta['doc_type'],
                '[IA] Classé automatiquement. ',
                $documentId,
            ]
        );
    }
}
