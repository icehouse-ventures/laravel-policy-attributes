<?php

namespace App\Traits;

use App\Http\Middleware\PolicyCheck;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;

trait PolicyBaseTrait
{
    protected array $abilityMap = [];
    protected array $methodsWithoutModels = [];
    protected string $model;

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

    protected function getMethods(): Collection
    {
        $reflectionClass = new ReflectionClass($this::class);
        return collect($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC))
            ->filter(fn ($method) => $method->class == self::class);
    }

    protected function resourceAbilityMap(): array
    {
        return array_merge(parent::resourceAbilityMap(), $this->abilityMap);
    }

    protected function resourceMethodsWithoutModels(): array
    {
        return array_merge(parent::resourceMethodsWithoutModels(), $this->methodsWithoutModels);
    }
}
