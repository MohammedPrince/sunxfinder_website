<?php

namespace App\Support;

use App\Models\Invoice;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * Renders an invoice to PDF. Uses mPDF because customer names and invoice items
 * can be Arabic, which needs real letter shaping and right-to-left support.
 */
class InvoicePdf
{
    public static function render(Invoice $invoice): string
    {
        $tempDir = storage_path('framework/cache/mpdf');
        File::ensureDirectoryExists($tempDir);

        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 16,
            'margin_right' => 16,
            'margin_top' => 16,
            'margin_bottom' => 16,
            'default_font' => 'dejavusans',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => $tempDir,
        ]);

        $pdf->SetTitle('Invoice ' . $invoice->invoice_number);
        $pdf->SetAuthor((string) ($invoice->company_details['name'] ?? 'SunXFinder'));
        $pdf->WriteHTML(view('company.quote-requests.invoice-pdf', compact('invoice'))->render());

        return $pdf->Output('', Destination::STRING_RETURN);
    }

    public static function filename(Invoice $invoice): string
    {
        // Invoice numbers are typed by the company and may hold any character.
        return (Str::slug($invoice->invoice_number) ?: 'invoice-' . $invoice->id) . '.pdf';
    }
}
