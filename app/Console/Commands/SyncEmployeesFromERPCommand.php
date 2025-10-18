<?php
// app/Console/Commands/SyncEmployeesFromERPCommand.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncEmployeesFromERP;
use App\Models\SyncLog;
use Carbon\Carbon;

class SyncEmployeesFromERPCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:sync-employees
                            {--since= : Data inicial para sincronização (formato: Y-m-d H:i:s)}
                            {--force : Força execução mesmo se já estiver rodando}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza funcionários do ERP com o portal';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $since = $this->option('since')
            ? Carbon::parse($this->option('since'))
            : now()->subHours(1);

        // Verifica se já há sincronização em andamento
        $runningSync = SyncLog::where('type', 'employee_sync')
            ->where('status', 'running')
            ->first();

        if ($runningSync && !$this->option('force')) {
            $this->error('Já há uma sincronização de funcionários em andamento.');
            $this->info('Use --force para executar mesmo assim.');
            return Command::FAILURE;
        }

        $this->info("Iniciando sincronização de funcionários desde: {$since->toISOString()}");

        // Cria log de sincronização
        $syncLog = SyncLog::create([
            'type' => 'employee_sync',
            'status' => 'running',
            'started_at' => now(),
            'parameters' => [
                'since' => $since->toISOString(),
                'forced' => $this->option('force'),
            ],
        ]);

        try {
            // Executa o job de sincronização
            SyncEmployeesFromERP::dispatch($since);

            $this->info('Sincronização iniciada com sucesso!');
            $this->info("ID do log: {$syncLog->id}");
            $this->info('Use o comando "php artisan queue:work" para processar o job.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $syncLog->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_details' => [
                    'message' => $e->getMessage(),
                ],
            ]);

            $this->error('Erro ao iniciar sincronização: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
