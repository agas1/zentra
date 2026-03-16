<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Fix SSL cert on Windows for all HTTP clients (Guzzle, Socialite, cURL)
        if (PHP_OS_FAMILY === 'Windows') {
            $certPath = 'C:\\Users\\IAGENTE\\Downloads\\php-8.4.14-Win32-vs17-x64\\extras\\ssl\\cacert.pem';
            if (file_exists($certPath)) {
                putenv("CURL_CA_BUNDLE={$certPath}");
                putenv("SSL_CERT_FILE={$certPath}");
                \Illuminate\Support\Facades\Http::globalOptions([
                    'verify' => $certPath,
                ]);
            }
        }

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('chat-message', function (Request $request) {
            return Limit::perMinute(30)->by(auth()->id() ?: $request->ip());
        });

        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        RateLimiter::for('api-external', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->attributes->get('api_key')?->id ?? $request->ip()
            );
        });
    }
}
