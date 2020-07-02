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

namespace AliChry\Laminas\Authorization\Test\Resource;

use AliChry\Laminas\Authorization\AuthorizationChain;
use AliChry\Laminas\Authorization\AuthorizationException;
use AliChry\Laminas\Authorization\AuthorizationLink;
use AliChry\Laminas\Authorization\AuthorizationService;
use AliChry\Laminas\Authorization\Factory\AuthorizationChainFactory;
use AliChry\Laminas\Authorization\Factory\AuthorizationLinkFactory;
use AliChry\Laminas\Authorization\Factory\AuthorizationServiceFactory;
use AliChry\Laminas\Authorization\Module;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\Application;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    public function testGetConfig()
    {
        $module = new Module();
        $this->assertSame(
            $module->getConfig(),
            include __DIR__ . '/../config/module.config.php'
        );
    }

    public function testConfig()
    {
        $module = new Module();
        $config = $module->getConfig();
        $serviceManagerConfig = $config['service_manager'] ?? null;
        $this->assertTrue(
            isset($serviceManagerConfig),
            'service_manager key is not set in config'
        );
        $this->assertTrue(
            is_array($serviceManagerConfig),
            'service_manager key is not an array in config'
        );
        $factoriesConfig = $serviceManagerConfig['factories'] ?? null;
        $this->assertTrue(
            isset($factoriesConfig),
            'factories key is not set in config'
        );
        $this->assertTrue(
            is_array($factoriesConfig),
            'factories key is not an array in config'
        );
        $factories = [
            AuthorizationService::class => AuthorizationServiceFactory::class,
            AuthorizationLink::class =>
                AuthorizationLinkFactory::class,
            AuthorizationChain::class => AuthorizationChainFactory::class
        ];
        foreach ($factories as $service => $expectedFactory) {
            $factory = $factoriesConfig[$service] ?? null;
            $this->assertTrue(
                isset($factory),
                sprintf(
                    '%s service is not set in factories config',
                    $service
                )
            );
            $this->assertTrue(
                class_exists($factory),
                sprintf(
                    'factory %s is not found',
                    $factory
                )
            );
            $this->assertSame(
                $expectedFactory,
                $factory
            );
        }
    }

    public function testOnBootstrapWithBadApplicationObject()
    {
        $module = new Module();
        $mockEvent = $this->createMock(MvcEvent::class);
        $mockBadApplication = new \stdClass();
        $mockEvent->expects($this->once())
            ->method('getParam')
            ->with($this->identicalTo('application'))
            ->willReturn($mockBadApplication);

        $this->expectException(AuthorizationException::class);

        $module->onBootstrap($mockEvent);
    }

    public function testOnBootstrapWithBadApplicationScalar()
    {
        $module = new Module();
        $mockEvent = $this->createMock(MvcEvent::class);
        $mockBadApplication = 2;
        $mockEvent->expects($this->once())
            ->method('getParam')
            ->with($this->identicalTo('application'))
            ->willReturn($mockBadApplication);

        $this->expectException(AuthorizationException::class);

        $module->onBootstrap($mockEvent);
    }

    public function testOnBootstrap()
    {
        $module = new Module();
        $mockEvent = $this->createMock(MvcEvent::class);
        $mockApplication = $this->createMock(Application::class);
        $mockServiceManager = $this->createMock(ServiceManager::class);
        $mockEventManager = $this->createMock(EventManagerInterface::class);
        $mockSharedEventManager = $this->createMock(
            SharedEventManagerInterface::class
        );
        $mockAuthorizationService = $this->createMock(
            AuthorizationService::class
        );

        $mockEvent->expects($this->once())
            ->method('getParam')
            ->with($this->identicalTo('application'))
            ->willReturn($mockApplication);
        $mockApplication->expects($this->once())
            ->method('getServiceManager')
            ->willReturn($mockServiceManager);
        $mockApplication->expects($this->once())
            ->method('getEventManager')
            ->willReturn($mockEventManager);
        $mockEventManager->expects($this->once())
            ->method('getSharedManager')
            ->willReturn($mockSharedEventManager);
        $mockServiceManager->expects($this->once())
            ->method('get')
            ->with($this->identicalTo(AuthorizationService::class))
            ->willReturn($mockAuthorizationService);
        $mockSharedEventManager->expects($this->once())
            ->method('attach')
            ->with(
                $this->identicalTo(AbstractActionController::class),
                $this->identicalTo(MvcEvent::EVENT_DISPATCH),
                $this->identicalTo([$mockAuthorizationService, 'onDispatch']),
                Module::DISPATCH_PRIORITY
            );

        $module->onBootstrap($mockEvent);
    }
}
