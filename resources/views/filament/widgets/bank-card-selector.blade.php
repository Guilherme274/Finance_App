<x-filament-widgets::widget>
    <style>
        .emv-chip {
            width: 42px;
            height: 30px;
            background: linear-gradient(135deg, #fcd34d 0%, #f59e0b 50%, #d97706 100%);
            border-radius: 6px;
            position: relative;
            box-shadow: inset 0 0 2px rgba(0,0,0,0.4);
            border: 1px solid #b45309;
        }
        .emv-chip::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            width: 100%;
            height: 1px;
            background: rgba(0,0,0,0.25);
        }
        .emv-chip::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            width: 1px;
            height: 100%;
            background: rgba(0,0,0,0.25);
        }
        .physical-card {
            min-width: 320px;
            width: 320px;
            height: 195px;
            border-radius: 20px;
            padding: 22px;
            position: relative;
            cursor: pointer;
            user-select: none;
            transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2);
            flex-shrink: 0;
            margin: 6px;
        }
        .physical-card:hover {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 22px 35px -5px rgba(0,0,0,0.35);
        }
        .physical-card.selected-card {
            transform: translateY(-6px) scale(1.04);
            box-shadow: 0 0 25px rgba(147, 51, 234, 0.6);
            border: 2.5px solid #ffffff !important;
        }
    </style>

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-credit-card" class="h-5 w-5 text-indigo-500" />
                    Seus Cartões e Contas
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Clique em um cartão físico para filtrar instantaneamente as transações dessa conta
                </p>
            </div>

            @if ($selectedAccountId)
                <button 
                    wire:click="selectAccount(null)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 shadow-2xs hover:bg-indigo-100 transition-colors"
                >
                    <x-filament::icon icon="heroicon-m-x-mark" class="h-4 w-4" />
                    Limpar Filtro de Cartão
                </button>
            @endif
        </div>

        <div class="flex items-center gap-8 overflow-x-auto pb-6 pt-3 px-1 no-scrollbar">
            <!-- ALL ACCOUNTS GLOBAL CARD -->
            <div 
                wire:click="selectAccount(null)"
                class="physical-card flex flex-col justify-between {{ is_null($selectedAccountId) ? 'selected-card' : '' }}"
                style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%); color: #ffffff; border: 1.5px solid rgba(255,255,255,0.2);"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold uppercase tracking-widest text-indigo-400">Visão Global</span>
                    <x-filament::icon icon="heroicon-m-squares-2x2" class="h-6 w-6 text-indigo-300" />
                </div>
                <div class="my-auto">
                    <p class="text-2xl font-black tracking-tight">Todas as Contas</p>
                    <p class="text-xs text-gray-400 mt-1">Exibindo movimentações completas</p>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-300 font-mono">
                    <span>FILTRO GLOBAL</span>
                    <span class="font-bold text-indigo-400">{{ is_null($selectedAccountId) ? '✓ ATIVO' : 'SELECIONAR' }}</span>
                </div>
            </div>

            <!-- INDIVIDUAL BANK ACCOUNTS AS PHYSICAL CARDS -->
            @foreach ($cards as $card)
                <div 
                    wire:click="selectAccount({{ $card['id'] }})"
                    class="physical-card flex flex-col justify-between {{ $selectedAccountId === $card['id'] ? 'selected-card' : '' }}"
                    style="{{ $card['bgStyle'] }}"
                >
                    <!-- Card Top Header (Logo & Contactless) -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            @if ($card['isNubank'])
                                <span class="font-black text-lg tracking-tight text-white flex items-center gap-1.5">
                                    <span class="bg-white text-purple-900 rounded-lg px-2 py-0.5 text-xs font-black">nu</span>
                                    Nubank
                                </span>
                            @elseif ($card['isMercadoPago'])
                                <span class="font-black text-lg tracking-tight text-white flex items-center gap-1.5">
                                    <span class="bg-white text-sky-600 rounded-lg px-2 py-0.5 text-xs font-black">MP</span>
                                    Mercado Pago
                                </span>
                            @else
                                <span class="font-bold text-base text-white">{{ $card['institution'] }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Contactless wave icon -->
                            <svg class="w-5 h-5 text-white/90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 010-7.778M12.354 19.646a10 10 0 000-14.142M15.536 21.768a13 13 0 000-18.384"></path>
                            </svg>
                            <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full bg-white/20 text-white backdrop-blur-md">
                                {{ $card['typeLabel'] }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Chip & Masked Number -->
                    <div class="my-auto space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="emv-chip"></div>
                            <span class="text-sm font-bold text-white font-mono tracking-wider bg-black/20 px-2.5 py-1 rounded-lg backdrop-blur-sm">
                                {{ $card['balanceFormatted'] }}
                            </span>
                        </div>
                        <p class="text-sm font-mono tracking-widest text-white/90 pt-1">
                            {{ $card['cardNumber'] }}
                        </p>
                    </div>

                    <!-- Card Bottom Footer (Holder & Expiry) -->
                    <div class="flex items-center justify-between text-[11px] font-mono text-white/80 uppercase">
                        <div>
                            <p class="text-[9px] text-white/60">TITULAR</p>
                            <p class="font-semibold tracking-wider text-white truncate max-w-[160px]">{{ $card['holderName'] }}</p>
                        </div>
                        <div>
                            <p class="text-[9px] text-white/60">VALIDADE</p>
                            <p class="font-semibold text-white">{{ $card['expiry'] }}</p>
                        </div>
                        <div class="font-black italic text-sm text-white">
                            VISA
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
