<?php
// app/Http/Controllers/ReportController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Models\Department;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Exibe lista de relatórios disponíveis
     */
    public function index()
    {
        return view('reports.index');
    }

    /**
     * Relatório de folha de pagamento
     */
    public function payroll(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department_id' => 'nullable|exists:departments,id',
            'format' => 'nullable|in:html,pdf,csv',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $query = PayrollRecord::with(['employee.department'])
            ->whereBetween('period_start', [$startDate, $endDate])
            ->whereBetween('period_end', [$startDate, $endDate]);

        if ($request->filled('department_id')) {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        $payrollRecords = $query->get();

        // Calcula totais
        $totals = [
            'gross_total' => $payrollRecords->sum('gross_salary'),
            'net_total' => $payrollRecords->sum('net_salary'),
            'records_count' => $payrollRecords->count(),
        ];

        $format = $request->get('format', 'html');

        if ($format === 'pdf') {
            return $this->generatePayrollPDF($payrollRecords, $totals, $startDate, $endDate);
        }

        if ($format === 'csv') {
            return $this->generatePayrollCSV($payrollRecords, $startDate, $endDate);
        }

        return view('reports.payroll', compact('payrollRecords', 'totals', 'startDate', 'endDate'));
    }

    /**
     * Relatório de funcionários
     */
    public function employees(Request $request)
    {
        $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'is_active' => 'nullable|boolean',
            'format' => 'nullable|in:html,pdf,csv',
        ]);

        $query = Employee::with('department');

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->is_active !== null) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $employees = $query->orderBy('name')->get();

        $format = $request->get('format', 'html');

        if ($format === 'pdf') {
            return $this->generateEmployeesPDF($employees);
        }

        if ($format === 'csv') {
            return $this->generateEmployeesCSV($employees);
        }

        return view('reports.employees', compact('employees'));
    }

    /**
     * Dashboard de métricas
     */
    public function dashboard(Request $request)
    {
        $period = $request->get('period', 'current_month');
        $startDate = $this->getPeriodStartDate($period);
        $endDate = now();

        // Métricas gerais
        $metrics = [
            'total_employees' => Employee::count(),
            'active_employees' => Employee::active()->count(),
            'total_salary' => PayrollRecord::whereBetween('period_start', [$startDate, $endDate])
                ->sum('net_salary'),
            'departments_count' => Department::count(),
            'recent_payrolls' => PayrollRecord::whereBetween('period_start', [$startDate, $endDate])
                ->count(),
        ];

        // Gráfico de distribuição por departamento
        $departmentDistribution = Employee::selectRaw('departments.name, COUNT(*) as count')
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->groupBy('departments.name')
            ->pluck('count', 'departments.name');

        // Gráfico de evolução salarial
        $salaryEvolution = PayrollRecord::selectRaw('DATE_FORMAT(period_start, "%Y-%m") as period, SUM(net_salary) as total')
            ->where('period_start', '>=', now()->subMonths(12))
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('total', 'period');

        return view('reports.dashboard', compact('metrics', 'departmentDistribution', 'salaryEvolution'));
    }

    /**
     * Gera PDF da folha de pagamento
     */
    protected function generatePayrollPDF($records, $totals, $startDate, $endDate)
    {
        $pdf = Pdf::loadView('reports.pdf.payroll', compact('records', 'totals', 'startDate', 'endDate'));

        return $pdf->download("folha-pagamento-{$startDate->format('Y-m')}.pdf");
    }

    /**
     * Gera CSV da folha de pagamento
     */
    protected function generatePayrollCSV($records, $startDate, $endDate)
    {
        $filename = "folha-pagamento-{$startDate->format('Y-m')}.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($records) {
            $file = fopen('php://output', 'w');

            // Cabeçalho
            fputcsv($file, [
                'Funcionário',
                'Departamento',
                'Período',
                'Salário Bruto',
                'Salário Líquido',
                'Data Processamento'
            ]);

            // Dados
            foreach ($records as $record) {
                fputcsv($file, [
                    $record->employee->name,
                    $record->employee->department->name ?? '',
                    $record->period_start->format('m/Y'),
                    number_format($record->gross_salary, 2, ',', '.'),
                    number_format($record->net_salary, 2, ',', '.'),
                    $record->processed_at?->format('d/m/Y H:i') ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Gera PDF dos funcionários
     */
    protected function generateEmployeesPDF($employees)
    {
        $pdf = Pdf::loadView('reports.pdf.employees', compact('employees'));

        return $pdf->download('funcionarios-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Gera CSV dos funcionários
     */
    protected function generateEmployeesCSV($employees)
    {
        $filename = 'funcionarios-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($employees) {
            $file = fopen('php://output', 'w');

            // Cabeçalho
            fputcsv($file, [
                'ID',
                'Nome',
                'Email',
                'Cargo',
                'Departamento',
                'Data Admissão',
                'Salário',
                'Status'
            ]);

            // Dados
            foreach ($employees as $employee) {
                fputcsv($file, [
                    $employee->employee_id,
                    $employee->name,
                    $employee->email,
                    $employee->job_title,
                    $employee->department->name ?? '',
                    $employee->hire_date->format('d/m/Y'),
                    $employee->salary ? number_format($employee->salary, 2, ',', '.') : '',
                    $employee->is_active ? 'Ativo' : 'Inativo',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Obtém data de início baseada no período
     */
    protected function getPeriodStartDate(string $period): Carbon
    {
        return match($period) {
            'current_month' => now()->startOfMonth(),
            'last_month' => now()->subMonth()->startOfMonth(),
            'current_year' => now()->startOfYear(),
            'last_year' => now()->subYear()->startOfYear(),
            default => now()->startOfMonth(),
        };
    }
}
