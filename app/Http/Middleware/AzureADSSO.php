<?php
// app/Http/Middleware/AzureADSSO.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\Employee;

class AzureADSSO
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Se usuário já está autenticado, continua
        if (Auth::check()) {
            return $next($request);
        }

        // Verifica se é callback do Azure AD
        if ($request->has('code') && $request->has('state')) {
            return $this->handleAzureCallback($request, $next);
        }

        // Verifica se há token de sessão válido
        if ($request->hasHeader('Authorization') || $request->cookie('azure_token')) {
            return $this->handleTokenAuthentication($request, $next);
        }

        // Redireciona para autenticação Azure AD
        return $this->redirectToAzure($request);
    }

    /**
     * Redireciona usuário para autenticação Azure AD
     */
    protected function redirectToAzure(Request $request)
    {
        $state = bin2hex(random_bytes(32));
        session(['azure_state' => $state]);

        $redirectUrl = Socialite::driver('azure')
            ->setScopes(['openid', 'profile', 'email', 'User.Read'])
            ->with(['state' => $state])
            ->redirect()
            ->getTargetUrl();

        return redirect($redirectUrl);
    }

    /**
     * Trata callback do Azure AD
     */
    protected function handleAzureCallback(Request $request, Closure $next)
    {
        try {
            $azureUser = Socialite::driver('azure')->user();

            // Busca ou cria usuário
            $user = $this->findOrCreateUser($azureUser);

            // Autentica usuário
            Auth::login($user);

            // Atualiza último login
            $user->update(['last_login_at' => now()]);

            // Log de auditoria
            \App\Models\AuditLog::log(
                \App\Models\AuditLog::OPERATION_LOGIN,
                $user,
                $user,
                null,
                ['provider' => 'azure_ad', 'azure_id' => $azureUser->id]
            );

            // Redireciona para dashboard ou URL pretendida
            $redirectTo = session('url.intended', route('dashboard'));
            return redirect($redirectTo);

        } catch (\Exception $e) {
            Log::error('Erro no callback do Azure AD', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return redirect()->route('login')->withErrors(['sso' => 'Erro na autenticação SSO. Tente novamente.']);
        }
    }

    /**
     * Trata autenticação por token
     */
    protected function handleTokenAuthentication(Request $request, Closure $next)
    {
        try {
            $token = $request->bearerToken() ?? $request->cookie('azure_token');

            if (!$token) {
                return $this->redirectToAzure($request);
            }

            // Valida token (simplificado - implementar validação JWT completa)
            $tokenData = $this->decodeToken($token);

            if (!$tokenData || $tokenData['exp'] < time()) {
                return $this->redirectToAzure($request);
            }

            // Busca usuário por Azure ID
            $user = User::where('azure_id', $tokenData['sub'])->first();

            if (!$user) {
                Log::warning('Usuário não encontrado por Azure ID', ['azure_id' => $tokenData['sub']]);
                return $this->redirectToAzure($request);
            }

            Auth::login($user);

            return $next($request);

        } catch (\Exception $e) {
            Log::error('Erro na autenticação por token', [
                'error' => $e->getMessage(),
            ]);

            return $this->redirectToAzure($request);
        }
    }

    /**
     * Busca ou cria usuário baseado nos dados do Azure AD
     */
    protected function findOrCreateUser($azureUser): User
    {
        // Busca usuário existente
        $user = User::where('azure_id', $azureUser->id)->first();

        if ($user) {
            // Atualiza dados se necessário
            $user->update([
                'email' => $azureUser->email,
                'name' => $azureUser->name,
            ]);
            return $user;
        }

        // Busca funcionário por email
        $employee = Employee::where('email', $azureUser->email)->first();

        if (!$employee) {
            Log::warning('Funcionário não encontrado por email do Azure AD', [
                'email' => $azureUser->email,
                'azure_id' => $azureUser->id,
            ]);

            // Cria usuário sem funcionário associado (será associado posteriormente pelo RH)
            return User::create([
                'name' => $azureUser->name,
                'email' => $azureUser->email,
                'azure_id' => $azureUser->id,
                'is_active' => true,
                'password' => bcrypt(str_random(32)), // Senha aleatória (não será usada)
            ]);
        }

        // Cria usuário associado ao funcionário
        $user = User::create([
            'name' => $azureUser->name,
            'email' => $azureUser->email,
            'employee_id' => $employee->employee_id,
            'azure_id' => $azureUser->id,
            'department_id' => $employee->department_id,
            'manager_id' => $employee->manager_id,
            'is_active' => $employee->is_active,
            'password' => bcrypt(str_random(32)), // Senha aleatória
        ]);

        // Associa roles baseado no cargo/departamento
        $this->assignRoles($user, $employee);

        return $user;
    }

    /**
     * Atribui roles ao usuário baseado no funcionário
     */
    protected function assignRoles(User $user, Employee $employee): void
    {
        // Baseado no cargo ou departamento
        if (str_contains(strtolower($employee->job_title), 'rh') ||
            str_contains(strtolower($employee->job_title), 'recursos humanos')) {
            $user->assignRole('rh');
        } elseif ($employee->department && $employee->department->manager_id === $employee->id) {
            $user->assignRole('manager');
        } else {
            $user->assignRole('employee');
        }

        // Admin baseado em configuração específica
        if (in_array($employee->email, config('app.admin_emails', []))) {
            $user->assignRole('admin');
        }
    }

    /**
     * Decodifica token JWT (implementação simplificada)
     */
    protected function decodeToken(string $token): ?array
    {
        try {
            $parts = explode('.', $token);

            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(
                base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])),
                true
            );

            return $payload;
        } catch (\Exception $e) {
            Log::error('Erro ao decodificar token JWT', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
