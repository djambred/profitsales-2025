<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class BranchEarningsStat extends Widget
{
    protected static string $view = 'filament.widgets.branch-earnings-stat';
    protected static ?string $heading = '💰 Total PO per Cabang';

    public array $branches = [];

    public function mount(): void
    {
        $this->branches = DB::table('branches')
            ->join('employees', 'employees.branch_id', '=', 'branches.id')
            ->join('sales', 'sales.employee_id', '=', 'employees.id')
            ->join('orders', 'orders.sales_id', '=', 'sales.id')
            ->where('orders.category', 'PO')
            ->select(
                'branches.name as branch_name',
                DB::raw('SUM(orders.total - orders.sales_profit) as po_net_income')
            )
            ->groupBy('branches.name')
            ->orderBy('branches.name')
            ->get()
            ->map(fn($row) => [
                'name' => $row->branch_name,
                'total' => number_format($row->po_net_income, 0, ',', '.'),
            ])
            ->toArray();
    }
}
