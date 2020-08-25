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
use AliChry\Laminas\Authorization\AuthorizationLink;
use AliChry\Laminas\Authorization\Factory\AuthorizationChainFactory as Factory;
use AliChry\Laminas\Authorization\Factory\AuthorizationLinkFactory;
use Interop\Container\Exception\ContainerException;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Interop\Container\ContainerInterface;
use Throwable;

class AuthorizationChainFactoryTest extends TestCase
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

    public function testInvocationWithNullOption()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->invokeFactory();
    }

    /**
     * @depends testInvocationWithNullOption
     */
    public function testInvocationWithNullOperator()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->invokeFactory([]);
    }

    /**
     * @depends testInvocationWithNullOperator
     * @throws ContainerException
     */
    public function testInvocationWithNullChain()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->invokeFactory(
            [
                Factory::OPTION_OPERATOR => AuthorizationChain::OPERATOR_OR
            ]
        );
    }

    /**
     * @depends testInvocationWithNullChain
     */
    public function testInvocationWrapsExceptions()
    {
        $this->mockContainer->expects($this->once())
            ->method('get')
            ->with($this->identicalTo(ServiceManager::class))
            ->willReturn($this->mockServiceManager);

        $chainOption = [
            'link' => [
                'option' => 'value'
            ]
        ];

        // force service manager to throw an exception and see if it gets
        // wrapped and rethrown
        $mockException = $this->createMock(Throwable::class);
        $this->mockServiceManager->expects($this->once())
            ->method('build')
            ->with(
                $this->identicalTo(AuthorizationLink::class),
                $this->identicalTo(\array_merge(
                    array_values($chainOption)[0],
                    [
                        AuthorizationLinkFactory::OPTION_NAME =>
                            array_keys($chainOption)[0]
                    ]
                ))
            )->willThrowException($mockException);
        $this->expectException(ServiceNotCreatedException::class);
        try {
            $this->invokeFactory(
                [
                    Factory::OPTION_OPERATOR => AuthorizationChain::OPERATOR_OR,
                    Factory::OPTION_CHAIN => $chainOption
                ]
            );
        } catch (\Throwable $e) {
            $this->assertSame(
                $mockException,
                $e->getPrevious()
            );
            // throw back the exception so PHPUnit can verify its expectation
            throw $e;
        }
    }

    /**
     * @dataProvider chainOptionProvider
     * @param $chainOption array
     * @param $links array|AuthorizationLink[]
     */
    public function testInvocation($chainOption, $links)
    {
        /*
        /* the below is replaced in getReturnValueMap
        $this->mockContainer->expects($this->once())
            ->method('get')
            ->with($this->identicalTo(ServiceManager::class))
            ->willReturn($this->mockServiceManager);
        */

        $buildReturnValueMap = [];
        $getReturnValueMap = [
            [
                ServiceManager::class,
                $this->mockServiceManager
            ]
        ];
        foreach ($chainOption as $linkName => $linkOptions) {
            if (! is_array($linkOptions)) {
                $getReturnValueMap[] = [
                    $linkOptions,
                    $links[$linkName],
                ];
                continue;
            }
            $buildReturnValueMap[] = [
                $linkOptions['service'] ?? AuthorizationLink::class,
                array_merge(
                    $linkOptions,
                    [
                        AuthorizationLinkFactory::OPTION_NAME => $linkName
                    ]
                ),
                $links[$linkName]
            ];
        }
        $this->mockServiceManager
            ->expects($this->exactly(count($buildReturnValueMap)))
            ->method('build')
            ->will($this->returnValueMap($buildReturnValueMap));
        $this->mockContainer
            ->expects($this->exactly(count($getReturnValueMap)))
            ->method('get')
            ->will($this->returnValueMap($getReturnValueMap));

        $chain = $this->invokeFactory([
            Factory::OPTION_OPERATOR => AuthorizationChain::OPERATOR_OR,
            Factory::OPTION_CHAIN => $chainOption
        ]);
        $this->assertSame(
            $chain->getOperator(),
            AuthorizationChain::OPERATOR_OR
        );
        $this->assertEquals(
            count($links),
            count($chain->getLinks())
        );
        foreach ($links as $index => $link) {
            $authLink = $chain->getLink($index);
            $this->assertEquals(
                $authLink,
                $link
            );
        }
        $this->assertEquals(
            new AuthorizationChain(
                AuthorizationChain::OPERATOR_OR,
                $links
            ),
            $chain
        );
    }

    public function chainOptionProvider()
    {
        $mockLink = $this->createMock(AuthorizationLink::class);
        $mockLink1 = $this->createMock(AuthorizationLink::class);
        $mockLink2 = $this->createMock(AuthorizationLink::class);
        $mockLink->method('getName')
            ->willReturn('link');
        $mockLink1->method('getName')
            ->willReturn('link1');
        $mockLink2->method('getName')
            ->willReturn('link2');
        return [
            [
                [
                    'link' => [
                        'option' => 'value'
                    ]
                ],
                [
                    'link' => $mockLink
                ]
            ],
            [
                [
                    'link1' => [
                        'service' => 'SomeService',
                        'option1' => 'value1'
                    ],
                    'link2' => 'SomeService'
                ],
                [
                    'link1' => $mockLink1,
                    'link2' => $mockLink2
                ]
            ]
        ];

    }

    /**
     * @param null $options
     * @return AuthorizationChain|object
     * @throws ContainerException
     */
    private function invokeFactory($options = null)
    {
        $factory = $this->factory;
        return $factory($this->mockContainer, null, $options);
    }

}