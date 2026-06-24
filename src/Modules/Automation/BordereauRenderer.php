<?php

declare(strict_types=1);

namespace App\Modules\Automation;

use App\Modules\Declarations\DeclarationRepository;

final class BordereauRenderer
{
    public static function renderHtml(array $declaration, string $periodLabel): string
    {
        $type = $declaration['type'];
        $template = match (true) {
            str_contains($type, 'CNAS') => 'cnas.php',
            $type === 'CACOBATPH' => 'cacobatph.php',
            $type === 'G50' => 'g50.php',
            $type === 'G12', $type === 'G12_BIS' => 'g12.php',
            default => 'default.php',
        };

        $path = ROOT_PATH . '/templates/declarations/bordereau/' . $template;
        if (!is_file($path)) {
            $path = ROOT_PATH . '/templates/declarations/bordereau/default.php';
        }

        ob_start();
        require $path;
        $html = ob_get_clean();

        return $html !== false ? $html : '';
    }

    /** Generate printable file; returns path to .html (and .pdf if Dompdf available). */
    public static function generateFile(int $declarationId): ?string
    {
        $declaration = DeclarationRepository::find($declarationId);
        if (!$declaration) {
            return null;
        }

        $periodLabel = DeadlineService::periodLabel($declaration['type'], [
            'year' => (int) $declaration['period_year'],
            'month' => $declaration['period_month'] ? (int) $declaration['period_month'] : null,
            'quarter' => $declaration['period_quarter'] ? (int) $declaration['period_quarter'] : null,
        ]);

        $html = self::renderHtml($declaration, $periodLabel);
        if ($html === '') {
            return null;
        }

        $dir = ROOT_PATH . '/storage/generated/' . date('Y/m');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $base = sprintf('bordereau_%d_%s_%s', $declarationId, $declaration['type'], date('Ymd_His'));
        $htmlPath = $dir . '/' . $base . '.html';
        file_put_contents($htmlPath, $html);

        self::tryDompdf($html, $dir . '/' . $base . '.pdf');

        return $htmlPath;
    }

    private static function tryDompdf(string $html, string $pdfPath): void
    {
        if (!class_exists(\Dompdf\Dompdf::class)) {
            return;
        }
        try {
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            file_put_contents($pdfPath, $dompdf->output());
        } catch (\Throwable) {
            // HTML fallback sufficient
        }
    }
}
