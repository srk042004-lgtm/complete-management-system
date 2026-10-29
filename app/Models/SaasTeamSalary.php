<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaasTeamSalary extends Model
{
    protected $table = 'saas_team_salaries';

    protected $fillable = [
        'employee_name', 'designation', 'base_salary', 'bonus_allowances', 'deductions', 'net_salary', 'payment_month', 'payment_date', 'payment_method', 'status', 'notes'
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'bonus_allowances' => 'decimal:2',
        'deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'payment_date' => 'date',
    ];
}
