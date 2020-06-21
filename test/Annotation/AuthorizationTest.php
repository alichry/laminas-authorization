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
declare(strict_types=1);

namespace AliChry\Laminas\Authorization\Test\Annotation;

use AliChry\Laminas\AccessControl\Policy\Policy;
use AliChry\Laminas\Authorization\Annotation\Authorization;
use AliChry\Laminas\Authorization\AuthorizationException;
use PHPUnit\Framework\TestCase;

class AuthorizationTest extends TestCase
{
    /**
     * @throws AuthorizationException
     */
    public function testGetPolicyType()
    {
        $authorization = new Authorization();
        $authorization->policy = 'Reject';
        $this->assertSame(
            Policy::POLICY_REJECT,
            $authorization->getPolicyType()
        );
        $authorization->policy = 'Authorize';
        $this->assertSame(
            Policy::POLICY_AUTHORIZE,
            $authorization->getPolicyType()
        );
        $authorization->policy = 'Authenticate';
        $this->assertSame(
            Policy::POLICY_AUTHENTICATE,
            $authorization->getPolicyType()
        );
        $authorization->policy = 'Allow';
        $this->assertSame(
            Policy::POLICY_ALLOW,
            $authorization->getPolicyType()
        );
        $this->expectException(AuthorizationException::class);
        $authorization->policy = 't';
        $authorization->getPolicyType();
    }
}
