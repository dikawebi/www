<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_transactions', function (Blueprint $table) {
            $table->foreignId('outlet_id')->nullable()->after('employee_id')
                ->constrained()->cascadeOnDelete();
        });

        // Backfill outlet_id dari outlet karyawannya (kompatibel SQLite/MySQL/Postgres).
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('
                UPDATE employee_transactions
                SET outlet_id = employees.outlet_id
                FROM employees
                WHERE employees.id = employee_transactions.employee_id
            ');
        } elseif ($driver === 'sqlite') {
            DB::statement('
                UPDATE employee_transactions
                SET outlet_id = (SELECT employees.outlet_id FROM employees WHERE employees.id = employee_transactions.employee_id)
                WHERE outlet_id IS NULL
            ');
        } else {
            DB::statement('
                UPDATE employee_transactions
                JOIN employees ON employees.id = employee_transactions.employee_id
                SET employee_transactions.outlet_id = employees.outlet_id
            ');
        }
    }

    public function down(): void
    {
        Schema::table('employee_transactions', function (Blueprint $table) {
            $table->dropForeign(['outlet_id']);
            $table->dropColumn('outlet_id');
        });
    }
};
