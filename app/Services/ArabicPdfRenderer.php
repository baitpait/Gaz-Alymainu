<?php

namespace App\Services;

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * تحويل HTML عربي (RTL) إلى PDF عبر mPDF مع خط XB Riyaz — للتقارير والكشوف.
 */
class ArabicPdfRenderer
{
    public function stream(
        string $html,
        string $filename,
        string $disposition = 'inline',
        ?string $documentTitle = null,
        ?string $documentAuthor = null,
    ): Response {
        $defaultConfig = (new ConfigVariables)->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables)->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $mpdfFontDir = base_path('vendor/mpdf/mpdf/ttfonts');

        $fontData = array_merge($fontData, [
            'xbriyaz' => [
                'R' => 'XB Riyaz.ttf',
                'B' => 'XB RiyazBd.ttf',
                'I' => 'XB RiyazIt.ttf',
                'BI' => 'XB RiyazBdIt.ttf',
                'useOTL' => 0xFF,
                'useKashida' => 75,
            ],
        ]);

        $mpdfTempDir = storage_path('app/mpdf');
        if (! is_dir($mpdfTempDir)) {
            mkdir($mpdfTempDir, 0775, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_top' => 20,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
            'fontDir' => array_merge($fontDirs, [$mpdfFontDir]),
            'fontdata' => $fontData,
            'default_font' => 'xbriyaz',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'useSubstitutions' => true,
            'tempDir' => $mpdfTempDir,
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        if ($documentTitle !== null && $documentTitle !== '') {
            $mpdf->SetTitle($documentTitle);
        }

        $author = $documentAuthor ?? config('app.company_display_name', 'Gaz Al-Yamani');
        $mpdf->SetAuthor($author);
        $mpdf->SetCreator($author);

        $mpdf->WriteHTML($html);

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
        ]);
    }
}
