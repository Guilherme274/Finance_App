<?php
$lines = file('C:/Users/guilh/Downloads/Meu extrato Nubank.csv');
$count = 0;
foreach($lines as $i => $l) {
    if ($i === 0) continue;
    $cols = str_getcsv($l);
    if (count($cols) < 3) continue;
    if (strpos($cols[0], '2026-04') === 0) {
        $val = str_replace('"', '', $cols[2]);
        $val = str_replace('.', '', $val);
        $val = str_replace(',', '.', $val);
        $val = (float)$val;
        if ($val < 0) {
            $count++;
            echo "$count. CSV: " . $cols[0] . " | " . $cols[1] . " | " . abs($val) . "\n";
        }
    }
}
