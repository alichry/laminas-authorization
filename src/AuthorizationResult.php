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
 * Date: 2020-05-28
 * Time: 09:46
 */

namespace AliChry\Laminas\Authorization;

use AliChry\Laminas\AccessControl\Status;

class AuthorizationResult
{
    const RESULT_ALLOWED = 0;
    const RESULT_REJECTED = 1;

    /**
     * @var int
     */
    private $code;

    /**
     * @var Status
     */
    private $accessStatus;

    /**
     * @var AuthorizationLink
     * if the result is not OK, this will be
     * the AuthorizationLink that filtered it
     */
    private $authLink;

    /**
     * @var array|string[]
     */
    private $messages;

    /**
     * AuthorizationResult constructor.
     * @param Status $accessStatus
     * @param AuthorizationLink $authLink
     * @param bool $authenticated
     * @param array $messages
     */
    public function __construct(
        $accessStatus,
        $authLink,
        $authenticated = false,
        array $messages = []
    )
    {
        $this->setAccessStatus($accessStatus);
        $this->setAuthLink($authLink);
        $this->setMessages($messages);
        $this->code = self::inferCode(
            $accessStatus,
            $authenticated
        );
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->code === self::RESULT_ALLOWED;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return Status
     */
    public function getAccessStatus(): Status
    {
        return $this->accessStatus;
    }

    /**
     * @param Status $status
     */
    private function setAccessStatus(Status $status)
    {
        $this->accessStatus = $status;
    }

    /**
     * @return AuthorizationLink
     */
    public function getAuthLink(): AuthorizationLink
    {
        return $this->authLink;
    }

    /**
     * @param AuthorizationLink $authLink
     * @throws \TypeError if auth link is null
     */
    private function setAuthLink(AuthorizationLink $authLink)
    {
        $this->authLink = $authLink;
    }

    /**
     * @return array|string[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @param array $messages
     */
    private function setMessages(array $messages = [])
    {
        $this->messages = $messages;
    }

    /**
     * @return array|string[]
     */
    public function getAllMessages(): array
    {
        return array_merge(
            $this->messages,
            $this->accessStatus->getMessages()
        );
    }

    /**
     * @param Status $accessStatus
     * @param bool $authenticated
     * @return int
     */
    private static function inferCode(
        Status $accessStatus,
        bool $authenticated = false
    ): int
    {
        $statusCode = $accessStatus->getCode();
        if ($statusCode === Status::PUBLIC) {
            return self::RESULT_ALLOWED;
        }
        if ($statusCode === Status::OK && true === $authenticated) {
            return self::RESULT_ALLOWED;
        }
        return self::RESULT_REJECTED;
    }
}