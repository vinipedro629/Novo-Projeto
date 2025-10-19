<#
  setup-dev.ps1
  Script de setup para desenvolvimento local (Windows PowerShell).

  O que ele faz (opções padrões):
  - Verifica se PHP, Composer, Node e npm estão instalados
  - Cria diretórios necessários (bootstrap/cache, storage, database)
  - Ajusta permissões locais com icacls (requer executar como Administrador)
  - Roda `composer install`
  - Roda `npm install` (e tenta instalar autoprefixer se necessário)
  - Gera APP_KEY se necessário
  - Roda migrations e seed (SQLite por padrão)
  - Roda `npm run build`

  Uso:
    Abra o PowerShell como Administrador (recomendado) e execute:
      .\setup-dev.ps1

  Observação: o script NÃO deve ser executado em produção. É para facilitar desenvolvimento local.
#>

Set-StrictMode -Version Latest
Write-Host "== Setup de desenvolvimento (Portal Corporativo) ==" -ForegroundColor Cyan

function Check-Command($name, $cmd) {
    if (-not (Get-Command $cmd -ErrorAction SilentlyContinue)) {
        Write-Host "ERRO: '$name' não encontrado. Instale antes de continuar." -ForegroundColor Red
        return $false
    }
    Write-Host "$name encontrado." -ForegroundColor Green
    return $true
}

$ok = $true
$ok = $ok -and (Check-Command 'PHP' 'php')
$ok = $ok -and (Check-Command 'Composer' 'composer')
$ok = $ok -and (Check-Command 'Node' 'node')
$ok = $ok -and (Check-Command 'NPM' 'npm')

if (-not $ok) { Write-Host "Instale as dependências faltantes e rode este script novamente." -ForegroundColor Yellow; exit 1 }

# Detecta se é administrador (necessário para icacls e alterar hosts se quiser)
function Test-IsAdmin {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

if (-not (Test-IsAdmin)) {
    Write-Host "Aviso: não está rodando como Administrador. Algumas operações (icacls) podem falhar." -ForegroundColor Yellow
    Write-Host "Se ocorrerem erros de permissão, feche e reabra o PowerShell como Administrador e rode: .\setup-dev.ps1" -ForegroundColor Yellow
}

Push-Location -LiteralPath (Split-Path -Path $MyInvocation.MyCommand.Definition -Parent)

Write-Host "Criando diretórios necessários..." -ForegroundColor Cyan
$dirs = @(
    'bootstrap\cache',
    'storage',
    'storage\logs',
    'storage\framework',
    'storage\framework\views',
    'storage\framework\sessions',
    'storage\framework\cache',
    'database'
)
foreach ($d in $dirs) {
    if (-not (Test-Path $d)) {
        New-Item -ItemType Directory -Path $d -Force | Out-Null
        Write-Host "  Criado: $d"
    } else {
        Write-Host "  Existe:  $d"
    }
}

# cria arquivo sqlite se usando sqlite
if ((Get-Content .env -ErrorAction SilentlyContinue) -match 'DB_CONNECTION=sqlite') {
    $sqlite = Join-Path (Get-Location) 'database\database.sqlite'
    if (-not (Test-Path $sqlite)) {
        New-Item -Path $sqlite -ItemType File -Force | Out-Null
        Write-Host "  Criado DB SQLite: database\database.sqlite"
    }
}

if (Test-IsAdmin) {
    Write-Host "Ajustando permissões (icacls) para seu usuário..." -ForegroundColor Cyan
    icacls .\bootstrap\cache /grant "$env:USERNAME:(OI)(CI)F" /T | Out-Null
    icacls .\storage /grant "$env:USERNAME:(OI)(CI)F" /T | Out-Null
    Write-Host "Permissões aplicadas." -ForegroundColor Green
} else {
    Write-Host "(Pulando icacls porque o script não está rodando como Administrador)" -ForegroundColor Yellow
}

Write-Host "Executando composer install..." -ForegroundColor Cyan
composer install --no-interaction
if ($LASTEXITCODE -ne 0) { Write-Host "composer install falhou (veja o output)." -ForegroundColor Red; exit 1 }

Write-Host "Copiando .env.example para .env se necessário..." -ForegroundColor Cyan
if (-not (Test-Path .env)) {
    if (Test-Path .env.example) {
        Copy-Item .env.example .env -Force
        Write-Host "  Copiado .env.example -> .env"
    } else {
        Write-Host "  .env.example não encontrado. Certifique-se de configurar .env manualmente." -ForegroundColor Yellow
    }
}

Write-Host "Gerando APP_KEY se necessário..." -ForegroundColor Cyan
$envFile = Get-Content .env -ErrorAction SilentlyContinue
if ($envFile -and ($envFile -notmatch 'APP_KEY=base64:') -and ($envFile -match 'APP_KEY=' -and ($envFile -match 'APP_KEY=$' -or $envFile -match 'APP_KEY=$null'))) {
    php artisan key:generate
} else {
    # se não houver APP_KEY ou vazia, gere
    if ($envFile -and ($envFile -notmatch 'APP_KEY=')) { php artisan key:generate }
}

Write-Host "Executando npm install..." -ForegroundColor Cyan
npm install
if ($LASTEXITCODE -ne 0) {
    Write-Host "npm install retornou erro. Tentando com --legacy-peer-deps..." -ForegroundColor Yellow
    npm install --legacy-peer-deps
    if ($LASTEXITCODE -ne 0) { Write-Host "npm install falhou. Verifique o output." -ForegroundColor Red; exit 1 }
}

# Verifica se autoprefixer está instalado (às vezes falta mesmo após npm install)
try {
    node -e "require('autoprefixer'); console.log('autoprefixer ok')" 2>$null
    if ($LASTEXITCODE -ne 0) { throw 'missing' }
    Write-Host "autoprefixer presente." -ForegroundColor Green
} catch {
    Write-Host "autoprefixer não encontrado: instalando..." -ForegroundColor Yellow
    npm install autoprefixer --save-dev
}

Write-Host "Build dos assets (vite)..." -ForegroundColor Cyan
npm run build
if ($LASTEXITCODE -ne 0) { Write-Host "npm run build falhou. Verifique o output." -ForegroundColor Red; exit 1 }

Write-Host "Executando migrations e seed..." -ForegroundColor Cyan
php artisan migrate --force
if ($LASTEXITCODE -ne 0) { Write-Host "php artisan migrate falhou." -ForegroundColor Red; exit 1 }

php artisan db:seed --force
if ($LASTEXITCODE -ne 0) { Write-Host "php artisan db:seed falhou." -ForegroundColor Yellow }

Write-Host "Setup concluído. Para iniciar o servidor, rode:" -ForegroundColor Green
Write-Host "  php artisan serve --port=8080" -ForegroundColor Cyan

Pop-Location

Write-Host "Pronto — acesse http://127.0.0.1:8080 no navegador." -ForegroundColor Green
