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

use AliChry\Laminas\AccessControl\Status as AccessStatus;

class Result
{
    const RESULT_REJECTED = 0;
    const RESULT_ALLOWED = 1;

    /**
     * @var int
     */
    private $code;

    /**
     * @var null|AccessStatus
     * if the result is created using fromAccessStatus()
     * the passed access status will be stored here.
     */
    private $accessStatus;

    /**
     * @var LinkInterface
     * if the result is not OK, this will be
     * the LinkInterface that filtered it
     */
    private $authLink;

    /**
     * @var array|string[]
     */
    private $messages;

    /**
     * Result constructor.
     * @param int $code
     * @param LinkInterface $authLink
     * @param AccessStatus|null $status this does not affect the underlying code,
     *  (result) use fromAccessStatus() to infer code and store it
     * @param array|null $messages
     * @throws AuthorizationException
     */
    public function __construct(
        int $code,
        LinkInterface $authLink,
        AccessStatus $status = null,
        array $messages = null
    )
    {
        $this->setCode($code);
        $this->setAuthLink($authLink);
        if (null !== $status) {
            $this->setAccessStatus($status);
        }
        if (null !== $messages) {
            $this->setMessages($messages);
        }
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
     * @param int $code
     * @throws AuthorizationException
     */
    private function setCode(int $code): void
    {
        if ($code !== self::RESULT_ALLOWED && $code !== self::RESULT_REJECTED) {
            throw new AuthorizationException(
                'Invalid code: ' . $code,
                AuthorizationException::AR_INVALID_CODE
            );
        }
        $this->code = $code;
    }

    /**
     * @return AccessStatus|null
     */
    public function getAccessStatus(): ?AccessStatus
    {
        return $this->accessStatus;
    }

    /**
     * @param AccessStatus $status
     */
    private function setAccessStatus(AccessStatus $status)
    {
        $this->accessStatus = $status;
    }

    /**
     * @return LinkInterface
     */
    public function getAuthLink(): LinkInterface
    {
        return $this->authLink;
    }

    /**
     * @param LinkInterface $authLink
     */
    private function setAuthLink(LinkInterface $authLink)
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
        if (null === $this->accessStatus) {
            return $this->getMessages();
        }
        return array_merge(
            $this->messages,
            $this->accessStatus->getMessages()
        );
    }

    /**
     * @param AccessStatus $accessStatus
     * @param bool $authenticated
     * @return int
     */
    private static function inferCode(
        AccessStatus $accessStatus,
        bool $authenticated = false
    ): int
    {
        $statusCode = $accessStatus->getCode();
        if ($statusCode === AccessStatus::PUBLIC) {
            return self::RESULT_ALLOWED;
        }
        if ($statusCode === AccessStatus::OK && true === $authenticated) {
            return self::RESULT_ALLOWED;
        }
        return self::RESULT_REJECTED;
    }

    /**
     * @param LinkInterface $authLink
     * @param AccessStatus $accessStatus
     * @param bool $authenticated
     * @param array $messages
     * @return Result
     * @throws AuthorizationException
     */
    public static function fromAccessStatus(
        LinkInterface $authLink,
        AccessStatus $accessStatus,
        bool $authenticated = false,
        array $messages = []
    ): self
    {
        $code = self::inferCode($accessStatus, $authenticated);
        return new Result(
            $code,
            $authLink,
            $accessStatus,
            $messages
        );
    }
}