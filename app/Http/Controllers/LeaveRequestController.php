<?php
// app/Http/Controllers/LeaveRequestController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\LeaveRequest;
use App\Models\Employee;
use App\Models\AuditLog;
use Carbon\Carbon;

class LeaveRequestController extends Controller
{
    /**
     * Display a listing of leave requests.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        $query = LeaveRequest::with(['employee' => function($query) {
            $query->select('id', 'name', 'department_id')->with('department:id,name');
        }]);

        // Se não for admin, filtra por subordinados ou próprias solicitações
        if (!$user->hasRole('admin')) {
            if ($user->hasRole('manager')) {
                // Gestor vê solicitações dos subordinados + próprias
                $query->where(function($q) use ($employee) {
                    $q->where('employee_id', $employee->id)
                      ->orWhereHas('employee', function($subQuery) use ($employee) {
                          $subQuery->where('manager_id', $employee->id);
                      });
                });
            } else {
                // Funcionário vê apenas próprias solicitações
                $query->where('employee_id', $employee->id);
            }
        }

        // Filtros
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('start_date_from')) {
            $query->where('start_date', '>=', $request->start_date_from);
        }

        if ($request->filled('start_date_to')) {
            $query->where('start_date', '<=', $request->start_date_to);
        }

        $leaveRequests = $query->orderBy('created_at', 'desc')->paginate(15);

        if ($request->expectsJson()) {
            return response()->json($leaveRequests);
        }

        return view('leave-requests.index', compact('leaveRequests'));
    }

    /**
     * Show the form for creating a new leave request.
     */
    public function create()
    {
        $employee = Auth::user()->employee;

        return view('leave-requests.create', compact('employee'));
    }

    /**
     * Store a newly created leave request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:' . implode(',', [LeaveRequest::TYPE_VACATION, LeaveRequest::TYPE_SICK, LeaveRequest::TYPE_PERSONAL, LeaveRequest::TYPE_MATERNITY]),
            'reason' => 'required|string|max:1000',
        ]);

        $user = Auth::user();
        $employee = $user->employee;

        // Verifica se já existe solicitação pendente para o período
        $existingRequest = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->where(function($query) use ($request) {
                $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                      ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                      ->orWhere(function($q) use ($request) {
                          $q->where('start_date', '<=', $request->start_date)
                            ->where('end_date', '>=', $request->end_date);
                      });
            })
            ->first();

        if ($existingRequest) {
            return back()->withErrors(['period' => 'Já existe uma solicitação pendente para este período.']);
        }

        DB::transaction(function() use ($request, $employee) {
            $leaveRequest = LeaveRequest::create([
                'employee_id' => $employee->id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'type' => $request->type,
                'reason' => $request->reason,
            ]);

            // Log de auditoria
            AuditLog::log(
                AuditLog::OPERATION_CREATE,
                $leaveRequest,
                Auth::user()
            );
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Solicitação criada com sucesso!']);
        }

        return redirect()->route('leave-requests.index')->with('success', 'Solicitação de férias criada com sucesso!');
    }

    /**
     * Display the specified leave request.
     */
    public function show(LeaveRequest $leaveRequest)
    {
        $this->authorize('view', $leaveRequest);

        $leaveRequest->load(['employee.department', 'approver']);

        if (request()->expectsJson()) {
            return response()->json($leaveRequest);
        }

        return view('leave-requests.show', compact('leaveRequest'));
    }

    /**
     * Show the form for editing the specified leave request.
     */
    public function edit(LeaveRequest $leaveRequest)
    {
        $this->authorize('update', $leaveRequest);

        return view('leave-requests.edit', compact('leaveRequest'));
    }

    /**
     * Update the specified leave request.
     */
    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorize('update', $leaveRequest);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:' . implode(',', [LeaveRequest::TYPE_VACATION, LeaveRequest::TYPE_SICK, LeaveRequest::TYPE_PERSONAL, LeaveRequest::TYPE_MATERNITY]),
            'reason' => 'required|string|max:1000',
            'comments' => 'nullable|string|max:500',
        ]);

        $oldValues = $leaveRequest->toArray();

        DB::transaction(function() use ($request, $leaveRequest) {
            $leaveRequest->update([
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'type' => $request->type,
                'reason' => $request->reason,
                'comments' => $request->comments,
            ]);

            // Log de auditoria
            AuditLog::log(
                AuditLog::OPERATION_UPDATE,
                $leaveRequest,
                Auth::user(),
                $oldValues
            );
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Solicitação atualizada com sucesso!']);
        }

        return redirect()->route('leave-requests.show', $leaveRequest)->with('success', 'Solicitação atualizada com sucesso!');
    }

    /**
     * Approve the specified leave request.
     */
    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorize('approve', $leaveRequest);

        $request->validate([
            'comments' => 'nullable|string|max:500',
        ]);

        if ($leaveRequest->isApproved()) {
            return response()->json(['message' => 'Solicitação já foi aprovada.'], 400);
        }

        DB::transaction(function() use ($request, $leaveRequest) {
            $oldValues = $leaveRequest->toArray();

            $leaveRequest->update([
                'status' => LeaveRequest::STATUS_APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'comments' => $request->comments,
            ]);

            // Log de auditoria
            AuditLog::log(
                AuditLog::OPERATION_UPDATE,
                $leaveRequest,
                Auth::user(),
                $oldValues
            );
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Solicitação aprovada com sucesso!']);
        }

        return redirect()->back()->with('success', 'Solicitação aprovada com sucesso!');
    }

    /**
     * Reject the specified leave request.
     */
    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorize('approve', $leaveRequest);

        $request->validate([
            'comments' => 'required|string|max:500',
        ]);

        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            return response()->json(['message' => 'Apenas solicitações pendentes podem ser rejeitadas.'], 400);
        }

        DB::transaction(function() use ($request, $leaveRequest) {
            $oldValues = $leaveRequest->toArray();

            $leaveRequest->update([
                'status' => LeaveRequest::STATUS_REJECTED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'comments' => $request->comments,
            ]);

            // Log de auditoria
            AuditLog::log(
                AuditLog::OPERATION_UPDATE,
                $leaveRequest,
                Auth::user(),
                $oldValues
            );
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Solicitação rejeitada.']);
        }

        return redirect()->back()->with('success', 'Solicitação rejeitada.');
    }

    /**
     * Cancel the specified leave request.
     */
    public function cancel(LeaveRequest $leaveRequest)
    {
        $this->authorize('update', $leaveRequest);

        if (!in_array($leaveRequest->status, [LeaveRequest::STATUS_PENDING, LeaveRequest::STATUS_APPROVED])) {
            return response()->json(['message' => 'Apenas solicitações pendentes ou aprovadas podem ser canceladas.'], 400);
        }

        if ($leaveRequest->start_date < now()) {
            return response()->json(['message' => 'Não é possível cancelar solicitações que já iniciaram.'], 400);
        }

        DB::transaction(function() use ($leaveRequest) {
            $oldValues = $leaveRequest->toArray();

            $leaveRequest->update([
                'status' => LeaveRequest::STATUS_CANCELLED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // Log de auditoria
            AuditLog::log(
                AuditLog::OPERATION_UPDATE,
                $leaveRequest,
                Auth::user(),
                $oldValues
            );
        });

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Solicitação cancelada com sucesso!']);
        }

        return redirect()->back()->with('success', 'Solicitação cancelada com sucesso!');
    }
}
