<?php
// app/Models/AuditLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'operation',
        'user_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    const OPERATION_CREATE = 'create';
    const OPERATION_UPDATE = 'update';
    const OPERATION_DELETE = 'delete';
    const OPERATION_LOGIN = 'login';
    const OPERATION_LOGOUT = 'logout';
    const OPERATION_VIEW = 'view';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function entity()
    {
        return $this->morphTo();
    }

    // Scope para operações específicas
    public function scopeWithOperation($query, $operation)
    {
        return $query->where('operation', $operation);
    }

    // Scope para entidade específica
    public function scopeForEntity($query, $entityType, $entityId = null)
    {
        $query->where('entity_type', $entityType);
        if ($entityId) {
            $query->where('entity_id', $entityId);
        }
        return $query;
    }

    // Scope para período específico
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Scope para usuário específico
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Helper para criar log de auditoria
    public static function log($operation, $entity, $user = null, $oldValues = null, $newValues = null)
    {
        return static::create([
            'entity_type' => get_class($entity),
            'entity_id' => $entity->getKey(),
            'operation' => $operation,
            'user_id' => $user ? $user->id : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
