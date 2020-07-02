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

namespace AliChry\Laminas\Authorization\Test\Resource;

use AliChry\Laminas\AccessControl\AccessControlException;
use AliChry\Laminas\AccessControl\Policy\Policy;
use AliChry\Laminas\AccessControl\Resource\Resource;
use AliChry\Laminas\AccessControl\Resource\ResourceIdentifier;
use AliChry\Laminas\Authorization\Annotation\Authorization;
use AliChry\Laminas\Authorization\AuthorizationException;
use AliChry\Laminas\Authorization\Resource\AnnotatedResourceManager;
use AliChry\Laminas\Authorization\Resource\LinkAwareResourceIdentifier;
use AliChry\Laminas\Authorization\Test\Resource\Asset\BadDummyControllerTestAsset;
use AliChry\Laminas\Authorization\Test\Resource\Asset\ControllerAsset;
use AliChry\Laminas\Authorization\Test\Resource\Asset\ControllerTestAsset;
use AliChry\Laminas\Authorization\Test\Resource\Asset\DummyControllerTestAsset;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use stdClass;
use TypeError;
use function array_merge;
use function foo\func;

class AnnotatedResourceManagerTest extends TestCase
{
    /**
     * @var AnnotationReader
     */
    private $reader;

    /**
     * @var MockObject|AnnotationReader
     */
    private $mockReader;

    /**
     * @var AnnotatedResourceManager
     */
    private $manager;

    /**
     * Used by mockReader
     *
     * @var callable
     */
    private $readerGetMethodAnnotations;

    /**
     * Used by mockReader
     *
     * @var callable
     */
    private $readerGetClassAnnotations;

    /**
     * Used by mockReader
     *
     * @var array|Authorization[]
     */
    private $classAnnotations;

    /**
     * Used by mockReader
     *
     * @var array|Authorization[]
     */
    private $methodAnnotations;

    /**
     * @throws AccessControlException
     * @throws AuthorizationException
     */
    public function setUp()
    {
        $this->reader = new AnnotationReader();
        $this->mockReader = $this->createMock(
            AnnotationReader::class
        );
        $this->manager = new AnnotatedResourceManager(
            AnnotatedResourceManager::MODE_STRICT,
            Policy::POLICY_REJECT,
            $this->reader
        );
    }

    protected function registerMockReaderExpectations()
    {
        $this->manager->setReader($this->mockReader);

        $classCallback = &$this->readerGetClassAnnotations;
        $methodCallback = &$this->readerGetMethodAnnotations;

        $classAnnotations = &$this->classAnnotations;
        $methodAnnotations = &$this->methodAnnotations;

        $this->mockReader->expects($this->once())
            ->method('getClassAnnotations')
            ->with($this->callback(function ($r) use (&$classCallback) {
                return call_user_func($classCallback, $r);
            }))->willReturnCallback(function () use (&$classAnnotations) {
                return $classAnnotations;
            });
        $this->mockReader->expects($this->once())
            ->method('getMethodAnnotations')
            ->with($this->callback(function ($r) use (&$methodCallback) {
                return call_user_func($methodCallback, $r);
            }))->willReturnCallback(function () use (&$methodAnnotations) {
                return $methodAnnotations;
            });
    }

    protected function registerMockClassAnnotationCallback(string $classname)
    {
        $this->readerGetClassAnnotations =
            function (\ReflectionClass $rc) use ($classname) {
                return $rc->getName() === $classname;
        };
    }

    protected function registerMockMethodAnnotationCallback(string $method)
    {
        $this->readerGetMethodAnnotations =
            function (\ReflectionMethod $rm) use ($method) {
                return $rm->getName() === $method;
        };
    }

    protected function registerMockClassAnnotations(array $a)
    {
        $this->classAnnotations = $a;
    }

    protected function registerMockMethodAnnotations(array $a)
    {
        $this->methodAnnotations = $a;
    }

    /**
     * @param $class
     * @param $method
     * @throws ReflectionException
     */
    protected function registerMockReader($class, $method)
    {
        $this->registerMockReaderExpectations();
        $this->registerMockClassAnnotationCallback($class);
        $this->registerMockMethodAnnotationCallback($method);
        $this->registerMockClassAnnotations(
            // add dummy stdClass instance
            array_merge(
                $this->getClassAnnotations($class),
                [
                    new stdClass()
                ]
            )
        );
        $this->registerMockMethodAnnotations(
            // add dummy stdClass instance
            array_merge(
                $this->getMethodAnnotations($class, $method),
                [
                    new stdClass()
                ]
            )
        );
    }

    /**
     * @param $class
     * @param $method
     * @return array
     * @throws ReflectionException
     */
    protected function getMethodAnnotations($class, $method)
    {
        $rc = new \ReflectionClass($class);
        $rm = $rc->getMethod($method);
        return $this->reader->getMethodAnnotations($rm);
    }

    /**
     * @param $class
     * @return array
     * @throws ReflectionException
     */
    protected function getClassAnnotations($class)
    {
        $rc = new \ReflectionClass($class);
        return $this->reader->getClassAnnotations($rc);
    }

    /**
     * @throws AuthorizationException
     * @throws AccessControlException
     */
    public function testMode()
    {
        $mode = AnnotatedResourceManager::MODE_STRICT;
        $mode2 = AnnotatedResourceManager::MODE_CHILL;
        $manager = new AnnotatedResourceManager($mode);
        $this->assertSame(
            $mode,
            $manager->getMode()
        );
        $manager->setMode($mode2);
        $this->assertSame(
            $mode2,
            $manager->getMode()
        );
    }

    /**
     * @throws AuthorizationException
     * @throws AccessControlException
     */
    public function testPolicy()
    {
        $policyReject = Policy::POLICY_REJECT;
        $policyAuthenticate = Policy::POLICY_AUTHENTICATE;
        $policyAllow = Policy::POLICY_ALLOW;
        $manager = new AnnotatedResourceManager(
            AnnotatedResourceManager::MODE_STRICT
        );
        // default policy is reject
        $this->assertSame(
            $policyReject,
            $manager->getPolicy()
        );
        $manager->setPolicy($policyReject);
        $this->assertSame(
            $policyReject,
            $manager->getPolicy()
        );
        $this->assertSame(
            $policyReject,
            $manager->getPolicy()
        );
        $manager->setPolicy($policyAuthenticate);
        $this->assertSame(
            $policyAuthenticate,
            $manager->getPolicy()
        );
        $manager->setPolicy($policyAllow);
        $this->assertSame(
            $policyAllow,
            $manager->getPolicy()
        );
    }

    /**
     * @throws AccessControlException
     * @throws AuthorizationException
     */
    public function testReader()
    {
        $manager = new AnnotatedResourceManager(
            AnnotatedResourceManager::MODE_STRICT
        );
        $this->assertTrue(
            $manager->getReader() instanceof AnnotationReader
        );
        $reader = new AnnotationReader();
        $manager = new AnnotatedResourceManager(
            AnnotatedResourceManager::MODE_STRICT,
            Policy::POLICY_REJECT,
            $reader
        );
        $this->assertSame(
            $reader,
            $manager->getReader()
        );
    }

    /**
     * @dataProvider badIdentifierProvider
     * @throws AccessControlException
     * @throws AuthorizationException
     * @throws ReflectionException
     */
    public function testGetResourceWithBadIdentifier($identifier)
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(
            AuthorizationException::ARM_BAD_RESOURCE_IDENTIFIER
        );
        $this->manager->getResource($identifier);
    }

    /**
     * @throws AccessControlException
     * @throws AuthorizationException
     * @throws ReflectionException
     */
    public function testGetResourceDefaultPolicyWithStrictMode()
    {
        $class = DummyControllerTestAsset::class;
        $method = 'dummy';
        $link = 'link'; // not found
        $identifier = new LinkAwareResourceIdentifier(
            $link,
            $class,
            $method
        );
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(
            AuthorizationException::ARM_UNDEFINED_ANNOTATION
        );
        $this->manager->getResource($identifier);
    }

    /**
     * @throws AccessControlException
     * @throws AuthorizationException
     * @throws ReflectionException
     */
    public function testGetResourceDefaultPolicyWithChillMode()
    {
        $this->manager->setMode(AnnotatedResourceManager::MODE_CHILL);
        $class = DummyControllerTestAsset::class;
        $method = 'dummy';
        $link = 'link'; // not found, but default policy will be used
        $identifier = new LinkAwareResourceIdentifier(
            $link,
            $class,
            $method
        );
        $expectedResource = new Resource(
            $identifier,
            new Policy(Policy::POLICY_REJECT)
        );

        $this->assertEquals(
            $expectedResource,
            $this->manager->getResource($identifier)
        );

        $this->registerMockReader($class, $method);

        $this->assertEquals(
            $expectedResource,
            $this->manager->getResource($identifier)
        );
    }

    public function testGetResourceAllow()
    {
        $class = ControllerTestAsset::class;
        $method = 'mix';
        $link = 'link1';
        $identifier = new LinkAwareResourceIdentifier(
            $link,
            $class,
            $method
        );
        $expectedResource = new Resource(
            $identifier,
            new Policy(Policy::POLICY_ALLOW)
        );

        $this->assertEquals(
            $expectedResource,
            $this->manager->getResource($identifier)
        );

        $this->registerMockReader($class, $method);

        $this->assertEquals(
            $expectedResource,
            $this->manager->getResource($identifier)
        );
    }

    /**
     * @throws AccessControlException
     * @throws AuthorizationException
     * @throws ReflectionException
     */
    public function testGetResourceReject()
    {
        $class = ControllerTestAsset::class;
        $method = 'mix';
        $link = 'link2';
        $identifier = new LinkAwareResourceIdentifier(
            $link,
            $class,
            $method
        );
        $expectedResource = new Resource(
            $identifier,
            new Policy(Policy::POLICY_REJECT)
        );

        $this->assertEquals(
            $expectedResource,
            $this->manager->getResource($identifier)
        );

        $this->registerMockReader($class, $method);

        $this->assertEquals(
            $expectedResource,
            $this->manager->getResource($identifier)
        );
    }

    /**
     * @throws AccessControlException
     * @throws AuthorizationException
     * @throws ReflectionException
     */
    public function testGetResourceAuthenticate()
    {
        $class = ControllerTestAsset::class;
        $method = 'mixAuth';
        $link = 'link2';
        $identifier = new LinkAwareResourceIdentifier(
            $link,
            $class,
            $method
        );
        $expectedResource = new Resource(
            $identifier,
            new Policy(Policy::POLICY_AUTHENTICATE)
        );

        $this->assertEquals(
            $expectedResource,
            $this->manager->getResource($identifier)
        );

        $this->registerMockReader($class, $method);

        $this->assertEquals(
            $expectedResource,
            $this->manager->getResource($identifier)
        );
    }

    /**
     * @throws AccessControlException
     * @throws AuthorizationException
     * @throws ReflectionException
     */
    public function testGetResourceAuthorize()
    {
        $class = ControllerTestAsset::class;
        $method = 'mixAuth';
        $link = 'link1';
        $identifier = new LinkAwareResourceIdentifier(
            $link,
            $class,
            $method
        );
        $expectedResource = new Resource(
            $identifier,
            new Policy(Policy::POLICY_AUTHORIZE),
            'perm-1'
        );

        $this->assertEquals(
            $expectedResource,
            $this->manager->getResource($identifier)
        );

        $this->registerMockReader($class, $method);

        $this->assertEquals(
            $expectedResource,
            $this->manager->getResource($identifier)
        );
    }

    /**
     * @throws AccessControlException
     * @throws AuthorizationException
     * @throws ReflectionException
     */
    public function testGetResourceMethodDefaultAnnotationFallback()
    {
        $class = ControllerTestAsset::class;
        $method = 'methodFallback';
        $link = 'link2';
        $identifier = new LinkAwareResourceIdentifier(
            $link,
            $class,
            $method
        );
        $expectedResource = new Resource(
            $identifier,
            new Policy(Policy::POLICY_ALLOW)
        );

        $this->assertEquals(
            $expectedResource,
            $this->manager->getResource($identifier)
        );

        $this->registerMockReader($class, $method);

        $this->assertEquals(
            $expectedResource,
            $this->manager->getResource($identifier)
        );
    }

    public function testGetResourceClassFallback()
    {
        $class = ControllerTestAsset::class;
        $method = 'classFallback';
        $link = 'link2';
        $identifier = new LinkAwareResourceIdentifier(
            $link,
            $class,
            $method
        );
        $expectedResource = new Resource(
            $identifier,
            new Policy(Policy::POLICY_AUTHORIZE),
            'fallback2'
        );

        $this->assertEquals(
            $expectedResource,
            $this->manager->getResource($identifier)
        );

        $this->registerMockReader($class, $method);

        $this->assertEquals(
            $expectedResource,
            $this->manager->getResource($identifier)
        );
    }

    public function testGetResourceClassDefaultAnnotationFallback()
    {
        $class = ControllerTestAsset::class;
        $method = 'classFallback';
        $link = 'link3';
        $identifier = new LinkAwareResourceIdentifier(
            $link,
            $class,
            $method
        );
        $expectedResource = new Resource(
            $identifier,
            new Policy(Policy::POLICY_AUTHORIZE),
            'all'
        );

        $this->assertEquals(
            $expectedResource,
            $this->manager->getResource($identifier)
        );

        $this->registerMockReader($class, $method);

        $this->assertEquals(
            $expectedResource,
            $this->manager->getResource($identifier)
        );
    }

    public function testGetResourceDuplicateClassAnnotations()
    {
        $class = BadDummyControllerTestAsset::class;
        $method = 'empty';
        $link = 'link';
        $identifier = new LinkAwareResourceIdentifier(
            $link,
            $class,
            $method
        );
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(
            AuthorizationException::ARM_DUPLICATE_ANNOTATION
        );
        $this->manager->getResource($identifier);
    }

    public function testGetResourceDuplicateMethodAnnotations()
    {
        $class = ControllerTestAsset::class;
        $method = 'bad';
        $link = 'link';
        $identifier = new LinkAwareResourceIdentifier(
            $link,
            $class,
            $method
        );
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(
            AuthorizationException::ARM_DUPLICATE_ANNOTATION
        );
        $this->manager->getResource($identifier);
    }

    /**
     * @dataProvider dataProvider
     * @param $mode
     * @param $policy
     * @param $reader
     * @throws AccessControlException
     * @throws AuthorizationException
     */
    public function testGettersAndSetters($mode, $policy, $reader)
    {
        $expectingException = null;
        $expectingExceptionCode = null;
        if (
            null !== $mode
            && AnnotatedResourceManager::MODE_STRICT !== $mode
            && AnnotatedResourceManager::MODE_CHILL !== $mode
        ) {
            $expectingException = AuthorizationException::class;
            $expectingExceptionCode = AuthorizationException::ARM_BAD_MODE;
        } else if (
            null !== $policy
            && ! Policy::checkType($policy)
        ) {
            $expectingException = AccessControlException::class;
        } else If (Policy::POLICY_AUTHORIZE === $policy) {
            $expectingException = AuthorizationException::class;
        } else if (null !== $reader && ! $reader instanceof Reader) {
            $expectingException = TypeError::class;
        }
        if (isset($expectingException)) {
            $this->expectException($expectingException);
        }
        if (isset($expectingExceptionCode)) {
            $this->expectExceptionCode($expectingExceptionCode);
        }
        $manager = new AnnotatedResourceManager(
            $mode,
            $policy,
            $reader
        );
        if ($expectingException) {
            return;
        }
        $this->assertSame(
            $mode ?? AnnotatedResourceManager::MODE_STRICT,
            $manager->getMode()
        );
        $this->assertSame(
            $policy ?? Policy::POLICY_REJECT,
            $manager->getPolicy()
        );
        if (null === $reader) {
            $this->assertTrue(
                $manager->getReader() instanceof Reader
            );
        } else {
            $this->assertSame(
                $reader,
                $manager->getReader()
            );
        }
    }

    public function dataProvider()
    {
        $modes = [
            null,
            AnnotatedResourceManager::MODE_STRICT - 1,
            AnnotatedResourceManager::MODE_CHILL + 1,
            AnnotatedResourceManager::MODE_STRICT,
            AnnotatedResourceManager::MODE_CHILL
        ];
        $policies = [
            null,
            Policy::POLICY_REJECT - 1,
            Policy::POLICY_ALLOW + 1,
            Policy::POLICY_REJECT,
            Policy::POLICY_AUTHORIZE,
            Policy::POLICY_AUTHENTICATE,
            Policy::POLICY_ALLOW
        ];
        $readers = [
            null,
            $this->createMock(Reader::class),
            new stdClass()
        ];
        $data = [];
        foreach ($modes as $mode) {
            foreach ($policies as $policy) {
                foreach ($readers as $reader) {
                    $data[] = [
                        $mode,
                        $policy,
                        $reader
                    ];
                }
            }
        }
        return $data;
    }

    public function badIdentifierProvider()
    {
        return [
            [null],
            [new stdClass()],
            [1],
            ['yum'],
            [[]]
        ];
    }
}