<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Models\Ingredient;
use App\Models\Stock;
use App\Models\StockOpname;
use App\Models\StockTransfer;
use App\Services\StockService;
use App\Support\OutletContext;
use App\Support\RolePermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Inertia\Inertia;
use Inertia\Response;

class InertiaStockController extends Controller
{
    public function stock(Request $request): Response
    {
        $user = OutletContext::user();
        abort_unless(RolePermission::can($user, 'StockResource', 'view'), 403);
        $rows = OutletContext::visibleQuery(Stock::query())
            ->with(['outlet:id,name', 'ingredient:id,name,unit,min_stock'])
            ->when($request->filled('q'), fn ($query) => $query->whereHas('ingredient', fn ($ingredient) => $ingredient->where('name', 'like', '%'.$request->string('q').'%')))
            ->when($request->boolean('low'), fn ($query) => $query->whereHas('ingredient', fn ($ingredient) => $ingredient->whereColumn('stocks.quantity', '<', 'ingredients.min_stock')))
            ->orderByDesc('quantity')->get();

        return Inertia::render('Stock/Index', ['mode' => 'stock', 'rows' => $rows, 'filters' => $request->only('q', 'low')]);
    }

    public function transfers(Request $request): Response
    {
        abort_unless(RolePermission::can(OutletContext::user(), 'StockTransferResource', 'view'), 403);
        $request->validate(['status' => ['nullable', 'in:draft,sent,received,cancelled']]);

        return Inertia::render('Stock/Index', [
            'mode' => 'transfers',
            'rows' => StockTransfer::query()->when(! OutletContext::user()->isAdmin(), fn ($q) => $q->where(fn ($scope) => $scope->where('from_outlet_id', OutletContext::user()->outlet_id)->orWhere('to_outlet_id', OutletContext::user()->outlet_id)))->with(['fromOutlet:id,name', 'toOutlet:id,name', 'items.ingredient:id,name,unit', 'sender:id,name', 'receiver:id,name', 'canceller:id,name'])->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))->latest()->get(),
            'outlets' => OutletContext::selectableOutletOptions(),
            'ingredients' => Ingredient::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit']),
            'filters' => $request->only('status'),
        ]);
    }

    public function storeTransfer(Request $request): RedirectResponse
    {
        abort_unless(RolePermission::can(OutletContext::user(), 'StockTransferResource', 'create'), 403);
        $data = $request->validate([
            'source' => ['required', 'in:transfer,purchase'],
            'from_outlet_id' => ['nullable', 'integer', 'exists:outlets,id'],
            'to_outlet_id' => ['required', 'integer', 'exists:outlets,id'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.ingredient_id' => ['required', 'integer', 'distinct', 'exists:ingredients,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);
        $user = OutletContext::user();
        $fromOutlet = $data['source'] === 'transfer' ? ($data['from_outlet_id'] ?? $user?->outlet_id) : null;
        if (! $user->isAdmin() && (int) $fromOutlet !== (int) $user->outlet_id) abort(403);
        if ((int) $data['to_outlet_id'] === (int) $fromOutlet) throw ValidationException::withMessages(['to_outlet_id' => 'Outlet asal dan tujuan harus berbeda.']);

        $transfer = DB::transaction(function () use ($data, $fromOutlet, $user) {
            $transfer = StockTransfer::create([
                'source' => $data['source'], 'from_outlet_id' => $fromOutlet, 'to_outlet_id' => $data['to_outlet_id'],
                'status' => 'draft', 'created_by' => $user->id, 'note' => $data['note'] ?? null,
            ]);
            $transfer->items()->createMany($data['items']);
            return $transfer;
        });

        return back()->with('success', "Transfer #{$transfer->id} dibuat.");
    }

    public function transferAction(Request $request, int $id, string $action, StockService $service): RedirectResponse
    {
        abort_unless(RolePermission::can(OutletContext::user(), 'StockTransferResource', 'edit'), 403);
        $transfer = StockTransfer::query()->with(['fromOutlet', 'toOutlet'])->findOrFail($id);
        if (! OutletContext::user()->isAdmin() && ! in_array(OutletContext::user()->outlet_id, [$transfer->from_outlet_id, $transfer->to_outlet_id], true)) abort(403);
        if (! in_array($action, ['ship', 'receive', 'cancel'], true)) abort(404);
        if (! OutletContext::user()->isAdmin() && (($action === 'receive' && OutletContext::user()->outlet_id !== $transfer->to_outlet_id) || (in_array($action, ['ship', 'cancel'], true) && OutletContext::user()->outlet_id !== $transfer->from_outlet_id))) abort(403);
        try {
            match ($action) {
                'ship' => $service->shipTransfer($transfer, OutletContext::user()->id),
                'receive' => $service->receiveTransfer($transfer, OutletContext::user()->id),
                'cancel' => $service->cancelTransfer($transfer, OutletContext::user()->id),
            };
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['transfer' => $exception->getMessage()]);
        }

        return back()->with('success', 'Status transfer diperbarui.');
    }

    public function opnames(Request $request): Response
    {
        abort_unless(RolePermission::can(OutletContext::user(), 'StockOpnameResource', 'view'), 403);
        $request->validate(['status' => ['nullable', 'in:draft,applied,cancelled']]);
        return Inertia::render('Stock/Index', [
            'mode' => 'opnames',
            'rows' => OutletContext::visibleQuery(StockOpname::query())->with(['outlet:id,name', 'items.ingredient:id,name,unit', 'performer:id,name', 'applier:id,name'])->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))->latest()->get(),
            'outlets' => OutletContext::selectableOutletOptions(),
            'ingredients' => Ingredient::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit']),
            'filters' => $request->only('status'),
        ]);
    }

    public function applyOpname(int $id, StockService $service): RedirectResponse
    {
        abort_unless(RolePermission::can(OutletContext::user(), 'StockOpnameResource', 'edit'), 403);
        $opname = StockOpname::query()->with(['outlet', 'items.ingredient'])->findOrFail($id);
        abort_unless(OutletContext::user()->isAdmin() || $opname->outlet_id === OutletContext::user()->outlet_id, 403);
        DB::transaction(function () use ($opname, $service) {
            $locked = StockOpname::query()->lockForUpdate()->with(['outlet', 'items.ingredient'])->findOrFail($opname->id);
            if ($locked->status !== 'draft' || $locked->items->isEmpty()) throw ValidationException::withMessages(['opname' => 'Opname tidak dapat diterapkan karena sudah diproses atau belum memiliki item.']);
            foreach ($locked->items as $item) {
                $stock = Stock::query()->firstOrCreate(
                    ['outlet_id' => $locked->outlet_id, 'ingredient_id' => $item->ingredient_id],
                    ['quantity' => 0],
                );
                $stock = Stock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
                $item->update(['system_qty' => $stock->quantity]);
                $difference = (float) $item->actual_qty - (float) $stock->quantity;
                if ($difference !== 0.0) $service->recordMovement($locked->outlet, $item->ingredient, StockMovementType::OpnameAdjustment, $difference, $locked, OutletContext::user()->id, "Opname #{$locked->id}");
            }
            $locked->update(['status' => 'applied', 'applied_by' => OutletContext::user()->id, 'applied_at' => now()]);
        });
        return back()->with('success', 'Stock opname diterapkan.');
    }

    public function storeOpname(Request $request): RedirectResponse
    {
        $user = OutletContext::user();
        abort_unless(RolePermission::can($user, 'StockOpnameResource', 'create'), 403);
        $data = $request->validate(['outlet_id' => ['required', 'integer', 'exists:outlets,id'], 'opname_date' => ['required', 'date'], 'note' => ['nullable', 'string'], 'items' => ['required', 'array', 'min:1'], 'items.*.ingredient_id' => ['required', 'integer', 'distinct', 'exists:ingredients,id'], 'items.*.actual_qty' => ['required', 'numeric', 'min:0']]);
        abort_unless($user->isAdmin() || (int) $data['outlet_id'] === (int) $user->outlet_id, 403);
        DB::transaction(function () use ($data, $user) {
            $opname = StockOpname::create(['outlet_id' => $data['outlet_id'], 'opname_date' => $data['opname_date'], 'performed_by' => $user->id, 'status' => 'draft', 'note' => $data['note'] ?? null]);
            foreach ($data['items'] as $item) $opname->items()->create(['ingredient_id' => $item['ingredient_id'], 'system_qty' => Stock::query()->where('outlet_id', $data['outlet_id'])->where('ingredient_id', $item['ingredient_id'])->value('quantity') ?? 0, 'actual_qty' => $item['actual_qty']]);
        });
        return back()->with('success', 'Stock opname dibuat.');
    }
}
