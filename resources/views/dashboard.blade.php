<!DOCTYPE html>
<html lang="pt-BR" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinanceApp - Gestão Inteligente</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
        }

        .glass {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .card-hover {
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(139, 92, 246, 0.2);
        }

        .gradient-text {
            background: linear-gradient(135deg, #a78bfa, #c084fc, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#a78bfa',
                        darkbg: '#0f172a',
                        cardbg: '#1e293b'
                    }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen flex flex-col">

    <!-- Header -->
    <header class="glass sticky top-0 z-50 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-violet-600 to-pink-500 flex items-center justify-center shadow-lg shadow-violet-500/30">
                    <i class="fa-solid fa-wallet text-white text-xl"></i>
                </div>
                <h1 class="text-2xl font-bold tracking-tight">Finance<span class="gradient-text">App</span></h1>
            </div>

            <div class="flex items-center gap-4">
                <div class="hidden sm:flex items-center gap-2 px-4 py-2 rounded-full bg-slate-800/50 border border-slate-700">
                    <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                    <span class="text-sm font-medium text-slate-300">Sistema Conectado</span>
                </div>
                <button class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center hover:bg-slate-700 transition">
                    <i class="fa-regular fa-bell text-slate-300"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">

        <!-- Messages -->
        @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex gap-3 items-center text-emerald-400">
            <i class="fa-solid fa-circle-check"></i>
            <p>{{ session('success') }}</p>
        </div>
        @endif
        @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 flex gap-3 items-center text-red-400">
            <i class="fa-solid fa-circle-exclamation"></i>
            <p>{{ session('error') }}</p>
        </div>
        @endif

        <!-- Tabs Navigation -->
        <div class="border-b border-slate-700/50 mb-8 mt-2">
            <nav class="-mb-px flex space-x-8 px-2" aria-label="Tabs">
                <button id="tab-btn-overview" onclick="switchTab('overview')" class="border-fuchsia-500 text-fuchsia-400 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors" aria-current="page">
                    <i class="fa-solid fa-wallet mr-2"></i>Visão Geral
                </button>
                <button id="tab-btn-investments" onclick="switchTab('investments')" class="border-transparent text-slate-400 hover:border-slate-500 hover:text-slate-300 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    <i class="fa-solid fa-chart-line mr-2"></i>Meus Investimentos
                </button>
            </nav>
        </div>

        <div id="tab-overview">
            <!-- Dashboard Overview Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

                <div class="md:col-span-2 glass rounded-3xl p-8 card-hover relative overflow-hidden group">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-violet-600/20 rounded-full blur-3xl group-hover:bg-violet-600/30 transition duration-500"></div>
                    <p class="text-slate-400 font-medium mb-1 uppercase tracking-wider text-sm">Saldo Consolidado</p>
                    <div class="flex items-baseline gap-2 mb-4">
                        <span class="text-5xl font-bold text-white">R$ {{ number_format($totalBalance, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex gap-4 opacity-80">
                        <div class="flex items-center gap-1 text-emerald-400 text-sm font-medium"><i class="fa-solid fa-arrow-trend-up"></i> Atualizado hoje</div>
                        <div class="flex items-center gap-1 text-slate-300 text-sm"><i class="fa-solid fa-building-columns relative top-[-1px]"></i> {{ $accounts->count() }} Contas vinculadas</div>
                    </div>
                </div>

                <div class="glass rounded-3xl p-6 card-hover flex flex-col justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-white mb-2 flex items-center gap-2">
                            <i class="fa-solid fa-cloud-arrow-down text-violet-400"></i> Importação de Dados
                        </h3>
                        <p class="text-slate-400 text-sm mb-4">Sincronize Mercado Pago ou importe CSV do Nubank.</p>
                        
                        <div class="space-y-3">
                            <button onclick="syncMercadoPago()" id="btn-sync-mp" class="w-full py-2.5 px-4 rounded-xl bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 text-sm font-medium border border-blue-500/30 transition-colors flex items-center justify-center gap-2">
                                <i class="fa-solid fa-rotate"></i> Sincronizar Mercado Pago
                            </button>
                            
                            <form id="form-import-nubank" onsubmit="importNubank(event)" class="w-full">
                                <label for="nubank-csv" class="w-full py-2.5 px-4 rounded-xl bg-purple-500/20 hover:bg-purple-500/30 text-purple-400 text-sm font-medium border border-purple-500/30 transition-colors flex items-center justify-center gap-2 cursor-pointer">
                                    <i class="fa-solid fa-file-csv"></i> Importar CSV Nubank
                                </label>
                                <input type="file" id="nubank-csv" accept=".csv, .xlsx" class="hidden" onchange="document.getElementById('btn-submit-nubank').click()">
                                <button type="submit" id="btn-submit-nubank" class="hidden"></button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Accounts List -->
                <div class="lg:col-span-1 space-y-4">
                    <h2 class="text-xl font-bold flex items-center gap-2 mb-4"><i class="fa-solid fa-building-columns text-slate-400 text-sm"></i> Minhas Contas</h2>

                    @forelse($accounts as $acc)
                    <div class="glass rounded-2xl p-5 card-hover">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h4 class="font-semibold text-white text-lg">{{ $acc->name }}</h4>
                                <p class="text-xs text-slate-400 uppercase tracking-wide">{{ $acc->type }}</p>
                            </div>
                            <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center">
                                <i class="fa-solid fa-wallet text-slate-400 text-xs"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-2xl font-bold tracking-tight text-white">R$ {{ number_format($acc->balance, 2, ',', '.') }}</p>
                            <p class="text-xs text-emerald-400 mt-1"><i class="fa-solid fa-circle text-[8px] mr-1"></i>Sincronizado</p>
                        </div>
                    </div>
                    @empty
                    <div class="glass border-dashed border-2 border-slate-700 rounded-2xl p-8 text-center text-slate-500">
                        Nenhuma conta bancária vinculada.
                    </div>
                    @endforelse
                </div>

                <div class="lg:col-span-2">
                    <div class="flex justify-between items-center mb-4 flex-wrap gap-3">
                        <h2 class="text-xl font-bold flex items-center gap-2"><i class="fa-regular fa-rectangle-list text-slate-400 text-sm"></i> Últimas Transações</h2>
                        <div class="flex items-center gap-2">
                            <form action="{{ route('dashboard') }}" method="GET" class="flex items-center gap-2">
                                <input type="date" name="start_date" value="{{ request('start_date', $startDate ?? \Carbon\Carbon::now()->startOfMonth()->toDateString()) }}" class="bg-slate-900 border border-slate-700 text-slate-300 text-sm rounded-lg focus:ring-violet-500 focus:border-violet-500 block p-2">
                                <span class="text-slate-500">até</span>
                                <input type="date" name="end_date" value="{{ request('end_date', $endDate ?? \Carbon\Carbon::now()->endOfMonth()->toDateString()) }}" class="bg-slate-900 border border-slate-700 text-slate-300 text-sm rounded-lg focus:ring-violet-500 focus:border-violet-500 block p-2">
                                <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white p-2 rounded-lg transition-colors" title="Filtrar por data"><i class="fa-solid fa-filter"></i></button>
                            </form>
                            <button id="bulk-delete-btn" onclick="bulkDeleteTransactions()" class="hidden px-4 py-2 bg-red-600 hover:bg-red-500 text-white text-sm font-bold rounded-lg transition-colors flex items-center gap-2 shadow-lg shadow-red-500/20">
                                <i class="fa-solid fa-trash"></i> (<span id="bulk-count">0</span>)
                            </button>
                        </div>
                    </div>

                    <div class="glass rounded-3xl overflow-hidden shadow-xl border border-slate-700/50">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-800/80 border-b border-slate-700/80">
                                        <th class="py-4 px-6 w-12 text-center text-xs uppercase tracking-wider text-slate-400 font-medium">
                                            <input type="checkbox" id="selectAll" onclick="toggleAllTx(this)" class="w-4 h-4 rounded border-slate-700 text-violet-500 focus:ring-violet-500 bg-slate-900 cursor-pointer">
                                        </th>
                                        <th class="py-4 px-6 text-xs uppercase tracking-wider text-slate-400 font-medium">Data</th>
                                        <th class="py-4 px-6 text-xs uppercase tracking-wider text-slate-400 font-medium">Descrição</th>
                                        <th class="py-4 px-6 text-xs uppercase tracking-wider text-slate-400 font-medium">Conta</th>
                                        <th class="py-4 px-6 text-xs uppercase tracking-wider text-slate-400 font-medium text-right">Valor</th>
                                        <th class="py-4 px-6 text-xs uppercase tracking-wider text-slate-400 font-medium text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700/50">
                                    @forelse($transactions as $txn)
                                    <tr id="tx-row-{{ $txn->id }}" class="hover:bg-slate-800/40 transition">
                                        <td class="py-4 px-6 text-center">
                                            <input type="checkbox" class="tx-checkbox w-4 h-4 rounded border-slate-700 text-violet-500 focus:ring-violet-500 bg-slate-900 cursor-pointer" value="{{ $txn->id }}" onclick="checkTx(event)">
                                        </td>
                                        <td class="py-4 px-6 text-sm text-slate-300 font-medium whitespace-nowrap">{{ \Carbon\Carbon::parse($txn->date)->format('d/m/Y') }}</td>
                                        <td class="py-4 px-6 text-sm text-white">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-lg {{ $txn->amount < 0 ? 'bg-rose-500/10 text-rose-400' : 'bg-emerald-500/10 text-emerald-400' }} flex items-center justify-center shrink-0">
                                                    <i class="fa-solid {{ $txn->amount < 0 ? 'fa-arrow-down' : 'fa-arrow-up' }} text-xs"></i>
                                                </div>
                                                <span class="truncate max-w-[150px] md:max-w-xs pb-[2px]">{{ $txn->description }}</span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 text-sm text-slate-400 whitespace-nowrap">{{ optional($txn->bankAccount)->name ?? 'Conta Desconhecida' }}</td>
                                        <td class="py-4 px-6 text-sm font-semibold text-right whitespace-nowrap {{ $txn->amount < 0 ? 'text-white' : 'text-emerald-400' }}">
                                            {{ $txn->amount < 0 ? '-' : '+' }} R$ {{ number_format(abs($txn->amount), 2, ',', '.') }}
                                        </td>
                                        <td class="py-4 px-6 text-center whitespace-nowrap">
                                            <button onclick="deleteTransaction({{ $txn->id }})" class="p-2 text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 rounded-lg transition-colors" title="Excluir">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="py-12 px-6 text-center text-slate-500">
                                            As transações aparecerão aqui após conectar uma conta ou importar dados.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- End tab-overview -->

        <div id="tab-investments" class="hidden">
            <!-- Grid Top: Investments Balance -->
            <div class="grid grid-cols-1 mb-8">
                <div class="bg-slate-800/40 border border-slate-700/50 rounded-2xl p-6 shadow-xl backdrop-blur-xl relative overflow-hidden group">
                    <div class="absolute -right-20 -top-20 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl group-hover:bg-emerald-500/20 transition-all duration-500"></div>
                    <div class="relative z-10 text-center">
                        <p class="text-slate-400 text-sm font-semibold tracking-wider mb-2 uppercase">Total Investido (Consolidado)</p>
                        <h2 class="text-5xl font-black bg-clip-text text-transparent bg-gradient-to-r from-emerald-400 to-teal-200">
                            R$ {{ number_format($totalInvested, 2, ',', '.') }}
                        </h2>
                    </div>
                </div>
            </div>

            <!-- Categorized Tables -->
            <div class="space-y-8">
                @if($investmentsGrouped->isEmpty())
                <div class="bg-slate-800/40 border border-slate-700/50 rounded-2xl p-8 text-center border-dashed border-2">
                    <i class="fa-solid fa-seedling text-4xl text-slate-600 mb-3"></i>
                    <p class="text-slate-400 text-sm">Sua carteira de investimentos está vazia ou não foi sincronizada.</p>
                </div>
                @else
                @foreach($investmentsGrouped as $type => $group)
                <div class="bg-slate-800/40 border border-slate-700/50 rounded-2xl shadow-xl backdrop-blur-xl overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-700/50 bg-slate-800/60 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400">
                            <i class="fa-solid @if($type=='FIXED_INCOME') fa-building-columns @elseif($type=='MUTUAL_FUND') fa-briefcase @elseif($type=='EQUITY') fa-arrow-trend-up @else fa-piggy-bank @endif"></i>
                        </div>
                        <h3 class="text-lg font-bold text-white tracking-wide uppercase">{{ str_replace('_', ' ', $type) }}</h3>
                        <span class="ml-auto text-sm text-slate-400 font-medium pb-1 border-b border-slate-600">
                            R$ {{ number_format($group->sum('balance'), 2, ',', '.') }}
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-slate-400 uppercase bg-slate-900/30">
                                <tr>
                                    <th scope="col" class="px-6 py-4 font-semibold tracking-wider">Ativo / Nome</th>
                                    <th scope="col" class="px-6 py-4 font-semibold tracking-wider">Produto</th>
                                    <th scope="col" class="px-6 py-4 font-semibold tracking-wider">Conta</th>
                                    <th scope="col" class="px-6 py-4 text-right font-semibold tracking-wider">Saldo Atual</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/50">
                                @foreach($group as $inv)
                                <tr class="hover:bg-slate-700/20 transition-colors group/row">
                                    <td class="px-6 py-4 font-medium text-slate-200">
                                        {{ $inv->name }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-400">
                                        <span class="px-2.5 py-1 text-xs font-semibold rounded-md bg-slate-700/50 border border-slate-600">
                                            {{ $inv->subtype ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-400 text-xs">
                                        Conta Final {{ substr($inv->pluggy_item_id, -4) }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="font-semibold text-emerald-400">
                                            R$ {{ number_format($inv->balance, 2, ',', '.') }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach
                @endif
            </div>
        </div>

    </main>

    <script>
        const csrfToken = '{{ csrf_token() }}';

        function syncMercadoPago() {
            const btn = document.getElementById('btn-sync-mp');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sincronizando...';
            btn.disabled = true;

            fetch('/api/mercadopago/sync', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                alert('Mercado Pago sincronizado com sucesso!');
                location.reload();
            })
            .catch(() => {
                alert('Erro ao sincronizar Mercado Pago.');
                btn.innerHTML = '<i class="fa-solid fa-rotate"></i> Sincronizar Mercado Pago';
                btn.disabled = false;
            });
        }

        function importNubank(e) {
            e.preventDefault();
            const fileInput = document.getElementById('nubank-csv');
            if (!fileInput.files.length) return;

            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            formData.append('account_name', 'Nubank');

            const label = document.querySelector('label[for="nubank-csv"]');
            const originalContent = label.innerHTML;
            label.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Importando...';

            fetch('/import/upload', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Erro na importação: ' + (data.error || 'Erro desconhecido.'));
                    label.innerHTML = originalContent;
                }
            })
            .catch(() => {
                alert('Erro ao realizar upload do arquivo.');
                label.innerHTML = originalContent;
            });
        }

        function switchTab(tabName) {
            document.getElementById('tab-overview').classList.add('hidden');
            document.getElementById('tab-investments').classList.add('hidden');

            document.getElementById('tab-btn-overview').className = "border-transparent text-slate-400 hover:border-slate-500 hover:text-slate-300 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors";
            document.getElementById('tab-btn-investments').className = "border-transparent text-slate-400 hover:border-slate-500 hover:text-slate-300 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors";

            document.getElementById('tab-' + tabName).classList.remove('hidden');

            if (tabName === 'overview') {
                document.getElementById('tab-btn-' + tabName).className = "border-fuchsia-500 text-fuchsia-400 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors";
            } else {
                document.getElementById('tab-btn-' + tabName).className = "border-emerald-500 text-emerald-400 whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors";
            }
        }

        function toggleAllTx(el) {
            const checkboxes = document.querySelectorAll('.tx-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = el.checked;
            });
            toggleBulkDeleteBtn();
        }

        function checkTx(event) {
            if (event) {
                event.stopPropagation();
            }
            toggleBulkDeleteBtn();
        }

        function toggleBulkDeleteBtn() {
            const checkedCount = document.querySelectorAll('.tx-checkbox:checked').length;
            const btn = document.getElementById('bulk-delete-btn');
            const countSpan = document.getElementById('bulk-count');
            
            if (countSpan) {
                countSpan.textContent = checkedCount;
            }
            if (btn) {
                if (checkedCount > 0) {
                    btn.classList.remove('hidden');
                } else {
                    btn.classList.add('hidden');
                }
            }
        }

        function deleteTransaction(id) {
            if (!confirm('Tem certeza que deseja excluir esta transação?')) return;
            fetch('/api/transactions/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById('tx-row-' + id);
                    if (row) {
                        row.style.transition = 'opacity .3s';
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.remove();
                            location.reload();
                        }, 300);
                    } else {
                        location.reload();
                    }
                }
            })
            .catch(() => alert('Erro ao excluir transação'));
        }

        function bulkDeleteTransactions() {
            const checked = document.querySelectorAll('.tx-checkbox:checked');
            if (checked.length === 0) return;
            if (!confirm(`Tem certeza que deseja excluir as ${checked.length} transações selecionadas?`)) return;

            const ids = Array.from(checked).map(cb => cb.value);

            fetch('/api/transactions/bulk', {
                method: 'DELETE',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken 
                },
                body: JSON.stringify({ ids: ids })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('selectAll').checked = false;
                    toggleBulkDeleteBtn();
                    location.reload();
                } else {
                    alert('Erro ao excluir em massa');
                }
            })
            .catch(() => alert('Erro ao excluir em massa'));
        }
    </script>

</body>

</html>