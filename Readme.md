# Portal Corporativo - Integração com ERP

Portal corporativo integrado com sistema ERP, desenvolvido em PHP com Laravel e MySQL. Sistema completo para gestão de funcionários, folhas de pagamento, solicitações de férias e integração com ERP via REST API.

## 🚀 Funcionalidades Principais

- **Dashboard personalizado** com KPIs e notificações
- **Gestão de funcionários** com sincronização automática do ERP
- **Consulta de contracheques** e histórico de pagamentos
- **Solicitações de férias** com workflow de aprovação
- **Integração REST** com ERP (SAP, Oracle, Dynamics, etc.)
- **Autenticação SSO** com Azure Active Directory
- **Sistema de roles e permissões** (RBAC)
- **Auditoria completa** de todas as operações
- **Jobs assíncronos** para sincronização de dados
- **Interface responsiva** e moderna

## 📋 Pré-requisitos

- PHP 8.1 ou superior
- Composer
- MySQL 5.7 ou superior
- Node.js e npm (para assets)
- Servidor web (Apache/Nginx)

## 🛠️ Instalação

1. **Clone o projeto:**
   ```bash
   git clone <repository-url>
   cd portal-corporativo
   ```

2. **Instale as dependências:**
   ```bash
   composer install
   npm install
   ```

3. **Configure o ambiente:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure o banco de dados:**
   - Crie um banco MySQL
   - Configure as credenciais no arquivo `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=portal_corporativo
   DB_USERNAME=seu_usuario
   DB_PASSWORD=sua_senha
   ```

5. **Execute as migrations:**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Configure o SSO Azure AD:**
   - Registre a aplicação no Azure AD
   - Configure as credenciais no `.env`:
   ```env
   AZURE_AD_CLIENT_ID=seu_client_id
   AZURE_AD_CLIENT_SECRET=seu_client_secret
   AZURE_AD_TENANT_ID=seu_tenant_id.onmicrosoft.com
   ```

7. **Configure a integração com ERP:**
   ```env
   ERP_BASE_URL=https://api.seu-erp.com
   ERP_CLIENT_ID=seu_erp_client_id
   ERP_CLIENT_SECRET=seu_erp_client_secret
   ERP_API_KEY=sua_api_key
   ```

8. **Compile os assets:**
   ```bash
   npm run build
   ```

9. **Inicie o servidor:**
   ```bash
   php artisan serve
   ```

## 🔧 Comandos Úteis

### Sincronização com ERP

```bash
# Sincronizar funcionários
php artisan erp:sync-employees

# Sincronizar funcionários desde data específica
php artisan erp:sync-employees --since="2024-01-01 00:00:00"

# Forçar sincronização mesmo com jobs em execução
php artisan erp:sync-employees --force
```

### Jobs e Filas

```bash
# Processar fila de jobs
php artisan queue:work

# Monitorar status da fila
php artisan queue:monitor

# Limpar jobs falhados
php artisan queue:clear
```

### Cache e Performance

```bash
# Limpar cache da aplicação
php artisan cache:clear

# Limpar cache de configuração
php artisan config:clear

# Otimizar aplicação
php artisan optimize
```

## 🏗️ Arquitetura

### Módulos Principais

- **Portal_Core**: Autenticação, roles, navegação
- **Portal_Entities**: Modelos e entidades do sistema
- **Portal_RH**: Funcionalidades de recursos humanos
- **Portal_Finance**: Relatórios financeiros
- **Portal_Integrations**: Integração com ERP
- **Portal_Services**: Jobs e timers
- **Portal_Admin**: Administração do sistema

### Estrutura de Banco de Dados

- `employees` - Dados dos funcionários
- `departments` - Departamentos da empresa
- `payroll_records` - Registros de folha de pagamento
- `leave_requests` - Solicitações de férias
- `audit_logs` - Log de auditoria
- `sync_logs` - Controle de sincronizações

## 🔐 Segurança

- **SSO Azure AD** para autenticação
- **RBAC** com roles e permissões
- **Auditoria completa** de operações
- **Criptografia** de dados sensíveis
- **Rate limiting** em APIs
- **Validação server-side** de todos os inputs

## 🔄 Integração com ERP

### Endpoints Utilizados

```http
GET  /api/v1/employees?since={timestamp}&page={page}&pageSize={size}
GET  /api/v1/departments
GET  /api/v1/payroll?startDate={date}&endDate={date}
POST /api/v1/finance/payments
```

### Fluxo de Sincronização

1. **Timer** executa a cada 10 minutos
2. Busca funcionários alterados desde última sincronização
3. **Upsert** no banco local
4. Registra resultado no `sync_logs`
5. Em caso de erro, insere em fila de retry

## 📱 Interface do Usuário

### Perfis de Acesso

- **Funcionário**: Dashboard pessoal, contracheques, férias
- **Gestor**: Aprovação de férias, relatórios da equipe
- **RH**: Gestão completa de funcionários e processos
- **Admin**: Administração do sistema e configurações

### Principais Telas

- Dashboard com KPIs
- Perfil do funcionário
- Consulta de contracheques
- Solicitações de férias (criar/aprovar)
- Relatórios financeiros
- Administração do sistema

## 🚨 Monitoramento

### Logs Importantes

- `storage/logs/laravel.log` - Logs da aplicação
- Tabela `audit_logs` - Auditoria de operações
- Tabela `sync_logs` - Controle de sincronizações

### Métricas

- Tempo de resposta das integrações
- Taxa de sucesso das sincronizações
- Uso de recursos por usuário
- Performance das consultas

## 🔧 Personalização

### Adicionar Novos Tipos de Licença

```php
// Em LeaveRequest model
const TYPE_MATERNITY = 'maternity';
const TYPE_PATERNITY = 'paternity';
```

### Configurar Novos Roles

```php
// Em DatabaseSeeder
$user->assignRole('custom_role');
```

### Integração com Outro ERP

1. Modificar `ERPIntegrationService`
2. Implementar novos endpoints
3. Atualizar mapeamentos DTO
4. Testar sincronização

## 📞 Suporte

Para dúvidas ou problemas:

1. Verifique os logs em `storage/logs/`
2. Consulte a documentação do Laravel
3. Abra um issue no repositório
4. Entre em contato com a equipe de desenvolvimento

## 📄 Licença

Este projeto está sob a licença MIT.