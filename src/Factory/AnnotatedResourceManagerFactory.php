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

use AliChry\Laminas\AccessControl\AccessControlException;
use AliChry\Laminas\Authorization\AuthorizationException;
use AliChry\Laminas\Authorization\Resource\AnnotatedResourceManager;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Throwable;

class AnnotatedResourceManagerFactory implements FactoryInterface
{
    const OPTION_MODE = 'mode';
    const OPTION_POLICY = 'policy';
    const OPTION_CACHE = 'cache';

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return AnnotatedResourceManager
     * @throws ServiceNotCreatedException
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (null === $options) {
            $options = [];
        }
        $modeOption = $options[self::OPTION_MODE] ?? null;
        $policyOption = $options[self::OPTION_POLICY] ?? null;
        $cacheOption = $options[self::OPTION_CACHE] ?? 'array';
        $cacheProvider = $container->get('doctrine.cache.' . $cacheOption);
        $reader = new CachedReader(
            new AnnotationReader(),
            $cacheProvider
        );
        try {
            return new AnnotatedResourceManager(
                $modeOption,
                $policyOption,
                $reader
            );
        } catch (Throwable $e) {
            // >>> throw it back <<<
            throw new ServiceNotCreatedException(
                sprintf(
                    'Unable to create an instance of AnnotatedResourceManager, '
                    . 'exception thrown with message: %s',
                    $e->getMessage()
                ),
                1,
                $e
            );
        }
    }
}