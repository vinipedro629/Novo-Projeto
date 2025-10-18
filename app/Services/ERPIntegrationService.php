<?php
// app/Services/ERPIntegrationService.php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Employee;
use App\Models\Department;
use App\Models\PayrollRecord;
use Carbon\Carbon;

class ERPIntegrationService
{
    protected $baseUrl;
    protected $clientId;
    protected $clientSecret;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('erp.base_url');
        $this->clientId = config('erp.client_id');
        $this->clientSecret = config('erp.client_secret');
        $this->apiKey = config('erp.api_key');
    }

    /**
     * Busca funcionários atualizados desde uma data específica
     */
    public function getChangedEmployees(Carbon $since, int $page = 1, int $pageSize = 100): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'Content-Type' => 'application/json',
                    'X-API-Key' => $this->apiKey,
                ])
                ->get("{$this->baseUrl}/api/v1/employees", [
                    'since' => $since->toISOString(),
                    'page' => $page,
                    'pageSize' => $pageSize,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Erro ao buscar funcionários do ERP', [
                'status' => $response->status(),
                'body' => $response->body(),
                'since' => $since->toISOString(),
            ]);

            return ['data' => [], 'pagination' => null];
        } catch (\Exception $e) {
            Log::error('Erro de conexão com ERP ao buscar funcionários', [
                'error' => $e->getMessage(),
                'since' => $since->toISOString(),
            ]);

            return ['data' => [], 'pagination' => null];
        }
    }

    /**
     * Busca departamentos
     */
    public function getDepartments(): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'Content-Type' => 'application/json',
                    'X-API-Key' => $this->apiKey,
                ])
                ->get("{$this->baseUrl}/api/v1/departments");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Erro ao buscar departamentos do ERP', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Erro de conexão com ERP ao buscar departamentos', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Busca registros de folha de pagamento
     */
    public function getPayrollRecords(Carbon $startDate, Carbon $endDate, int $page = 1): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'Content-Type' => 'application/json',
                    'X-API-Key' => $this->apiKey,
                ])
                ->get("{$this->baseUrl}/api/v1/payroll", [
                    'startDate' => $startDate->toDateString(),
                    'endDate' => $endDate->toDateString(),
                    'page' => $page,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Erro ao buscar registros de folha do ERP', [
                'status' => $response->status(),
                'body' => $response->body(),
                'startDate' => $startDate->toDateString(),
                'endDate' => $endDate->toDateString(),
            ]);

            return ['data' => [], 'pagination' => null];
        } catch (\Exception $e) {
            Log::error('Erro de conexão com ERP ao buscar folha', [
                'error' => $e->getMessage(),
                'startDate' => $startDate->toDateString(),
                'endDate' => $endDate->toDateString(),
            ]);

            return ['data' => [], 'pagination' => null];
        }
    }

    /**
     * Envia lote de pagamentos para o ERP
     */
    public function sendPayments(array $payments): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'Content-Type' => 'application/json',
                    'X-API-Key' => $this->apiKey,
                ])
                ->post("{$this->baseUrl}/api/v1/finance/payments", [
                    'payments' => $payments,
                    'requestId' => uniqid('payment_', true),
                    'timestamp' => now()->toISOString(),
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Erro ao enviar pagamentos para ERP', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payments_count' => count($payments),
            ]);

            return ['success' => false, 'error' => 'Erro na comunicação com ERP'];
        } catch (\Exception $e) {
            Log::error('Erro de conexão com ERP ao enviar pagamentos', [
                'error' => $e->getMessage(),
                'payments_count' => count($payments),
            ]);

            return ['success' => false, 'error' => 'Erro de conexão com ERP'];
        }
    }

    /**
     * Obtém token de acesso do ERP (OAuth2)
     */
    protected function getAccessToken(): ?string
    {
        $cacheKey = 'erp_access_token';

        return Cache::remember($cacheKey, 3500, function() {
            try {
                $response = Http::timeout(10)
                    ->asForm()
                    ->post("{$this->baseUrl}/oauth/token", [
                        'grant_type' => 'client_credentials',
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['access_token'] ?? null;
                }

                Log::error('Erro ao obter token de acesso do ERP', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Erro de conexão ao obter token do ERP', [
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Sincroniza funcionários do ERP
     */
    public function syncEmployees(Carbon $since = null): array
    {
        $since = $since ?? now()->subDays(1);
        $page = 1;
        $allEmployees = [];
        $errors = [];

        do {
            $response = $this->getChangedEmployees($since, $page, 100);

            if (empty($response['data'])) {
                break;
            }

            foreach ($response['data'] as $employeeData) {
                try {
                    $this->upsertEmployee($employeeData);
                    $allEmployees[] = $employeeData['employeeId'];
                } catch (\Exception $e) {
                    $errors[] = [
                        'employee_id' => $employeeData['employeeId'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $page++;
        } while (!empty($response['data']) && ($response['pagination']['hasNext'] ?? false));

        return [
            'success' => count($allEmployees),
            'errors' => count($errors),
            'errors_details' => $errors,
        ];
    }

    /**
     * Insere ou atualiza funcionário
     */
    protected function upsertEmployee(array $data): Employee
    {
        // Mapeia dados do ERP para o modelo local
        $employeeData = [
            'employee_id' => $data['employeeId'],
            'name' => $data['fullName'],
            'cpf' => $data['cpf'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'birth_date' => Carbon::parse($data['birthDate'])->format('Y-m-d'),
            'job_title' => $data['jobTitle'] ?? null,
            'department_id' => $this->getOrCreateDepartment($data['department']),
            'manager_id' => isset($data['managerId']) ? $this->findEmployeeByErpId($data['managerId'])->id : null,
            'hire_date' => isset($data['hireDate']) ? Carbon::parse($data['hireDate'])->format('Y-m-d') : now()->format('Y-m-d'),
            'erp_updated_at' => Carbon::parse($data['updatedAt']),
        ];

        return Employee::updateOrCreate(
            ['employee_id' => $data['employeeId']],
            $employeeData
        );
    }

    /**
     * Busca ou cria departamento
     */
    protected function getOrCreateDepartment(array $departmentData): ?int
    {
        if (!$departmentData) {
            return null;
        }

        $department = Department::firstOrCreate(
            ['department_id' => $departmentData['id']],
            [
                'name' => $departmentData['name'],
                'erp_code' => $departmentData['erpCode'] ?? null,
            ]
        );

        return $department->id;
    }

    /**
     * Busca funcionário por ID do ERP
     */
    protected function findEmployeeByErpId(string $erpId): ?Employee
    {
        return Employee::where('employee_id', $erpId)->first();
    }
}
