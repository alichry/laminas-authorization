## Access control list configuration
[alichry/laminas-accesscontrol](https://github.com/alichry/laminas-accesscontrol)
provides different implementations of an `AccessControlListInterface`. 
`ArrayAccessControlList` lets you define your list of controller/methods
authorization policies using configuration while `IdentityAccessControlList`
depends on a `ResourceManagerInterface` and relies on passed identity objects
implementing `IdentityInterface`. See
[alichry/laminas-accesscontrol](https://github.com/alichry/laminas-accesscontrol)
for configuration.

## Authorization configuration
To setup this module, you need register `AliChry\Laminas\AccessControl`
and `AliChry\Laminas\Authorization` in config/modules.config.php

```php
# config/modules.config.php
<?php
return [
    ...
    'AliChry\Laminas\AccessControl',
    'AliChry\Laminas\Authorization',
    ...
];
```

Additionally, you need to register factories for your provided authentication
adapter(s) and authentication service(s). If you have your local authentication
module, then register it under the module's config. If you've configured your
authentication service using Doctrine, you can omit this.

```php
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

Finally, all is left is configuring this module. The configuration takes place
under `alichry`.`authorization` keys in the configuration. You can add it in the 
global configuration (config/autoload/global.php). As we mentioned earlier,
this module provides the ability span multiple Authorization Links forming an
Authorization Chain.  

The first configuration option is the `operator` key that will specify the binary
operator to aggregate the results from multiple Authorization Links.  

In the configuration, the `chain` key should correspond to an associative array
where each item in the array consists of link options mapped by a link name as key.
The link options are pertaining the redirect route to utilize during filtering
unauthorized users, the Authentication Service and the Access Control List instance
to consult.  Under each link, there exists 3 options:  

- Authentication Service: `authentication_service` 
- Redirect route: `redirect_route`
- Access Control List: `access_control_list`

The `authentication_service` option should either provide a service name
(usually a FQCN such as `AuthenticationService::class`), or an array that will
additionally allow providing custom build options to the
AuthenticationServiceFactory. If you have your own factory for the Authentication
Service that does not rely on build option, you can use the former which is
preferable. Otherwise, if you don't have a factory for the standard
AuthenticationService from `laminas/authentication` or your registered factory
requires build options, you can provide build options under 
`options`.`authentication_service` as an array, consequently, there should exist
2 keys under it:  

- Service name: `service` -- typically a FQCN
- Build options: `options` -- an array of options that are passed to the factory.
For our provided AuthenticationServiceFactory options, see the sample
configuration or inspect the factory
[AuthenticationServiceFactory](https://github.com/alichry/laminas-authorization/blob/master/src/Factory/AuthenticationServiceFactory.php#L41).

Whether you decide to pass build options to the factory or not, the registered
service name should be retrievable by the service manager. If you're using doctrine
for authentication service, you can use `doctrine.authenticationservice.$key`

The `redirect_route` option specifies the route to use when redirecting an
unauthorized user. This is typically the login route. Currently, there is no
option to specify route options, only a literal route, but future milestones
should mention that feature.  

The `access_control_list` option is indicates the ACL to use. Access control lists are defined under `alichry.access_control.list`, see [alichry/laminas-accesscontrol](https://github.com/alichry/laminas-accesscontrol). The value should be the access control list identifier. By default, this module configures an `IdentityAccessControlList` identified by `identity`. If you wish to use an `ArrayAccessControlList`, you can configure your list in `alichry.access_control.list` and use its name as the identifier.  

See sample configuration under `config/`