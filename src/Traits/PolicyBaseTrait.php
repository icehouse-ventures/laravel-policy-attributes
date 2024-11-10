<?php

namespace IcehouseVentures\LaravelPolicyAttributes\Traits;

use IcehouseVentures\LaravelPolicyAttributes\Middleware\PolicyCheck;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;

trait PolicyBaseTrait
{
    protected array $abilityMap = [];
    protected array $methodsWithoutModels = [];
    protected string $model;

    // This allows us to interact with Laravel's built in resource authorization
    public function authorizeResource($model, $parameter = null, array $options = [], $request = null): void
    {
        $this->model = $model;
        $this->middleware(PolicyCheck::class);

        $this->getMethods()->each(function ($method) {
            // Process the policy requirement if the controller has the trait
            if (in_array(HasPolicyRequirement::class, class_uses_recursive(self::class))) {
                $this->processPolicyRequirement($method);
            }

            // Process the policy attributes if the controller has the trait
            if (in_array(HasPolicyAttributes::class, class_uses_recursive(self::class))) {
                $this->processPolicyAttributes($method);
            }
        });

        parent::authorizeResource($model, $parameter, $options, $request);
    }

    // This gets all the public methods in the controller
    protected function getMethods(): Collection
    {
        $reflectionClass = new ReflectionClass($this::class);
        return collect($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC))
            ->filter(fn ($method) => $method->class == self::class);
    }

    // This merges the default ability map (eg 'view', 'create', 'update')
    // with the controller's custom ability map (eg 'showPost', 'sendInvoice')
    protected function resourceAbilityMap(): array
    {
        return array_merge(parent::resourceAbilityMap(), $this->abilityMap);
    }

    // This merges the default methods without specified model instances (eg 'index', 'create', etc)
    // with the controller's custom methods without model instances (eg 'import', 'export', etc)
    protected function resourceMethodsWithoutModels(): array
    {
        return array_merge(parent::resourceMethodsWithoutModels(), $this->methodsWithoutModels);
    }
}
