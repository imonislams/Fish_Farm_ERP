<x-layout.app :title="$title ?? 'Dashboard'">

    <x-layout.page-header
        title="Dashboard"
        subtitle="Live farm overview — figures come straight from the database."
    >
        <x-slot:actions>
            <x-badge.badge tone="success" dot>Live</x-badge.badge>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- ---------------------------------------------------------------- KPIs --}}
    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        @foreach ($kpis as $kpi)
            <x-kpi-card.kpi-card
                :label="$kpi->label"
                :value="$kpi->display()"
                :hint="$kpi->hint"
                :tone="$kpi->available ? 'primary' : 'warning'"
                :icon="$kpi->icon()"
            />
        @endforeach
    </div>

    {{-- -------------------------------------------------------- quick actions --}}
    <div class="mt-5">
        <x-card.card title="Quick actions" subtitle="Jump straight into the common daily tasks.">
            <div class="flex flex-wrap gap-2">
                @foreach ($quickActions as $action)
                    @if ($action['route'])
                        <x-button.button :href="route($action['route'])" variant="outline" size="sm" :icon="$action['icon']">
                            {{ $action['label'] }}
                        </x-button.button>
                    @else
                        {{-- Route not implemented yet: show the action, disabled and honest. --}}
                        <x-button.button variant="outline" size="sm" :icon="$action['icon']" disabled
                                        title="This module is not implemented yet">
                            {{ $action['label'] }}
                        </x-button.button>
                    @endif
                @endforeach
            </div>
        </x-card.card>
    </div>

    {{-- ------------------------------------------------------------ analytics --}}
    @php
        $analyticsMeta = [
            'pond_status' => ['Pond Status', 'droplet', 'Pond counts grouped by status.'],
            'fish_stock' => ['Fish Stock', 'fish', 'Live fish count per pond.'],
            'feed_usage' => ['Feed Usage', 'feed', 'Feed consumed over time.'],
            'growth' => ['Growth', 'chart', 'Average weight trend.'],
            'sales' => ['Sales', 'cart', 'Sales value over time.'],
            'income_vs_expense' => ['Income vs Expense', 'report', 'Cash flow comparison.'],
        ];
    @endphp

    <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
        @foreach ($analytics as $key => $rows)
            <x-card.card :title="$analyticsMeta[$key][0] ?? $key">
                @if ($rows->isEmpty())
                    <x-empty-state.empty-state
                        :icon="$analyticsMeta[$key][1] ?? 'chart'"
                        title="No data yet"
                        :message="$analyticsMeta[$key][2] ?? 'This block populates once its module records data.'"
                    />
                @else
                    <x-table.table :headers="['Label', 'Value']">
                        @foreach ($rows as $row)
                            <tr>
                                <td class="px-4 py-3">{{ $row->label ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">{{ $row->value ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </x-table.table>
                @endif
            </x-card.card>
        @endforeach
    </div>

    {{-- --------------------------------------------------------------- recent --}}
    <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-card.card title="Recent transactions">
            @if ($recentTransactions->isEmpty())
                <x-empty-state.empty-state
                    icon="book"
                    title="No transactions yet"
                    message="Sales, purchases and payments will appear here as they are recorded."
                />
            @else
                <x-table.table :headers="['Date', 'Reference', 'Amount']">
                    @foreach ($recentTransactions as $transaction)
                        <tr>
                            <td class="px-4 py-3">{{ $transaction->date ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $transaction->reference ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ $transaction->amount ?? '—' }}</td>
                        </tr>
                    @endforeach
                </x-table.table>
            @endif
        </x-card.card>

        <x-card.card title="Recent inspections">
            @if ($recentInspections->isEmpty())
                <x-empty-state.empty-state
                    icon="chart"
                    title="No inspections recorded"
                    message="Pond inspections and their findings will be listed here."
                />
            @else
                <x-table.table :headers="['Date', 'Pond', 'Result']">
                    @foreach ($recentInspections as $inspection)
                        <tr>
                            <td class="px-4 py-3">{{ $inspection->date ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $inspection->pond ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $inspection->result ?? '—' }}</td>
                        </tr>
                    @endforeach
                </x-table.table>
            @endif
        </x-card.card>
    </div>

    {{-- Keeps the foundation honest about its current state. --}}
    <div class="mt-5">
        <x-alert.alert tone="info">
            <strong>Architecture foundation in place.</strong>
            Dashboard KPIs show “—” until their modules are implemented, so no figure on this
            page is ever invented. See <code>docs/MODULES.md</code> for module status.
        </x-alert.alert>
    </div>

</x-layout.app>
