<?php

namespace App\Http\Controllers;

use App\Support\OutletContext;
use App\Support\RolePermission;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InertiaReportController extends Controller
{
    public function __invoke(Request $request, string $report, ReportService $reports): Response
    {
        $definition = $reports->definition($report);
        $user = OutletContext::user();

        abort_unless(RolePermission::can($user, $definition['key'], 'view'), 403);

        $outletRule = Rule::exists('outlets', 'id')->where('is_active', true);
        $outletRules = ['nullable', 'integer', $outletRule];
        if (! $user?->isAdmin()) {
            $outletRules[] = Rule::in(array_keys(OutletContext::selectableOutletOptions()));
        }

        $request->merge([
            'start_date' => $request->input('start_date') ?: now()->startOfMonth()->toDateString(),
            'end_date' => $request->input('end_date') ?: now()->toDateString(),
        ]);
        $filters = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:end_date'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'outlet_id' => $outletRules,
        ]);

        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];
        $outletId = $user?->isAdmin() ? ($filters['outlet_id'] ?? null) : $user?->outlet_id;
        $data = $reports->generate($report, $startDate, $endDate, $outletId);

        return Inertia::render('Reports/Show', [
            'report' => [
                'slug' => $report,
                'title' => $definition['title'],
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'outlet_id' => $outletId,
            ],
            'outlets' => OutletContext::selectableOutletOptions(),
            'reportNavigation' => collect($reports->definitions())
                ->filter(fn (array $item) => RolePermission::can($user, $item['key'], 'view'))
                ->map(fn (array $item, string $slug) => ['slug' => $slug, 'title' => $item['title']])
                ->values()
                ->all(),
            ...$data,
        ]);
    }
}
