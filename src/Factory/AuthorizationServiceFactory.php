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
 * Date: 2020-06-06
 * Time: 17:04
 */

namespace AliChry\Laminas\Authorization\Factory;

use AliChry\Laminas\Authorization\AuthorizationService;
use AliChry\Laminas\Authorization\AuthorizationChain;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\Config\Config;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceManager;

class AuthorizationServiceFactory implements FactoryInterface
{
    const CONFIG_AUTHORIZATION = 'authorization';

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
        $config = $container->get('config');
        $serviceManager = $container->get(ServiceManager::class);
        $chainOptions = $config[self::CONFIG_AUTHORIZATION] ?? null;
        if (null === $chainOptions) {
            throw new ServiceNotCreatedException(
                sprintf(
                    'Expecting configuration key "%s" to be set, got unset',
                    self::CONFIG_AUTHORIZATION
                )
            );
        }
        try {
            return new AuthorizationService(
                $serviceManager->build(
                    AuthorizationChain::class,
                    $chainOptions
                )
            );
        } catch (\Throwable $e) {
            throw new ServiceNotCreatedException(
                sprintf(
                    'Unable to create AuthorizationService, exception thrown '
                    . 'with message: %s',
                    $e->getMessage()
                ),
                1,
                $e
            );
        }
    }
}
