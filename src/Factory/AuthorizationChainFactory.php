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
 * Time: 10:29
 */

namespace AliChry\Laminas\Authorization\Factory;

use AliChry\Laminas\Authorization\AuthorizationChain;
use AliChry\Laminas\Authorization\AuthorizationException;
use AliChry\Laminas\Authorization\AuthorizationLink;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceManager;
use Throwable;

class AuthorizationChainFactory implements FactoryInterface
{
    const OPTION_OPERATOR = 'operator';
    const OPTION_CHAIN = 'chain';

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
                'Expecting options to be non-null, got null.'
            );
        }
        $operator = $options[self::OPTION_OPERATOR] ?? null;
        if (null === $operator) {
            throw new ServiceNotCreatedException(
                sprintf(
                    'Expecting key "%s" to be set in under configuration root, '
                    . 'got undefined',
                    self::OPTION_OPERATOR
                )
            );
        }
        $chain = $options[self::OPTION_CHAIN] ?? null;
        if (null === $chain) {
            throw new ServiceNotCreatedException(
                sprintf(
                    'Expecting key "%s" to be set under configuration root, '
                    . 'got undefined',
                    self::OPTION_CHAIN
                )
            );
        }
        try {
            $authorizationChain = new AuthorizationChain($operator);
            $serviceManager = $container->get(ServiceManager::class);
            foreach ($chain as $linkName => $linkOptions) {
                $authorizationChain->addLink(
                    $serviceManager->build(
                        AuthorizationLink::class,
                        array_merge(
                            $linkOptions,
                            [
                                AuthorizationLinkFactory::OPTION_NAME => $linkName
                            ]
                        )
                    )
                );
            }
            return $authorizationChain;
        } catch (Throwable $e) {
            throw new ServiceNotCreatedException(
                sprintf(
                    'Unable to create AuthorizationChain, exception thrown '
                    . 'with message: %s',
                    $e->getMessage()
                ),
                1,
                $e
            );
        }
    }
}
