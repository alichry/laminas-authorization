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

use AliChry\Laminas\Authorization\AuthorizationResult;
use AliChry\Laminas\Authorization\AuthorizationService;
use AliChry\Laminas\Authorization\Controller\EigenRestfulController;
use Laminas\Mvc\Exception\RuntimeException;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Exception\DomainException;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\Stdlib\Parameters;
use Laminas\Stdlib\RequestInterface;
use Laminas\Stdlib\ResponseInterface;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\MockObject\Stub\ReturnStub;
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

    protected function createMockControllerMockOneMethodOnly(
        $expected,
        $targetMethod,
        $will,
        ... $with
    )
    {
        $this->controller = $this->getMockBuilder(EigenRestfulController::class)
            ->setConstructorArgs([$this->authService])
            ->setMethods([$targetMethod])
            ->getMock();
        $mocker = $this->controller->expects($this->exactly($expected))
            ->method($targetMethod);
        if (! empty($with)) {
            $mocker = $mocker->with(
                ... $with
            );
        }
        $mocker->will(
            $will instanceof Stub
                ? $will
                : $this->returnValue($will)
        );
        $this->controller->setEvent($this->event);
    }

    /**
     * @param bool $authorized
     * @param string $targetMethod
     * @return Response
     */
    protected function registerAuthorizationExpectations(
        bool $authorized,
        string $targetMethod
    )
    {
        $authMvcResult = new Response();
        $authResult = $this->createMock(AuthorizationResult::class);
        $this->authService->expects($this->once())
            ->method('isAuthorized')
            ->with(
                $this->identicalTo(EigenRestfulController::class),
                $this->identicalTo($targetMethod)
            )->willReturn($authResult);
        $this->authService->expects($this->once())
            ->method('getMvcResult')
            ->with($this->identicalTo($authResult))
            ->willReturn(
                $authorized ? null : $authMvcResult
            );
        return $authMvcResult;
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

        $this->createMockControllerMockOneMethodOnly(
            1,
            $method,
            $this->response
        );

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

    /**
     * @dataProvider trueOrFalseProvider
     * @param $authorized bool
     */
    public function testDispatchCustomMethod(bool $authorized)
    {
        $httpMethod = 'DESCRIBE';
        $targetMethod = 'describe';
        $descriptionResult = [
            'description' => 'okay'
        ];

        $this->createMockControllerMockOneMethodOnly(
            (int) $authorized,
            $targetMethod,
            $descriptionResult
        );

        $this->event->setRouteMatch($this->routeMatch);

        $this->controller->addHttpMethodHandler(
            $httpMethod,
            [$this->controller, $targetMethod]
        );
        $this->request->setMethod($httpMethod);

        $authMvcResult = $this->registerAuthorizationExpectations(
            $authorized,
            $targetMethod
        );

        $result = $this->controller->dispatch($this->request, $this->response);
        if ($authorized) {
            $expectedResult = $descriptionResult;
        } else {
            $expectedResult = $authMvcResult;
        }
        $this->assertSame(
            $expectedResult,
            $result
        );
        $this->assertSame(
            $expectedResult,
            $this->event->getResult()
        );
    }

    /**
     * @dataProvider trueOrFalseProvider
     * @param bool $authorized
     */
    public function testDispatchDeleteWithIdentifier(bool $authorized)
    {
        $targetMethod = 'delete';
        $id = 1;
        $deleteResult = [
            'content' => 'Method Not Allowed'
        ];
        $this->createMockControllerMockOneMethodOnly(
            (int) $authorized,
            $targetMethod,
            $deleteResult,
            $this->identicalTo($id)
        );

        $this->event->setRouteMatch($this->routeMatch);
        $this->request->setMethod('DELETE');
        $this->routeMatch->setParam($this->controller->getIdentifierName(), $id);

        $authMvcResult = $this->registerAuthorizationExpectations(
            $authorized,
            $targetMethod
        );
        if ($authorized) {
            $expectedResult = $deleteResult;
        } else {
            $expectedResult = $authMvcResult;
        }
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame(
            $expectedResult,
            $result
        );
        $this->assertSame(
            $expectedResult,
            $this->event->getResult()
        );
    }

    /**
     * @dataProvider trueOrFalseProvider
     * @param bool $authorized
     */
    public function testDispatchDeleteWithoutIdentifier(bool $authorized)
    {
        $targetMethod = 'deleteList';
        $requestData = [
            'name' => 'value'
        ];
        $deleteListResult = [
            'content' => 'Method Not Allowed'
        ];
        $this->createMockControllerMockOneMethodOnly(
            (int) $authorized,
            $targetMethod,
            $deleteListResult,
            $this->identicalTo($requestData)
        );

        $this->event->setRouteMatch($this->routeMatch);
        $this->request->setMethod('DELETE');
        $this->request->setContent(
            http_build_query($requestData)
        );

        $authMvcResult = $this->registerAuthorizationExpectations(
            $authorized,
            $targetMethod
        );
        if ($authorized) {
            $expectedResult = $deleteListResult;
        } else {
            $expectedResult = $authMvcResult;
        }
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame(
            $expectedResult,
            $result
        );
        $this->assertSame(
            $expectedResult,
            $this->event->getResult()
        );
    }

    /**
     * @dataProvider trueOrFalseProvider
     * @param bool $authorized
     */
    public function testDispatchGetWithIdentifier(bool $authorized)
    {
        $targetMethod = 'get';
        $id = 1;
        $getResult = [
            'content' => 'Method Not Allowed'
        ];
        $this->createMockControllerMockOneMethodOnly(
            (int) $authorized,
            $targetMethod,
            $getResult,
            $this->identicalTo($id)
        );

        $this->event->setRouteMatch($this->routeMatch);
        $this->request->setMethod('GET');
        $this->routeMatch->setParam($this->controller->getIdentifierName(), $id);

        $authMvcResult = $this->registerAuthorizationExpectations(
            $authorized,
            $targetMethod
        );
        if ($authorized) {
            $expectedResult = $getResult;
        } else {
            $expectedResult = $authMvcResult;
        }
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame(
            $expectedResult,
            $result
        );
        $this->assertSame(
            $expectedResult,
            $this->event->getResult()
        );
    }

    /**
     * @dataProvider trueOrFalseProvider
     * @param bool $authorized
     */
    public function testDispatchGetWithoutIdentifier(bool $authorized)
    {
        $targetMethod = 'getList';
        $getListResult = [
            'content' => 'Method Not Allowed'
        ];
        $this->createMockControllerMockOneMethodOnly(
            (int) $authorized,
            $targetMethod,
            $getListResult
        );

        $this->event->setRouteMatch($this->routeMatch);
        $this->request->setMethod('GET');

        $authMvcResult = $this->registerAuthorizationExpectations(
            $authorized,
            $targetMethod
        );
        if ($authorized) {
            $expectedResult = $getListResult;
        } else {
            $expectedResult = $authMvcResult;
        }
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame(
            $expectedResult,
            $result
        );
        $this->assertSame(
            $expectedResult,
            $this->event->getResult()
        );
    }

    /**
     * @dataProvider trueOrFalseProvider
     * @param bool $authorized
     */
    public function testDispatchHeadWithIdentifier(bool $authorized)
    {
        $targetMethod = 'head';
        $id = 1;
        $headResult = new Response();
        $headResult->setHeaders(
            $headResult->getHeaders()->addHeaderLine(
                'X-Test', 'value'
            )
        );
        $this->createMockControllerMockOneMethodOnly(
            (int) $authorized,
            $targetMethod,
            $headResult,
            $this->identicalTo($id)
        );

        $this->event->setRouteMatch($this->routeMatch);
        $this->request->setMethod('HEAD');
        $this->routeMatch->setParam($this->controller->getIdentifierName(), $id);

        $authMvcResult = $this->registerAuthorizationExpectations(
            $authorized,
            $targetMethod
        );
        if ($authorized) {
            // since RestfulController clones Response during HEAD
            $expectedResult = $headResult;
            $assert = 'assertEquals';
        } else {
            $expectedResult = $authMvcResult;
            $assert = 'assertSame';
        }
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->$assert(
            $expectedResult,
            $result
        );
        $this->$assert(
            $expectedResult,
            $this->event->getResult()
        );
    }

    /**
     * @dataProvider trueOrFalseProvider
     * @param bool $authorized
     */
    public function testDispatchHeadWithoutIdentifier(bool $authorized)
    {
        $targetMethod = 'head';
        $headResult = new Response();
        $headResult->setHeaders(
            $headResult->getHeaders()->addHeaderLine(
                'X-Test', 'value'
            )
        );
        $this->createMockControllerMockOneMethodOnly(
            (int) $authorized,
            $targetMethod,
            $headResult
        );

        $this->event->setRouteMatch($this->routeMatch);
        $this->request->setMethod('HEAD');

        $authMvcResult = $this->registerAuthorizationExpectations(
            $authorized,
            $targetMethod
        );
        if ($authorized) {
            // since RestfulController clones Response during HEAD
            $expectedResult = $headResult;
            $assert = 'assertEquals';
        } else {
            $expectedResult = $authMvcResult;
            $assert = 'assertSame';
        }
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->$assert(
            $expectedResult,
            $result
        );
        $this->$assert(
            $expectedResult,
            $this->event->getResult()
        );
    }

    /**
     * @dataProvider trueOrFalseProvider
     * @param bool $authorized
     */
    public function testDispatchOptions(bool $authorized)
    {
        $targetMethod = 'options';
        // for some reason, RestfulController sets the result for OPTIONS
        // as the $e->getResponse, instead of the return value of
        // the options method
        $optionsResult = $this->response;
        $this->createMockControllerMockOneMethodOnly(
            (int) $authorized,
            $targetMethod,
            null // return value not used in onDispatch @ OPTIONS
        );

        $this->event->setRouteMatch($this->routeMatch);
        $this->request->setMethod('OPTIONS');

        $authMvcResult = $this->registerAuthorizationExpectations(
            $authorized,
            $targetMethod
        );
        if ($authorized) {
            $expectedResult = $optionsResult;
        } else {
            $expectedResult = $authMvcResult;
        }
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame(
            $expectedResult,
            $result
        );
        $this->assertSame(
            $expectedResult,
            $this->event->getResult()
        );
    }

    /**
     * @dataProvider trueOrFalseProvider
     * @param bool $authorized
     */
    public function testDispatchPatchWithIdentifier(bool $authorized)
    {
        $targetMethod = 'patch';
        $id = 1;
        $requestData = [
            'name' => 'value'
        ];
        $patchListResult = [
            'content' => 'Method Not Allowed'
        ];
        $this->createMockControllerMockOneMethodOnly(
            (int) $authorized,
            $targetMethod,
            $patchListResult,
            $this->identicalTo($id)
        );

        $this->event->setRouteMatch($this->routeMatch);
        $this->routeMatch->setParam(
            $this->controller->getIdentifierName(),
            $id
        );
        $this->request->setMethod('PATCH');
        $this->request->setContent(
            http_build_query($requestData)
        );

        $authMvcResult = $this->registerAuthorizationExpectations(
            $authorized,
            $targetMethod
        );
        if ($authorized) {
            $expectedResult = $patchListResult;
        } else {
            $expectedResult = $authMvcResult;
        }
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame(
            $expectedResult,
            $result
        );
        $this->assertSame(
            $expectedResult,
            $this->event->getResult()
        );
    }

    /**
     * @dataProvider trueOrFalseProvider
     * @param bool $authorized
     */
    public function testDispatchPatchWithoutIdentifier(bool $authorized)
    {
        $targetMethod = 'patchList';
        $requestData = [
            'name' => 'value'
        ];
        $patchListResult = [
            'content' => 'Method Not Allowed'
        ];
        $this->createMockControllerMockOneMethodOnly(
            (int) $authorized,
            $targetMethod,
            $patchListResult,
            $this->identicalTo($requestData)
        );

        $this->event->setRouteMatch($this->routeMatch);
        $this->request->setMethod('PATCH');
        $this->request->setContent(
            http_build_query($requestData)
        );

        $authMvcResult = $this->registerAuthorizationExpectations(
            $authorized,
            $targetMethod
        );
        if ($authorized) {
            $expectedResult = $patchListResult;
        } else {
            $expectedResult = $authMvcResult;
        }
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame(
            $expectedResult,
            $result
        );
        $this->assertSame(
            $expectedResult,
            $this->event->getResult()
        );
    }

    public function testDispatchPatchWithoutIdentifierThrowsException()
    {
        $targetMethod = 'patchList';
        $requestData = [
            'name' => 'value'
        ];
        $patchListResult = [
            'content' => 'Method Not Allowed'
        ];
        $exception = new RuntimeException();
        $this->createMockControllerMockOneMethodOnly(
            1,
            $targetMethod,
            $this->throwException($exception),
            $this->identicalTo($requestData)
        );

        $this->event->setRouteMatch($this->routeMatch);
        $this->request->setMethod('PATCH');
        $this->request->setContent(
            http_build_query($requestData)
        );

        $authMvcResult = $this->registerAuthorizationExpectations(
            true,
            $targetMethod
        );
        $expectedResponse = $this->response;
        $response = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame(
            $expectedResponse,
            $response
        );
        $this->assertSame(
            405,
            $expectedResponse->getStatusCode()
        );
    }

    /**
     * @dataProvider trueOrFalseProvider
     * @param bool $authorized
     */
    public function testDispatchPost(bool $authorized)
    {
        $targetMethod = 'create';
        $requestData = [
            'name' => 'value'
        ];
        $createResult = [
            'content' => 'Method Not Allowed'
        ];
        $this->createMockControllerMockOneMethodOnly(
            (int) $authorized,
            $targetMethod,
            $createResult,
            $this->identicalTo($requestData)
        );

        $this->event->setRouteMatch($this->routeMatch);
        $this->request->setMethod('POST');
        $this->request->setPost(
            new Parameters($requestData)
        );

        $authMvcResult = $this->registerAuthorizationExpectations(
            $authorized,
            $targetMethod
        );
        if ($authorized) {
            $expectedResult = $createResult;
        } else {
            $expectedResult = $authMvcResult;
        }
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame(
            $expectedResult,
            $result
        );
        $this->assertSame(
            $expectedResult,
            $this->event->getResult()
        );
    }

    /**
     * @dataProvider trueOrFalseProvider
     * @param bool $authorized
     */
    public function testDispatchPutWithIdentifier(bool $authorized)
    {
        $targetMethod = 'update';
        $id = 1;
        $requestData = [
            'name' => 'value'
        ];
        $updateResult = [
            'content' => 'Method Not Allowed'
        ];
        $this->createMockControllerMockOneMethodOnly(
            (int) $authorized,
            $targetMethod,
            $updateResult,
            $this->identicalTo($id, $requestData)
        );

        $this->event->setRouteMatch($this->routeMatch);
        $this->routeMatch->setParam(
            $this->controller->getIdentifierName(),
            $id
        );
        $this->request->setMethod('PUT');
        $this->request->setContent(
            http_build_query($requestData)
        );

        $authMvcResult = $this->registerAuthorizationExpectations(
            $authorized,
            $targetMethod
        );
        if ($authorized) {
            $expectedResult = $updateResult;
        } else {
            $expectedResult = $authMvcResult;
        }
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame(
            $expectedResult,
            $result
        );
        $this->assertSame(
            $expectedResult,
            $this->event->getResult()
        );
    }

    /**
     * @dataProvider trueOrFalseProvider
     * @param bool $authorized
     */
    public function testDispatchPutWithoutIdentifier(bool $authorized)
    {
        $targetMethod = 'replaceList';
        $requestData = [
            'name' => 'value'
        ];
        $replaceListResult = [
            'content' => 'Method Not Allowed'
        ];
        $this->createMockControllerMockOneMethodOnly(
            (int) $authorized,
            $targetMethod,
            $replaceListResult,
            $this->identicalTo($requestData)
        );

        $this->event->setRouteMatch($this->routeMatch);
        $this->request->setMethod('PUT');
        $this->request->setContent(
            http_build_query($requestData)
        );

        $authMvcResult = $this->registerAuthorizationExpectations(
            $authorized,
            $targetMethod
        );
        if ($authorized) {
            $expectedResult = $replaceListResult;
        } else {
            $expectedResult = $authMvcResult;
        }
        $result = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame(
            $expectedResult,
            $result
        );
        $this->assertSame(
            $expectedResult,
            $this->event->getResult()
        );
    }

    public function testDispatchDefault()
    {
        $expectedResponse = $this->response;
        $this->event->setRouteMatch($this->routeMatch);
        $this->request->setMethod('OTHER');
        $response = $this->controller->dispatch($this->request, $this->response);
        $this->assertSame(
            $expectedResponse,
            $response
        );
        $this->assertSame(
            405,
            $this->response->getStatusCode()
        );
    }

    /**
     * @return \bool[][]
     */
    public function trueOrFalseProvider()
    {
        return [
            [true],
            [false]
        ];
    }
}