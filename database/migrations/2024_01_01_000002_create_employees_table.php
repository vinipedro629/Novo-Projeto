<?php
// database/migrations/2024_01_01_000002_create_employees_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id')->unique(); // Chave externa do ERP
            $table->string('name');
            $table->string('cpf')->unique(); // Campo sensível
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->date('birth_date');
            $table->string('job_title')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->date('hire_date');
            $table->decimal('salary', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('erp_updated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('department_id')->references('id')->on('departments');
            $table->foreign('manager_id')->references('id')->on('employees');

            $table->index(['is_active', 'name']);
            $table->index(['department_id', 'is_active']);
            $table->index(['manager_id']);
            $table->index(['erp_updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
