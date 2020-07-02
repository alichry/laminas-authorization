<?php

namespace AliChry\Laminas\Authorization\Test\Resource\Asset;

use AliChry\Laminas\Authorization\Annotation\Authorization;

/**
 * Class ControllerAsset
 * @version  dummy
 * @Authorization(policy="Authorize", permission="all")
 * @Authorization(link="link1", policy="Authorize", permission="fallback1")
 * @Authorization(link="link2", policy="Authorize", permission="fallback2")
 * @version dummy
 */
class ControllerTestAsset
{
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