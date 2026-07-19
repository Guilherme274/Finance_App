<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; margin: 20px; }
        .header { display: table; width: 100%; border-bottom: 2px solid #6366f1; padding-bottom: 10px; margin-bottom: 20px; }
        .header-title { font-size: 20px; font-weight: bold; color: #4f46e5; }
        .header-meta { font-size: 9px; color: #6b7280; text-align: right; }
        
        .summary-box { display: table; width: 100%; margin-bottom: 20px; background: #f9fafb; padding: 10px; border-radius: 6px; }
        .summary-card { display: table-cell; width: 33%; text-align: center; }
        .summary-card .label { font-size: 10px; color: #6b7280; text-transform: uppercase; }
        .summary-card .value { font-size: 14px; font-weight: bold; margin-top: 4px; }
        .text-green { color: #059669; }
        .text-red { color: #dc2626; }
        .text-indigo { color: #4f46e5; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #4f46e5; color: #ffffff; text-align: left; padding: 6px; font-size: 10px; font-weight: bold; }
        td { border-bottom: 1px solid #e5e7eb; padding: 6px; }
        tr:nth-child(even) { background: #f9fafb; }
        .badge { display: inline-block; padding: 2px 6px; font-size: 8px; font-weight: bold; border-radius: 4px; color: white; }
        .badge-credit { background: #d97706; }
        .badge-debit { background: #059669; }
        .badge-cat { background: #6366f1; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-title">{{ $title }}</div>
        <div class="header-meta">Gerado em: {{ $dateGenerated }}</div>
    </div>

    <div class="summary-box">
        <div class="summary-card">
            <div class="label">Total Crédito (Entradas)</div>
            <div class="value text-green">R$ {{ number_format($totalCredit, 2, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Total Débito / Pix (Saídas)</div>
            <div class="value text-red">R$ {{ number_format($totalDebit, 2, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <div class="label">Resultado / Balanço</div>
            <div class="value {{ $balance >= 0 ? 'text-green' : 'text-red' }}">R$ {{ number_format($balance, 2, ',', '.') }}</div>
        </div>
    </div>

    @if(!empty($byCategory))
    <h4 style="margin-bottom: 5px;">Gastos por Categoria</h4>
    <table>
        <thead>
            <tr>
                <th>Categoria</th>
                <th>Total (R$)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($byCategory as $catName => $catTotal)
            <tr>
                <td><strong>{{ $catName }}</strong></td>
                <td>R$ {{ number_format($catTotal, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <h4 style="margin-top: 20px; margin-bottom: 5px;">Detalhamento de Transações</h4>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Conta</th>
                <th>Tipo</th>
                <th>Descrição</th>
                <th>Categoria</th>
                <th style="text-align: right;">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $t)
            <tr>
                <td>{{ $t->date ? $t->date->format('d/m/Y') : '-' }}</td>
                <td>{{ $t->bankAccount?->name ?? $t->account_name ?? 'Outra' }}</td>
                <td>
                    <span class="badge {{ $t->type === 'CREDIT' ? 'badge-credit' : 'badge-debit' }}">
                        {{ $t->type === 'CREDIT' ? 'CRÉDITO' : 'PIX / DÉBITO' }}
                    </span>
                </td>
                <td>{{ $t->description }}</td>
                <td><span class="badge badge-cat">{{ $t->category ?? 'Outros' }}</span></td>
                <td style="text-align: right; font-weight: bold;" class="{{ $t->type === 'CREDIT' ? 'text-green' : 'text-red' }}">
                    R$ {{ number_format((float) $t->amount, 2, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
