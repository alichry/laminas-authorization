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
 * Date: 2019-08-21
 * Time: 09:55
 */

namespace AliChry\Laminas\Authorization;

use AliChry\Laminas\AccessControl\AccessControlException;
use AliChry\Laminas\AccessControl\AccessControlListInterface;
use AliChry\Laminas\AccessControl\Status;
use AliChry\Laminas\Authorization\Resource\LinkAwareResourceIdentifier;
use Laminas\Authentication\AuthenticationServiceInterface;

class AuthorizationLink implements LinkInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var AuthenticationServiceInterface
     */
    private $authenticationService;

    /**
     * @var null|AccessControlListInterface
     */
    private $accessControlList;

    /**
     * @var null|string
     */
    private $redirectRoute;

    /**
     * AuthorizationLink constructor.
     * @param string $name
     * @param AuthenticationServiceInterface $authenticationService
     * @param AccessControlListInterface $list
     * @param string|null $redirectRoute
     */
    public function __construct(
        string $name,
        AuthenticationServiceInterface $authenticationService,
        AccessControlListInterface $list = null,
        string $redirectRoute = null
    )
    {
        $this->setName($name);
        $this->setAuthenticationService($authenticationService);
        if (null !== $list) {
            $this->setAccessControlList($list);
        }
        if (null !== $redirectRoute) {
            $this->setRedirectRoute($redirectRoute);
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return AuthenticationServiceInterface
     */
    public function getAuthenticationService(): AuthenticationServiceInterface
    {
        return $this->authenticationService;
    }

    /**
     * @param AuthenticationServiceInterface $authenticationService
     */
    public function setAuthenticationService(
        AuthenticationServiceInterface $authenticationService
    ): void
    {
        $this->authenticationService = $authenticationService;
    }

    /**
     * @return null|AccessControlListInterface
     */
    public function getAccessControlList(): ?AccessControlListInterface
    {
        return $this->accessControlList;
    }

    /**
     * @param AccessControlListInterface $accessControlList
     */
    public function setAccessControlList(
        AccessControlListInterface $accessControlList
    ): void
    {
        $this->accessControlList = $accessControlList;
    }

    /**
     * @return null|string
     */
    public function getRedirectRoute(): ?string
    {
        return $this->redirectRoute;
    }

    /**
     * @param string $redirectRoute
     */
    public function setRedirectRoute(string $redirectRoute): void
    {
        $this->redirectRoute = $redirectRoute;
    }

    /**
     * @return Result
     * @throws AuthorizationException
     */
    public function isAuthenticated(): Result
    {
        $authenticated = $this->authenticationService->hasIdentity();
        if ($authenticated) {
            $code = Result::RESULT_ALLOWED;
        } else {
            $code = Result::RESULT_REJECTED;
        }
        return new Result(
            $code,
            $this
        );
    }

    /**
     * @param $controller
     * @param $method
     * @return Result
     * @throws AuthorizationException|AccessControlException
     */
    public function isAuthorized($controller, $method): Result
    {
        if (null === $this->accessControlList) {
            /**
             * This allows to span authorization links without an underlying
             * access control list, essentially spanning authentication links.
             * If there's no ACL, all authenticated users are authorized.
             */
            return $this->isAuthenticated();
        }
        // TODO: pass/use $this->options in Result
        $accessStatus = $this->accessControlList->getAccessStatus(
            $this->authenticationService->getIdentity(),
            new LinkAwareResourceIdentifier(
                $this->name,
                $controller,
                $method
            )
        );
        $code = $accessStatus->getCode();
        switch ($code) {
            case Status::PUBLIC:
            case Status::REJECTED:
            case Status::UNAUTHORIZED:
            case Status::UNAUTHENTICATED: // if passed identity is null
                return Result::fromAccessStatus(
                    $this,
                    $accessStatus
                );
                break;
            case Status::OK:
                $authenticated = $this->authenticationService->hasIdentity();
                return Result::fromAccessStatus(
                    $this,
                    $accessStatus,
                    $authenticated
                );
                break;
            default:
                throw new AuthorizationException(
                    sprintf(
                        'Unknown access status code %s',
                        (string) $code
                    )
                );
        }
    }
}