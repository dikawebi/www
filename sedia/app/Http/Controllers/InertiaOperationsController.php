<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\EmployeeTransaction;
use App\Models\Ingredient;
use App\Models\Payroll;
use App\Models\SalesTransaction;
use App\Models\Stock;
use App\Models\User;
use App\Notifications\KasbonStatusNotification;
use App\Support\OutletContext;
use App\Support\RolePermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InertiaOperationsController extends Controller
{
    public function index(Request $request, string $mode): Response
    {
        $user = OutletContext::user();
        $keys = ['payrolls' => 'PayrollResource', 'kasbons' => 'KasbonResource', 'closing' => 'TutupKasirHarian', 'reorder' => 'SaranReorder', 'activity-logs' => 'ActivityLogResource'];
        abort_unless(isset($keys[$mode]) && RolePermission::can($user, $keys[$mode], 'view'), 403);
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:draft,paid,cancelled,pending,approved,rejected'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $props = ['mode' => $mode, 'filters' => $request->only('q', 'status', 'date_from', 'date_to')];
        if ($mode === 'payrolls') $props['rows'] = OutletContext::visibleQuery(Payroll::query())->with(['outlet:id,name', 'employee:id,name', 'processor:id,name'])->when($request->filled('q'), fn ($q) => $q->whereHas('employee', fn ($employee) => $employee->where('name', 'like', '%'.$request->string('q').'%')))->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))->when($request->filled('date_from'), fn ($q) => $q->whereDate('pay_date', '>=', $request->date('date_from')))->when($request->filled('date_to'), fn ($q) => $q->whereDate('pay_date', '<=', $request->date('date_to')))->latest('pay_date')->paginate(25)->withQueryString();
        if ($mode === 'kasbons') $props['rows'] = OutletContext::visibleQuery(EmployeeTransaction::query())->where('type', 'kasbon')->with(['outlet:id,name', 'employee:id,name', 'reviewer:id,name'])->when($request->filled('q'), fn ($q) => $q->whereHas('employee', fn ($employee) => $employee->where('name', 'like', '%'.$request->string('q').'%')))->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))->when($request->filled('date_from'), fn ($q) => $q->whereDate('trans_date', '>=', $request->date('date_from')))->when($request->filled('date_to'), fn ($q) => $q->whereDate('trans_date', '<=', $request->date('date_to')))->latest('trans_date')->paginate(25)->withQueryString();
        if ($mode === 'activity-logs') $props['rows'] = ActivityLog::query()->when(! $user->isAdmin(), fn ($query) => $query->where('outlet_id', $user->outlet_id))->with(['user:id,name', 'outlet:id,name'])->when($request->filled('q'), fn ($q) => $q->where(fn ($builder) => $builder->where('action', 'like', '%'.$request->string('q').'%')->orWhere('description', 'like', '%'.$request->string('q').'%')))->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('date_from')))->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('date_to')))->latest()->paginate(30)->withQueryString();
        if ($mode === 'closing') { $closing = $this->closing($request); $props['rows'] = $closing['rows']; $props['summary'] = $closing['summary']; }
        if ($mode === 'reorder') $props['rows'] = $this->reorder($user);
        $props['employees'] = Employee::query()->where('status', 'active')->when(! $user->isAdmin(), fn ($q) => $q->where('outlet_id', $user->outlet_id))->get(['id', 'name', 'outlet_id']);
        $props['outlets'] = OutletContext::selectableOutletOptions();
        return Inertia::render('Operations/Index', $props);
    }

    public function storePayroll(Request $request): RedirectResponse
    {
        $user = OutletContext::user(); abort_unless(RolePermission::can($user, 'PayrollResource', 'create'), 403);
        $data = $request->validate(['outlet_id' => ['required', 'integer', 'exists:outlets,id'], 'employee_id' => ['required', 'integer', 'exists:employees,id'], 'pay_date' => ['required', 'date'], 'period_start' => ['required', 'date'], 'period_end' => ['required', 'date', 'after_or_equal:period_start'], 'base_salary' => ['required', 'numeric', 'min:0'], 'bonus_masuk' => ['nullable', 'numeric', 'min:0'], 'bonus_goreng' => ['nullable', 'numeric', 'min:0'], 'kasbon_deduction' => ['nullable', 'numeric', 'min:0'], 'note' => ['nullable', 'string']]);
        abort_unless($user->isAdmin() || (int) $data['outlet_id'] === (int) $user->outlet_id, 403);
        $employee = Employee::query()->whereKey($data['employee_id'])->where('status', 'active')->firstOrFail();
        if ((int) $employee->outlet_id !== (int) $data['outlet_id']) throw ValidationException::withMessages(['employee_id' => 'Karyawan harus berasal dari outlet penggajian.']);
        $gross = (float) $data['base_salary'] + (float) ($data['bonus_masuk'] ?? 0) + (float) ($data['bonus_goreng'] ?? 0);
        $deduction = (float) ($data['kasbon_deduction'] ?? 0);
        if ($deduction > $gross) throw ValidationException::withMessages(['kasbon_deduction' => 'Potongan kasbon tidak boleh melebihi gaji kotor.']);
        if ($deduction > max(0, $employee->outstandingKasbon())) throw ValidationException::withMessages(['kasbon_deduction' => 'Potongan kasbon melebihi saldo kasbon karyawan.']);
        Payroll::create([...$data, 'status' => 'draft']); return back()->with('success', 'Draft penggajian berhasil disimpan.');
    }

    public function payrollAction(int $id, string $action): RedirectResponse
    {
        $user = OutletContext::user();
        abort_unless($user->isAdmin() && RolePermission::can($user, 'PayrollResource', 'edit'), 403);
        if (! in_array($action, ['pay', 'cancel'], true)) abort(404);

        DB::transaction(function () use ($id, $action, $user) {
            $payroll = Payroll::query()->lockForUpdate()->findOrFail($id);
            if ($payroll->status !== 'draft') throw ValidationException::withMessages(['payroll' => 'Penggajian sudah diproses.']);
            $employee = Employee::query()->lockForUpdate()->findOrFail($payroll->employee_id);
            if ((int) $employee->outlet_id !== (int) $payroll->outlet_id) throw ValidationException::withMessages(['payroll' => 'Outlet karyawan tidak sesuai dengan penggajian.']);
            if ($action === 'pay' && (float) $payroll->kasbon_deduction > max(0, $employee->outstandingKasbon())) throw ValidationException::withMessages(['payroll' => 'Saldo kasbon berubah dan tidak lagi mencukupi potongan ini.']);
            $payroll->update(['status' => $action === 'pay' ? 'paid' : 'cancelled', 'processed_by' => $user->id, 'processed_at' => now()]);
        });

        return back()->with('success', $action === 'pay' ? 'Penggajian ditandai sudah dibayar.' : 'Penggajian dibatalkan.');
    }

    public function storeKasbon(Request $request): RedirectResponse
    {
        $user = OutletContext::user(); abort_unless(RolePermission::can($user, 'KasbonResource', 'create'), 403);
        $data = $request->validate(['employee_id' => ['required', 'integer', 'exists:employees,id'], 'amount' => ['required', 'numeric', 'gt:0'], 'trans_date' => ['required', 'date'], 'note' => ['nullable', 'string']]);
        $employee = Employee::findOrFail($data['employee_id']); abort_unless($user->isAdmin() || $employee->outlet_id === $user->outlet_id, 403);
        $transaction = EmployeeTransaction::create([...$data, 'outlet_id' => $employee->outlet_id, 'type' => 'kasbon', 'status' => 'pending']);
        User::query()->where('role', 'admin')->where('is_active', true)->get()->each->notify(new KasbonStatusNotification($transaction, 'submitted'));
        return back()->with('success', 'Kasbon diajukan.');
    }

    public function kasbonAction(int $id, string $action): RedirectResponse
    {
        $user = OutletContext::user(); abort_unless($user->isAdmin() && RolePermission::can($user, 'KasbonResource', 'edit'), 403);
        if (! in_array($action, ['approve', 'reject'], true)) abort(404);
        $transaction = DB::transaction(function () use ($id, $action) {
            $transaction = EmployeeTransaction::query()->where('type', 'kasbon')->lockForUpdate()->findOrFail($id);
            if ($transaction->status !== 'pending') throw ValidationException::withMessages(['kasbon' => 'Kasbon sudah diproses dan tidak dapat diubah lagi.']);
            $transaction->update(['status' => $action === 'approve' ? 'approved' : 'rejected', 'reviewed_by' => OutletContext::user()->id, 'reviewed_at' => now()]);
            return $transaction;
        });
        $transaction->employee()->with('user')->first()?->user?->notify(new KasbonStatusNotification($transaction, $action === 'approve' ? 'approved' : 'rejected'));
        return back()->with('success', $action === 'approve' ? 'Kasbon disetujui.' : 'Kasbon ditolak.');
    }

    private function closing(Request $request): array
    {
        $date = $request->query('date', now()->toDateString()); $query = OutletContext::visibleQuery(SalesTransaction::query())->where('status', 'completed')->whereDate('transaction_date', $date)->with('cashier');
        $transactions = $query->get();
        $rows = $transactions->groupBy('cashier_id')->map(fn ($items) => ['cashier_name' => $items->first()->cashier?->name ?? '—', 'transaction_count' => $items->count(), 'total' => (float) $items->sum('total_amount')])->values();
        $payments = $transactions->flatMap(function (SalesTransaction $transaction) {
            $change = (float) $transaction->change_amount;
            return collect($transaction->getPaymentsArray())->map(function (array $payment) use (&$change) {
                $amount = (float) $payment['amount'];
                if ($payment['method'] === 'cash' && $change > 0) {
                    $deduction = min($amount, $change);
                    $amount -= $deduction;
                    $change -= $deduction;
                }
                return ['method' => $payment['method'], 'amount' => $amount];
            });
        })->groupBy('method')->map(fn ($items) => (float) $items->sum('amount'));
        return ['date' => $date, 'summary' => ['transaction_count' => $rows->sum('transaction_count'), 'total' => $rows->sum('total'), 'payments' => $payments], 'rows' => $rows];
    }

    private function reorder($user): array
    {
        $outletId = OutletContext::currentOutletId() ?? $user->outlet_id; $stocks = Stock::query()->where('outlet_id', $outletId)->with('ingredient')->get()->keyBy('ingredient_id');
        return Ingredient::query()->where('is_active', true)->get()->map(function ($ingredient) use ($stocks) { $current = (float) ($stocks[$ingredient->id]->quantity ?? 0); $suggested = max(0, ceil((float) $ingredient->min_stock - $current)); return ['ingredient_name' => $ingredient->name, 'unit' => $ingredient->unit, 'current' => $current, 'min_stock' => (float) $ingredient->min_stock, 'suggested' => $suggested]; })->filter(fn ($row) => $row['suggested'] > 0)->values()->all();
    }
}
