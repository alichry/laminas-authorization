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
 * Time: 09:52
 */

namespace AliChry\Laminas\Authorization;

use AliChry\Laminas\AccessControl\AccessControlException;

class AuthorizationChain implements AuthorizationChainInterface
{
    // binary operator
    const OPERATOR_AND = 'AND';
    const OPERATOR_OR = 'OR';

    /**
     * aggregator/closer..
     * @var string
     */
    private $operator;

    /**
     * @var array|AuthorizationLink[]
     */
    private $links;

    /**
     * AuthorizationChain constructor.
     * @param string $operator
     * @param array $links
     * @throws AuthorizationException
     */
    public function __construct(
        $operator = self::OPERATOR_AND,
        $links = []
    )
    {
        $this->setOperator($operator);
        $this->setLinks($links);
    }

    /**
     * @return mixed
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @param $operator
     * @throws AuthorizationException
     */
    public function setOperator($operator)
    {
        if (! self::checkOperator($operator)) {
            throw new AuthorizationException(
                sprintf('Passed operator "%s" is not valid', $operator),
                AuthorizationException::AC_INVALID_OPERATOR
            );
        }
        $this->operator = $operator;
    }

    /**
     * @return array|LinkInterface[]
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * @param array|LinkInterface[] $links
     */
    public function setLinks(array $links)
    {
        $this->links = $links;
    }

    /**
     * @param LinkInterface $link
     * @throws AuthorizationException
     */
    public function addLink(LinkInterface $link)
    {
        $linkName = $link->getName();
        if (isset($this->links[$linkName])) {
            throw new AuthorizationException(
                sprintf(
                    'Unable to add link to chain, provided link name "%s"'
                    . 'already exists in chain',
                    $linkName
                ),
                AuthorizationException::AC_DUPLICATE_LINK_NAME
            );
        }
        $this->links[$linkName] = $link;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->links);
    }

    /**
     * @param string|int $index
     * @return LinkInterface
     * @throws AuthorizationException
     */
    public function getLink($index): LinkInterface
    {
        if (is_int($index)) {
            $keys = \array_keys($this->links);
            $name = $keys[$index] ?? null;
        } else {
            $name = $index;
        }
        if ($name === null || !isset($this->links[$name])) {
            throw new AuthorizationException(
                sprintf(
                    'Invalid index/name "%s"',
                    $index
                )
            );
        }
        return $this->links[$name];
    }

    /**
     * @param string $controllerName
     * @param null|string $method
     * @return AuthorizationResult
     * @throws AuthorizationException|AccessControlException
     */
    public function isAuthorized(string $controllerName, ?string $method = null)
    : AuthorizationResult
    {
        $returnResult = null;
        foreach ($this->links as $index => $link) {
            if (! $link instanceof LinkInterface) {
                throw new AuthorizationException(
                    sprintf(
                        'Invalid link at index/name %s, must be an instance '
                        . ' of %s, got %s',
                        (string) $index,
                        AuthorizationLink::class,
                        is_object($link)
                            ? get_class($link)
                            : gettype($link)
                    )
                );
            }
            $result = $link->isAuthorized($controllerName, $method);
            switch ($this->getOperator()) {
                case self::OPERATOR_OR:
                    if (true === $result->isValid()) {
                        return $result;
                    }
                    if (! isset($returnResult)) {
                        // in case none of the links returns a valid result
                        // we store the first killer result in returnResult
                        // to return at last.
                        $returnResult = $result;
                    }
                    break;
                case self::OPERATOR_AND:
                    if (false === $result->isValid()) {
                        return $result;
                    }
                    // we save last successful result in returnResult
                    $returnResult = $result;
                    break;
                default:
                    throw new AuthorizationException(
                        sprintf(
                            'Invalid binary operator "%s"',
                            (string) $this->operator
                        ),
                        AuthorizationException::AC_INVALID_OPERATOR
                    );
            }
        }
        if (! isset($returnResult)) {
            throw new AuthorizationException(
                'Authorization chain is empty!'
            );
        }
        return $returnResult;
    }

    /**
     * @param $operator
     * @return bool
     */
    private static function checkOperator($operator): bool
    {
        return $operator === self::OPERATOR_AND
            || self::OPERATOR_OR === $operator;
    }
}