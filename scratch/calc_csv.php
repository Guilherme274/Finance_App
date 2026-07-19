<?php
// Update the parser in our scratch script to match the new class method
$lines = file('C:/Users/guilh/Downloads/Meu extrato Nubank.csv');
$total = 0;
$count = 0;
foreach($lines as $i => $l) {
    if ($i === 0) continue;
    $cols = str_getcsv($l);
    if (count($cols) < 3) continue;
    if (strpos($cols[0], '2026-04') === 0) {
        $raw = $cols[2];
        $clean = preg_replace('/[R$\s€£¥]/u', '', $raw);
        if (strpos($clean, ',') !== false) {
            if (strpos($clean, '.') !== false) {
                $clean = str_replace('.', '', $clean);
            }
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace(',', '', $clean);
        }
        $val = (float)$clean;
        if ($val < 0) {
            $total += abs($val);
            $count++;
            echo "$count. CSV: " . $cols[0] . " | " . $cols[1] . " | " . abs($val) . "\n";
        }
    }
}
echo "TOTAL: " . $total . " | COUNT: " . $count . "\n";
