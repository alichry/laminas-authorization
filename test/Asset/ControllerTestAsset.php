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

namespace AliChry\Laminas\Authorization\Test\Asset;

use AliChry\Laminas\Authorization\Annotation\Authorization;
use Laminas\Mvc\Controller\AbstractActionController;

/**
 * Class ControllerAsset
 * @version  dummy
 * @Authorization(policy="Authorize", permission="all")
 * @Authorization(link="link1", policy="Authorize", permission="fallback1")
 * @Authorization(link="link2", policy="Authorize", permission="fallback2")
 * @version dummy
 */
class ControllerTestAsset extends AbstractActionController
{
    /**
     * @Authorization(policy="Authenticate")
     * @Authorization(policy="Authorize", permission="bad")
     */
    public function bad()
    {
    }

    /**
     * @version dummy
     * @Authorization(link="some-unused-link", policy="Reject")
     */
    public function classFallback()
    {
    }

    /**
     * @version  dummy
     * @Authorization(policy="Allow")
     * @Authorization(link="some-unused-link", policy="Reject")
     * @version  dummy
     */
    public function methodFallback()
    {
    }

    /**
     * @version dummy
     * @Authorization(policy="Authenticate")
     * @Authorization(link="link1", policy="Allow")
     * @Authorization(link="link2", policy="Reject")
     * @version dummy
     */
    public function mix()
    {
    }

    /**
     * @version dummy
     * @Authorization(policy="Authenticate")
     * @Authorization(link="link1", policy="Allow")
     * @Authorization(link="link2", policy="Reject")
     * @version dummy
     */
    public function mixAction()
    {

    }

    /**
     * @version dummy
     * @Authorization(policy="Reject")
     * @Authorization(link="link1", policy="Authorize", permission="perm-1")
     * @Authorization(link="link2", policy="Authenticate")
     * @version dummy
     */
    public function mixAuth()
    {
    }

    /**
     * @version dummy
     * @Authorization(policy="Reject")
     * @Authorization(link="link1", policy="Authorize", permission="perm-1")
     * @Authorization(link="link2", policy="Authorize", permission="perm-2")
     * @version dummy
     */
    public function mixAuthDifferentPerm()
    {
    }
}