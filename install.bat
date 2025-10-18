@echo off
cd /d "%~dp0"

echo "=== INSTALANDO PORTAL CORPORATIVO ==="
echo.

echo "1. Gerando chave da aplicação..."
php artisan key:generate
if errorlevel 1 (
    echo "ERRO: PHP ou Laravel não encontrado!"
    echo "Verifique se o PHP 8.1+ está instalado e no PATH"
    pause
    exit /b 1
)

echo.
echo "2. Instalando dependências PHP..."
composer install --no-dev --optimize-autoloader
if errorlevel 1 (
    echo "ERRO: Falha na instalação do Composer!"
    pause
    exit /b 1
)

echo.
echo "3. Instalando dependências Node.js..."
npm install
if errorlevel 1 (
    echo "ERRO: NPM não encontrado!"
    echo "Instale o Node.js de https://nodejs.org"
    pause
    exit /b 1
)

echo.
echo "4. Compilando assets..."
npm run build
if errorlevel 1 (
    echo "ERRO: Falha na compilação dos assets!"
    pause
    exit /b 1
)

echo.
echo "5. Configurando banco de dados..."
echo "Certifique-se de que o MySQL está rodando e configure o .env"

echo.
echo "=== INSTALAÇÃO CONCLUÍDA! ==="
echo.
echo "Próximos passos:"
echo "1. Configure o arquivo .env com suas credenciais"
echo "2. Execute: php artisan migrate"
echo "3. Execute: php artisan db:seed"
echo "4. Execute: php artisan serve"
echo.
pause
