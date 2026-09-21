@props([
'headers' => [], // string[] or [['label' => '', 'align' => 'right', 'class' => '']]
'striped' => true,
])

{{--
 | Data table shell.
 | Wraps content in a horizontally scrollable container so action buttons are
 | never clipped on small screens. The caller supplies <tr>/<td> rows via the
 | default slot.
 |
 | Usage:
 |   <x-table.table :headers="['Pond', 'Area', ['label' => 'Actions', 'align' => 'right']]">
 |       <tr> ... </tr>
 |   </x-table.table>
--}}
<div class="table-shell">
    <table {{ $attributes->merge(['class' => 'w-full text-sm']) }}>
        @if (! empty($headers))
        <thead class="border-b border-border bg-surface-muted">
            <tr>
                @foreach ($headers as $header)
                @php
                $label = is_array($header) ? ($header['label'] ?? '') : $header;
                $align = is_array($header) ? ($header['align'] ?? 'left') : 'left';
                @endphp
                <th
                    scope="col"
                    @class([ 'whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-muted' , 'text-left'=> $align === 'left',
                    'text-right' => $align === 'right',
                    'text-center' => $align === 'center',
                    ])
                    >
                    {{ $label }}
                </th>
                @endforeach
            </tr>
        </thead>
        @endif

        <tbody @class(['divide-y divide-border', 'even:[&>tr]:bg-surface-muted'=> $striped])>
            {{ $slot }}
        </tbody>
    </table>
</div>