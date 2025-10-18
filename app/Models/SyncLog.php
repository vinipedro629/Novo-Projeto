<?php
// app/Models/SyncLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'status',
        'started_at',
        'completed_at',
        'records_processed',
        'records_failed',
        'parameters',
        'error_details',
        'metadata'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'parameters' => 'array',
        'error_details' => 'array',
        'metadata' => 'array',
    ];

    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_COMPLETED_WITH_ERRORS = 'completed_with_errors';
    const STATUS_FAILED = 'failed';

    const TYPE_EMPLOYEE_SYNC = 'employee_sync';
    const TYPE_PAYROLL_SYNC = 'payroll_sync';
    const TYPE_DEPARTMENT_SYNC = 'department_sync';

    /**
     * Scope para sincronizações em execução
     */
    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    /**
     * Scope para sincronizações concluídas hoje
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope para tipo específico
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para status específico
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Verifica se a sincronização está em execução
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Verifica se foi concluída com sucesso
     */
    public function isSuccessful(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_COMPLETED_WITH_ERRORS]);
    }

    /**
     * Verifica se falhou
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
