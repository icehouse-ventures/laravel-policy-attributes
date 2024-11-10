<?php

namespace IcehouseVentures\LaravelPolicyAttributes\Middleware;

use IcehouseVentures\LaravelPolicyAttributes\Attributes\Policy;
use Closure;
use Illuminate\Http\Request;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;

class PolicyCheck
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        // Get Policy attributes for the current method
        $policyAttributes = (new ReflectionClass($route->getControllerClass()))
            ->getMethod($route->getActionMethod())
            ->getAttributes(Policy::class);

        foreach ($policyAttributes as $policyAttribute) {
            $attribute = $policyAttribute->newInstance();

            // Complex attributes are handled by this middleware
            if (!$attribute->isSimpleAttribute()) {
                $route->getController()->authorize(...$attribute->prepareAuthorisation($request));
            }

            // Simple attributes are handled by the base policy trait

        }

        return $next($request);
    }
}
