<?php
// app/Models/LeaveRequest.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'type',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'comments',
        'erp_request_id'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    const TYPE_VACATION = 'vacation';
    const TYPE_SICK = 'sick';
    const TYPE_PERSONAL = 'personal';
    const TYPE_MATERNITY = 'maternity';

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'entity');
    }

    // Scope para status específico
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope para tipo específico
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Scope para período específico
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }

    // Helper para calcular duração em dias
    public function getDurationAttribute()
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    // Helper para verificar se está pendente
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    // Helper para verificar se foi aprovada
    public function isApproved()
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
