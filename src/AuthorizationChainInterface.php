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
 * Time: 09:53
 */

namespace AliChry\Laminas\Authorization;

interface AuthorizationChainInterface
{
    /**
     * @return mixed
     */
    public function getOperator();

    /**
     * @param AuthorizationLink $link
     */
    public function addLink(AuthorizationLink $link);

    /**
     * @param array|AuthorizationLink[] $chain
     */
    public function setLinks(array $chain);

    /**
     * @return array|AuthenticationLink[]
     */
    public function getLinks(): array;

    /**
     * @param string|int $index
     * @return AuthorizationLink
     */
    public function getLink($index): AuthorizationLink;

    /**
     * @param string $controllerName
     * @param null|string $method
     * @return AuthorizationResult
     */
    public function isAuthorized(string $controllerName, ?string $method = null): AuthorizationResult;
}