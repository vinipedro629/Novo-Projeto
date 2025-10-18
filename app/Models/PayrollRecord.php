<?php
// app/Models/PayrollRecord.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'period_start',
        'period_end',
        'gross_salary',
        'net_salary',
        'details', // JSON com detalhes do contracheque
        'erp_payroll_id',
        'processed_at'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'gross_salary' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'details' => 'array',
        'processed_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'entity');
    }

    // Scope para período específico
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start', '>=', $startDate)
                    ->where('period_end', '<=', $endDate);
    }

    // Scope para ano/mês específico
    public function scopeForMonth($query, $year, $month)
    {
        $startDate = "{$year}-{$month}-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        return $query->where('period_start', '>=', $startDate)
                    ->where('period_end', '<=', $endDate);
    }
}
