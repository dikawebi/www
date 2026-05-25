<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fuel Journal Master</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        .sticky-header { position: sticky; top: 0; z-index: 1000; background: white; border-bottom: 2px solid #eee; padding: 15px 0; }
        .table thead th { background: #2d3436; color: white; font-size: 0.75rem; white-space: nowrap; }
        .table thead th a { color: white; text-decoration: none; display: block; width: 100%; }
        .table thead th a:hover { color: #00cec9; }
        .sort-icon { font-size: 0.6rem; margin-left: 5px; }
        .mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body>

<div class="sticky-header shadow-sm">
    <div class="container-fluid">
        <form action="" method="GET" id="filterForm" class="row g-2 align-items-end">
            <!-- Hidden Page & Sort Params -->
            <input type="hidden" name="page" value="0">
            <input type="hidden" name="sort" value="{{ $sortField }}">
            <input type="hidden" name="order" value="{{ $sortOrder }}">

            <div class="col-md-2">
                <label class="small fw-bold text-muted">FROM DATE</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
            </div>
            <div class="col-md-2">
                <label class="small fw-bold text-muted">TO DATE</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
            </div>
            <div class="col-md-3">
                <label class="small fw-bold text-muted">SEARCH UNIT (DT)</label>
                <input type="text" name="unit" class="form-control form-control-sm" placeholder="Search Unit..." value="{{ $unit }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-dark btn-sm w-100 fw-bold">APPLY FILTERS</button>
            </div>
            <div class="col-md-3 text-end">
                <a href="{{ url()->current() }}" class="btn btn-outline-danger btn-sm">RESET</a>
            </div>
        </form>
    </div>
</div>

<div class="container-fluid mt-3">
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        @php
                            // Helper function untuk generate Sort Link
                            function sortLink($field, $label, $currentField, $currentOrder) {
                                $nextOrder = ($currentField === $field && $currentOrder === 'asc') ? 'desc' : 'asc';
                                $icon = $currentField === $field ? ($currentOrder === 'asc' ? '▲' : '▼') : '⇅';
                                $url = request()->fullUrlWithQuery(['sort' => $field, 'order' => $nextOrder, 'page' => 0]);
                                return "<th class='text-nowrap'><a href='{$url}'>{$label} <span class='sort-icon'>{$icon}</span></a></th>";
                            }
                        @endphp

                        {!! sortLink('TransactionDate', 'Date', $sortField, $sortOrder) !!}
                        {!! sortLink('JournalNumber', 'Journal No', $sortField, $sortOrder) !!}
                        {!! sortLink('WBPDTNumber', 'Unit (DT)', $sortField, $sortOrder) !!}
                        <th class="text-end">HM Start</th>
                        <th class="text-end">HM End</th>
                        {!! sortLink('InventoryQuantity', 'Qty (L)', $sortField, $sortOrder) !!}
                        {!! sortLink('CostAmount', 'Total Cost', $sortField, $sortOrder) !!}
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                    <tr>
                        <td class="mono small">{{ date('d/m/Y', strtotime($tx['TransactionDate'])) }}</td>
                        <td class="fw-bold small">{{ $tx['JournalNumber'] }}</td>
                        <td class="text-primary fw-bold mono">{{ $tx['WBPDTNumber'] ?: '-' }}</td>
                        <td class="text-end mono small">{{ number_format($tx['WBPHMFirst'], 2) }}</td>
                        <td class="text-end mono small">{{ number_format($tx['WBPHMEnd'], 2) }}</td>
                        <td class="text-end mono fw-bold text-danger">{{ number_format($tx['InventoryQuantity'], 2) }}</td>
                        <td class="text-end mono fw-bold">{{ number_format($tx['CostAmount'], 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-5">Data tidak ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-between align-items-center mt-3 pb-5">
        <a href="{{ request()->fullUrlWithQuery(['page' => max(0, $page - 1)]) }}"
           class="btn btn-sm btn-white border {{ $page == 0 ? 'disabled' : '' }}">← Prev 10k</a>

        <span class="small fw-bold">PAGE {{ $page + 1 }}</span>

        <a href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}"
           class="btn btn-sm btn-white border {{ !$hasNextPage ? 'disabled' : '' }}">Next 10k →</a>
    </div>
</div>

</body>
</html>
