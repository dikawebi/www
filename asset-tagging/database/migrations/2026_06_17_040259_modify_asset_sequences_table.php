<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // PostgreSQL: use raw SQL statements
            DB::statement('ALTER TABLE asset_sequences DROP CONSTRAINT IF EXISTS asset_sequences_category_id_foreign');
            DB::statement('ALTER TABLE asset_sequences DROP CONSTRAINT IF EXISTS asset_sequences_category_id_unique');
            
            // Check if column exists before dropping
            $columnExists = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name='asset_sequences' AND column_name='category_id';");
            
            if (!empty($columnExists)) {
                DB::statement('ALTER TABLE asset_sequences DROP COLUMN category_id');
            }
            
            // Check if department_id column already exists
            $deptColumnExists = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name='asset_sequences' AND column_name='department_id';");
            
            if (empty($deptColumnExists)) {
                DB::statement('ALTER TABLE asset_sequences ADD COLUMN department_id BIGINT');
            }
            
            // Drop unique constraint if exists
            DB::statement('ALTER TABLE asset_sequences DROP CONSTRAINT IF EXISTS asset_sequences_department_id_unique');
            
            // Create unique constraint
            DB::statement('ALTER TABLE asset_sequences ADD CONSTRAINT asset_sequences_department_id_unique UNIQUE (department_id)');
            
            // Add foreign key constraint if not exists
            try {
                DB::statement('ALTER TABLE asset_sequences ADD CONSTRAINT asset_sequences_department_id_foreign FOREIGN KEY (department_id) REFERENCES departments(id)');
            } catch (\Throwable $e) {
                // Constraint might already exist
            }
        } else {
            // SQLite (testing) - column should not exist since we're creating fresh
            // Only attempt to drop if column exists
            if (Schema::hasColumn('asset_sequences', 'category_id')) {
                Schema::table('asset_sequences', function (Blueprint $table) {
                    try {
                        $table->dropForeign(['category_id']);
                    } catch (\Throwable $e) {}
                    
                    $table->dropColumn('category_id');
                });
            }
            
            // Add department_id only if it doesn't exist
            if (!Schema::hasColumn('asset_sequences', 'department_id')) {
                Schema::table('asset_sequences', function (Blueprint $table) {
                    $table->foreignId('department_id')->unique()->constrained('departments')->after('id');
                });
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE asset_sequences DROP CONSTRAINT IF EXISTS asset_sequences_department_id_foreign');
            DB::statement('ALTER TABLE asset_sequences DROP CONSTRAINT IF EXISTS asset_sequences_department_id_unique');
            DB::statement('ALTER TABLE asset_sequences DROP COLUMN IF EXISTS department_id');
            
            DB::statement('ALTER TABLE asset_sequences ADD COLUMN category_id BIGINT');
            DB::statement('ALTER TABLE asset_sequences ADD CONSTRAINT asset_sequences_category_id_unique UNIQUE (category_id)');
            DB::statement('ALTER TABLE asset_sequences ADD CONSTRAINT asset_sequences_category_id_foreign FOREIGN KEY (category_id) REFERENCES categories(id)');
        } else {
            Schema::table('asset_sequences', function (Blueprint $table) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            });
            
            Schema::table('asset_sequences', function (Blueprint $table) {
                $table->foreignId('category_id')->unique()->nullable()->constrained('categories')->after('id');
            });
        }
    }
};
