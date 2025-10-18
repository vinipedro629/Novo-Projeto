<?php
// app/Models/Employee.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id', // Chave externa do ERP
        'name',
        'cpf',
        'email',
        'phone',
        'birth_date',
        'job_title',
        'department_id',
        'manager_id',
        'hire_date',
        'salary',
        'is_active',
        'erp_updated_at'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
        'salary' => 'decimal:2',
        'is_active' => 'boolean',
        'erp_updated_at' => 'datetime',
    ];

    protected $hidden = [
        'cpf' // Campo sensível
    ];

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function payrollRecords()
    {
        return $this->hasMany(PayrollRecord::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'entity');
    }

    // Scope para funcionários ativos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope para buscar por período de atualização do ERP
    public function scopeUpdatedSince($query, $since)
    {
        return $query->where('erp_updated_at', '>=', $since);
    }
}
