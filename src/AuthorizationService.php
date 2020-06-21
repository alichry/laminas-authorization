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
 * Date: 2019-07-29
 * Time: 09:22
 */

namespace AliChry\Laminas\Authorization;

use AliChry\Laminas\AccessControl\AccessControlException;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\Controller\Plugin\Redirect;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ResponseInterface;

class AuthorizationService implements AuthorizationServiceInterface
{
    /**
     * @var AuthenticationServiceInterface
     */
    private $authorizationChain;

    /**
     * @var string|null
     */
    private $controller;

    /**
     * @var string|null
     */
    private $method;

    /**
     * @var Redirect
     */
    private $redirectPlugin;

    /**
     * AccessFilter constructor.
     * @param AuthorizationChain $authorizationChain
     */
    public function __construct(
        AuthorizationChain $authorizationChain
    )
    {
        $this->authorizationChain = $authorizationChain;
    }

    /**
     * @return AuthorizationChain
     */
    public function getAuthorizationChain(): AuthorizationChain
    {
        return $this->authorizationChain;
    }

    /**
     * @return string|null
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @return string|null
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param MvcEvent $event
     * @return null|ResponseInterface
     * @throws AuthorizationException|AccessControlException
     */
    public function onDispatch(MvcEvent $event): ?ResponseInterface
    {
        $routeMatch = $event->getRouteMatch();
        if (null === $routeMatch) {
            throw new AuthorizationException(
                'Retrieved route match is null, unable to infer route params'
            );
        }
        /**
         * @var AbstractController
         */
        $controller = $event->getTarget();
        $isObject = is_object($controller);
        if (
            ! $isObject || ! $controller instanceof AbstractController
        ) {
            throw new AuthorizationException(
                sprintf(
                    'Expecting controller (target) to be an instance of %s, '
                    . 'got %s',
                    AbstractController::class,
                    $isObject
                        ? get_class($controller)
                        : gettype($controller)
                )
            );
        }
        $this->redirectPlugin = $controller->plugin('redirect');
        $controllerName = $routeMatch->getParam('controller', null);
        $action = $routeMatch->getParam('action', null);
        $method = null;
        if (null !== $action) {
            $method = AbstractActionController::getMethodFromAction($action);
        }
        $this->controller = $controllerName;
        $this->method = $method;
        $result = $this->getMvcResult();
        $event->setResult($result);
        return $result;
    }

    /**
     * @param null $controller
     * @param null $method
     * @return AuthorizationResult
     * @throws AuthorizationException|AccessControlException
     */
    public function isAuthorized(
        $controller = null,
        $method = null
    ): AuthorizationResult
    {
        $controller = $controller ?? $this->controller;
        $method = $method ?? $this->method;
        if ($controller === null) {
            throw new AuthorizationException(
                'Unable to check if identity is authorized, controller not set'
            );
        }
        return $this->authorizationChain->isAuthorized(
            $controller,
            $method
        );
    }

    /**
     * @param null|AuthorizationResult $authResult
     * @return null|ResponseInterface
     * @throws AuthorizationException|AccessControlException
     */
    public function getMvcResult(
        AuthorizationResult $authResult = null
    ): ?ResponseInterface
    {
        if (null === $authResult) {
            $authResult = $this->isAuthorized();
        }
        if ($authResult->isValid()) {
            return null;
        }
        // TODO: Allow more options other than redirect,
        // TODO: maybe dropping JSON
        $authLink = $authResult->getAuthLink();
        return $this->redirectPlugin->toRoute($authLink->getRedirectRoute());
    }
}