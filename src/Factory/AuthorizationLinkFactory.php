<?php
/**
 * Copyright (c) 2019, 2020 Ali Cherry
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * Date: 2020-05-28
 * Time: 13:39
 */

namespace AliChry\Laminas\Authorization\Factory;

use AliChry\Laminas\Authorization\AuthorizationLink;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceManager;

class AuthorizationLinkFactory implements FactoryInterface
{
    const OPTION_NAME = 'name';
    const OPTION_AUTH_SERVICE = 'authentication_service';
    const OPTION_ACCESS_CONTROL_LIST = 'access_control_list';
    const OPTION_REDIRECT_ROUTE = 'redirect_route';

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (null === $options) {
            throw new ServiceNotCreatedException(
                'Expecting options to be set, got null.'
            );
        }
        $name = $options[self::OPTION_NAME] ?? null;
        $authenticationServiceOption = $options[self::OPTION_AUTH_SERVICE] ?? null;
        $aclOption = $options[self::OPTION_ACCESS_CONTROL_LIST] ?? null;
        $redirectRoute = $options[self::OPTION_REDIRECT_ROUTE] ?? null;
        if (null === $name) {
            throw new ServiceNotCreatedException(
                sprintf(
                    'Expecting key "%s" to be set in options, got undefined.',
                    self::OPTION_NAME
                )
            );
        }
        if (null === $authenticationServiceOption) {
            throw new ServiceNotCreatedException(
                sprintf(
                    'Expecting key "%s" to be set in options, got undefined.',
                    self::OPTION_AUTH_SERVICE
                )
            );
        }
        if (null === $aclOption) {
            throw new ServiceNotCreatedException(
                sprintf(
                    'Expecting key "%s" to be set in options, got undefined.',
                    self::OPTION_ACCESS_CONTROL_LIST
                )
            );
        }
        if (null === $redirectRoute) {
            throw new ServiceNotCreatedException(
                sprintf(
                    'Expecting key "%s" to be set in options, got undefined.',
                    self::OPTION_REDIRECT_ROUTE
                )
            );
        }
        $serviceManager = $container->get(ServiceManager::class);
        if (! is_array($authenticationServiceOption)) {
            $authenticationService = $serviceManager->get(
                $authenticationServiceOption
            );
        } else {
            $authenticationService = $serviceManager->build(
                $authenticationServiceOption['service'] ?? null,
                $authenticationServiceOption['options'] ?? null
            );
        }

        $aclPrefix = 'alichry.access_control.list.';
        if (! is_array($aclOption)) {
            $accessControlList = $serviceManager->get(
                $aclPrefix . $aclOption
            );
        } else {
            $service = $aclOption['service'] ?? null;
            if ($service) {
                $service = $aclPrefix . $service;
            }
            $accessControlList = $serviceManager->build(
                $service,
                $aclOption['options'] ?? null
            );
        }

        return new AuthorizationLink(
            $name,
            $authenticationService,
            $accessControlList,
            $redirectRoute
        );
    }
}
