# Laminas Authorization
Out of the box, laminas provide an authentication module to authenticate users and save their identities in a session storage. This module aims to provide authorization and transparent redirection of unauthorized users with the definition of mutliple authorization links forming an authorization chain.

## Installation
Install using composer, run

```
$ composer require alichry/laminas-authorization
```

## Prerequisites
While this module provides authorization services, you are still required to write your own authentication adapater that implements `AuthenticationAdapterInterface` from `laminas/laminas-authentication`.  
Depending on what sort of Authentication Service you are intending to use, you need to configure your session container (storage) that you define in this module's confiiguration, e.g. if you specify the value `LoginAuth` for the `session_container` key under this module's configuration, you need to specify that container name in the `session_containers` key in laminas configuration (preferably in the global configuration).  
In other words, if you reference any session container name, make sure to reference it under the `session_containers` key in laminas config:

```
# global.php
	 ...
    'session_containers' => [
        'LoginAuth'
    ],
    ...
```

## Authorization Link
An Authorization Link is built around entities that can infer whether an (authenticated) identity is authorized to access a controller or a controller's action.  
This is achieved by relying on `AuthenticationService` (for authentication status) and  a `ListAdapterInterface` from `alichry/laminas-accesscontrol` (currently only `AccessControlList`) that defines a list of propertities that dictates the roles, permissions and the accessibility or authorization level of each controller or controller's action.
Eventually, an Authorization Link can imply whether an (authenticated) identity is granted access to a certain resource (controller/action) and will return the result.

## Authorization Chain
An Authorization Chain is built from one or more Authorization Links which the authorized result is aggregated using a specified binary operator (OR/AND). While most applications generally utilizes only one link, this is primarily related to the design.  
If you are building an administrative end for your application, you may end up using a different Authentication Service, therefore additional Authorization Link and ACL. Alternatively, you may use the same Authentication Serivce and assign each identity with a user or admin role/permission (or the like...)

## Redirection of unauthorized users
This module listens to the MVC Dispatch Event that is triggered once the MVC assigns the target controller and possibility the action too (after routing). The listener is called before the target controller since we set a higher priority, and we can retrieve the target controller/action and redirect the user to a specified route if the identity is not authorized to access the controller/action.

## Access control list configuration
ACL configuration defines a list of controller/action entries that can indicate the authorization level it requires. Currently there exists the following statuses or levels:  

- Allow all (public)
- Reject all
- Authenticated only
- A defined role or a permission the referenced identity must be granted to 

Static functions provided by `AccessControlList` can aid in building your configuration and ensures compatibility with future versions. See [alichry/laminas-accesscontrol](https://github.com/alichry/laminas-accesscontrol)

## Sample configuration
```
# see config/config.module.php.dist for a more commented version
<?php

use AliChry\Laminas\Authorization\Factory\AuthenticationServiceFactory;
use AliChry\Laminas\AccessControl\AccessControlList as ACL;
use AliChry\Laminas\AccessControl\Factory\AccessControlListFactory;
use AliChry\Laminas\Authorization\AuthorizationLink;
use AliChry\Laminas\Authorization\AuthorizationChain;
use Laminas\Authentication\AuthenticationService;

return [
    'service_manager' => [
        'factories' => [
            ACL::class => AccessControlListFactory::class,
            AuthenticationService::class => AuthenticationServiceFactory::class,
            // The Auth adapter that implements AuthenticationAdapterInterface
            // from laminas/laminas-authentication
            YourAuthAdapter::class => YourAuthAdapterFactory::class,
            AuthorizationLink::class => AuthenticationLinkFactory::class,
            AuthorizationChain::class => AuthenticationChainFactory::class,
        ],
    ],
    'authorization' => [
        'operator' => AuthorizationChain::OPERATOR_AND,
        'chain' => [
            'global' => [
                'authentication_service' => [
                    'service' => AuthenticationService::class,
                    'options' => [
                        'session' => 'session',
                        'session_container' => 'LoginAuth',
                        'adapter' => YourAuthenticationAdapter::class
                    ]
                ],
                'access_control' => [
                    'service' => ACL::class,
                    'options' => [
                        'mode' => ACL::MODE_STRICT,
                        'controllers' => [
                            YourController::class => ACL::ACCESS_ALL,
                            YourAuthenticatedController::class =>
                                ACL::ACCESS_AUTHENTICATED_ONLY,
                            YourRejectedController::class => ACL::ACCESS_REJECT_ALL,
                            AdminRoleController::class => ACL::role('admin'),
                            AdminPermController::class => ACL::permission('admin'),
                            YourMultipleAccessController::class => [
                                // actions
                                'public' => ACL::ACCESS_ALL,
                                'authenticated' => ACL::ACCESS_AUTHENTICATED_ONLY,
                                'rejectAll' => ACL::ACCESS_REJECT_ALL,
                                'adminRole' => ACL::role('admin'),
                                'adminPerm' => ACL::perm('admin-perm'),
                                'specialPerm' => ACL::permission('special')
                            ]
                        ],
                        'identities' => [
                            'user1@test.com' => [
                                'roles' => [
                                    'admin'
                                ],
                                'permissions' => [
                                    'special-perm'
                                ]
                            ],
                            'user2@test.com' => [
                                'permissions' => [
                                    'admin-perm'
                                ]
                            ]
                        ],
                        'roles' => [
                            'admin' => [
                                'admin-perm'
                            ]
                        ],
                        'permissions' => [
                            'admin-perm',
                            'special-perm'
                        ]
                    ]
                ],
            ],
        ],
    ],
];
```

## TODO
- Allow different response behaviors other than simply redirecting the user to a different route, possibly dropping a json response
- Test factories
- Release