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
 * Date: 2019-08-21
 * Time: 12:33
 */

namespace AliChry\Laminas\Authorization\Factory;

use Interop\Container\ContainerInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Storage\Session;
use Laminas\Session\SessionManager;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AuthenticationServiceFactory implements FactoryInterface
{
    const OPTION_SESSION = 'session';
    const OPTION_SESSION_CONTAINER = 'session_container';
    const OPTION_AUTH_ADAPTER = 'adapter';

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
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (null === $options) {
            // options can be left null
            // the session (container) names will default, and
            // the authentication service accepts null as an adapter
            // only be set later on or passed during authentication
            $options = [];
        }
        $sessionManager = $container->get(SessionManager::class);
        $sessionName = $options[self::OPTION_SESSION] ?? null;
        $sessionContainerName = $options[self::OPTION_SESSION_CONTAINER] ?? null;
        $authAdapterName = $options[self::OPTION_AUTH_ADAPTER] ?? null;
        // will rely on default session (container) names if null
        $storage = new Session(
            $sessionContainerName,
            $sessionName,
            $sessionManager
        );
        $authAdapter = $container->get($authAdapterName);
        return new AuthenticationService($storage, $authAdapter);
    }
}
