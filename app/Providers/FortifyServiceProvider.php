<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\Fortify\LoginResponse;
use App\Http\Responses\Fortify\PasswordConfirmedResponse;
use App\Models\User;
use App\Services\PasswordSecurityService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\PasswordConfirmedResponse as PasswordConfirmedResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(PasswordConfirmedResponseContract::class, PasswordConfirmedResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::confirmPasswordsUsing(function ($user, string $password) {
            return PasswordSecurityService::checkPassword($password, $user->password);
        });

        Fortify::authenticateUsing(function (Request $request) {
            $email = strtolower(trim($request->email));
            $user = User::where('email', $email)->first();

            if (! $user) {
                \Illuminate\Support\Facades\Log::warning("Login falhou: Usuário $email não encontrado.");
                throw ValidationException::withMessages([
                    'email' => [trans('auth.failed')],
                ]);
            }

            if (! PasswordSecurityService::checkPassword($request->password, $user->password)) {
                \Illuminate\Support\Facades\Log::warning("Login falhou: Senha incorreta para $email.");
                // Dispara o evento de falha manualmente para que os logs capturem
                event(new Failed('web', $user, $request->only('email', 'password')));

                throw ValidationException::withMessages([
                    'email' => [trans('auth.failed')],
                ]);
            }

            return $user;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(function () {
            if (Auth::check()) {
                return redirect()->route('dashboard');
            }

            return view('pages::auth.login');
        });
        Fortify::verifyEmailView(fn () => view('pages::auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('pages::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('pages::auth.confirm-password'));
        Fortify::registerView(fn () => view('pages::auth.register'));
        Fortify::resetPasswordView(fn () => view('pages::auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('pages::auth.forgot-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower((string) $request->input(Fortify::username()));
            $ip = $request->ip();
            $throttleKey = Str::transliterate($email.'|'.$ip);
            $userIncludingTrashed = User::withTrashed()->where('email', $email)->first();

            // Usuario desativado (soft delete) deve sempre receber erro generico de credenciais,
            // sem feedback de bloqueio por tentativas.
            if ($userIncludingTrashed?->trashed()) {
                return Limit::none();
            }

            return Limit::perMinutes(60, 5)->by($throttleKey)->response(function (Request $request, $limit) use ($throttleKey) {
                event(new Lockout($request));

                $seconds = 0;

                // 1. Tenta via RateLimiter padrão
                $seconds = RateLimiter::availableIn($throttleKey);
                if ($seconds <= 0) {
                    $seconds = RateLimiter::availableIn('login:'.$throttleKey);
                }

                // 2. Fallback Nuclear: Varredura física do cache (necessário em alguns ambientes Windows/Laravel 13)
                if ($seconds <= 0) {
                    $cachePath = storage_path('framework/cache/data');
                    if (is_dir($cachePath)) {
                        $dir = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($cachePath));
                        $now = time();
                        $latestTimer = 0;
                        foreach ($dir as $file) {
                            if ($file->isFile() && $file->getFilename() !== '.gitignore') {
                                $content = @file_get_contents($file->getPathname());
                                if ($content && strlen($content) > 10) {
                                    $val = substr($content, 10);
                                    if (strpos($val, 'i:') === 0) {
                                        $num = (int) substr($val, 2, -1);
                                        // Procura por um timestamp futuro (timer)
                                        if ($num > $now && $num < $now + 7200) {
                                            if ($num > $latestTimer) {
                                                $latestTimer = $num;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if ($latestTimer > 0) {
                            $seconds = $latestTimer - $now;
                        }
                    }
                }

                if ($seconds > 0) {
                    $minutes = ceil($seconds / 60);
                    $message = "Muitas tentativas de login. Sua conta está bloqueada por mais {$minutes} minuto(s).";
                } else {
                    $message = 'Muitas tentativas de login. Tente novamente em alguns minutos.';
                }

                return back()->withErrors([
                    Fortify::username() => $message,
                ]);
            });
        });
    }
}
