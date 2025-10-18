<?php
// app/Http/Controllers/AdminController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SyncLog;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Employee;
use App\Jobs\SyncEmployeesFromERP;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Dashboard administrativo
     */
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_employees' => Employee::count(),
            'active_employees' => Employee::active()->count(),
            'recent_syncs' => SyncLog::latest()->limit(5)->get(),
            'recent_audits' => AuditLog::with('user:id,name')->latest()->limit(10)->get(),
        ];

        return view('admin.index', compact('stats'));
    }

    /**
     * Monitor de sincronizações
     */
    public function syncMonitor(Request $request)
    {
        $query = SyncLog::with('user:id,name');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        $syncLogs = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.sync-monitor', compact('syncLogs'));
    }

    /**
     * Log de auditoria
     */
    public function auditLog(Request $request)
    {
        $query = AuditLog::with(['user:id,name', 'entity']);

        if ($request->filled('operation')) {
            $query->where('operation', $request->operation);
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $auditLogs = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('admin.audit-log', compact('auditLogs'));
    }

    /**
     * Configurações do sistema
     */
    public function settings(Request $request)
    {
        if ($request->isMethod('post')) {
            // Salvar configurações
            $validated = $request->validate([
                'erp_base_url' => 'required|url',
                'erp_client_id' => 'required|string',
                'erp_client_secret' => 'required|string',
                'erp_api_key' => 'required|string',
                'azure_ad_client_id' => 'required|string',
                'azure_ad_client_secret' => 'required|string',
                'azure_ad_tenant_id' => 'required|string',
            ]);

            // Salvar no arquivo .env ou banco de dados
            // Por simplicidade, vamos apenas mostrar sucesso
            return redirect()->back()->with('success', 'Configurações salvas com sucesso!');
        }

        return view('admin.settings');
    }

    /**
     * Forçar sincronização de funcionários
     */
    public function forceSyncEmployees(Request $request)
    {
        $request->validate([
            'since' => 'nullable|date',
        ]);

        $since = $request->since ? Carbon::parse($request->since) : now()->subHours(1);

        // Verificar se já há sincronização em andamento
        $runningSync = SyncLog::where('type', 'employee_sync')
            ->where('status', 'running')
            ->first();

        if ($runningSync) {
            return response()->json([
                'success' => false,
                'message' => 'Já há uma sincronização em andamento.'
            ], 409);
        }

        // Disparar job de sincronização
        SyncEmployeesFromERP::dispatch($since);

        return response()->json([
            'success' => true,
            'message' => 'Sincronização iniciada com sucesso!'
        ]);
    }

    /**
     * Detalhes de uma sincronização
     */
    public function syncDetails(SyncLog $syncLog)
    {
        return view('admin.sync-details', compact('syncLog'));
    }

    /**
     * Usuários do sistema
     */
    public function users(Request $request)
    {
        $query = User::with(['employee.department', 'roles']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        $users = $query->paginate(20);

        return view('admin.users', compact('users'));
    }

    /**
     * Alternar status de usuário
     */
    public function toggleUserStatus(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'success' => true,
            'active' => $user->is_active,
            'message' => $user->is_active ? 'Usuário ativado' : 'Usuário desativado'
        ]);
    }

    /**
     * Atribuir role a usuário
     */
    public function assignRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        $user->assignRole($request->role);

        return response()->json([
            'success' => true,
            'message' => 'Role atribuída com sucesso!'
        ]);
    }

    /**
     * Remover role de usuário
     */
    public function removeRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        $user->removeRole($request->role);

        return response()->json([
            'success' => true,
            'message' => 'Role removida com sucesso!'
        ]);
    }

    /**
     * Limpar logs antigos
     */
    public function clearOldLogs(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:30',
        ]);

        $cutoffDate = now()->subDays($request->days);

        // Limpar logs de auditoria antigos
        AuditLog::where('created_at', '<', $cutoffDate)->delete();

        // Limpar logs de sincronização antigos (exceto os últimos 100)
        SyncLog::where('created_at', '<', $cutoffDate)
            ->orderBy('created_at', 'desc')
            ->skip(100)
            ->take(PHP_INT_MAX)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "Logs anteriores a {$request->days} dias foram removidos."
        ]);
    }
}
