<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = \App\Models\Payroll::select('period_start','period_end','pay_date','status','total_salary','outlet_id','employee_id')
    ->whereBetween('pay_date',['2026-05-01','2026-08-31'])
    ->orderBy('outlet_id')->orderBy('period_start')->orderBy('employee_id')
    ->get();

echo '=== TOTAL ROWS ==='.PHP_EOL;
echo $rows->count().PHP_EOL;

echo PHP_EOL.'=== DISTINCT period_start ==='.PHP_EOL;
foreach($rows->pluck('period_start')->unique()->sort()->values() as $p){ echo $p.PHP_EOL; }

echo PHP_EOL.'=== DISTINCT pay_date ==='.PHP_EOL;
foreach($rows->pluck('pay_date')->unique()->sort()->values() as $p){ echo $p.PHP_EOL; }

echo PHP_EOL.'=== BY STATUS ==='.PHP_EOL;
foreach($rows->groupBy('status') as $s=>$r){ echo $s.': '.$r->count().PHP_EOL; }

echo PHP_EOL.'=== BY OUTLET ==='.PHP_EOL;
foreach($rows->groupBy('outlet_id') as $s=>$r){ echo 'Outlet '.$s.': '.$r->count().PHP_EOL; }

echo PHP_EOL.'=== UNIQUE (employee, period_start, outlet) ==='.PHP_EOL;
$unique = $rows->unique(fn($r)=>$r->employee_id.'|'.$r->period_start.'|'.$r->outlet_id);
echo 'Unique combos: '.$unique->count().PHP_EOL;

echo PHP_EOL.'=== BY (period_start, outlet) ==='.PHP_EOL;
foreach($rows->groupBy(fn($r)=>$r->period_start.'|O='.$r->outlet_id) as $k=>$r){ echo $k.' = '.$r->count().' rows'.PHP_EOL; }

echo PHP_EOL.'=== PERIODE YANG MUNCUL DI REPORT (periode_start unique) ==='.PHP_EOL;
foreach($rows->pluck('period_start')->unique()->sort()->values() as $p){
    echo $p.' → '.date('M Y',strtotime($p)).PHP_EOL;
}

echo PHP_EOL.'=== SUMMARY PAID (sesuai filter report) ==='.PHP_EOL;
$paid = $rows->where('status','paid');
echo 'Total paid rows: '.$paid->count().PHP_EOL;
echo 'Total salary paid: Rp '.number_format($paid->sum('total_salary'),0,',','.').PHP_EOL;
