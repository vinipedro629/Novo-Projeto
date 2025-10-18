<?php
// app/Models/Department.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id', // Chave externa do ERP
        'name',
        'erp_code',
        'manager_id',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'entity');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
