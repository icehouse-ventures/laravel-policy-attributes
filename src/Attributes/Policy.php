<?php

namespace IcehouseVentures\LaravelPolicyAttributes\Attributes;

use Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class Policy
{
    public function __construct(
        public string $ability,
        public null|string|array $model = null,
        public ?string $parameter = null
    ) {
        // Map a method in a controller to an action in a policy
    }

    // A simple attribute just provides mapping from a method in the controller to an action in a policy
    public function isSimpleAttribute(): bool
    {
        if ($this->model) {
            return false;
        }
        return true;
    }

    // Infer the model (and therefore the policy to apply)
    public function prepareAuthorisation(Request $request): array
    {
        // Attempt to use the request to infer the model
        if (is_array($this->model)) {
            return $this->authoriseUsingRequest($request);
        }

        // Check route model binding for the model
        foreach ($request->route()->parameters() as $parameter) {
            if (is_object($parameter) && $parameter instanceof $this->model) {
                return [$this->ability, $parameter];
            }
        }

        // Check for a model declared in the attribute itself
        if (Str::startsWith($this->model, 'App\Models')) {
            if ($this->parameter) {
                foreach ($request->route()->parameters() as $parameter) {
                    if (is_object($parameter) && $parameter instanceof $this->parameter) {
                        return [$this->ability, [$this->model, $parameter]];
                    }
                }
            }

            return [$this->ability, $this->model];
        }

        return $this->failedMessage($request);
    }

    private function failedMessage(Request $request): array
    {
        $controller = $request->route()->getControllerClass();
        $method = $request->route()->getActionMethod();

        return ['Check the Policy attribute on ' . $method . ' in ' . $controller];
    }

    private function authoriseUsingRequest($request): array
    {
        if (!Str::startsWith(Arr::first($this->model), 'request:') or !isset($this->model[1])) {
            return $this->failedMessage($request);
        }

        $modelClass = new (Arr::last($this->model))();
        $model = $modelClass::find($request->input(Str::after(Arr::first($this->model), 'request:')));

        if (!$model) {
            return $this->failedMessage($request);
        }

        return [$this->ability, $model];
    }
}
