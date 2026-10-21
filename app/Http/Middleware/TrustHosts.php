<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * @return array<int, string|null>
     */
    public function hosts(): array
    {
        return array_filter([
            $this->allSubdomainsOfApplicationUrl(),
            '^(.+\.)?onrender\.com$',
            '^localhost(:[0-9]+)?$',
            '^127\.0\.0\.1(:[0-9]+)?$',
            '^10\.[0-9]+\.[0-9]+\.[0-9]+(:[0-9]+)?$',
            '^172\.(1[6-9]|2[0-9]|3[0-1])\.[0-9]+\.[0-9]+(:[0-9]+)?$',
            env('APP_DOMAIN') ? '^(.+\.)?' . preg_quote(env('APP_DOMAIN')) . '$' : null,
        ]);
    }
}
