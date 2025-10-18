<?php
// app/Http/Controllers/DashboardController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Models\LeaveRequest;
use App\Models\Department;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        // Verifica se o usuário tem funcionário associado
        if (!$employee) {
            return redirect()->route('profile.edit')->with('error', 'Perfil de funcionário não encontrado. Entre em contato com o RH.');
        }

        // Dados do dashboard
        $dashboardData = [
            'employee' => $employee,
            'recent_payrolls' => $this->getRecentPayrolls($employee),
            'pending_requests' => $this->getPendingRequests($employee),
            'upcoming_events' => $this->getUpcomingEvents($employee),
            'kpis' => $this->getKPIs($employee),
            'notifications' => $this->getNotifications($employee),
        ];

        if ($request->expectsJson()) {
            return response()->json($dashboardData);
        }

        return view('dashboard.index', $dashboardData);
    }

    /**
     * Obtém registros recentes de folha de pagamento
     */
    protected function getRecentPayrolls(Employee $employee)
    {
        return PayrollRecord::where('employee_id', $employee->id)
            ->orderBy('period_end', 'desc')
            ->limit(6)
            ->get();
    }

    /**
     * Obtém solicitações pendentes
     */
    protected function getPendingRequests(Employee $employee)
    {
        $pendingRequests = [];

        // Solicitações de férias pendentes de aprovação (se gestor)
        if ($employee->subordinates()->exists()) {
            $pendingRequests = LeaveRequest::whereHas('employee', function($query) use ($employee) {
                $query->where('manager_id', $employee->id);
            })
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->with(['employee' => function($query) {
                $query->select('id', 'name');
            }])
            ->limit(5)
            ->get();
        }

        // Próprias solicitações pendentes
        $ownPendingRequests = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->limit(3)
            ->get();

        return [
            'to_approve' => $pendingRequests,
            'own_pending' => $ownPendingRequests,
        ];
    }

    /**
     * Obtém eventos futuros (férias, feriados, etc.)
     */
    protected function getUpcomingEvents(Employee $employee)
    {
        $events = [];

        // Próximas férias aprovadas
        $upcomingLeaves = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->limit(3)
            ->get();

        foreach ($upcomingLeaves as $leave) {
            $events[] = [
                'type' => 'leave',
                'title' => ucfirst($leave->type) . ' - ' . $leave->getDurationAttribute() . ' dias',
                'start_date' => $leave->start_date,
                'end_date' => $leave->end_date,
                'status' => 'confirmed',
            ];
        }

        return $events;
    }

    /**
     * Obtém KPIs do funcionário
     */
    protected function getKPIs(Employee $employee)
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Salário atual (última folha)
        $latestPayroll = PayrollRecord::where('employee_id', $employee->id)
            ->orderBy('period_end', 'desc')
            ->first();

        // Dias de férias disponíveis (simplificado)
        $usedVacationDays = LeaveRequest::where('employee_id', $employee->id)
            ->where('type', LeaveRequest::TYPE_VACATION)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereYear('start_date', $currentYear)
            ->sum(\DB::raw('DATEDIFF(end_date, start_date) + 1'));

        // Dias trabalhados no mês atual
        $workedDays = now()->diffInDays(now()->startOfMonth()) + 1;

        return [
            'current_salary' => $latestPayroll ? $latestPayroll->net_salary : 0,
            'used_vacation_days' => $usedVacationDays,
            'available_vacation_days' => max(0, 30 - $usedVacationDays), // 30 dias por ano (simplificado)
            'worked_days_current_month' => $workedDays,
            'department_size' => $employee->department ? $employee->department->employees()->active()->count() : 0,
        ];
    }

    /**
     * Obtém notificações do sistema
     */
    protected function getNotifications(Employee $employee)
    {
        $notifications = [];

        // Verifica se há solicitações pendentes de aprovação
        $pendingApprovals = LeaveRequest::whereHas('employee', function($query) use ($employee) {
            $query->where('manager_id', $employee->id);
        })->where('status', LeaveRequest::STATUS_PENDING)->count();

        if ($pendingApprovals > 0) {
            $notifications[] = [
                'type' => 'warning',
                'message' => "Você tem {$pendingApprovals} solicitação(ões) de férias pendente(s) de aprovação.",
                'action_url' => route('leave-requests.index'),
                'action_text' => 'Ver solicitações',
            ];
        }

        // Verifica aniversário próximo
        $nextBirthday = Carbon::createFromDate($employee->birth_date->year, $employee->birth_date->month, $employee->birth_date->day);
        $nextBirthday->year = now()->year;

        if ($nextBirthday->isBetween(now(), now()->addDays(7))) {
            $daysUntilBirthday = now()->diffInDays($nextBirthday);
            $notifications[] = [
                'type' => 'info',
                'message' => "Seu aniversário é em {$daysUntilBirthday} dia(s)!",
                'icon' => 'birthday-cake',
            ];
        }

        // Verifica férias próximas
        $upcomingLeave = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->where('start_date', '>=', now())
            ->where('start_date', '<=', now()->addDays(7))
            ->first();

        if ($upcomingLeave) {
            $daysUntilLeave = now()->diffInDays($upcomingLeave->start_date);
            $notifications[] = [
                'type' => 'success',
                'message' => "Suas {$upcomingLeave->type} começam em {$daysUntilLeave} dia(s)!",
                'icon' => 'calendar-check',
            ];
        }

        return $notifications;
    }
}
