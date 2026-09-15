<?php

namespace App\Http\Controllers;

use App\Support\OutletContext;
use App\Support\RolePermission;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InertiaReportController extends Controller
{
    public function __invoke(Request $request, string $report, ReportService $reports): Response
    {
        $definition = $reports->definition($report);
        $user = OutletContext::user();

        abort_unless(RolePermission::can($user, $definition['key'], 'view'), 403);

        $filters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'outlet_id' => ['nullable', 'integer'],
        ]);

        $startDate = $filters['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $filters['end_date'] ?? now()->toDateString();
        $data = $reports->generate($report, $startDate, $endDate, $filters['outlet_id'] ?? null);

        return Inertia::render('Reports/Show', [
            'report' => [
                'slug' => $report,
                'title' => $definition['title'],
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'outlet_id' => $user?->isAdmin() ? ($filters['outlet_id'] ?? null) : $user?->outlet_id,
            ],
            'outlets' => OutletContext::selectableOutletOptions(),
            ...$data,
        ]);
    }
}
