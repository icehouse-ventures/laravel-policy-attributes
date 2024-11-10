<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PolicyRequirement
{
    public function handle(Request $request, Closure $next): Response
    {
        $controller = $request->route()->getControllerClass();

        $method = $request->route()->getActionMethod();

        $request->route()->getController()->authorize(['Check policy requirement on ' . $method . ' in ' . $controller]);

        return $next($request);
    }
}
