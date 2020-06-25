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

namespace AliChry\Laminas\Authorization\Test\Factory;

use AliChry\Laminas\AccessControl\Policy\Policy;
use AliChry\Laminas\Authorization\AuthorizationException;
use AliChry\Laminas\Authorization\Factory\AnnotatedResourceManagerFactory;
use AliChry\Laminas\Authorization\Resource\AnnotatedResourceManager;
use Doctrine\Common\Cache\CacheProvider;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use AliChry\Laminas\Authorization\Factory\AnnotatedResourceManagerFactory as Factory;
use SebastianBergmann\CodeCoverage\Report\PHP;
use Throwable;

class AnnotatedResourceManagerFactoryTest extends TestCase
{
    /**
     * @var AnnotatedResourceManagerFactory
     */
    private $factory;

    /**
     * @var MockObject|ContainerInterface
     */
    private $mockContainer;

    /**
     * @var MockObject|CacheProvider
     */
    private $mockCacheProvider;

    /**
     * @var string
     */
    private $expectedCacheKey = 'doctrine.cache.array';

    public function setUp()
    {
        $this->factory = new Factory();
        $this->mockContainer = $this->createMock(ContainerInterface::class);
        $this->mockCacheProvider = $this->createMock(CacheProvider::class);
        $key = &$this->expectedCacheKey;
        $this->mockContainer->expects($this->once())
            ->method('get')
            ->with($this->callback(function ($value) use (&$key) {
                    return $value == $key;
                })
            )->willReturn($this->mockCacheProvider);
    }

    public function testNoOptions()
    {
        $arm = $this->invokeFactory();
        $this->assertTrue(
            $arm instanceof AnnotatedResourceManager
        );
    }

    /**
     * @dataProvider modeProvider
     * @param $mode
     */
    public function testMode($mode)
    {
        $arm = $this->invokeFactory(
            [
                Factory::OPTION_MODE => $mode
            ]
        );
        $this->assertSame(
            $mode,
            $arm->getMode()
        );
    }

    /**
     * @dataProvider policyProvder
     * @param $policy
     */
    public function testPolicy($policy)
    {
        $arm = $this->invokeFactory(
            [
                Factory::OPTION_POLICY => $policy
            ]
        );
        $this->assertSame(
            $policy,
            $arm->getPolicy()
        );
    }

    public function testCache()
    {
        $this->expectedCacheKey = 'doctrine.cache.apcu';
        $arm = $this->invokeFactory(
            [
                Factory::OPTION_CACHE => 'apcu'
            ]
        );
    }

    /**
     * @return array[]
     */
    public function modeProvider()
    {
        return [
            [AnnotatedResourceManager::MODE_CHILL],
            [AnnotatedResourceManager::MODE_STRICT]
        ];
    }

    /**
     * @throws Throwable
     */
    public function testWrapsThrowable()
    {
        $this->expectException(ServiceNotCreatedException::class);
        try {
            $arm = $this->invokeFactory(
                [
                    // bad policy
                    Factory::OPTION_POLICY => Policy::POLICY_AUTHORIZE
                ]
            );
        } catch (Throwable $e) {
            $this->assertTrue(
                $e->getPrevious() instanceof AuthorizationException
            );
            throw $e;
        }
    }

    /**
     * @return array[]
     */
    public function policyProvder()
    {
        return [
            [Policy::POLICY_REJECT],
            [Policy::POLICY_AUTHENTICATE],
            [Policy::POLICY_ALLOW]
        ];
    }

    private function invokeFactory($options = null)
    {
        $factory = $this->factory;
        return $factory($this->mockContainer, null, $options);
    }

}