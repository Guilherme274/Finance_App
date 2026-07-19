<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class SpreadsheetParserService
{
    /**
     * Parse uploaded CSV or XLSX file.
     * Returns array of associative rows.
     */
    public function parse(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'csv') {
            return $this->parseCsv($file->getRealPath());
        }

        if (in_array($extension, ['xlsx', 'xls'])) {
            return $this->parseXlsx($file->getRealPath());
        }

        throw new \InvalidArgumentException("Formato não suportado: {$extension}. Use CSV ou XLSX.");
    }

    private function parseCsv(string $path): array
    {
        $rows = [];
        $headers = [];

        if (($handle = fopen($path, 'r')) !== false) {
            // Detect delimiter (comma or semicolon)
            $firstLine = fgets($handle);
            rewind($handle);
            $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

            $rowIndex = 0;
            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                // Convert encoding to UTF-8 to handle ISO-8859-1 or Windows-1252 character sets
                $data = array_map(function($value) {
                    $encoding = mb_detect_encoding($value, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
                    return $encoding && $encoding !== 'UTF-8' ? mb_convert_encoding($value, 'UTF-8', $encoding) : $value;
                }, $data);

                if ($rowIndex === 0) {
                    $headers = array_map(fn($h) => mb_strtolower(trim($h)), $data);
                } else {
                    if (count($data) === count($headers)) {
                        $rows[] = array_combine($headers, $data);
                    }
                }
                $rowIndex++;
            }
            fclose($handle);
        }

        return $rows;
    }

    private function parseXlsx(string $path): array
    {
        $rows = [];
        $zip = new \ZipArchive();

        if ($zip->open($path) !== true) {
            throw new \RuntimeException("Não foi possível abrir o arquivo XLSX.");
        }

        // Read shared strings
        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml) {
            $ss = simplexml_load_string($ssXml);
            foreach ($ss->si as $si) {
                // Handle both simple <t> and <r><t> structure
                if (isset($si->t)) {
                    $sharedStrings[] = (string) $si->t;
                } else {
                    $text = '';
                    foreach ($si->r as $r) {
                        $text .= (string) $r->t;
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        // Read first sheet
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (!$sheetXml) {
            throw new \RuntimeException("Planilha vazia ou inválida.");
        }

        $sheet = simplexml_load_string($sheetXml);
        $headers = [];
        $rowIndex = 0;

        foreach ($sheet->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $value = '';
                $type  = (string) ($cell['t'] ?? '');

                if ($type === 's') {
                    // Shared string
                    $idx   = (int) $cell->v;
                    $value = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) $cell->is->t;
                } else {
                    $value = isset($cell->v) ? (string) $cell->v : '';
                }

                $rowData[] = $value;
            }

            if ($rowIndex === 0) {
                $headers = array_map(fn($h) => mb_strtolower(trim($h)), $rowData);
            } else {
                // Pad row to header length
                while (count($rowData) < count($headers)) {
                    $rowData[] = '';
                }
                if (!empty(array_filter($rowData))) {
                    $rows[] = array_combine($headers, array_slice($rowData, 0, count($headers)));
                }
            }
            $rowIndex++;
        }

        return $rows;
    }

    /**
     * Auto-map raw column names to our standard fields.
     * Standard fields: date, description, amount, type, category, account
     */
    public function detectColumnMapping(array $headers): array
    {
        $mapping = [];

        $patterns = [
            'date'        => ['data', 'date', 'dt', 'vencimento', 'lancamento', 'lançamento'],
            'description' => ['descricao', 'descrição', 'description', 'historico', 'histórico', 'memo', 'title', 'titulo', 'títulos'],
            'amount'      => ['valor', 'amount', 'value', 'vlr', 'vl', 'quantia', 'preco', 'preço'],
            'type'        => ['tipo', 'type', 'natureza', 'movimento'],
            'category'    => ['categoria', 'category', 'cat', 'grupo'],
            'account'     => ['conta', 'account', 'banco', 'bank', 'cartao', 'cartão'],
        ];

        foreach ($headers as $header) {
            $normalised = mb_strtolower(trim($header));
            foreach ($patterns as $field => $aliases) {
                if (in_array($normalised, $aliases) && !isset($mapping[$field])) {
                    $mapping[$field] = $header;
                    break;
                }
            }
        }

        return $mapping;
    }

    /**
     * Map a raw row to a Transaction-ready array using the detected column mapping.
     */
    public function mapRow(array $row, array $columnMapping, ?\App\Models\BankAccount $bankAccount = null): ?array
    {
        $get = fn($field) => isset($columnMapping[$field]) ? trim($row[$columnMapping[$field]] ?? '') : '';

        $rawDate   = $get('date');
        $rawAmount = $get('amount');
        $desc      = $get('description') ?: 'Importado';
        $type      = strtoupper($get('type')) ?: null;
        $category  = $get('category') ?: null;
        $account   = $get('account') ?: ($bankAccount?->name ?? null);

        // Parse date
        $date = $this->parseDate($rawDate);
        if (!$date) return null;

        // Parse amount
        $amount = $this->parseAmount($rawAmount);
        if ($amount === null) return null;

        // Determine type based on amount sign:
        // Positive/zero = CREDIT (Inflow / Entrada)
        // Negative = DEBIT (Outflow / Saída)
        $type = $amount >= 0 ? 'CREDIT' : 'DEBIT';

        // Force transfers/payments to Nubank/Fatura from Mercado Pago as DEBIT (outflow)
        if ($bankAccount && str_contains(strtolower($bankAccount->name), 'mercado')) {
            $d = strtolower($desc);
            if (str_contains($d, 'nubank') || str_contains($d, 'fatura')) {
                $type = 'DEBIT';
            }
        }

        // Auto-categorize if empty
        if (!$category) {
            $category = $this->inferCategory($desc);
        }

        return [
            'bank_account_id' => $bankAccount?->id,
            'description'     => $desc,
            'amount'          => abs($amount),
            'date'            => $date,
            'type'            => $type,
            'category'        => $category,
            'account_name'    => $account,
        ];
    }

    /**
     * Auto-infer category based on transaction description.
     */
    private function inferCategory(string $desc): string
    {
        $d = mb_strtolower($desc);

        if (preg_match('/(netflix|spotify|prime|hbo|disney|youtube|apple|google|cloud|patreon|assinatura|sub|recurring)/i', $d)) {
            return 'Assinatura';
        }
        if (preg_match('/(cinema|teatro|show|festa|evento|ingresso|games|steam|playstation|xbox|nintendo|jogo|entretenimento)/i', $d)) {
            return 'Entretenimento';
        }
        if (preg_match('/(restaurante|bar|pub|viagem|praia|hotel|pizzaria|fast food|lazer|parque|mcdonalds|burger)/i', $d)) {
            return 'Lazer';
        }
        if (preg_match('/(invest|rendimento|tesouro|cdb|fii|ações|cripto|binance|xp|rico|nuinvest|inter|investimento)/i', $d)) {
            return 'Investimento';
        }
        if (preg_match('/(mercado|supermercado|ifood|uber|posto|gasolina|farmacia|saude|aluguel|luz|agua|internet)/i', $d)) {
            return 'Alimentação / Essenciais';
        }

        return 'Outros';
    }

    private function parseDate(string $raw): ?string
    {
        if (empty($raw)) return null;

        // Excel serial date
        if (is_numeric($raw) && $raw > 1000 && $raw < 100000) {
            $unixDate = ($raw - 25569) * 86400;
            return date('Y-m-d', $unixDate);
        }

        // Try explicit ISO first
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) return $raw;

        // To prevent roll-over (e.g. month 28 adding 2 years), we check for warnings.
        // We prioritize US format if the month clearly looks like it, but let's check all:
        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'd.m.Y', 'm/d/Y', 'Y/m/d', 'd/m/y', 'm/d/y'];
        
        foreach ($formats as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $raw);
            if ($dt) {
                $errors = \DateTime::getLastErrors();
                if ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)) {
                    return $dt->format('Y-m-d');
                }
            }
        }

        // Fallback
        $ts = strtotime($raw);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function parseAmount(string $raw): ?float
    {
        if ($raw === '' || $raw === null) return null;

        // Remove currency symbols and spaces
        $clean = preg_replace('/[R$\s€£¥]/u', '', $raw);

        // Check if it has a comma as a decimal separator (common in BR format like -48,8 or -1.234,56)
        if (strpos($clean, ',') !== false) {
            // Check if there is also a period (e.g. -1.234,56)
            if (strpos($clean, '.') !== false) {
                // Remove the period (thousands separator) and replace comma with period (decimal)
                $clean = str_replace('.', '', $clean);
            }
            $clean = str_replace(',', '.', $clean);
        } else {
            // If there's no comma, it might be US format (e.g., -1,234.56 or -48.8)
            // Remove comma (thousands separator in US)
            $clean = str_replace(',', '', $clean);
        }

        return is_numeric($clean) ? (float) $clean : null;
    }
}
