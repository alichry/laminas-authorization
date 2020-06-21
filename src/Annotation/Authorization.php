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

namespace AliChry\Laminas\Authorization\Annotation;

use AliChry\Laminas\AccessControl\Policy\Policy;
use AliChry\Laminas\Authorization\AuthorizationException;

/**
 * Class Authorization
 * @package AliChry\Laminas\AccessControl\Annotation
 * @Annotation
 * @Target("METHOD")
 */
final class Authorization
{
    /**
     * @var string
     */
    public $link;

    /**
     * @Enum({"Allow", "Reject", "Authenticate", "Authorize"})
     */
    public $policy;

    /**
     * @var string
     */
    public $permission;

    /**
     * @return int
     * @throws AuthorizationException
     */
    public function getPolicyType()
    {
        switch ($this->policy) {
            case 'Reject':
                return Policy::POLICY_REJECT;
            case 'Authorize':
                return Policy::POLICY_AUTHORIZE;
            case 'Authenticate':
                return Policy::POLICY_AUTHENTICATE;
            case 'Allow':
                return Policy::POLICY_ALLOW;
            default:
                throw new AuthorizationException(
                    sprintf(
                        'Bad policy type: %s',
                        print_r($this->policy, true)
                    )
                );
        }
    }
}