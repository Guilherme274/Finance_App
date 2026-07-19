<x-filament-panels::page>
    <style>
        .invest-card {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 1.25rem;
            padding: 1.25rem;
            box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.05);
            transition: all 0.25s ease;
        }
        .dark .invest-card {
            background-color: #111827;
            border-color: #1f2937;
            box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.4);
        }
        .invest-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 25px -5px rgba(0, 0, 0, 0.1);
        }
    </style>

    <div class="space-y-8">
        <!-- TOP SUMMARY CARDS & QUICK ACTION -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="invest-card border-l-4 border-l-indigo-500">
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Aplicado</p>
                <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">R$ {{ number_format($totalInvested, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Valor acumulado aportado</p>
            </div>

            <div class="invest-card border-l-4 border-l-emerald-500">
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Patrimônio Atual</p>
                <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">R$ {{ number_format($totalBalance, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Saldo atualizado dos ativos</p>
            </div>

            <div class="invest-card border-l-4 border-l-amber-500">
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rendimento Acumulado</p>
                <p class="text-2xl font-black {{ $totalProfit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} mt-1">
                    {{ $totalProfit >= 0 ? '+' : '' }}R$ {{ number_format($totalProfit, 2, ',', '.') }}
                </p>
                <p class="text-xs font-semibold {{ $profitPercentage >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} mt-2">
                    {{ $profitPercentage >= 0 ? '▲' : '▼' }} {{ number_format($profitPercentage, 2, ',', '.') }}% sobre os aportes
                </p>
            </div>

            <div class="invest-card flex flex-col justify-between bg-gradient-to-br from-indigo-600 to-violet-700 text-white border-none">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-200">Novo Ativo</p>
                    <p class="text-lg font-bold mt-1">Adicionar Posição</p>
                </div>
                <a 
                    href="/admin/investments/create"
                    class="mt-4 inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-bold text-indigo-700 bg-white rounded-xl shadow hover:bg-indigo-50 transition-colors"
                >
                    <x-filament::icon icon="heroicon-m-plus-circle" class="h-4 w-4" />
                    Cadastrar Investimento
                </a>
            </div>
        </div>

        <!-- POPULAR MARKET RECOMMENDATIONS -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-filament::icon icon="heroicon-m-fire" class="h-5 w-5 text-amber-500" />
                        Oportunidades & Investimentos Populares no Mercado
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Ativos com boa relação risco x retorno sugeridos para diversificar seu patrimônio</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-5 gap-4">
                @foreach ($recommendations as $rec)
                    <div class="invest-card flex flex-col justify-between space-y-3">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full {{ $rec['colorClass'] }}">
                                    {{ $rec['category'] }}
                                </span>
                                <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400">
                                    {{ $rec['risk'] }}
                                </span>
                            </div>
                            <h3 class="font-bold text-sm text-gray-900 dark:text-white leading-tight">
                                {{ $rec['name'] }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $rec['institution'] }}
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-300 mt-2 line-clamp-3">
                                {{ $rec['description'] }}
                            </p>
                        </div>

                        <div class="pt-3 border-t border-gray-100 dark:border-gray-800 space-y-1 text-xs">
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Rentabilidade:</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $rec['yield'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Mínimo:</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $rec['minInvestment'] }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- ASSET CLASSES GUIDE & EXPLORER -->
        <div 
            x-data="{ tab: 'rendafixa' }"
            class="invest-card space-y-4"
        >
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-200 dark:border-gray-800 pb-3">
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-filament::icon icon="heroicon-m-academic-cap" class="h-5 w-5 text-indigo-500" />
                        Guia dos Tipos de Investimento
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Entenda as características de cada classe de ativo antes de alocar</p>
                </div>

                <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl text-xs overflow-x-auto">
                    <button 
                        @click="tab = 'rendafixa'"
                        :class="tab === 'rendafixa' ? 'bg-white dark:bg-gray-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400'"
                        class="px-3 py-1.5 font-bold rounded-lg transition-colors whitespace-nowrap"
                    >
                        Renda Fixa
                    </button>
                    <button 
                        @click="tab = 'fiis'"
                        :class="tab === 'fiis' ? 'bg-white dark:bg-gray-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400'"
                        class="px-3 py-1.5 font-bold rounded-lg transition-colors whitespace-nowrap"
                    >
                        Fundos Imobiliários (FIIs)
                    </button>
                    <button 
                        @click="tab = 'acoes'"
                        :class="tab === 'acoes' ? 'bg-white dark:bg-gray-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400'"
                        class="px-3 py-1.5 font-bold rounded-lg transition-colors whitespace-nowrap"
                    >
                        Ações (Renda Variável)
                    </button>
                    <button 
                        @click="tab = 'cripto'"
                        :class="tab === 'cripto' ? 'bg-white dark:bg-gray-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400'"
                        class="px-3 py-1.5 font-bold rounded-lg transition-colors whitespace-nowrap"
                    >
                        Criptomoedas
                    </button>
                </div>
            </div>

            <!-- TAB RENDA FIXA -->
            <div x-show="tab === 'rendafixa'" class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-200 dark:border-gray-800 space-y-2">
                    <p class="font-bold text-gray-900 dark:text-white text-sm">CDB, LCI e LCA</p>
                    <p class="text-gray-600 dark:text-gray-300">Títulos emitidos por bancos. CDBs pagam imposto de renda regressivo, enquanto LCIs e LCAs são totalmente **isentos de Imposto de Renda**.</p>
                    <p class="font-semibold text-emerald-600 dark:text-emerald-400">Proteção FGC até R$ 250 mil por CPF.</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-200 dark:border-gray-800 space-y-2">
                    <p class="font-bold text-gray-900 dark:text-white text-sm">Tesouro Direto</p>
                    <p class="text-gray-600 dark:text-gray-300">Títulos públicos do Governo Federal. O Tesouro Selic rende diariamente pós-fixado; o Tesouro IPCA protege contra a inflação.</p>
                    <p class="font-semibold text-indigo-600 dark:text-indigo-400">Risco soberano (menor risco do país).</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-200 dark:border-gray-800 space-y-2">
                    <p class="font-bold text-gray-900 dark:text-white text-sm">Quando Investir?</p>
                    <p class="text-gray-600 dark:text-gray-300">Ideal para reserva de emergência, metas de curto a médio prazo (1 a 3 anos) ou investidores conservadores.</p>
                    <p class="font-semibold text-amber-600 dark:text-amber-400">Previsibilidade total do retorno.</p>
                </div>
            </div>

            <!-- TAB FIIS -->
            <div x-show="tab === 'fiis'" x-cloak class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-200 dark:border-gray-800 space-y-2">
                    <p class="font-bold text-gray-900 dark:text-white text-sm">O que são FIIs?</p>
                    <p class="text-gray-600 dark:text-gray-300">Fundos que compram grandes imóveis (galpões logísticos, shoppings, prédios corporativos) e dividem o aluguel proporcionalmente.</p>
                    <p class="font-semibold text-emerald-600 dark:text-emerald-400">Aluguéis mensais isentos de IR na conta.</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-200 dark:border-gray-800 space-y-2">
                    <p class="font-bold text-gray-900 dark:text-white text-sm">Tijolo vs Papel</p>
                    <p class="text-gray-600 dark:text-gray-300">FIIs de Tijolo são imóveis físicos. FIIs de Papel investem em dívidas imobiliárias (CRIs) indexadas à inflação ou CDI.</p>
                    <p class="font-semibold text-sky-600 dark:text-sky-400">Acessível com poucas dezenas de reais.</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-200 dark:border-gray-800 space-y-2">
                    <p class="font-bold text-gray-900 dark:text-white text-sm">Vantagens</p>
                    <p class="text-gray-600 dark:text-gray-300">Liquidez diária para vender na bolsa sem precisar vender um imóvel inteiro. Dividend Yield mensal.</p>
                    <p class="font-semibold text-indigo-600 dark:text-indigo-400">Geração de renda passiva mensal.</p>
                </div>
            </div>

            <!-- TAB AÇÕES -->
            <div x-show="tab === 'acoes'" x-cloak class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-200 dark:border-gray-800 space-y-2">
                    <p class="font-bold text-gray-900 dark:text-white text-sm">Sócio de Grandes Empresas</p>
                    <p class="text-gray-600 dark:text-gray-300">Ao comprar uma ação, você se torna sócio de empresas gigantescas (Bancos, Energia, Varejo, Tecnologia).</p>
                    <p class="font-semibold text-amber-600 dark:text-amber-400">Potencial ilimitado de valorização.</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-200 dark:border-gray-800 space-y-2">
                    <p class="font-bold text-gray-900 dark:text-white text-sm">Dividendos & JCP</p>
                    <p class="text-gray-600 dark:text-gray-300">As empresas lucrativas distribuem parte de seus lucros aos acionistas trimestralmente ou anualmente.</p>
                    <p class="font-semibold text-emerald-600 dark:text-emerald-400">Construção de patrimônio no longo prazo.</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-200 dark:border-gray-800 space-y-2">
                    <p class="font-bold text-gray-900 dark:text-white text-sm">Gerenciamento de Risco</p>
                    <p class="text-gray-600 dark:text-gray-300">As cotações oscilam diariamente. Diversifique entre setores (Elétrico, Bancário, Saneamento) para reduzir a volatilidade.</p>
                    <p class="font-semibold text-rose-600 dark:text-rose-400">Requer mentalidade de longo prazo.</p>
                </div>
            </div>

            <!-- TAB CRIPTO -->
            <div x-show="tab === 'cripto'" x-cloak class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-200 dark:border-gray-800 space-y-2">
                    <p class="font-bold text-gray-900 dark:text-white text-sm">Bitcoin & Ethereum</p>
                    <p class="text-gray-600 dark:text-gray-300">Moedas digitais descentralizadas e globais. O Bitcoin funciona como escassez digital e reserva de valor global.</p>
                    <p class="font-semibold text-purple-600 dark:text-purple-400">Ativo 24/7 sem intermediários bancários.</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-200 dark:border-gray-800 space-y-2">
                    <p class="font-bold text-gray-900 dark:text-white text-sm">Alta Volatilidade</p>
                    <p class="text-gray-600 dark:text-gray-300">Grandes oscilações de preço no curto prazo. Deve representar uma fatia moderada (1% a 5%) do portfólio total.</p>
                    <p class="font-semibold text-rose-600 dark:text-rose-400">Alto risco x Alto retorno potencial.</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-200 dark:border-gray-800 space-y-2">
                    <p class="font-bold text-gray-900 dark:text-white text-sm">Como Alocar?</p>
                    <p class="text-gray-600 dark:text-gray-300">Invista valores que não precisará no curto prazo e mantenha aportes fracionados recorrentes (DCA).</p>
                    <p class="font-semibold text-indigo-600 dark:text-indigo-400">Inovação em tecnologia blockchain.</p>
                </div>
            </div>
        </div>

        <!-- LIVE COMPOUND INTEREST INVESTMENT SIMULATOR -->
        <div 
            x-data="{
                initial: 1000,
                monthly: 300,
                annualRate: 10.5,
                years: 5,
                get totalMonths() { return this.years * 12; },
                get monthlyRate() { return Math.pow(1 + (this.annualRate / 100), 1/12) - 1; },
                get totalDeposited() { return parseFloat(this.initial) + (parseFloat(this.monthly) * this.totalMonths); },
                get finalAmount() {
                    let total = parseFloat(this.initial);
                    let mRate = this.monthlyRate;
                    let mDeposit = parseFloat(this.monthly);
                    for (let i = 0; i < this.totalMonths; i++) {
                        total = (total + mDeposit) * (1 + mRate);
                    }
                    return total;
                },
                get totalInterest() { return Math.max(0, this.finalAmount - this.totalDeposited); }
            }"
            class="invest-card space-y-6"
        >
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-3">
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-filament::icon icon="heroicon-m-calculator" class="h-5 w-5 text-emerald-500" />
                        Simulador de Investimentos & Juros Compostos
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Projete o poder do tempo e dos aportes constantes no seu patrimônio</p>
                </div>
                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20">
                    Tempo: <span x-text="years"></span> Anos
                </span>
            </div>

            <!-- SIMULATOR CONTROLS -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
                <div>
                    <label class="font-bold text-gray-700 dark:text-gray-300">Aplicação Inicial (R$)</label>
                    <input 
                        type="number" 
                        x-model.number="initial" 
                        class="mt-1 w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm font-bold p-2.5 focus:ring-2 focus:ring-indigo-500"
                    />
                </div>

                <div>
                    <label class="font-bold text-gray-700 dark:text-gray-300">Aporte Mensal (R$)</label>
                    <input 
                        type="number" 
                        x-model.number="monthly" 
                        class="mt-1 w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm font-bold p-2.5 focus:ring-2 focus:ring-indigo-500"
                    />
                </div>

                <div>
                    <label class="font-bold text-gray-700 dark:text-gray-300">Taxa Estimada (% a.a.)</label>
                    <input 
                        type="number" 
                        step="0.1"
                        x-model.number="annualRate" 
                        class="mt-1 w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm font-bold p-2.5 focus:ring-2 focus:ring-indigo-500"
                    />
                </div>

                <div>
                    <label class="font-bold text-gray-700 dark:text-gray-300">Horizonte de Tempo (Anos)</label>
                    <input 
                        type="range" 
                        min="1" 
                        max="15" 
                        x-model.number="years" 
                        class="mt-3 w-full accent-indigo-600 cursor-pointer"
                    />
                    <div class="flex justify-between text-[10px] text-gray-400 mt-1 font-mono">
                        <span>1 ano</span>
                        <span>5 anos</span>
                        <span>10 anos</span>
                        <span>15 anos</span>
                    </div>
                </div>
            </div>

            <!-- SIMULATION RESULTS -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 dark:bg-gray-900/60 p-5 rounded-2xl border border-gray-200 dark:border-gray-800">
                <div class="space-y-1">
                    <p class="text-xs font-bold text-gray-500 dark:text-gray-400">Total Investido (Aportes)</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">
                        R$ <span x-text="totalDeposited.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                    </p>
                    <p class="text-[10px] text-gray-500">Seu capital desembolsado</p>
                </div>

                <div class="space-y-1">
                    <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400">Ganho em Juros Compostos</p>
                    <p class="text-xl font-black text-emerald-600 dark:text-emerald-400">
                        +R$ <span x-text="totalInterest.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                    </p>
                    <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">Dinheiro trabalhando para você</p>
                </div>

                <div class="space-y-1">
                    <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400">Montante Final Acumulado</p>
                    <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400">
                        R$ <span x-text="finalAmount.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                    </p>
                    <p class="text-[10px] text-indigo-500 font-semibold">Patrimônio total previsto</p>
                </div>
            </div>
        </div>

        <!-- MY INVESTMENTS TABLE LISTING -->
        <div class="invest-card space-y-4">
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-3">
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-filament::icon icon="heroicon-m-chart-bar-square" class="h-5 w-5 text-indigo-500" />
                        Seus Ativos Cadastrados
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Lista das suas aplicações atuais</p>
                </div>
                <a 
                    href="/admin/investments"
                    class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline"
                >
                    Gerenciar Posições →
                </a>
            </div>

            @if ($myInvestments->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-800 text-gray-500 dark:text-gray-400 uppercase font-bold text-[10px]">
                                <th class="py-2.5 px-3">Investimento</th>
                                <th class="py-2.5 px-3">Instituição</th>
                                <th class="py-2.5 px-3">Tipo</th>
                                <th class="py-2.5 px-3 text-right">Aplicado</th>
                                <th class="py-2.5 px-3 text-right">Saldo Atual</th>
                                <th class="py-2.5 px-3 text-right">Lucro / Rentab.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 font-medium">
                            @foreach ($myInvestments as $inv)
                                @php
                                    $profit = $inv->balance - $inv->amount_invested;
                                    $pct = $inv->amount_invested > 0 ? ($profit / $inv->amount_invested) * 100 : 0;
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="py-3 px-3 font-bold text-gray-900 dark:text-white">
                                        {{ $inv->name }}
                                    </td>
                                    <td class="py-3 px-3 text-gray-500 dark:text-gray-400">
                                        {{ $inv->institution ?? '-' }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                                            {{ $inv->type }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-right text-gray-600 dark:text-gray-300 font-mono">
                                        R$ {{ number_format($inv->amount_invested, 2, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-bold text-gray-900 dark:text-white font-mono">
                                        R$ {{ number_format($inv->balance, 2, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-bold font-mono {{ $profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ $profit >= 0 ? '+' : '' }}R$ {{ number_format($profit, 2, ',', '.') }}
                                        <span class="text-[10px] block">({{ number_format($pct, 2, ',', '.') }}%)</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400 space-y-2">
                    <x-filament::icon icon="heroicon-o-chart-bar" class="h-10 w-10 mx-auto text-gray-400" />
                    <p class="font-bold text-sm">Nenhum investimento cadastrado ainda</p>
                    <p class="text-xs">Cadastre seu primeiro ativo para acompanhar a evolução patrimonial.</p>
                    <a 
                        href="/admin/investments/create"
                        class="inline-block mt-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow transition-colors"
                    >
                        + Cadastrar Investimento
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
