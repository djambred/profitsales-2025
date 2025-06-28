<?php

namespace App\Filament\Sales\Widgets;

use App\Models\Order;
use App\Models\SalesCommissions;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget\Card;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class TotalCommissions extends BaseWidget
{
    protected function getCards(): array
    {
        $userId = Auth::id();

        // 🔢 Commissions
        $totalThisMonth = SalesCommissions::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->whereHas('sales', fn($q) => $q->where('user_id', $userId))
            ->sum('amount');

        $totalLastMonth = SalesCommissions::whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->whereHas('sales', fn($q) => $q->where('user_id', $userId))
            ->sum('amount');

        // 📦 Order counts
        $totalSO = Order::where('category', 'SO')
            ->whereHas('sales', fn($q) => $q->where('user_id', $userId))
            ->count();

        $totalPO = Order::where('category', 'PO')
            ->whereHas('sales', fn($q) => $q->where('user_id', $userId))
            ->count();

        $rejectedSO = Order::where('category', 'SO')
            ->where('status', 'Reject')
            ->whereHas('sales', fn($q) => $q->where('user_id', $userId))
            ->count();

        $pendingSO = Order::where('category', 'SO')
            ->where('status', 'Pending')
            ->whereHas('sales', fn($q) => $q->where('user_id', $userId))
            ->count();

        return [
            Card::make('Total Commissions This Month', 'IDR ' . number_format($totalThisMonth, 0))->color('success'),
            Card::make('Total Commissions Last Month', 'IDR ' . number_format($totalLastMonth, 0))->color('gray'),
            Card::make('Total Order', $totalPO . ' Orders')->color('warning')->description('Just order already being PO'),
            Card::make('Rejected Order', $rejectedSO . ' Orders')->color('danger')->description('Sales Orders rejected'),
            Card::make('Pending Approval', $pendingSO . ' Orders')->color('primary')->description('SO waiting for approval'),
        ];
    }
}
