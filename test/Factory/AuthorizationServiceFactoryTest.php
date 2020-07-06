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
 */
declare(strict_types=1);

namespace AliChry\Laminas\Authorization\Test\Factory;

use AliChry\Laminas\Authorization\AuthorizationChain;
use AliChry\Laminas\Authorization\AuthorizationService;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use AliChry\Laminas\Authorization\Factory\AuthorizationServiceFactory as Factory;
class AuthorizationServiceFactoryTest extends TestCase
{
    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var ContainerInterface|MockObject
     */
    private $mockContainer;

    /**
     * @var ServiceManager|MockObject
     */
    private $mockServiceManager;

    public function setUp(): void
    {
        $this->factory = new Factory();
        $this->mockContainer = $this->createMock(ContainerInterface::class);
        $this->mockServiceManager = $this->createMock(ServiceManager::class);
    }

    public function testUnsetAuthorizationKeyFromConfig()
    {
        $this->mockContainer->expects($this->exactly(2))
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['config', []],
                        [ServiceManager::class, $this->mockServiceManager]
                    ]
                )
            );
        $this->expectException(ServiceNotCreatedException::class);
        $this->invokeFactory();
    }

    public function testInvocationWrapsException()
    {
        $mockException = $this->createMock(\Throwable::class);
        $this->expectException(ServiceNotCreatedException::class);
        $this->mockContainer->expects($this->exactly(2))
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'config',
                            [
                                'alichry' => [
                                    Factory::CONFIG_AUTHORIZATION => [
                                        'test'
                                    ]
                                ]
                            ]
                        ],
                        [ServiceManager::class, $this->mockServiceManager]
                    ]
                )
            );
        $this->mockServiceManager->expects($this->once())
            ->method('build')
            ->with(
                $this->identicalTo(AuthorizationChain::class),
                $this->identicalTo(
                    [
                        'test'
                    ]
                )
            )->willThrowException($mockException);
        $this->expectException(ServiceNotCreatedException::class);
        try {
            $this->invokeFactory();
        } catch (\Throwable $e) {
            $this->assertSame(
                $mockException,
                $e->getPrevious()
            );
            // throw back the exception so PHPUnit can verify its occurrence
            throw $e;
        }
    }

    public function testInvocation()
    {
        $mockChain = $this->createMock(AuthorizationChain::class);

        $this->mockContainer->expects($this->exactly(2))
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'config',
                            [
                                'alichry' => [
                                    Factory::CONFIG_AUTHORIZATION => [
                                        'test'
                                    ]
                                ]
                            ]
                        ],
                        [ServiceManager::class, $this->mockServiceManager]
                    ]
                )
            );
        $this->mockServiceManager->expects($this->once())
            ->method('build')
            ->with(
                $this->identicalTo(AuthorizationChain::class),
                $this->identicalTo(
                    [
                        'test'
                    ]
                )
            )->willReturn($mockChain);

        $service = $this->invokeFactory();
        $this->assertSame(
            $mockChain,
            $service->getAuthorizationChain()
        );
        $this->assertEquals(
            new AuthorizationService(
                $mockChain
            ),
            $service
        );
        //$this->assertTrue(false);
    }

    /**
     * @param null $options
     * @return AuthorizationService
     * @throws ContainerException
     */
    private function invokeFactory($options = null)
    {
        $factory = $this->factory;
        return $factory($this->mockContainer, null, $options);
    }

}