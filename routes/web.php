<?php
// routes/web.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\Auth\LoginController;

// Rotas de autenticação
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/auth/azure', [LoginController::class, 'redirectToAzure'])->name('auth.azure');
    Route::get('/auth/azure/callback', [LoginController::class, 'handleAzureCallback'])->name('auth.azure.callback');
});

// Rotas autenticadas
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Solicitações de férias
    Route::resource('leave-requests', LeaveRequestController::class);
    Route::post('leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
    Route::post('leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('leave-requests.reject');
    Route::post('leave-requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])->name('leave-requests.cancel');

    // Perfil do usuário
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// API Routes
Route::prefix('api')->group(function () {
    // Rotas públicas da API
    Route::post('/sync/employees', [ERPIntegrationController::class, 'syncEmployees'])->name('api.sync.employees');

    // Rotas autenticadas da API
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('api.dashboard');
        Route::apiResource('employees', EmployeeController::class);
        Route::apiResource('payroll-records', PayrollRecordController::class);
        Route::apiResource('leave-requests', LeaveRequestController::class);
    });
});

// Logout
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');
