<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Expense;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;

class FinanceController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $pendapatanHariIni = Invoice::where('status', 'paid')
            ->whereDate('paid_at', $today)
            ->sum('amount');

        $pendapatanBulanIni = Invoice::where('status', 'paid')
            ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $totalPiutang = Invoice::whereIn('status', ['unpaid', 'overdue'])
            ->sum('amount');

        $invoiceBelumDibayarCount = Invoice::whereIn('status', ['unpaid', 'overdue'])
            ->count();

        $pengeluaranBulanIni = Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $labaBersih = $pendapatanBulanIni - $pengeluaranBulanIni;

        $expenses = Expense::latest()->get();

        $chartData = [
            'labels' => [],
            'revenue' => [],
            'expense' => [],
        ];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $revenueSum = Invoice::where('status', 'paid')
                ->whereMonth('paid_at', $month->month)
                ->whereYear('paid_at', $month->year)
                ->sum('amount');

            $expenseSum = Expense::whereMonth('expense_date', $month->month)
                ->whereYear('expense_date', $month->year)
                ->sum('amount');

            $chartData['labels'][] = $month->translatedFormat('M Y');
            $chartData['revenue'][] = (float) $revenueSum;
            $chartData['expense'][] = (float) $expenseSum;
        }

        return Inertia::render('Finance/Index', [
            'stats' => [
                'pendapatanHariIni' => (float)$pendapatanHariIni,
                'pendapatanBulanIni' => (float)$pendapatanBulanIni,
                'totalPiutang' => (float)$totalPiutang,
                'invoiceBelumDibayarCount' => $invoiceBelumDibayarCount,
                'pengeluaranBulanIni' => (float)$pengeluaranBulanIni,
                'labaBersih' => (float)$labaBersih,
            ],
            'expenses' => $expenses,
            'chartData' => $chartData,
        ]);
    }

    public function storeExpense(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'expense_date' => 'required|date',
        ]);

        Expense::create($validated);

        return redirect()->back()->with('success', 'Pengeluaran berhasil dicatat.');
    }

    public function destroyExpense($id)
    {
        $expense = Expense::findOrFail($id);
        $expense->delete();

        return redirect()->back()->with('success', 'Pengeluaran berhasil dihapus.');
    }
}
