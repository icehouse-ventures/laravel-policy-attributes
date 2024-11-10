<?php

namespace IcehouseVentures\LaravelPolicyAttributes\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class PolicyRequirementOverride
{
    public function __construct()
    {
        // Override the requirement for a policy to be applied to a method.
    }
}
