<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * Proxies de confianza.
     *
     * Render utiliza un proxy inverso para gestionar HTTPS.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = '*';

    /**
     * Encabezados utilizados para identificar
     * el protocolo HTTPS original.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}