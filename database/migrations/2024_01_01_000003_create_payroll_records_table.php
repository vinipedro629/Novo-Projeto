<?php
// database/migrations/2024_01_01_000003_create_payroll_records_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('gross_salary', 10, 2);
            $table->decimal('net_salary', 10, 2);
            $table->json('details')->nullable(); // Detalhes do contracheque
            $table->string('erp_payroll_id')->nullable(); // ID do ERP
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees');

            $table->index(['employee_id', 'period_start', 'period_end']);
            $table->index(['erp_payroll_id']);
            $table->index(['processed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_records');
    }
};
