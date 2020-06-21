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

namespace AliChry\Laminas\Authorization\Controller;

use AliChry\Laminas\AccessControl\AccessControlException;
use AliChry\Laminas\Authorization\AuthorizationException;
use AliChry\Laminas\Authorization\AuthorizationService;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ResponseInterface as Response;
use Laminas\Mvc\Exception\DomainException;

class EigenRestfulController extends AbstractRestfulController
{
    /**
     * @var AuthorizationService
     */
    private $authorizationService;

    public function __construct(AuthorizationService $authService)
    {
        $this->authorizationService = $authService;
    }

    /**
     * @param $action
     * @return Response|null
     * @throws AccessControlException
     * @throws AuthorizationException
     */
    private function __check($action)
    {
        return $this->authorizationService->getMvcResult(
            $this->authorizationService->isAuthorized(
                __CLASS__,
                $action
            )
        );
    }

    /**
     * Handle the request
     *
     * @todo   try-catch in "patch" for patchList should be removed in the future
     * @param  MvcEvent $e
     * @return mixed
     * @throws DomainException if no route matches in event or invalid HTTP method
     * @throws AuthorizationException
     * @throws AccessControlException
     */
    public function onDispatch(MvcEvent $e)
    {
        $routeMatch = $e->getRouteMatch();
        if (! $routeMatch) {
            /**
             * @todo Determine requirements for when route match is missing.
             *       Potentially allow pulling directly from request metadata?
             */
            throw new Exception\DomainException('Missing route matches; unsure how to retrieve action');
        }

        $request = $e->getRequest();

        // Was an "action" requested?
        $action  = $routeMatch->getParam('action', false);
        if ($action) {
            // * if action was set, the AuthorizationService would have
            // * checked for authorization.
            // Handle arbitrary methods, ending in Action
            $method = static::getMethodFromAction($action);
            if (! method_exists($this, $method)) {
                $method = 'notFoundAction';
            }
            $return = $this->$method();
            $e->setResult($return);
            return $return;
        }

        // RESTful methods
        $method = strtolower($request->getMethod());
        switch ($method) {
            // Custom HTTP methods (or custom overrides for standard methods)
            case (isset($this->customHttpMethodsMap[$method])):
                $callable = $this->customHttpMethodsMap[$method];
                $action = $method;
                if ($return = $this->__check($action)) {
                    break;
                }
                $return = call_user_func($callable, $e);
                break;
            // DELETE
            case 'delete':
                $id = $this->getIdentifier($routeMatch, $request);

                if ($id !== false) {
                    $action = 'delete';
                    if ($return = $this->__check($action)) {
                        break;
                    }
                    $return = $this->delete($id);
                    break;
                }

                $data = $this->processBodyContent($request);

                $action = 'deleteList';
                if ($return = $this->__check($action)) {
                    break;
                }
                $return = $this->deleteList($data);
                break;
            // GET
            case 'get':
                $id = $this->getIdentifier($routeMatch, $request);
                if ($id !== false) {
                    $action = 'get';
                    if ($return = $this->__check($action)) {
                        break;
                    }
                    $return = $this->get($id);
                    break;
                }
                $action = 'getList';
                if ($return = $this->__check($action)) {
                    break;
                }
                $return = $this->getList();
                break;
            // HEAD
            case 'head':
                $id = $this->getIdentifier($routeMatch, $request);
                if ($id === false) {
                    $id = null;
                }
                $action = 'head';
                if ($return = $this->__check($action)) {
                    break;
                }
                $headResult = $this->head($id);
                $response = ($headResult instanceof Response) ? clone $headResult : $e->getResponse();
                $response->setContent('');
                $return = $response;
                break;
            // OPTIONS
            case 'options':
                $action = 'options';
                if ($return = $this->__check($action)) {
                    break;
                }
                $this->options();
                $return = $e->getResponse();
                break;
            // PATCH
            case 'patch':
                $id = $this->getIdentifier($routeMatch, $request);
                $data = $this->processBodyContent($request);

                if ($id !== false) {
                    $action = 'patch';
                    if ($return = $this->__check($action)) {
                        break;
                    }
                    $return = $this->patch($id, $data);
                    break;
                }

                // TODO: This try-catch should be removed in the future, but it
                // will create a BC break for pre-2.2.0 apps that expect a 405
                // instead of going to patchList
                try {
                    $action = 'patchList';
                    if ($return = $this->__check($action)) {
                        break;
                    }
                    $return = $this->patchList($data);
                } catch (Exception\RuntimeException $ex) {
                    $response = $e->getResponse();
                    $response->setStatusCode(405);
                    return $response;
                }
                break;
            // POST
            case 'post':
                $action = 'create';
                if ($return = $this->__check($action)) {
                    break;
                }
                $return = $this->processPostData($request);
                break;
            // PUT
            case 'put':
                $id   = $this->getIdentifier($routeMatch, $request);
                $data = $this->processBodyContent($request);

                if ($id !== false) {
                    $action = 'update';
                    if ($result = $this->__check($action)) {
                        return $result;
                    }
                    $return = $this->update($id, $data);
                    break;
                }

                $action = 'replaceList';
                if ($return = $this->__check($action)) {
                    break;
                }
                $return = $this->replaceList($data);
                break;
            // All others...
            default:
                $response = $e->getResponse();
                $response->setStatusCode(405);
                return $response;
        }

        $routeMatch->setParam('action', $action);
        $e->setResult($return);
        return $return;
    }
}