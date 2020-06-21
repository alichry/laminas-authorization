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

use AliChry\Laminas\AccessControl\AccessControlListInterface;
use AliChry\Laminas\Authorization\AuthorizationLink;
use AliChry\Laminas\Authorization\Factory\AuthorizationLinkFactory as Factory;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\Authentication\AuthenticationService;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthorizationLinkFactoryTest extends TestCase
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

    public function testInvocationWithNullOptions()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->invokeFactory();
    }

    /**
     * @depends testInvocationWithNullOptions
     */
    public function testInvocationWithNullName()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->invokeFactory([]);
    }

    /**
     * @depends testInvocationWithNullName
     */
    public function testInvocationWithNullAuthService()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->invokeFactory(
            [
                Factory::OPTION_NAME => 'link-name'
            ]
        );
    }

    /**
     * @depends testInvocationWithNullAuthService
     */
    public function testInvocationWithNullAccessControl()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->invokeFactory(
            [
                Factory::OPTION_NAME => 'link-name',
                Factory::OPTION_AUTH_SERVICE => 'Auth'
            ]
        );
    }

    /**
     * @depends testInvocationWithNullAccessControl
     */
    public function testInvocationWithNullRedirectRoute()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->invokeFactory(
            [
                Factory::OPTION_NAME => 'link-name',
                Factory::OPTION_AUTH_SERVICE => 'Auth',
                Factory::OPTION_ACCESS_CONTROL => 'ACL'
            ]
        );
    }

    /**
     * @depends      testInvocationWithNullRedirectRoute
     * @dataProvider authServiceAndAccessControlOptionProvider
     * @param string|array $authServiceOption
     * @param string|array $aclOption
     * @throws ContainerException
     */
    public function testInvocation(
        $authServiceOption,
        $aclOption
    )
    {
        $linkName = 'link-name';
        $redirectRoute = 'redirect';

        $mockAuthService = $this->createMock(AuthenticationService::class);
        $mockACL = $this->createMock(AccessControlListInterface::class);
        $mockAuthServiceCustom = $this->createMock(AuthenticationService::class);
        $mockACLCustom = $this->createMock(AccessControlListInterface::class);

        $expectedAuthService = null;
        $expectedACL = null;

        $getValueMap = [];
        $buildValueMap = [];
        if (! is_array($authServiceOption)) {
            $getValueMap[] = [$authServiceOption, $mockAuthService];
            $expectedAuthService = $mockAuthService;
        } else {
            $buildValueMap[] = [
                $authServiceOption['service'],
                $authServiceOption['options'],
                $mockAuthServiceCustom
            ];
            $expectedAuthService = $mockAuthServiceCustom;
        }
        if (! is_array($aclOption)) {
            $getValueMap[] = [$aclOption, $mockACL];
            $expectedACL = $mockACL;
        } else {
            $buildValueMap[] = [
                $aclOption['service'],
                $aclOption['options'],
                $mockACLCustom
            ];
            $expectedACL = $mockACLCustom;
        }

        $this->mockContainer->expects($this->once())
            ->method('get')
            ->with($this->identicalTo(ServiceManager::class))
            ->willReturn($this->mockServiceManager);

        $this->mockServiceManager->expects($this->exactly(count($getValueMap)))
            ->method('get')
            ->will($this->returnValueMap($getValueMap));
        $this->mockServiceManager->expects($this->exactly(count($buildValueMap)))
            ->method('build')
            ->will($this->returnValueMap($buildValueMap));

        $link = $this->invokeFactory(
            [
                Factory::OPTION_NAME => $linkName,
                Factory::OPTION_AUTH_SERVICE => $authServiceOption,
                Factory::OPTION_ACCESS_CONTROL => $aclOption,
                Factory::OPTION_REDIRECT_ROUTE => $redirectRoute
            ]
        );
        $this->assertEquals(
            new AuthorizationLink(
                $linkName,
                $expectedAuthService,
                $expectedACL,
                $redirectRoute
            ),
            $link
        );
    }

    public function authServiceAndAccessControlOptionProvider()
    {
        $authServiceOptions = [
            'Auth',
            [
                'service' => 'Auth',
                'options' => [
                    'test_option' => 'test_value'
                ]
            ]
        ];
        $aclOptions = [
            'ACL',
            [
                'service' => 'ACL',
                'options' => [
                    'test_option' => 'test_value'
                ]
            ]
        ];
        $data = [];
        foreach ($authServiceOptions as $authServiceOption) {
            foreach ($aclOptions as $aclOption) {
                $data[] = [
                    $authServiceOption,
                    $aclOption
                ];
            }
        }
        return $data;
    }

    /**
     * @param null $options
     * @return AuthorizationLink|object
     * @throws ContainerException
     */
    private function invokeFactory($options = null)
    {
        $factory = $this->factory;
        return $factory($this->mockContainer, null, $options);
    }

    private function registerServiceManagerRetrievalCheck()
    {
        $this->mockContainer->expects($this->once())
            ->with($this->identicalTo(ServiceManager::class))
            ->willReturn($this->mockServiceManager);
    }
}