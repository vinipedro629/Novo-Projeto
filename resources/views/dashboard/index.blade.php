<!-- resources/views/dashboard/index.blade.php -->
@extends('layouts.app')

@section('title', 'Dashboard - Portal Corporativo')

@section('content')
<div class="container-fluid py-4">
    <!-- Cabeçalho -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Dashboard</h1>
                    <p class="text-muted">Bem-vindo de volta, {{ $employee->name }}!</p>
                </div>
                <div class="btn-toolbar">
                    <button type="button" class="btn btn-outline-primary me-2" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt me-2"></i>Atualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="fas fa-money-bill-wave text-primary fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">Salário Líquido</h6>
                            <h4 class="mb-0 text-primary">R$ {{ number_format($kpis['current_salary'], 2, ',', '.') }}</h4>
                            <small class="text-muted">Última folha</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="fas fa-calendar-check text-success fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">Férias Disponíveis</h6>
                            <h4 class="mb-0 text-success">{{ $kpis['available_vacation_days'] }} dias</h4>
                            <small class="text-muted">{{ $kpis['used_vacation_days'] }} utilizados este ano</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="fas fa-clock text-info fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">Dias Trabalhados</h6>
                            <h4 class="mb-0 text-info">{{ $kpis['worked_days_current_month'] }} dias</h4>
                            <small class="text-muted">Este mês</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="fas fa-users text-warning fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">Equipe</h6>
                            <h4 class="mb-0 text-warning">{{ $kpis['department_size'] }}</h4>
                            <small class="text-muted">{{ $employee->department->name ?? 'Sem departamento' }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="row">
        <!-- Contracheques Recentes -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-receipt me-2 text-primary"></i>
                        Contracheques Recentes
                    </h5>
                </div>
                <div class="card-body">
                    @if($recent_payrolls->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Período</th>
                                        <th>Salário Bruto</th>
                                        <th>Salário Líquido</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recent_payrolls as $payroll)
                                        <tr>
                                            <td>
                                                {{ \Carbon\Carbon::parse($payroll->period_start)->format('m/Y') }}
                                            </td>
                                            <td>R$ {{ number_format($payroll->gross_salary, 2, ',', '.') }}</td>
                                            <td class="fw-bold">R$ {{ number_format($payroll->net_salary, 2, ',', '.') }}</td>
                                            <td>
                                                <span class="badge bg-success">Processado</span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewPayrollDetails({{ $payroll->id }})">
                                                    <i class="fas fa-eye"></i> Detalhes
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Nenhum contracheque encontrado</h5>
                            <p class="text-muted">Os contracheques aparecerão aqui após o processamento da folha.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Próximos Eventos -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2 text-primary"></i>
                        Próximos Eventos
                    </h5>
                </div>
                <div class="card-body">
                    @if(count($upcoming_events) > 0)
                        @foreach($upcoming_events as $event)
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0">
                                    <div class="bg-{{ $event['type'] == 'leave' ? 'success' : 'info' }} bg-opacity-10 p-2 rounded">
                                        <i class="fas fa-{{ $event['type'] == 'leave' ? 'plane' : 'birthday-cake' }} text-{{ $event['type'] == 'leave' ? 'success' : 'info' }}"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="fw-bold">{{ $event['title'] }}</div>
                                    <small class="text-muted">
                                        {{ \Carbon\Carbon::parse($event['start_date'])->format('d/m/Y') }}
                                        @if($event['end_date'] != $event['start_date'])
                                            - {{ \Carbon\Carbon::parse($event['end_date'])->format('d/m/Y') }}
                                        @endif
                                    </small>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-3">
                            <i class="fas fa-calendar-alt fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">Nenhum evento próximo</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Solicitações Pendentes -->
            @if($user->hasRole(['manager', 'rh', 'admin']))
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-clipboard-list me-2 text-warning"></i>
                            Aprovações Pendentes
                        </h5>
                    </div>
                    <div class="card-body">
                        @if(isset($pending_requests['to_approve']) && $pending_requests['to_approve']->count() > 0)
                            @foreach($pending_requests['to_approve'] as $request)
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <div class="bg-warning bg-opacity-10 p-2 rounded">
                                            <i class="fas fa-clock text-warning"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-bold">{{ $request->employee->name }}</div>
                                        <small class="text-muted">{{ $request->type }} - {{ \Carbon\Carbon::parse($request->start_date)->format('d/m') }}</small>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <a href="{{ route('leave-requests.show', $request) }}" class="btn btn-sm btn-outline-primary">
                                            Ver
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-3">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <p class="text-muted mb-0">Todas aprovações em dia!</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Notificações -->
    @if(count($notifications) > 0)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-bell me-2 text-info"></i>
                            Notificações
                        </h5>
                    </div>
                    <div class="card-body">
                        @foreach($notifications as $notification)
                            <div class="alert alert-{{ $notification['type'] }} alert-dismissible fade show" role="alert">
                                @if(isset($notification['icon']))
                                    <i class="fas fa-{{ $notification['icon'] }} me-2"></i>
                                @endif
                                {{ $notification['message'] }}
                                @if(isset($notification['action_url']))
                                    <a href="{{ $notification['action_url'] }}" class="alert-link">
                                        {{ $notification['action_text'] }}
                                    </a>
                                @endif
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
function refreshDashboard() {
    location.reload();
}

function viewPayrollDetails(payrollId) {
    // Implementar modal ou redirecionamento para detalhes do contracheque
    alert('Detalhes do contracheque - funcionalidade será implementada');
}
</script>
@endsection
