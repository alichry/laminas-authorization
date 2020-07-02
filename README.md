# laminas-authorization

[![Build Status](https://travis-ci.org/alichry/laminas-authorization.svg?branch=master)](https://travis-ci.org/alichry/laminas-authorization)
[![codecov](https://codecov.io/gh/alichry/laminas-authorization/branch/master/graph/badge.svg)](https://codecov.io/gh/alichry/laminas-authorization)

Out of the box, laminas's authentication module provides interfaces and services
for authenticating users and saving their identities in a session storage. 
This module provides an authorization service by transparently redirecting
unauthorized users accessing a certain resource, with the support of defining
multiple authorization links forming an authorization chain.
You can configure the list of controller/method authorization statuses (policies)
by creating annotations on top of your methods or setting it in the configuration.

## Installation
Install using composer, run

```
$ composer require alichry/laminas-authorization
```

Add the modules `AliChry\Laminas\AccessControl` and 
`AliChry\Laminas\Authorization` 
to `config/modules.config.php`

## Prerequisites
This module is not concerned with authenticating users, rather, its only intent
is to check the authorization status of the (authenticated) identity. To create
an authorization link, we require:

- An [`AuthenticationServiceInterface`](https://github.com/laminas/laminas-authentication):
You can use [doctrine/doctrine-module](https://github.com/doctrine/DoctrineModule)
to configure an authentication service easily.
- An [`AccessControlListInterface`](https://github.com/alichry/accesscontrol):
[alichry/laminas-accesscontrol](https://github.com/alichry/laminas-accesscontrol)
provides configurable services. By default, this module configures an
[`IdentityAccessControlList`](https://github.com/alichry/laminas-authorization/blob/master/config/module.config.php)
instance using
[`AnnotatedResourceManager`](https://github.com/alichry/laminas-authorization/blob/master/src/Resource/AnnotatedResourceManager.php)
as a dependency alongside passed identities implementing
[`IdentityInterface`](https://github.com/alichry/laminas-accesscontrol/blob/master/src/Identity/IdentityInterface.php).

## Quick start
The fastest path to create a laminas application with authorization support is
through Doctrine ORM integration. If you're unfamiliar with Doctrine ORM, please
check the doctrine project website and
[doctrine/doctrine-orm-module](https://github.com/doctrine/DoctrineORMModule)

- Let your identity type class implement 
[`IdentityInterface`](https://github.com/alichry/laminas-accesscontrol/blob/master/src/Identity/IdentityInterface.php).
The methods to implement are `hasPermission` and `hasRole`, in an ORM environment
where the associations are already defined, it is easy to implement such methods.
- The authorization service requires an authentication service, configure
[`Doctrine\Authentication`](https://github.com/doctrine/DoctrineModule/blob/master/docs/authentication.md)
to quickly deploy an authentication service based on your identity type.
- Configure this module by defining a "global" authorization link:

```php
<?php
# module.config.php

use Laminas\Authentication\AuthenticationService;

return [
    // ...
    'alichry' => [
        'authorization' => [
            'chain' => [
                'global' => [
                    'redirect_route' => 'login',
                    'authentication_service' => AuthenticationService::class,
                    'access_control_list' => 'identity'
                ]
            ]
        ]
    ],
    // ...
];
```

The authorization service is now configured, you can define annotations on top of
your methods to indicate authorization policies.

## Defining method policy using annotations
On top of your controller's method or class docblock, you can define `@Authorization` annotations,
indicating:

- The target link name.
- The policy: Allow, Reject, Authenticate or Authorize.
- The permission: if the specified policy is Authorize, permission should also be
specified.

You can define multiple annotations, each with a different link name.
Additionally, you can omit the link name and it will be treated as the fallback.  

Example:

```php
<?php
use AliChry\Laminas\Authorization\Annotation\Authorization;

/**
 * Class-level annotations are treated as a fallback. First method annotations
 * are consulted, if no relevant method annotations were found then
 * class-level annotations are utilized.
 *
 * Default class policy is to reject unspecified links:
 * @Authorization(policy="Reject")
 *
 * Require valid authentication status for "global" link:
 * @Authorization(link="global", policy="Authenticate")
 */
class ApplicationController
{
    /**
     * Allow this resource to be publicly accessible:
     * @Authorization(policy="Allow")
     * The above will override class-level annotations, and since the link
     * property was omitted, it will apply to all links.
     */
    public function indexAction()
    {
    }
        
    /**
     * Allow this resource to be accessible by entities granted the "delete"
     * permission under the "global" link:
     * @Authorization(link="global", policy="Authorize", permission="delete")
     */
    public function deleteAction()
    {
    }

    /**
     * No annotations are defined for this method, the class annotations
     * will be used as a fallback. This method requires the user to be
     * authenticated for the "global" link or the request is rejected for all
     * other links.
     */
    public function profileAction()
    {
    }
}
```

## Authorization Link
An Authorization Link can infer whether an
(authenticated) identity is authorized to access a controller or a controller's
action.  

This is achieved by relying on `AuthenticationService` (for authentication status)
and  a `AccessControlListInterface` from
[alichry/laminas-accesscontrol](https://github.com/alichry/laminas-accesscontro)
that implies the accessibility or authorization level of a controller or a
controller's method.  

Eventually, an Authorization Link can imply whether an (authenticated) identity
is granted access to a certain resource (controller/action) and will return the
result.

## Authorization Chain
An Authorization Chain is built from one or more Authorization Links which the
authorization result is aggregated using a specified binary operator (OR/AND).
While most applications generally utilize only one link, this is primarily
related to the design.  

If you are building an administrative end for your application, you may end up
using a different Authentication Service, therefore additional Authorization Link
and ACL. Alternatively, you may use the same Authentication Serivce and assign
each identity with a user or admin role/permission (or the like...)

## Redirection of unauthorized users
We perform authorization during the MVC lifecycle and prior dispatching requests for 
restful controllers. In Laminas MVC architecture, the target method to call 
for an action-based controller is retrievable prior dispatch by listening on
the MVC dispatch event. For restful controllers, however, the target method cannot
be retrieved prior dispatch. We provide
[EigenRestfulController](src/Controller/EigenRestfulController.php) as an ad-hoc
solution. Simply extend your controller from `EigenRestfulController` instead of
`AbstractRestfulController`.

During authorization, whether on the MVC-level or executed by `EigenRestfulController`,
we redirect unauthorized requests to a configured route.

## Configuration
See [config.md](docs/config.md)

## How to help ?
It would be nice to star this repository. It would help attract more
contributors, and makes me happy to receive a star ! :)