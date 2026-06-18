<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Distinta {{ $entry->number ?? $entry->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; margin: 20px; }
        .header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:18px; }
        .company { text-align:left; }
        .meta { text-align:right; margin-top:1rem}
        .meta h2 { margin:0; }
        .lines { width:100%; border-collapse: collapse; margin-top:10px; }
        .lines th, .lines td { border: 1px solid #ddd; padding:6px; vertical-align: top; }
        .lines th { background:#f5f5f5; font-weight:600; text-align:left; }
        .totals { margin-top:12px; width:100%; }
        .right { text-align:right; }
        .footer { position: fixed; bottom: 10px; left: 20px; right: 20px; text-align:center; font-size:10px; color:#666; }
        .notes { margin-top:12px; font-size:11px; }
        .small { font-size:11px; color:#555; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company">
            <strong>{{ $company['name'] }}</strong><br>
            @if(!empty($company['address'])) {!! nl2br(e($company['address'])) !!} @endif
        </div>

        <div class="meta">
            <h2>{{ __('purchase-stock-entry.stock_entry_title', ['id' => $entry->id, 'date' => \Carbon\Carbon::parse($entry->arrival_date)->format('d-m-Y')]) }}</h2>
            <div><strong>{{ __('purchase-stock-entry.supplier_document_number') }}:</strong> {{ $entry->number }}</div>
            <div><strong>{{ __('purchase-stock-entry.supplier_document_date') }}:</strong> {{ \Carbon\Carbon::parse($entry->document_date)->format('d-m-Y') ?? '-' }}</div>
            <div class="small"><strong>Team:</strong> {{ $entry->team?->name ?? '—' }}</div>
            <div class="small"><strong>{{ __('purchase-stock-entry.created_by') }}:</strong> {{ $entry->user?->name ?? '—' }}</div>
        </div>
    </div>

    <div>
        <strong>{{ __('purchase-stock-entry.purchase_order_origin') }}</strong>
        <div style="margin-bottom:8px;">{!! nl2br(e($entry->description)) !!}</div>
    </div>

    <table class="lines">
        <thead>
            <tr>
                <th style="width:5%;">#</th>
                <th style="width:15%;">{{ __('purchase-stock-entryCode') }}</th>
                <th style="width:15%;">Sku</th>
                <th>{{ __('purchase-stock-entry.Description') }}</th>
                <th style="width:10%;">{{ __('purchase-stock-entry.Quantity') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lines as $i => $line)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $line['internal_code'] ?? '-' }}</td>
                    <td>{{ $line['sku'] ?? '-' }}</td>
                    <td>{{ $line['description'] ?? '-' }}</td>
                    <td style="text-align:right;">{{ number_format($line['quantity'] ?? 0, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;">---</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3"></td>
                <td style="text-align:right;"><strong>{{ __('purchase-stock-entry.Total') }}</strong></td>
                <td style="text-align:right;">{{ number_format($totals['lines'] ?? 0, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="notes">
        <strong>{{ __('Annotations') }}</strong>
        <div>{!! nl2br(e($entry->details['notes'] ?? '')) !!}</div>
    </div>

    <div class="footer">
        {{ __('purchase-stock-entry.generated_at') }} {{ $generated_at->format('d/m/Y H:i') }}
    </div>
</body>
</html>
