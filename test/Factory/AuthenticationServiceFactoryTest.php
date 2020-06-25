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

use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Storage\Session;
use Laminas\Session\SessionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use AliChry\Laminas\Authorization\Factory\AuthenticationServiceFactory as Factory;
use Interop\Container\ContainerInterface;

class AuthenticationServiceFactoryTest extends TestCase
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
     * @var SessionManager|MockObject
     */
    private $mockSessionManager;

    public function setUp(): void
    {
        $this->factory = new Factory();
        $this->mockContainer = $this->createMock(ContainerInterface::class);
    }

    public function testInvocationWithNullOptions()
    {
        $this->mockContainer->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValueMap(
                [
                    [SessionManager::class, $this->mockSessionManager],
                    [null, null]
                ]
            ));
        $service = $this->invokeFactory();
        $this->assertEquals(
            new AuthenticationService(
                new Session(
                    null,
                    null,
                    $this->mockSessionManager
                )
            ),
            $service
        );
    }

    public function testInvocation()
    {
        $sessionName = 'session';
        $sessionContainer = 'sessionContainer';
        $mockAdapter = $this->createMock(AdapterInterface::class);

        $this->mockContainer->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValueMap(
                [
                    [SessionManager::class, $this->mockSessionManager],
                    ['AuthenticationAdapter', $mockAdapter]
                ]
            ));
        $options = [
            Factory::OPTION_AUTH_ADAPTER => 'AuthenticationAdapter',
            Factory::OPTION_SESSION => $sessionName,
            Factory::OPTION_SESSION_CONTAINER => $sessionContainer
        ];
        $service = $this->invokeFactory($options);
        $this->assertEquals(
            new AuthenticationService(
                new Session(
                    $sessionContainer,
                    $sessionName,
                    $this->mockSessionManager
                ),
                $mockAdapter
            ),
            $service
        );
    }

    private function invokeFactory($options = null)
    {
        $factory = $this->factory;
        return $factory($this->mockContainer, null, $options);
    }

}