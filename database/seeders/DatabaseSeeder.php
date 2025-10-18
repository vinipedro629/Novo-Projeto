<?php
// database/seeders/DatabaseSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Cria departamentos
        $ti = Department::create([
            'department_id' => 'D001',
            'name' => 'Tecnologia da Informação',
            'erp_code' => 'TI',
            'description' => 'Departamento de TI',
        ]);

        $rh = Department::create([
            'department_id' => 'D002',
            'name' => 'Recursos Humanos',
            'erp_code' => 'RH',
            'description' => 'Departamento de Recursos Humanos',
        ]);

        $financeiro = Department::create([
            'department_id' => 'D003',
            'name' => 'Financeiro',
            'erp_code' => 'FIN',
            'description' => 'Departamento Financeiro',
        ]);

        // Cria funcionários
        $managerTI = Employee::create([
            'employee_id' => 'E001',
            'name' => 'João Silva',
            'cpf' => '123.456.789-00',
            'email' => 'joao.silva@empresa.com',
            'phone' => '+5511999999999',
            'birth_date' => '1985-05-15',
            'job_title' => 'Gerente de TI',
            'department_id' => $ti->id,
            'hire_date' => '2020-01-15',
            'salary' => 8500.00,
            'is_active' => true,
        ]);

        $employeeTI = Employee::create([
            'employee_id' => 'E002',
            'name' => 'Maria Santos',
            'cpf' => '987.654.321-00',
            'email' => 'maria.santos@empresa.com',
            'phone' => '+5511888888888',
            'birth_date' => '1990-08-20',
            'job_title' => 'Analista de Sistemas',
            'department_id' => $ti->id,
            'manager_id' => $managerTI->id,
            'hire_date' => '2021-03-10',
            'salary' => 4500.00,
            'is_active' => true,
        ]);

        $rhManager = Employee::create([
            'employee_id' => 'E003',
            'name' => 'Ana Costa',
            'cpf' => '456.789.123-00',
            'email' => 'ana.costa@empresa.com',
            'phone' => '+5511777777777',
            'birth_date' => '1982-12-10',
            'job_title' => 'Gerente de RH',
            'department_id' => $rh->id,
            'hire_date' => '2019-06-01',
            'salary' => 7500.00,
            'is_active' => true,
        ]);

        // Atualiza manager do departamento de TI
        $ti->update(['manager_id' => $managerTI->id]);

        // Cria usuários
        $adminUser = User::create([
            'name' => 'Administrador do Sistema',
            'email' => 'admin@empresa.com',
            'employee_id' => 'E001',
            'azure_id' => 'azure-admin-id',
            'department_id' => $ti->id,
            'manager_id' => $managerTI->id,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $managerUser = User::create([
            'name' => 'João Silva',
            'email' => 'joao.silva@empresa.com',
            'employee_id' => 'E001',
            'azure_id' => 'azure-joao-id',
            'department_id' => $ti->id,
            'manager_id' => $managerTI->id,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $employeeUser = User::create([
            'name' => 'Maria Santos',
            'email' => 'maria.santos@empresa.com',
            'employee_id' => 'E002',
            'azure_id' => 'azure-maria-id',
            'department_id' => $ti->id,
            'manager_id' => $managerTI->id,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $rhUser = User::create([
            'name' => 'Ana Costa',
            'email' => 'ana.costa@empresa.com',
            'employee_id' => 'E003',
            'azure_id' => 'azure-ana-id',
            'department_id' => $rh->id,
            'manager_id' => $rhManager->id,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        // Atribui roles
        $adminUser->assignRole('admin');
        $managerUser->assignRole('manager');
        $employeeUser->assignRole('employee');
        $rhUser->assignRole('rh');

        // Cria alguns registros de folha de pagamento
        \App\Models\PayrollRecord::create([
            'employee_id' => $managerTI->id,
            'period_start' => now()->startOfMonth()->subMonth(),
            'period_end' => now()->startOfMonth()->subMonth()->endOfMonth(),
            'gross_salary' => 8500.00,
            'net_salary' => 6800.00,
            'details' => [
                'base_salary' => 8500.00,
                'overtime' => 200.00,
                'bonuses' => 300.00,
                'deductions' => [
                    'inss' => 935.00,
                    'irrf' => 1065.00,
                    'health_insurance' => 200.00,
                ],
            ],
        ]);

        \App\Models\PayrollRecord::create([
            'employee_id' => $employeeTI->id,
            'period_start' => now()->startOfMonth()->subMonth(),
            'period_end' => now()->startOfMonth()->subMonth()->endOfMonth(),
            'gross_salary' => 4500.00,
            'net_salary' => 3800.00,
            'details' => [
                'base_salary' => 4500.00,
                'deductions' => [
                    'inss' => 495.00,
                    'irrf' => 205.00,
                ],
            ],
        ]);

        $this->command->info('Dados iniciais criados com sucesso!');
    }
}
