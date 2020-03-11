<?php

namespace Garbetjie\Http\RequestLogging\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LaravelMiddleware extends Middleware
{
    /**
     * Laravel middleware handler.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle($request, Closure $next)
    {
        return $this->logRequest($request, $next, 'in');
    }
}
