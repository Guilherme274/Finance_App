<x-filament-widgets::widget>
    <style>
        .fin-stat-card {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 1.25rem;
            padding: 1.25rem;
            min-height: 160px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.05);
            transition: all 0.25s ease-in-out;
            position: relative;
            overflow: hidden;
        }
        .dark .fin-stat-card {
            background-color: #111827;
            border-color: #1f2937;
            box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.4);
        }
        .fin-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .dark .fin-stat-card:hover {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.6);
        }

        /* High contrast text rules */
        .fin-title {
            color: #374151;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .dark .fin-title {
            color: #f3f4f6 !important;
        }

        .fin-value {
            color: #111827;
            font-size: 1.75rem;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }
        .dark .fin-value {
            color: #ffffff !important;
        }

        .fin-desc {
            color: #4b5563;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .dark .fin-desc {
            color: #9ca3af !important;
        }

        .fin-label {
            color: #374151;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .dark .fin-label {
            color: #e5e7eb !important;
        }

        .fin-label-bold {
            color: #111827;
            font-size: 0.75rem;
            font-weight: 800;
        }
        .dark .fin-label-bold {
            color: #ffffff !important;
        }

        /* Specific card theme borders and accents */
        .fin-card-emerald { border-left: 5px solid #10b981; }
        .dark .fin-card-emerald { border-left: 5px solid #34d399; }

        .fin-card-purple { border-left: 5px solid #a855f7; }
        .dark .fin-card-purple { border-left: 5px solid #c084fc; }

        .fin-card-sky { border-left: 5px solid #0284c7; }
        .dark .fin-card-sky { border-left: 5px solid #38bdf8; }

        .fin-card-amber { border-left: 5px solid #f59e0b; }
        .dark .fin-card-amber { border-left: 5px solid #fbbf24; }

        .fin-card-rose { border-left: 5px solid #f43f5e; }
        .dark .fin-card-rose { border-left: 5px solid #fb7185; }
    </style>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem;">
        @foreach ($cards as $card)
            <div 
                x-data="{ hovered: false }" 
                @mouseenter="hovered = true" 
                @mouseleave="hovered = false"
                @click="hovered = !hovered"
                style="position: relative; cursor: pointer;"
            >
                <div class="fin-stat-card {{ $card['themeClass'] ?? 'fin-card-emerald' }}">
                    <!-- Header (Title & Icon) -->
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <span class="fin-title" style="display: flex; align-items: center; gap: 0.375rem;">
                            <template x-if="hovered">
                                <x-filament::icon icon="heroicon-m-calculator" style="width: 0.875rem; height: 0.875rem; color: #8b5cf6; display: inline;" />
                            </template>
                            {{ $card['title'] }}
                        </span>
                        @if ($card['icon'])
                            <div style="{{ $card['iconStyle'] }} width: 2.25rem; height: 2.25rem; display: flex; align-items: center; justify-content: center; border-radius: 0.75rem; flex-shrink: 0;">
                                <x-filament::icon
                                    :icon="$card['icon']"
                                    style="width: 1.25rem; height: 1.25rem;"
                                />
                            </div>
                        @endif
                    </div>

                    <!-- DEFAULT VIEW (Main total, shown when NOT hovered) -->
                    <div x-show="!hovered" style="flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <div style="margin-top: 0.5rem; margin-bottom: 0.75rem;">
                            <span class="fin-value">
                                {{ $card['value'] }}
                            </span>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                            <span class="fin-desc" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $card['description'] }}
                            </span>
                            <span style="{{ $card['badgeStyle'] }} font-size: 0.6875rem; font-weight: 700; padding: 0.25rem 0.625rem; border-radius: 9999px; display: inline-flex; align-items: center; gap: 0.25rem; flex-shrink: 0;">
                                <x-filament::icon icon="heroicon-m-sparkles" style="width: 0.75rem; height: 0.75rem;" />
                                Ver Cálculo
                            </span>
                        </div>
                    </div>

                    <!-- HOVER VIEW (Inline calculation breakdown inside the card when hovered) -->
                    <div x-show="hovered" x-cloak style="flex: 1; display: flex; flex-direction: column; justify-content: center; padding-top: 0.25rem;">
                        <div style="display: flex; flex-direction: column; gap: 0.4rem;">
                            @foreach ($card['breakdown'] as $item)
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                                    <span class="{{ str_contains($item['label'], '=') ? 'fin-label-bold' : 'fin-label' }}">
                                        {{ $item['label'] }}
                                    </span>
                                    <span style="{{ $item['colorStyle'] }} font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.775rem;">
                                        {{ $item['value'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>
