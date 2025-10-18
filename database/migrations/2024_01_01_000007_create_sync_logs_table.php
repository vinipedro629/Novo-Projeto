<?php
// database/migrations/2024_01_01_000007_create_sync_logs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['employee_sync', 'payroll_sync', 'department_sync']);
            $table->enum('status', ['running', 'completed', 'completed_with_errors', 'failed'])->default('running');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('records_processed')->default(0);
            $table->integer('records_failed')->default(0);
            $table->json('parameters')->nullable();
            $table->json('error_details')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
