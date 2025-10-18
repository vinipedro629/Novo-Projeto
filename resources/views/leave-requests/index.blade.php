<!-- resources/views/leave-requests/index.blade.php -->
@extends('layouts.app')

@section('title', 'Solicitações de Férias')

@section('content')
<div class="container-fluid py-4">
    <!-- Cabeçalho -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Solicitações de Férias</h1>
                    <p class="text-muted">Gerencie suas solicitações de férias e aprove as de sua equipe</p>
                </div>
                <div class="btn-toolbar">
                    @can('create', App\Models\LeaveRequest::class)
                        <a href="{{ route('leave-requests.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Nova Solicitação
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">Todos</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendente</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Aprovado</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejeitado</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="type" class="form-label">Tipo</label>
                            <select name="type" id="type" class="form-select">
                                <option value="">Todos</option>
                                <option value="vacation" {{ request('type') == 'vacation' ? 'selected' : '' }}>Férias</option>
                                <option value="sick" {{ request('type') == 'sick' ? 'selected' : '' }}>Doença</option>
                                <option value="personal" {{ request('type') == 'personal' ? 'selected' : '' }}>Pessoal</option>
                                <option value="maternity" {{ request('type') == 'maternity' ? 'selected' : '' }}>Maternidade</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="start_date_from" class="form-label">Data Inicial</label>
                            <input type="date" name="start_date_from" id="start_date_from" class="form-control" value="{{ request('start_date_from') }}">
                        </div>

                        <div class="col-md-3">
                            <label for="start_date_to" class="form-label">Data Final</label>
                            <input type="date" name="start_date_to" id="start_date_to" class="form-control" value="{{ request('start_date_to') }}">
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-2"></i>Filtrar
                            </button>
                            <a href="{{ route('leave-requests.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Limpar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Solicitações -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    @if($leaveRequests->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Funcionário</th>
                                        <th>Tipo</th>
                                        <th>Período</th>
                                        <th>Duração</th>
                                        <th>Status</th>
                                        <th>Solicitado em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($leaveRequests as $request)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-primary bg-opacity-10 p-2 rounded">
                                                            <i class="fas fa-user text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <div class="fw-bold">{{ $request->employee->name }}</div>
                                                        <small class="text-muted">{{ $request->employee->job_title }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $this->getTypeBadgeClass($request->type) }}">
                                                    {{ ucfirst($request->type) }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ \Carbon\Carbon::parse($request->start_date)->format('d/m/Y') }}
                                                -
                                                {{ \Carbon\Carbon::parse($request->end_date)->format('d/m/Y') }}
                                            </td>
                                            <td>{{ $request->duration }} dias</td>
                                            <td>
                                                @switch($request->status)
                                                    @case('pending')
                                                        <span class="badge bg-warning">Pendente</span>
                                                        @break
                                                    @case('approved')
                                                        <span class="badge bg-success">Aprovado</span>
                                                        @break
                                                    @case('rejected')
                                                        <span class="badge bg-danger">Rejeitado</span>
                                                        @break
                                                    @case('cancelled')
                                                        <span class="badge bg-secondary">Cancelado</span>
                                                        @break
                                                @endswitch
                                            </td>
                                            <td>{{ $request->created_at->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('leave-requests.show', $request) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>

                                                    @can('update', $request)
                                                        @if($request->status === 'pending' || $request->status === 'approved')
                                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="cancelRequest({{ $request->id }})">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        @endif
                                                    @endcan

                                                    @can('approve', $request)
                                                        @if($request->status === 'pending')
                                                            <button type="button" class="btn btn-sm btn-success" onclick="approveRequest({{ $request->id }})">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-danger" onclick="rejectRequest({{ $request->id }})">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        @endif
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginação -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $leaveRequests->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Nenhuma solicitação encontrada</h5>
                            <p class="text-muted">As solicitações de férias aparecerão aqui.</p>
                            @can('create', App\Models\LeaveRequest::class)
                                <a href="{{ route('leave-requests.create') }}" class="btn btn-primary mt-3">
                                    <i class="fas fa-plus me-2"></i>Criar Primeira Solicitação
                                </a>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Aprovação -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Aprovar Solicitação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Confirma a aprovação desta solicitação de férias?</p>
                    <div class="mb-3">
                        <label for="approveComments" class="form-label">Observações (opcional)</label>
                        <textarea class="form-control" id="approveComments" name="comments" rows="3" placeholder="Adicione comentários sobre a aprovação..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Aprovar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Rejeição -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rejeitar Solicitação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Confirma a rejeição desta solicitação de férias?</p>
                    <div class="mb-3">
                        <label for="rejectComments" class="form-label">Motivo da rejeição *</label>
                        <textarea class="form-control" id="rejectComments" name="comments" rows="3" placeholder="Informe o motivo da rejeição..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Rejeitar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentRequestId = null;

function approveRequest(requestId) {
    currentRequestId = requestId;
    document.getElementById('approveForm').action = `/leave-requests/${requestId}/approve`;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}

function rejectRequest(requestId) {
    currentRequestId = requestId;
    document.getElementById('rejectForm').action = `/leave-requests/${requestId}/reject`;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function cancelRequest(requestId) {
    if (confirm('Tem certeza que deseja cancelar esta solicitação?')) {
        fetch(`/leave-requests/${requestId}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        }).then(response => {
            if (response.ok) {
                location.reload();
            } else {
                alert('Erro ao cancelar solicitação');
            }
        });
    }
}

// Função auxiliar para determinar classe do badge
function getTypeBadgeClass(type) {
    const classes = {
        'vacation': 'primary',
        'sick': 'warning',
        'personal': 'info',
        'maternity': 'success'
    };
    return classes[type] || 'secondary';
}
</script>
@endsection
