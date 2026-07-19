<?php

namespace App\Services;

use App\Models\Transaction;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Export transactions to XLSX / CSV compatible format with UTF-8 BOM.
     */
    public function exportXlsx(iterable $transactions, string $filename = 'relatorio_financeiro.csv'): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            
            // Write UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Write CSV Header
            fputcsv($handle, ['Data', 'Conta', 'Tipo', 'Descrição', 'Categoria', 'Valor (R$)', 'Gasto Fixo'], ';');

            foreach ($transactions as $t) {
                $typeLabel = $t->type === 'CREDIT' ? 'Crédito' : 'Pix / Débito';
                $isFixedLabel = $t->is_fixed ? 'Sim' : 'Não';
                $accName = $t->bankAccount?->name ?? $t->account_name ?? 'Outra';

                fputcsv($handle, [
                    $t->date ? $t->date->format('d/m/Y') : '',
                    $accName,
                    $typeLabel,
                    $t->description,
                    $t->category ?? 'Outros',
                    number_format((float) $t->amount, 2, ',', '.'),
                    $isFixedLabel,
                ], ';');
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export transactions and analytics report to PDF using Dompdf.
     */
    public function exportPdf(iterable $transactions, string $title = 'Relatório Financeiro'): StreamedResponse
    {
        $rows = [];
        $totalCredit = 0;
        $totalDebit = 0;
        $byCategory = [];

        foreach ($transactions as $t) {
            $amt = (float) $t->amount;
            if ($t->type === 'CREDIT') {
                $totalCredit += $amt;
            } else {
                $totalDebit += $amt;
            }

            $cat = $t->category ?? 'Outros';
            $byCategory[$cat] = ($byCategory[$cat] ?? 0) + $amt;
            $rows[] = $t;
        }

        $balance = $totalCredit - $totalDebit;

        $html = view('exports.pdf_report', [
            'title'        => $title,
            'transactions' => $rows,
            'totalCredit'  => $totalCredit,
            'totalDebit'   => $totalDebit,
            'balance'      => $balance,
            'byCategory'   => $byCategory,
            'dateGenerated'=> now()->format('d/m/Y H:i'),
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response()->stream(function () use ($dompdf) {
            echo $dompdf->output();
        }, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="relatorio_financeiro.pdf"',
        ]);
    }
}
