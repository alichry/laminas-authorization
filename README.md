# Laminas Authorization
Out of the box, laminas provide an authentication module to authenticate users and save their identities in a session storage. This module aims to provide authorization and transparent redirection of unauthorized users with the definition of mutliple authorization links forming an authorization chain.

## Installation
Install using composer, run

```
$ composer require alichry/laminas-authorization
```

## Prerequisites
While this module provides authorization services, you are still required to write your own authentication adapater that implements `AuthenticationAdapterInterface` from `laminas/laminas-authentication`.  
Depending on what sort of Authentication Service you are intending to use, you need to configure your session container (storage) that you define in this module's configuration, e.g. if you specify the value `LoginAuth` for the `session_container` key under this module's configuration, you need to specify that container name in the `session_containers` key in laminas configuration (preferably in the global configuration).  
In other words, if you reference any session container name, make sure to reference it under the `session_containers` key in laminas config:

```
# config/autoload/global.php
<?php
return [
    ...
    'session_containers' => [
        'LoginAuth'
    ],
    ...
];
```

## Authorization Link
An Authorization Link is built around entities that can infer whether an (authenticated) identity is authorized to access a controller or a controller's action.  
This is achieved by relying on `AuthenticationService` (for authentication status) and  a `ListAdapterInterface` from `alichry/laminas-accesscontrol` (currently only `AccessControlList`) that defines a list of propertities that dictates the roles, permissions and the accessibility or authorization level of each controller or controller's action.
Eventually, an Authorization Link can imply whether an (authenticated) identity is granted access to a certain resource (controller/action) and will return the result.

## Authorization Chain
An Authorization Chain is built from one or more Authorization Links which the authorized result is aggregated using a specified binary operator (OR/AND). While most applications generally utilizes only one link, this is primarily related to the design.  
If you are building an administrative end for your application, you may end up using a different Authentication Service, therefore additional Authorization Link and ACL. Alternatively, you may use the same Authentication Serivce and assign each identity with a user or admin role/permission (or the like...)

## Redirection of unauthorized users
This module listens to the MVC Dispatch Event that is triggered once the MVC application assigns the target controller and possibility the action too (after routing). The listener is called before the target controller since we set a higher priority, and we can retrieve the target controller/action and redirect the user to a specified route if the identity is not authorized to access the controller/action.

## Access control list configuration
ACL configuration defines a list of controller/action entries that can indicate the authorization level it requires. Currently, there exists the following statuses or levels:  

- Allow all (public)
- Reject all
- Authenticated only
- A defined role or a permission the referenced identity must be granted to 

Static functions provided by `AccessControlList` can aid in building your configuration and ensures compatibility with future versions. See [alichry/laminas-accesscontrol](https://github.com/alichry/laminas-accesscontrol)

## Configuration
To setup this module, you need register `AliChry\Laminas\AccessControl` and `AliChry\Laminas\Authorization` in `config/modules.config.php`

```
# config/modules.config.php
<?php
return [
    ...
    'AliChry\Laminas\AccessControl',
    'AliChry\Laminas\Authorization',
    ...
];
```

Additionally, you need to register factories for your provided authentication adapter(s) and authentication service(s). If you have your local authentication module, then register it under the module's config. 

```
# path/to/local/project/module/Authentication/module.config.php
<?php

use AliChry\Laminas\Authorization\Factory\AuthenticationServiceFactory;
// import your AuthenticationAdapter:
//use Authentication\Service\YourAuthenticationAdapter;
// import your AuthenticationService:
use Laminas\Authentication\AuthenticationService;

return [
    'service_manager' => [
        'factories' => [
            // Your auth adapter that implements AuthenticationAdapter
            // from laminas/authentication
            YourAuthenticationAdapter::class =>
                YourAuthenticationAdapterFactory::class,
            // Registering authentication service depends whether you want to
            // use AuthenticationService from `laminas/authentication` or your
            // own. In any case, you need to provide a factory for it.
            // This module provides a common factory that accepts session
            // options using build options, see `AuthenticationServiceFactory`
            AuthenticationService::class => AuthenticationServiceFactory::class
        ],
    ]
];
```

Finally, all is left is configuring this module. The configuration takes place under `authorization` key in the configuration. You can place that key in the global configuration (config/autoload/global.php). As we mentioned earlier, this module provides the ability span multiple Authorization Links forming an Authorization Chain. The first configuration option is the `operator` key that will specify the binary operator to aggregate the results from multiple Authorization Links.  

In the configuration, the `chain` key should correspond to an associative array where each item in the array consists of link options mapped by a link name key. The link options are pertaining the redirect route to utilize during filtering unauthorized users, the Authentication Service and the Access Control List adapter to consult.  Under each link, there exists 3 options:  

- Authentication Service: `authentication_service` 
- Redirect route: `redirect_route`
- Access Control List: `access_control`

The `authentication_service` option should either provide a service name (usually a FQCN such as `AuthenticationService::class`) or an array that will additionally allow providing custom build options to the AuthenticationServiceFactory. If you have your own factory for the Authentication Service that does not rely on build option, you can use the former which is preferrable. Otherwise, if you don't have a factory for the standard AuthenticationService from `laminas/authentication` or your registered factory requires build options, you need to provide build options under `options`. `authentication_service` is an array, consequently, there should exists 2 keys under it:

- Service name: `service_name` -- typically a FQCN
- Build options: `options` -- an array of options that are passed to the factory. For our provided AuthenticationServiceFactory options, see the sample configuration or inspect the factory [AuthenticationServiceFactory](https://github.com/alichry/laminas-authorization/blob/master/src/Factory/AuthenticationServiceFactory.php#L41).

The `redirect_route` option specifies the route to use when redirecting an unauthorized user. This is typically the login route. Currently, there is no option to specify route options, only a literal route, but future milestones should mention that feature.  

The `access_control` option is conceptually similar to `authentication_service` option. We need to specify which `ListAdapterInterface` to use and pass any build option if it is required. The Access Control module, from `alichry/laminas-accesscontrol`, provides a factory that builds an `AccessControlList` by relying on passed build options. 

A sample authorization configuration would be (from config/module.config.php.dist):

```
<?php
# config/autoload/global.php

use Laminas\Authentication\AuthenticationService;
use AliChry\Laminas\Authorization\AuthorizationChain;
use AliChry\Laminas\AccessControl\AccessControlList as ACL;

return [
    // ...
    'authorization' => [
        'operator' => AuthorizationChain::OPERATOR_OR,
        'chain' => [
            // link name: global
            'global' => [
                'redirect_route' => 'login',
                'authentication_service' => [
                    'service' => AuthenticationService::class,
                    'options' => [
                        'session' => 'session',
                        'session_container' => 'LoginAuth',
                        'adapter' => YourAuthenticationAdapter::class
                    ]
                ]
                'access_control' => [
                    'service' => ACL::class,
                    'options' => [
                        'mode' => ACL::MODE_STRICT,
                        'policy' => ACL::POLICY_REJECT,
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
                ]
            ]
        ]
    ]
    // ...
];
```

## TODO
- Allow different response behaviors other than simply redirecting the user to a different route, possibly dropping a json response
- Provide authorization plugin for controllers.