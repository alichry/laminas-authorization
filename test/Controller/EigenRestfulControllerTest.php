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

namespace AliChry\Laminas\Authorization\Test\Controller;

use AliChry\Laminas\Authorization\AuthorizationService;
use AliChry\Laminas\Authorization\Controller\EigenRestfulController;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Exception\DomainException;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\Stdlib\RequestInterface;
use Laminas\Stdlib\ResponseInterface;
use PHPUnit\Framework\TestCase;

class EigenRestfulControllerTest extends TestCase
{
    /**
     * @var EigenRestfulController
     */
    private $controller;

    /**
     * @var AuthorizationService
     */
    private $authService;

    /**
     * @var MvcEvent
     */
    private $event;

    /**
     * @var RouteMatch
     */
    private $routeMatch;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    public function setUp()
    {
        $this->authService = $this->createMock(AuthorizationService::class);
        $this->controller = new EigenRestfulController($this->authService);
        $this->event = new MvcEvent();
        $this->routeMatch = new RouteMatch(['controller' => 'controller-restful']);
        $this->request = new Request();
        $this->response = new Response();
        $this->controller->setEvent($this->event);
    }

    public function testOnDispatchNoRouteMatch()
    {
        $this->expectException(DomainException::class);
        $this->controller->onDispatch($this->event);
    }

    public function testDispatchWithActionParam()
    {
        $action = 'go';
        $method = $action . 'Action';
        $this->controller = $this->getMockBuilder(EigenRestfulController::class)
            ->setConstructorArgs([$this->authService])
            ->setMethods([$method])
            ->getMock();
        $this->controller->expects($this->once())
            ->method($method)
            ->willReturn($this->response);
        $this->controller->setEvent($this->event);

        $this->event->setRouteMatch($this->routeMatch);
        $this->routeMatch->setParam('action', $action);
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame(
            $this->response,
            $result
        );
        $this->assertSame(
            $this->event->getResult(),
            $result
        );
    }

    public function testDispatchNotFoundAction()
    {
        $action = 'go';
        $method = $action . 'Action';
        // goAction method does not exists in EigenRestfulController

        $this->event->setRouteMatch($this->routeMatch);
        $this->routeMatch->setParam('action', $action);
        $result = $this->controller->dispatch($this->request, $this->response);
        $expectedResult = $this->controller->notFoundAction();
        $this->assertSame(
            $expectedResult,
            $result
        );
        $this->assertSame(
            $expectedResult,
            $this->event->getResult()
        );
    }


    public function testDispatchCustomMethod()
    {
        $httpMethod = 'DESCRIBE';
        $targetMethod = 'describe';
        $expectedResult = [
            'description' => 'okay'
        ];
        $this->controller = $this->getMockBuilder(EigenRestfulController::class)
            ->setConstructorArgs([$this->authService])
            ->setMethods([$targetMethod])
            ->getMock();
        $this->controller->expects($this->once())
            ->method($targetMethod)
            ->willReturn($expectedResult);
        $this->controller->setEvent($this->event);

        $this->event->setRouteMatch($this->routeMatch);

        $this->controller->addHttpMethodHandler(
            $httpMethod,
            [$this->controller, $targetMethod]
        );
        $this->request->setMethod($httpMethod);
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame(
            $expectedResult,
            $result
        );

        // *** todo: auth checking
    }
}