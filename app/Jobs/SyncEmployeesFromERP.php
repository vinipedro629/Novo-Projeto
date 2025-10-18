<?php
// app/Jobs/SyncEmployeesFromERP.php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ERPIntegrationService;
use App\Models\SyncLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncEmployeesFromERP implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos
    public $tries = 3;
    public $backoff = [10, 30, 60]; // Backoff exponencial

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected ?Carbon $since = null
    ) {
        $this->since = $since ?? now()->subHours(1);
    }

    /**
     * Execute the job.
     */
    public function handle(ERPIntegrationService $erpService): void
    {
        Log::info('Iniciando sincronização de funcionários do ERP', [
            'since' => $this->since->toISOString(),
        ]);

        $syncLog = SyncLog::create([
            'type' => 'employee_sync',
            'status' => 'running',
            'started_at' => now(),
            'parameters' => [
                'since' => $this->since->toISOString(),
            ],
        ]);

        try {
            $result = $erpService->syncEmployees($this->since);

            $syncLog->update([
                'status' => $result['errors'] > 0 ? 'completed_with_errors' : 'completed',
                'completed_at' => now(),
                'records_processed' => $result['success'],
                'records_failed' => $result['errors'],
                'error_details' => $result['errors_details'],
                'metadata' => $result,
            ]);

            Log::info('Sincronização de funcionários concluída', [
                'success' => $result['success'],
                'errors' => $result['errors'],
            ]);

            // Se houver erros, pode notificar administradores
            if ($result['errors'] > 0) {
                $this->notifyAdminOfErrors($result['errors_details']);
            }

        } catch (\Exception $e) {
            $syncLog->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_details' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ]);

            Log::error('Erro na sincronização de funcionários', [
                'error' => $e->getMessage(),
                'since' => $this->since->toISOString(),
            ]);

            throw $e; // Re-throw para que o job seja marcado como falhado
        }
    }

    /**
     * Notifica administradores sobre erros de sincronização
     */
    protected function notifyAdminOfErrors(array $errors): void
    {
        // Implementar notificação por email ou outro canal
        Log::warning('Erros encontrados na sincronização de funcionários', [
            'errors_count' => count($errors),
            'errors' => array_slice($errors, 0, 10), // Log apenas os primeiros 10 erros
        ]);

        // TODO: Implementar envio de email para administradores
        // Mail::to('admin@empresa.com')->send(new SyncErrorNotification($errors));
    }
}
