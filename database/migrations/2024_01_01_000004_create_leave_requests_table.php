<?php
// database/migrations/2024_01_01_000004_create_leave_requests_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('type', ['vacation', 'sick', 'personal', 'maternity']);
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('comments')->nullable();
            $table->string('erp_request_id')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('approved_by')->references('id')->on('employees');

            $table->index(['employee_id', 'status']);
            $table->index(['status', 'start_date']);
            $table->index(['approved_by']);
            $table->index(['erp_request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
