## Contexto rápido
Projeto: Portal Corporativo (Laravel 10+). Código em PHP 8.1+, MySQL. Integração principal: ERP via REST (OAuth2 client_credentials).

## Onde olhar primeiro
- `app/Services/ERPIntegrationService.php` — cliente HTTP para ERP, token em cache (`Cache::remember`), endpoints: `/api/v1/employees`, `/api/v1/departments`, `/api/v1/payroll`, `/api/v1/finance/payments`.
- `app/Jobs/SyncEmployeesFromERP.php` — job enfileirado (ShouldQueue) que chama `ERPIntegrationService::syncEmployees` e registra em `SyncLog`.
- `app/Console/Commands/SyncEmployeesFromERPCommand.php` — comando artisan que cria um `SyncLog` e despacha o job; aceita `--since` e `--force`.
- `app/Models/SyncLog.php` e `app/Models/Employee.php` — padrões de campos e scopes usados em buscas e para controlar execução.

## Arquitetura e fluxos importantes (resumo)
- Agendador/Timer -> Comando artisan -> cria `SyncLog` -> despacha Job -> `ERPIntegrationService` faz paginação e `upsert` dos registros locais (Employee/Department) -> Job atualiza `SyncLog` com resultado.
- Tokens do ERP são mantidos em cache por ~3500s via `Cache::remember`. Evite chamadas diretas ao endpoint de token sem cache.

## Convenções e padrões do projeto
- Mapeamento ERP->local: `employeeId` -> `employee_id` (use `Employee::updateOrCreate(['employee_id' => ...], $data)`);
- Departamentos: `Department::firstOrCreate(['department_id' => $id], [...])`;
- Logs de sincronização: use `SyncLog::create(...)` com `parameters` e atualize status para `running`, `completed`, `completed_with_errors` ou `failed`.
- Jobs: têm `public $timeout`, `public $tries` e `public $backoff` definidos; rethrow exceptions para marcar job como failed.

## Comandos úteis (dev/test)
- Instalar dependências: `composer install` e `npm install`.
- Configurar `.env` (DB, AZURE_AD_*, ERP_*). Veja `Readme.md` para exemplos.
- Migrations & seed: `php artisan migrate` e `php artisan db:seed`.
- Rodar servidor local: se estiver usando XAMPP/Apache, coloque a pasta no `htdocs` e acesse pelo VirtualHost configurado; alternativa de desenvolvimento: `php artisan serve` (padrão http://127.0.0.1:8000).
- Iniciar sincronização manual: `php artisan erp:sync-employees` (opções: `--since`, `--force`).
- Processar filas: `php artisan queue:work` (prod: use supervisor/process manager). Use `php artisan queue:failed` e `php artisan queue:retry` conforme necessário.

## Erros e pontos sensíveis
- Ao testar integrações, use variáveis `.env` com `ERP_BASE_URL` apontando para um mock se não tiver ERP real.
- A obtenção de token usa `client_credentials`; problemas de autenticação geralmente aparecem em `storage/logs/laravel.log` com o corpo da resposta.
- Atualizações de funcionários fazem `findEmployeeByErpId` para resolver manager_id; isso pode gerar N+1 se não for otimizado — considere pré-buscar por `employee_id` quando atualizar muitos registros.

## Exemplos práticos (copiar/colar)
- Disparar sincronização (developer):
```
php artisan erp:sync-employees --since="2025-10-01 00:00:00"
php artisan queue:work
```
- Criar log de sync manualmente (exemplo de seeder/test):
```
\App\Models\SyncLog::create([
  'type' => 'employee_sync',
  'status' => 'running',
  'started_at' => now(),
  'parameters' => ['since' => now()->subDay()->toISOString()],
]);
```

## Onde alterar integração com outro ERP
- Modifique `app/Services/ERPIntegrationService.php`: mantenha a mesma forma de retorno (array com `data` e `pagination`) e preserve `getAccessToken()` cache.

## Arquivos a referenciar durante mudanças
- `app/Services/ERPIntegrationService.php`
- `app/Jobs/SyncEmployeesFromERP.php`
- `app/Console/Commands/SyncEmployeesFromERPCommand.php`
- `app/Models/Employee.php`, `app/Models/Department.php`, `app/Models/SyncLog.php`
- `Readme.md` (documentação de setup)

## Perguntas rápidas para o revisor
- Preferem que eu inclua exemplos de VirtualHost (Apache) para desenvolvimento local?
- Desejam instruções de como mockar o ERP (ex.: json-server ou ngrok) aqui no arquivo?
