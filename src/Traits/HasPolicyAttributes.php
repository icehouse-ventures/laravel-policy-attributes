<?php

namespace IcehouseVentures\LaravelPolicyAttributes\Traits;

use IcehouseVentures\LaravelPolicyAttributes\Attributes\Policy;
use IcehouseVentures\LaravelPolicyAttributes\Attributes\PolicyRequirementOverride;
use ReflectionAttribute;
use ReflectionMethod;

trait HasPolicyAttributes
{
    use PolicyBaseTrait;

    // This trait is used to map a method in a controller to a policy via an attribute.
    // This is used to allow controller methods to be checked against different policies within a single controller.
    // It also allows for custom methods outside the standard resource controller methods to be more easily mapped.
    // The policy is then checked in a middleware.

    protected function processPolicyAttributes(ReflectionMethod $method): void
    {
        $policyAttributes = $method->getAttributes(Policy::class);

        if ($policyAttributes) {
            foreach ($policyAttributes as $attribute) {
                $this->applyMiddleware($attribute, $method);
            }
        }
    }

    private function applyMiddleware(ReflectionAttribute $attribute, ReflectionMethod $method): void
    {
        $methodWithModel = false;
        $instance = $attribute->newInstance();

        // If the attribute is not a simple attribute, skip it
        if (!$instance->isSimpleAttribute()) {
            return;
        }

        // Add the policy to the ability map
        $this->abilityMap[$method->name] = $instance->ability;

        // Check if the method has a model parameter
        foreach ($method->getParameters() as $param) {
            if ($param->getType()->getName() == $this->model) {
                $methodWithModel = true;
                break;
            }
        }

        // If the method does not have a model parameter, add it to the list of methods without models
        if (!$methodWithModel) {
            $this->methodsWithoutModels[] = $method->name;
        }
    }
}
