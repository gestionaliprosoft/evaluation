<?php

namespace App\Filament\Widgets;

use App\Models\Sale\SaleQuote;
use Carbon\CarbonPeriod;
use Filament\Forms\Components\DatePicker;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class QuotesChart extends ApexChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'xl';

    protected static ?string $chartId = 'quotesChart';

    public function getHeading(): string|Htmlable|null
    {
        return __('widgets.quotes.Quotes Trend');
    }

    protected function getOptions(): array
    {
        $start = Carbon::parse($this->filterFormData['date_start'] ?? now()->startOfYear());
        $end = Carbon::parse($this->filterFormData['date_end'] ?? now());

        $period = CarbonPeriod::create($start->startOfMonth(), '1 month', $end->endOfMonth());

        $categories = [];
        $counts = [];
        $sums = [];

        foreach ($period as $date) {
            $monthStart = $date->copy()->startOfMonth()->format('Y-m-d');
            $monthEnd = $date->copy()->endOfMonth()->format('Y-m-d');

            $categories[] = $date->translatedFormat('M Y');

            $stats = SaleQuote::selectRaw('COUNT(*) as qty, SUM(total) as total_sum')
                ->where('date', '<=', $monthEnd)
                ->where(function ($query) use ($monthStart) {
                    $query->whereNull('valid_until')
                        ->orWhere('valid_until', '>=', $monthStart);
                })
                ->first();

            $counts[] = (int) ($stats->qty ?? 0);
            $sums[] = (float) ($stats->total_sum ?? 0);
        }

        return [
            'chart' => [
                'type' => 'area',
                'height' => 300,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                [
                    'name' => __('widgets.quotes.Active Quotes'),
                    'data' => $counts,
                ],
                [
                    'name' => __('widgets.Total Value').' '.auth()->user()->currency,
                    'data' => $sums,
                ],
            ],
            'xaxis' => [
                'categories' => $categories,
                'labels' => ['style' => ['colors' => '#9ca3af']],
            ],
            'yaxis' => [
                [
                    'title' => ['text' => __('Quantity')],
                    'seriesName' => __('widgets.quotes.Active Quotes'),
                    'labels' => ['style' => ['colors' => '#6366f1']],
                ],
                [
                    'opposite' => true,
                    'title' => ['text' => __('widgets.Value').' '.auth()->user()->currency],
                    'seriesName' => __('widgets.Total Value').' '.auth()->user()->currency,
                    'labels' => ['style' => ['colors' => '#10b981']],
                ],
            ],
            'colors' => ['#6366f1', '#10b981'],
            'stroke' => ['curve' => 'smooth', 'width' => 3],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shadeIntensity' => 1,
                    'opacityFrom' => 0.45,
                    'opacityTo' => 0.05,
                ],
            ],
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('date_start')
                ->label(__('From'))
                ->default(now()->startOfYear()),
            DatePicker::make('date_end')
                ->label(__('To'))
                ->default(now()),
        ];
    }

    public static function canView(): bool
    {
        if (auth()->user()->isMainTenantSuperUser()) {
            return true;
        } else {
            return auth()->user()->can('widget_QuotesChart');
        }
    }
}
