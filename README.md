# laravel-policy-attributes
Laravel policy resource authorisation mapping using php attributes.

This package is a proposal for a new way to map policy authorisation to resources in Laravel.

# Background
Laravel provides us with world-class tools for foundation level user authentication and authorisation. There is also a rich ecosystem of packages and best practices for Role Based Access Control (RBAC) (e.g. [spatie/laravel-permission](https://github.com/spatie/laravel-permission) and Bouncer [JosephSilber/bouncer](https://github.com/JosephSilber/bouncer)). However, a common vulnerability vector is route model binding and other "business logic" often specific to particular model or a specific domain, that is not covered by RBAC.

# Proposal

The proposal is in two parts:

1. <Strong>Authorise Resource Default Closed</Strong> - An update to the framework's AuthorizeResource trait to support 'default closed' instead of current 'default open'. This would be a breaking change for existing applications, so the proposal is to pass a parameter to the trait to opt-in to the new behaviour. Naming for the prop could be 'defaultClosed' or 'defaultRestricted' or 'zeroTrust'. Other options could be to add a new trait such as "AuthorizeResourceDefaultClosed" or to add a new middleware called "NeedsAuthorization" that checks whether a policy call has been made at some point in the request lifecycle (whether via the AuthorizeResource trait or directly or via middleware). The locked-by-default is an example of the 'dead man's switch' security pattern which makes the experience for an individual developer in the moment 'worse' because they have to remember to unlock the resource before they can perform an action, but makes the overall application more secure by default.

2. <Strong>Policy Mapping with Attributes</Strong> - Ability to map policies to resources using php attributes. This helps smooth adoption of Policies and allows more a more declarative way to satisfy the "authorisation required" approach. Php attributes are a powerful feature of the language that are gaining increasing adoption across the Laravel community. Policy mapping is a uniquely good use case for attributes because the co-location of the mapping and the controller actions makes the code easier to understand and maintain.

# Security layers:
| Layer | Description | Example Question |
|-------|-------------|--------------|
| 1. User Authentication | Base layer Laravel authentication | Can this user access the application? |
| 2. Roles and Permissions | RBAC Permission management | Does this user have the right permissions to access this part of the application? |
| 3. Business Logic | ABAC Resource-specific policies | Does this user have the 'ownership' relationship needed to perform this specific action on this specific resource instance? |

# Package

To make the proposed framework changes clearer and to encourage broad discussion among the Laravel security community, this package provides a proof of concept 'package based'implementation of the proposed changes. The proof of concept uses archtiecture patterns that would not be neccessary for the framework level changes to be implemented (Traits, Custom Middleware, Contracts, etc). 


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

# Installation

```bash
composer require icehouse-ventures/laravel-policy-attributes
```

To apply the 'default closed' pattern, add the following to your middleware stack:

```php
'web' => [
    \IcehouseVentures\LaravelPolicyAttributes\Middleware\NeedsAuthorization::class,
],
```

To apply the policy mapping attributes, add the following to your controller:

```php
#[MapPolicyToResource]
```

# Usage
In the example below, the `PostPolicy` will be called with the `view` method and the `$post` instance.

```php
// Simple attribute mapping
#[PolicyCheck('view')]
public function viewPostComments(Post $post): Post
{
    return $post;
}

// Complex attribute mapping: Check a different policy
#[PolicyCheck(policy: ImagePolicy::class, method: 'view', parameter: 'image')]
public function viewPostAttachments(Post $post, Image $image): Post
{
    return $post;
}

// Complex attribute mapping: Check a different method
#[PolicyCheck(policy: PostPolicy::class, method: 'view', parameter: 'post')]
public function viewPostAttachments(Post $post): Post
{
    return $post;
}

// Complex attribute mapping: Check a different instance
#[PolicyCheck(policy: PostPolicy::class, method: 'view', parameter: 'post', id: 'postId')]
public function viewPostAttachments(Post $post): Post
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

The underlying 'default closed' pattern in cyber security was explained at Laracon AU 2024 by Jack Skinner using the example of a train with parking brakes that defaulted to being off.

https://www.youtube.com/watch?v=OWhEvdc2pSM&t=2024s

