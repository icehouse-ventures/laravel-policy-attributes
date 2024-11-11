# laravel-policy-attributes
Laravel policy resource authorisation mapping using php attributes.

This package is a proposal for a new way to map policy authorisation to resources in the Laravel framework.

# Background
Laravel provides us with world-class tools for foundation-level user authentication. There is also a rich ecosystem of packages and best practices for authorisation using Role Based Access Control (RBAC) (e.g. [spatie/laravel-permission](https://github.com/spatie/laravel-permission) and Bouncer [JosephSilber/bouncer](https://github.com/JosephSilber/bouncer)). Even so, a common vulnerability vector is route model binding and other "business logic" often specific to particular model or a specific domain, that is not covered by current RBAC patterns. Laravel provides a Policy class that is perfect for encapsulating this kind of "business logic" based authorisation (such as the relationship (or lack thereof) between users and a particular resource instance). 

The Laravel currently framework provides several ways to map these policies to resources using the AuthorizeResource trait. However, the current approach is 'default open' which means that if a policy is not specifcally mapped or called, the resource is open to all users.

# Proposal

The proposal is in two parts:

1. <Strong>Authorise Resource Default Closed</Strong> - An update to the framework's AuthorizeResource trait to support 'default closed' instead of current 'default open'. A simple change to the default behaviour of the current trait would be a breaking change for existing applications, so the proposal is to pass a parameter to the trait to opt-in to the new behaviour. Naming for the prop could be 'defaultClosed' or 'defaultRestricted' or 'zeroTrust'. Other options could be to add a new trait such as "AuthorizeResourceDefaultClosed", or "EnforcePolicy", or to add a new middleware called "NeedsAuthorization" that checks whether a policy call has been made at some point in the request lifecycle (whether via the AuthorizeResource trait or directly or via middleware). The locked-by-default is an example of the 'dead man's switch' security pattern which makes the experience for an individual developer in the moment 'worse' because they have to remember to unlock the resource before they can perform an action, but makes the overall application more secure by default.

2. <Strong>Policy Mapping with Attributes</Strong> - Ability to map policies to resources using php attributes. This helps smooth adoption of Policies and allows more a more declarative way to satisfy the "authorisation required" dead man's switch approach. Php attributes are a powerful feature of the language that are gaining increasing adoption across the Laravel community. Policy mapping is a uniquely good use case for attributes because the co-location of the mapping and the controller actions makes the code easier to understand and maintain. The naming of the attributes is an open question. The proposal uses Policy. Other attribute names we considered included Can, PolicyCheck, PolicyMap and CustomPolicyMapping.

# Security layers:
| Layer | Description | Example Question |
|-------|-------------|--------------|
| 1. User Authentication | Base layer Laravel authentication | Can this user access the application? |
| 2. Roles and Permissions | RBAC Permission management | Does this user have the right permissions to access this part of the application? |
| 3. Business Logic | ABAC Resource-specific policies | Does this user have the 'ownership' relationship needed to perform this specific action on this specific resource instance? |

# Package

To make the proposed framework changes clearer and to encourage broad discussion among the Laravel security community, this package provides a proof of concept 'package based' implementation of the proposed changes. The proof of concept uses archtiecture patterns that would not be neccessary for the framework level changes to be implemented (Traits, Custom Middleware, Contracts, etc). This package will be actively maintained and developed. Security is a core concern for all applications and this package is a starting point for a more secure and flexible approach to policy authorisation patterns in Laravel.

# Installation

```bash
composer require icehouse-ventures/laravel-policy-attributes
```

To apply the 'default closed' pattern, add the following to your controller:

```php
use IcehouseVentures\LaravelPolicyAttributes\Traits\HasPolicyRequirement;

class PostController extends Controller
{
    use HasPolicyRequirement;
}
```

To apply the policy mapping attributes, add the following to your controller:

```php
use IcehouseVentures\LaravelPolicyAttributes\Attributes\Policy;
use IcehouseVentures\LaravelPolicyAttributes\Traits\HasPolicyAttributes;

class PostController extends Controller
{
    use HasPolicyAttributes;
    
    #[Policy('view')]
    public function showComments(Post $post): Post
    {
        return $post;
    }
}
```

To use the requirement and the mapping together, add the following to your controller:

```php
use IcehouseVentures\LaravelPolicyAttributes\Attributes\Policy;
use IcehouseVentures\LaravelPolicyAttributes\Traits\HasPolicyAttributes;
use IcehouseVentures\LaravelPolicyAttributes\Traits\HasPolicyRequirement;

class PostController extends Controller
{
    use HasPolicyAttributes;
    use HasPolicyRequirement;

    #[Policy('view')]
    public function showComments(Post $post): Post
    {
        return $post;
    }
}
```

# Usage Examples
In the example below, the `PostPolicy` will be called with the `view` method and the `$post` instance.

```php
// Simple attribute mapping
#[Policy('view')]
public function showComments(Post $post): Post
{
    return $post;
}

// Complex attribute mapping: Check a different policy
#[Policy(policy: ImagePolicy::class, method: 'view', parameter: 'image')]
public function showPostAttachments(Post $post, Image $image): Post
{
    return $post;
}

// Complex attribute mapping: Check a different method
#[Policy(policy: PostPolicy::class, method: 'view', parameter: 'post')]
public function showPostAttachments(Post $post): Post
{
    return $post;
}

// Complex attribute mapping: Check a different instance
#[Policy(policy: PostPolicy::class, method: 'view', parameter: 'post', id: 'postId')]
public function showPostAttachments(Post $post): Post
{
    return $post;
}
```

# References

Stephen Rees Carter's article on the resource authorisation pattern which points out the "default open" issue with the current approach framework provided AuthorizeResource trait. This article was the seed crystal for the current proposal.

https://securinglaravel.com/security-tip-watch-out-for-resource/

An earlier approach to authorisation attributes. This is more flexible and powerful but our intention with the current proposal is to enhance the existing Policy class and AuthorizeResource trait.

https://github.com/Codestagero/laravel-authorization

A recent twitter thread from [@NewtonJob](https://github.com/newtonjob) with a code sample showing how to use attributes to map policies to resources.

https://x.com/_newtonjob/status/1845432101283279331

The underlying 'default closed' pattern in cyber security was explained at Laracon AU 2024 by [Jack Skinner](https://github.com/devjack) using the example of a runaway train with parking brakes that defaulted to being off in some circumstances.

https://www.youtube.com/watch?v=OWhEvdc2pSM&t=2024s

# Test Cases

## Default Closed Test Case 1: Default Closed
1. AuthorizeResource applied to a contoller.
2. NeedsAuthorization enabled.
3. Hit custom action.
4. Fails with "This action is unauthorized because no policy was specified."

## Default Closed Test Case 2: Opened by Custom Mapping
1. AuthorizeResource applied to a contoller.
2. NeedsAuthorization enabled.
3. Hit custom action.
4. Policy mapping applied to the custom action using the constructor array mapping
5. Policy call made.
6. Pass or fail as appropriate to Policy.

## Default Closed Test Case 3: Opened by Attribute Mapping
1. AuthorizeResource applied to a contoller.
2. NeedsAuthorization enabled.
3. Hit custom action.
4. Policy mapping applied to the custom action using the attribute mapping
5. Policy call made.
6. Pass or fail as appropriate to Policy.

## Default Closed Test Case 4: Requirement Override
1. AuthorizeResource applied to a contoller.
2. NeedsAuthorization enabled.
3. Hit custom action.
4. Policy has an attribute specified to override the requirement.
5. No policy call made.
6. Pass.

## Policy Mapping Test Case 1: Simple Attribute
1. Policy method has a simple attribute meaning that 
1.1. Model: The policy to be called is the Model in the AuthorizeResource trait.
1.2. Action: The policy method to check is a simple match to the action name specified in the attribute.
1.3. Instance: The model instance to check is the one found in the route model binding and type hinted in the controller method.
2. Controller action uses the attribute.
3. Policy method is called.
4. Pass or fail as appropriate to Policy.

## Policy Mapping Test Case 2: Complex Attribute Custom Model
1. Policy method has a complex attribute where the policy to check is different to the model in the AuthorizeResource trait. We find this using the desired model / policy as a parameter in the policy method.
2. Policy method is called.
3. Pass or fail as appropriate to Policy (check correct policy is called).

## Policy Mapping Test Case 3: Complex Attribute Custom Action
1. Policy method has a complex attribute where the policy method to check is different to the action expected. We find this using the desired method name as a parameter in the policy method.
2. Policy method is called.
3. Pass or fail as appropriate to Policy (check correct policy method is called).

## Policy Mapping Test Case 4: Complex Attribute Custom Instance
1. Policy method has a complex attribute where the model instance to check is different to the one expected. We find this by specifying a parameter ID to use to find the relevant model instance.
2. Policy method is called.
3. Pass or fail as appropriate to Policy (check correct model instance is used).

# Future Work
This package is a proof of concept for the proposed changes to the Laravel framework. In the meantime, this package provides an approach to 'default closed' authorisation that may be useful to businesses required to complete penetration testing or other security reviews where the 'default open' approach is not acceptable or may lead to exposed endpoints.

# Alternatives
<strong>Pest Architecture Tests</strong> - In the theory, Pest architecture tests could be used to test whether all controller actions are covered by a policy. 

<strong>Resource Ability Map</strong> - The resourceAbilityMap() function is a good approach for mapping policies to controller actions. However, it is not a 'dead man's switch' default-closed policy check and does not neccessarily provide a way to check that a policy has been called. One options would be to increase the documentation and visibility of the resourceAbilityMap() function and make it clear that it is the preferred method for mapping policies to 'custom'controller actions. (https://github.com/laravel/framework/blob/11.x/src/Illuminate/Foundation/Auth/Access/AuthorizesRequests.php#L113) However, it's really an add-on to the authorizeResource() function and does not solve the 'default open' issue.

<strong>Framework Provided Trait</strong> - The authorizeResource() method may be less attractive post Laravel 11 in favour of the AuthorizesRequests trait. The trait is still subject to the 'default open' issue and the need for custom mapping. (https://github.com/laravel/framework/discussions/50552) The Gate facade seems to be a preferred approach for policy checking in the framework going forward. (https://github.com/laravel/docs/commit/745bab30b0c845db5bacadcc99309a8d1d6565c3#diff-5b015cf220a3b563e76df3c8346c54bd8e2ca4aa314345342c30a66a0c6e4b09R618) 

<strong>Middleware</strong> - Another approach would be to use middleware more extensively to check that a policy has been called at some point in the request lifecycle.

<strong>Controller Methods</strong> - As Taylor noted a few year's back (https://github.com/laravel/ideas/issues/772) you can use the query itself (inside the controller method) to build the query for any business logic that needs to be checked for authorisation (such as is this use the author of this post). This approach works well for index methods and other query based patterns, but not so well for route model binding on single model instances.
