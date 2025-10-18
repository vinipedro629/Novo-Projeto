<?php
// app/Http/Controllers/Api/EmployeeController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmployeeController extends Controller
{
    /**
     * Lista todos os funcionários com paginação
     */
    public function index(Request $request): JsonResponse
    {
        $query = Employee::with(['department', 'manager'])
            ->select(['id', 'employee_id', 'name', 'email', 'job_title', 'department_id', 'manager_id', 'is_active', 'created_at']);

        // Filtros
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('name')->paginate(20);

        return response()->json($employees);
    }

    /**
     * Exibe um funcionário específico
     */
    public function show(Employee $employee): JsonResponse
    {
        $employee->load(['department', 'manager', 'subordinates', 'payrollRecords', 'leaveRequests']);

        return response()->json([
            'employee' => $employee,
            'statistics' => [
                'subordinates_count' => $employee->subordinates()->count(),
                'payroll_records_count' => $employee->payrollRecords()->count(),
                'leave_requests_count' => $employee->leaveRequests()->count(),
            ]
        ]);
    }

    /**
     * Busca funcionários por termo
     */
    public function search(Request $request): JsonResponse
    {
        $term = $request->get('q');

        $employees = Employee::where('name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('employee_id', 'like', "%{$term}%")
            ->select(['id', 'name', 'email', 'employee_id', 'job_title'])
            ->limit(10)
            ->get();

        return response()->json($employees);
    }

    /**
     * Obtém estatísticas dos funcionários
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::active()->count(),
            'inactive_employees' => Employee::where('is_active', false)->count(),
            'employees_by_department' => Employee::selectRaw('department_id, COUNT(*) as count')
                ->with('department:id,name')
                ->groupBy('department_id')
                ->get(),
        ];

        return response()->json($stats);
    }
}
