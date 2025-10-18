<?php
// database/migrations/2024_01_01_000006_extend_users_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_id')->nullable()->after('email');
            $table->string('azure_id')->nullable()->after('employee_id');
            $table->unsignedBigInteger('department_id')->nullable()->after('azure_id');
            $table->unsignedBigInteger('manager_id')->nullable()->after('department_id');
            $table->boolean('is_active')->default(true)->after('manager_id');
            $table->timestamp('last_login_at')->nullable()->after('is_active');

            $table->foreign('employee_id')->references('employee_id')->on('employees');
            $table->foreign('department_id')->references('id')->on('departments');
            $table->foreign('manager_id')->references('id')->on('employees');

            $table->index(['employee_id']);
            $table->index(['azure_id']);
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['department_id']);
            $table->dropForeign(['manager_id']);
            $table->dropColumn(['employee_id', 'azure_id', 'department_id', 'manager_id', 'is_active', 'last_login_at']);
        });
    }
};
