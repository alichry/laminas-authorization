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
 * Date: 2020-06-02
 * Time: 12:40
 */
declare(strict_types=1);

namespace AliChry\Laminas\Authorization\Test;

use AliChry\Laminas\Authorization\AuthorizationChain;
use AliChry\Laminas\Authorization\AuthorizationException;
use AliChry\Laminas\Authorization\AuthorizationLink;
use AliChry\Laminas\Authorization\AuthorizationResult;
use AliChry\Laminas\Authorization\AuthorizationService;
use Laminas\EventManager\EventInterface;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\Controller\Plugin\Redirect;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use PHPUnit\Framework\TestCase;

class AuthorizationServiceTest extends TestCase
{
    public function testConstructorAuthorizationChain()
    {
        $chain = $this->createMock(AuthorizationChain::class);
        $service = new AuthorizationService($chain);
        $this->assertEquals(
            $chain,
            $service->getAuthorizationChain()
        );
    }

    public function testOnDispatchWithInvalidEvent()
    {
        $badMockEvent = $this->createMock(EventInterface::class);
        $mockChain = $this->createMock(AuthorizationChain::class);

        $service = new AuthorizationService($mockChain);
        $this->expectException(\TypeError::class);
        $service->onDispatch($badMockEvent);
    }

    public function testOnDispatchWithInvalidRouteMatch()
    {
        $mockEvent = $this->createMock(MvcEvent::class);
        $mockChain = $this->createMock(AuthorizationChain::class);
        $badMockRouteMatch = null;

        $mockEvent->expects($this->once())
            ->method('getRouteMatch')
            ->willReturn($badMockRouteMatch);

        $service = new AuthorizationService($mockChain);
        $this->expectException(AuthorizationException::class);
        $service->onDispatch($mockEvent);
    }

    public function testOnDispatchWithInvalidTarget()
    {
        $mockEvent = $this->createMock(MvcEvent::class);
        $mockChain = $this->createMock(AuthorizationChain::class);
        $mockRouteMatch = $this->createMock(RouteMatch::class);
        $badMockTarget = new \stdClass();

        $mockEvent->expects($this->once())
            ->method('getRouteMatch')
            ->willReturn($mockRouteMatch);

        $mockEvent->expects($this->once())
            ->method('getTarget')
            ->willReturn($badMockTarget);

        $service = new AuthorizationService($mockChain);
        $this->expectException(AuthorizationException::class);
        $service->onDispatch($mockEvent);
    }

    public function testIsAuthorized()
    {
        $mockResultSuccess = $this->createMock(AuthorizationResult::class);
        $mockResultFailed = $this->createMock(AuthorizationResult::class);

        $mockChainSuccess = $this->createMock(AuthorizationChain::class);
        $mockChainFailure = $this->createMock(AuthorizationChain::class);

        $mockResultFailed->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $mockResultSuccess->expects($this->once())
            ->method('isValid')
            ->willReturn(true);


        $mockChainSuccess->expects($this->once())
            ->method('isAuthorized')
            ->with('TestController', 'getCatAction')
            ->willReturn($mockResultSuccess);

        $mockChainFailure->expects($this->once())
            ->method('isAuthorized')
            ->with('TestController', 'getCatAction')
            ->willReturn($mockResultFailed);

        $serviceSuccess = new AuthorizationService($mockChainSuccess);
        $serviceFailure = new AuthorizationService($mockChainFailure);

        $resultSuccess = $serviceSuccess->isAuthorized(
            'TestController',
            'getCatAction'
        );
        $this->assertSame(
            true,
            $resultSuccess->isValid()
        );

        $resultFailed = $serviceFailure->isAuthorized(
            'TestController',
            'getCatAction'
        );
        $this->assertSame(
            false,
            $resultFailed->isValid()
        );

        $this->expectException(AuthorizationException::class);
        $serviceSuccess->isAuthorized();
    }

    public function testOnDispatch()
    {
        $mockController = $this->createMock(AbstractController::class);
        $mockRedirect = $this->createMock(Redirect::class);
        $mockMvcEventFull = $this->createMock(MvcEvent::class);
        $mockMvcEventNullAction = $this->createMock(MvcEvent::class);
        $mockMvcEventNullController = $this->createMock(MvcEvent::class);
        $mockRouteMatchFull = $this->createMock(RouteMatch::class);
        $mockRouteMatchNullAction = $this->createMock(RouteMatch::class);
        $mockRouteMatchNullController = $this->createMock(RouteMatch::class);
        $mockAuthChainFailure = $this->createMock(AuthorizationChain::class);
        $mockAuthChainSuccess = $this->createMock(AuthorizationChain::class);
        $mockRedirectPlugin = $this->createMock(Redirect::class);
        $mockRedirectPlugin->expects($this->any())
            ->method('toRoute')
            ->will($this->returnCallback(
                function ($route) {
                    $url = 'http://test.com/' . sha1($route);
                    $response = new Response();
                    $response->getHeaders()->addHeaderLine('Location', $url);
                    $response->setStatusCode(302);
                    return $response;
                }
            ));

        $mockAuthChainNullActionFailure = $this->createMock(
            AuthorizationChain::class
        );
        $mockAuthChainNullActionSuccess = $this->createMock(
            AuthorizationChain::class
        );
        $mockAuthChainNullController = $this->createMock(
            AuthorizationChain::class
        );
        $mockAuthLink = $this->createMock(AuthorizationLink::class);
        $mockAuthResultFailed = $this->createMock(AuthorizationResult::class);
        $mockAuthResultSuccess = $this->createMock(AuthorizationResult::class);

        // any number of calls since the same object is being used multiple
        // times
        $mockController->expects($this->any())
            ->method('plugin')
            ->with('redirect')
            ->willReturn($mockRedirectPlugin);

        // each MvcEvent will induce 2 call for route match,
        // full match is being used twice so that's 4
        $mockRouteMatchFull->expects($this->exactly(4))
            ->method('getParam')
            ->will($this->returnValueMap(
                [
                    ['controller', null, 'TestController'],
                    ['action', null, 'get-cat']
                ]
            ));
        // same idea, 4 calls
        $mockRouteMatchNullAction->expects($this->exactly(4))
            ->method('getParam')
            ->will($this->returnValueMap(
                [
                    ['controller', null, 'TestController'],
                    ['action', null, null]
                ]
            ));

        // only used in one onDispatch call, only two calls
        $mockRouteMatchNullController->expects($this->exactly(2))
            ->method('getParam')
            ->will($this->returnValueMap(
                [
                    ['controller', null, null],
                    ['action', null, null]
                ]
            ));

        // being used on 2 onDispatch calls
        $mockMvcEventFull->expects($this->exactly(2))
            ->method('getRouteMatch')
            ->willReturn($mockRouteMatchFull);

        $mockMvcEventFull->expects($this->exactly(2))
            ->method('getTarget')
            ->willReturn($mockController);

        // being used on 2 onDispatch calls
        $mockMvcEventNullAction->expects($this->exactly(2))
            ->method('getRouteMatch')
            ->willReturn($mockRouteMatchNullAction);

        $mockMvcEventNullAction->expects($this->exactly(2))
            ->method('getTarget')
            ->willReturn($mockController);

        $mockMvcEventNullController->expects($this->once())
            ->method('getRouteMatch')
            ->willReturn($mockRouteMatchNullController);

        $mockMvcEventNullController->expects($this->once())
            ->method('getTarget')
            ->willReturn($mockController);

        $mockAuthLink->expects($this->exactly(2))
            ->method('getRedirectRoute')
            ->willReturn('redirect-route');

        // mockAuthResultFailed is being used in 2 chains, each will induce
        // 1 call. That's exactly 2 calls
        $mockAuthResultFailed->expects($this->exactly(2))
            ->method('isValid')
            ->willReturn(false);

        $mockAuthResultFailed->expects($this->exactly(2))
            ->method('getAuthLink')
            ->willReturn($mockAuthLink);

        // mockAuthResultSuccess is being used in 2 chains, each will
        // induce 1 call. That's twice
        $mockAuthResultSuccess->expects($this->exactly(2))
            ->method('isValid')
            ->willReturn(true);

        // never called since an a successful auth result will not query
        // the auth link to retrieve the redirect route.
        $mockAuthResultSuccess->expects($this->never())
            ->method('getAuthLink');

        $mockAuthChainFailure->expects($this->once())
            ->method('isAuthorized')
            ->with('TestController', 'getCatAction')
            ->willReturn($mockAuthResultFailed);

        $mockAuthChainSuccess->expects($this->once())
            ->method('isAuthorized')
            ->with('TestController', 'getCatAction')
            ->willReturn($mockAuthResultSuccess);

        $mockAuthChainNullActionFailure->expects($this->once())
            ->method('isAuthorized')
            ->with('TestController', null)
            ->willReturn($mockAuthResultFailed);

        $mockAuthChainNullActionSuccess->expects($this->once())
            ->method('isAuthorized')
            ->with('TestController', null)
            ->willReturn($mockAuthResultSuccess);

        $mockAuthChainNullController->expects($this->never())
            ->method('isAuthorized'); // exception will be thrown instead

        $serviceSuccess = new AuthorizationService($mockAuthChainSuccess);
        $serviceFailure = new AuthorizationService($mockAuthChainFailure);
        $serviceNullActionSuccess = new AuthorizationService(
            $mockAuthChainNullActionSuccess
        );
        $serviceNullActionFailure = new AuthorizationService(
            $mockAuthChainNullActionFailure
        );
        $serviceNUllController = new AuthorizationService(
            $mockAuthChainNullController
        );

        $this->assertSame(
            null,
            $serviceSuccess->onDispatch($mockMvcEventFull)
        );
        $this->assertSame(
            'TestController',
            $serviceSuccess->getController()
        );
        $this->assertSame(
            'getCatAction',
            $serviceSuccess->getMethod()
        );

        $this->assertEquals(
            $mockRedirectPlugin->toRoute('redirect-route'),
            $serviceFailure->onDispatch($mockMvcEventFull)
        );
        $this->assertSame(
            'TestController',
            $serviceFailure->getController()
        );
        $this->assertSame(
            'getCatAction',
            $serviceFailure->getMethod()
        );

        $this->assertSame(
            null,
            $serviceNullActionSuccess->onDispatch($mockMvcEventNullAction)
        );
        $this->assertSame(
            'TestController',
            $serviceNullActionSuccess->getController()
        );
        $this->assertSame(
            null,
            $serviceNullActionSuccess->getMethod()
        );

        $this->assertEquals(
            $mockRedirectPlugin->toRoute('redirect-route'),
            $serviceNullActionFailure->onDispatch($mockMvcEventNullAction)
        );
        $this->assertSame(
            'TestController',
            $serviceNullActionFailure->getController()
        );
        $this->assertSame(
            null,
            $serviceNullActionFailure->getMethod()
        );

        $this->expectException(AuthorizationException::class);
        $serviceNUllController->onDispatch($mockMvcEventNullController);
    }
}