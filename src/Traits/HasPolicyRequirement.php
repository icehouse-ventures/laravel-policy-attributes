<?php

namespace IcehouseVentures\LaravelPolicyAttributes\Traits;

use IcehouseVentures\LaravelPolicyAttributes\Attributes\Policy;
use IcehouseVentures\LaravelPolicyAttributes\Attributes\PolicyRequirementOverride;
use IcehouseVentures\LaravelPolicyAttributes\Http\Middleware\PolicyRequirement;
use ReflectionMethod;

trait HasPolicyRequirement
{
    use PolicyBaseTrait;

    // This trait is used to enforce the requirement for all methods in a controller to have a policy.
    // The requirement can be satisified by default policy mapping, controller mapping or attribute mapping.
    // If no policy is found, the method is denied access.
    // This is intended to make all controller methods 'secure by default' and encourage the use of policies.

    protected function processPolicyRequirement(ReflectionMethod $method): void
    {
        $policyAttributes = $method->getAttributes(Policy::class);
        $defaultPolicyMap = parent::resourceAbilityMap();
        $controllerAbilityMap = $this->resourceAbilityMap();

        // Manually skip the policy requirement
        if ($method->getAttributes(PolicyRequirementOverride::class)) {
            return;
        }

        // Satisfy the policy requirement with an attribute
        if ($policyAttributes) {
            return;
        }

        // Satisfy the policy requirement with controller mapping
        if (in_array($method->name, array_keys($controllerAbilityMap))) {
            return;
        }

        // Satisfy the policy requirement with default resource method policy mapping
        if (in_array($method->name, array_keys($defaultPolicyMap))) {
            return;
        }

        // Fail the policy requirement
        $this->denyMiddleware($method);
    }

    private function denyMiddleware(ReflectionMethod $method): void
    {
        $this->middleware(PolicyRequirement::class)->only($method->name);
    }
}
