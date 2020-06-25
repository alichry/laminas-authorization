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
use AliChry\Laminas\Authorization\Annotation\Authorization;
use AliChry\Laminas\Authorization\AuthorizationException;
use AliChry\Laminas\Authorization\Resource\AnnotatedResourceManager;
use AliChry\Laminas\Authorization\Resource\LinkAwareResourceIdentifier;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use stdClass;
use TypeError;

class DummyController
{
    public function dummyAction()
    {
    }
}

class AnnotatedResourceManagerTest extends TestCase
{
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
        $mockReader = $this->createMock(Reader::class);
        $manager = new AnnotatedResourceManager(
            AnnotatedResourceManager::MODE_STRICT,
            Policy::POLICY_REJECT,
            $mockReader
        );
        $this->assertSame(
            $mockReader,
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
        $resourceManager = new AnnotatedResourceManager(
            AnnotatedResourceManager::MODE_STRICT,
            Policy::POLICY_REJECT
        );
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(
            AuthorizationException::ARM_BAD_RESOURCE_IDENTIFIER
        );
        $resourceManager->getResource($identifier);
    }

    /**
     * @throws AccessControlException
     * @throws AuthorizationException
     * @throws ReflectionException
     */
    public function testGetResourceAllow()
    {
        $controller = DummyController::class;
        $method = 'dummyAction';
        $link = 'dummy-link';
        $perm = null;
        $authorization = new Authorization();
        $authorization->link = $link;
        $authorization->policy = 'Allow';
        $authorization->permission = $perm;
        $mockReader = $this->createMock(
            Reader::class
        );
        $mockResourceIdentifier = $this->createMock(
            LinkAwareResourceIdentifier::class
        );
        $mockResourceIdentifier->expects($this->once())
            ->method('getLink')
            ->willReturn($link);
        $mockResourceIdentifier->expects($this->once())
            ->method('getController')
            ->willReturn($controller);
        $mockResourceIdentifier->expects($this->once())
            ->method('getMethod')
            ->willReturn($method);
        $mockReader->expects($this->once())
            ->method('getMethodAnnotations')
            ->with($this->callback(function (\ReflectionMethod $rm) use ($method) {
                $name = $rm->getName();
                return $name === $method;
            }))
            ->willReturn(
                [
                    new stdClass(),
                    null,
                    $authorization
                ]
            );

        $resourceManager = new AnnotatedResourceManager(
            AnnotatedResourceManager::MODE_STRICT,
            Policy::POLICY_REJECT,
            $mockReader
        );
        $resource = $resourceManager->getResource($mockResourceIdentifier);
        $this->assertTrue(
            $resource instanceof Resource
        );
        $this->assertEquals(
            new Resource(
                $mockResourceIdentifier,
                new Policy(Policy::POLICY_ALLOW),
                $perm
            ),
            $resource
        );
    }

    /**
     * @throws AccessControlException
     * @throws AuthorizationException
     * @throws ReflectionException
     */
    public function testGetResourceDefaultPolicyWithStrictMode()
    {
        $controller = DummyController::class;
        $method = 'dummyAction';
        $link = 'dummy-link';
        $notRelatedAuthorization = new Authorization();
        $notRelatedAuthorization->link = 'other-link';
        $notRelatedAuthorization->policy = 'Authorize';
        $notRelatedAuthorization->policy = 'other-perm';
        $mockReader = $this->createMock(
            Reader::class
        );
        $mockResourceIdentifier = $this->createMock(
            LinkAwareResourceIdentifier::class
        );
        $mockResourceIdentifier->expects($this->once())
            ->method('getLink')
            ->willReturn($link);
        $mockResourceIdentifier->expects($this->once())
            ->method('getController')
            ->willReturn($controller);
        $mockResourceIdentifier->expects($this->once())
            ->method('getMethod')
            ->willReturn($method);
        $mockReader->expects($this->once())
            ->method('getMethodAnnotations')
            ->with($this->callback(function (\ReflectionMethod $rm) use ($method) {
                $name = $rm->getName();
                return $name === $method;
            }))
            ->willReturn(
                [
                    new stdClass(),
                    null,
                    $notRelatedAuthorization
                ]
            );

        $resourceManager = new AnnotatedResourceManager(
            AnnotatedResourceManager::MODE_STRICT,
            Policy::POLICY_REJECT,
            $mockReader
        );
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(
            AuthorizationException::ARM_UNDEFINED_ANNOTATION
        );
        $resourceManager->getResource($mockResourceIdentifier);
    }

    /**
     * @throws AccessControlException
     * @throws AuthorizationException
     * @throws ReflectionException
     */
    public function testGetResourceDefaultPolicyWithChillMode()
    {
        $controller = DummyController::class;
        $method = 'dummyAction';
        $link = 'dummy-link';
        $notRelatedAuthorization = new Authorization();
        $notRelatedAuthorization->link = 'other-link';
        $notRelatedAuthorization->policy = 'Authorize';
        $notRelatedAuthorization->permission = 'other-perm';
        $mockReader = $this->createMock(
            Reader::class
        );
        $mockResourceIdentifier = $this->createMock(
            LinkAwareResourceIdentifier::class
        );
        $mockResourceIdentifier->expects($this->once())
            ->method('getLink')
            ->willReturn($link);
        $mockResourceIdentifier->expects($this->once())
            ->method('getController')
            ->willReturn($controller);
        $mockResourceIdentifier->expects($this->once())
            ->method('getMethod')
            ->willReturn($method);
        $mockReader->expects($this->once())
            ->method('getMethodAnnotations')
            ->with($this->callback(function (\ReflectionMethod $rm) use ($method) {
                $name = $rm->getName();
                return $name === $method;
            }))
            ->willReturn(
                [
                    new stdClass(),
                    null,
                    $notRelatedAuthorization
                ]
            );

        $resourceManager = new AnnotatedResourceManager(
            AnnotatedResourceManager::MODE_CHILL,
            Policy::POLICY_REJECT,
            $mockReader
        );
        $resource = $resourceManager->getResource($mockResourceIdentifier);
        $this->assertTrue(
            $resource instanceof Resource
        );
        $this->assertEquals(
            new Resource(
                $mockResourceIdentifier,
                new Policy(Policy::POLICY_REJECT)
            ),
            $resource
        );
    }

    /**
     * @throws AccessControlException
     * @throws AuthorizationException
     * @throws ReflectionException
     */
    public function testGetResourceReject()
    {
        $controller = DummyController::class;
        $method = 'dummyAction';
        $link = 'dummy-link';
        $perm = null;
        $authorization = new Authorization();
        $authorization->link = $link;
        $authorization->policy = 'Reject';
        $authorization->permission = $perm;
        $mockReader = $this->createMock(
            Reader::class
        );
        $mockResourceIdentifier = $this->createMock(
            LinkAwareResourceIdentifier::class
        );
        $mockResourceIdentifier->expects($this->once())
            ->method('getLink')
            ->willReturn($link);
        $mockResourceIdentifier->expects($this->once())
            ->method('getController')
            ->willReturn($controller);
        $mockResourceIdentifier->expects($this->once())
            ->method('getMethod')
            ->willReturn($method);
        $mockReader->expects($this->once())
            ->method('getMethodAnnotations')
            ->with($this->callback(function (\ReflectionMethod $rm) use ($method) {
                $name = $rm->getName();
                return $name === $method;
            }))
            ->willReturn(
                [
                    new stdClass(),
                    null,
                    $authorization
                ]
            );

        $resourceManager = new AnnotatedResourceManager(
            AnnotatedResourceManager::MODE_STRICT,
            Policy::POLICY_REJECT,
            $mockReader
        );
        $resource = $resourceManager->getResource($mockResourceIdentifier);
        $this->assertTrue(
            $resource instanceof Resource
        );
        $this->assertEquals(
            new Resource(
                $mockResourceIdentifier,
                new Policy(Policy::POLICY_REJECT),
                $perm
            ),
            $resource
        );
    }

    /**
     * @throws AccessControlException
     * @throws AuthorizationException
     * @throws ReflectionException
     */
    public function testGetResourceRejectWithWildcardLink()
    {
        $controller = DummyController::class;
        $method = 'dummyAction';
        $link = null;
        $perm = null;
        $authorization = new Authorization();
        $authorization->link = $link;
        $authorization->policy = 'Reject';
        $authorization->permission = $perm;
        $mockReader = $this->createMock(
            Reader::class
        );
        $mockResourceIdentifier = $this->createMock(
            LinkAwareResourceIdentifier::class
        );
        $mockResourceIdentifier->expects($this->once())
            ->method('getLink')
            ->willReturn('dummy-link'); // has to be different than the annotation

        $mockResourceIdentifier->expects($this->once())
            ->method('getController')
            ->willReturn($controller);
        $mockResourceIdentifier->expects($this->once())
            ->method('getMethod')
            ->willReturn($method);
        $mockReader->expects($this->once())
            ->method('getMethodAnnotations')
            ->with($this->callback(function (\ReflectionMethod $rm) use ($method) {
                $name = $rm->getName();
                return $name === $method;
            }))
            ->willReturn(
                [
                    new stdClass(),
                    null,
                    $authorization
                ]
            );

        $resourceManager = new AnnotatedResourceManager(
            AnnotatedResourceManager::MODE_STRICT,
            Policy::POLICY_REJECT,
            $mockReader
        );
        $resource = $resourceManager->getResource($mockResourceIdentifier);
        $this->assertTrue(
            $resource instanceof Resource
        );
        $this->assertEquals(
            new Resource(
                $mockResourceIdentifier,
                new Policy(Policy::POLICY_REJECT),
                $perm
            ),
            $resource
        );
    }

    /**
     * @throws AccessControlException
     * @throws AuthorizationException
     * @throws ReflectionException
     */
    public function testGetResourceAuthenticate()
    {
        $controller = DummyController::class;
        $method = 'dummyAction';
        $link = 'dummy-link';
        $perm = null;
        $authorization = new Authorization();
        $authorization->link = $link;
        $authorization->policy = 'Authenticate';
        $authorization->permission = $perm;
        $mockReader = $this->createMock(
            Reader::class
        );
        $mockResourceIdentifier = $this->createMock(
            LinkAwareResourceIdentifier::class
        );
        $mockResourceIdentifier->expects($this->once())
            ->method('getLink')
            ->willReturn($link);
        $mockResourceIdentifier->expects($this->once())
            ->method('getController')
            ->willReturn($controller);
        $mockResourceIdentifier->expects($this->once())
            ->method('getMethod')
            ->willReturn($method);
        $mockReader->expects($this->once())
            ->method('getMethodAnnotations')
            ->with($this->callback(function (\ReflectionMethod $rm) use ($method) {
                $name = $rm->getName();
                return $name === $method;
            }))
            ->willReturn(
                [
                    new stdClass(),
                    null,
                    $authorization
                ]
            );

        $resourceManager = new AnnotatedResourceManager(
            AnnotatedResourceManager::MODE_STRICT,
            Policy::POLICY_REJECT,
            $mockReader
        );
        $resource = $resourceManager->getResource($mockResourceIdentifier);
        $this->assertTrue(
            $resource instanceof Resource
        );
        $this->assertEquals(
            new Resource(
                $mockResourceIdentifier,
                new Policy(Policy::POLICY_AUTHENTICATE),
                $perm
            ),
            $resource
        );
    }

    /**
     * @throws AccessControlException
     * @throws AuthorizationException
     * @throws ReflectionException
     */
    public function testGetResourceAuthenticateWithWildcardLink()
    {
        $controller = DummyController::class;
        $method = 'dummyAction';
        $link = null;
        $perm = null;
        $authorization = new Authorization();
        $authorization->link = $link;
        $authorization->policy = 'Authenticate';
        $authorization->permission = $perm;
        $mockReader = $this->createMock(
            Reader::class
        );
        $mockResourceIdentifier = $this->createMock(
            LinkAwareResourceIdentifier::class
        );
        $mockResourceIdentifier->expects($this->once())
            ->method('getLink')
            ->willReturn(null); // has to be different than the annotation link
        $mockResourceIdentifier->expects($this->once())
            ->method('getController')
            ->willReturn($controller);
        $mockResourceIdentifier->expects($this->once())
            ->method('getMethod')
            ->willReturn($method);
        $mockReader->expects($this->once())
            ->method('getMethodAnnotations')
            ->with($this->callback(function (\ReflectionMethod $rm) use ($method) {
                $name = $rm->getName();
                return $name === $method;
            }))
            ->willReturn(
                [
                    new stdClass(),
                    null,
                    $authorization
                ]
            );

        $resourceManager = new AnnotatedResourceManager(
            AnnotatedResourceManager::MODE_STRICT,
            Policy::POLICY_REJECT,
            $mockReader
        );
        $resource = $resourceManager->getResource($mockResourceIdentifier);
        $this->assertTrue(
            $resource instanceof Resource
        );
        $this->assertEquals(
            new Resource(
                $mockResourceIdentifier,
                new Policy(Policy::POLICY_AUTHENTICATE),
                $perm
            ),
            $resource
        );
    }

    /**
     * @throws AccessControlException
     * @throws AuthorizationException
     * @throws ReflectionException
     */
    public function testGetResourceAuthorize()
    {
        $controller = DummyController::class;
        $method = 'dummyAction';
        $link = 'dummy-link';
        $perm = 'dummy-perm';
        $authorization = new Authorization();
        $authorization->link = $link;
        $authorization->policy = 'Authorize';
        $authorization->permission = $perm;
        $mockReader = $this->createMock(
            Reader::class
        );
        $mockResourceIdentifier = $this->createMock(
            LinkAwareResourceIdentifier::class
        );
        $mockResourceIdentifier->expects($this->once())
            ->method('getLink')
            ->willReturn($link);
        $mockResourceIdentifier->expects($this->once())
            ->method('getController')
            ->willReturn($controller);
        $mockResourceIdentifier->expects($this->once())
            ->method('getMethod')
            ->willReturn($method);
        $mockReader->expects($this->once())
            ->method('getMethodAnnotations')
            ->with($this->callback(function (\ReflectionMethod $rm) use ($method) {
                $name = $rm->getName();
                return $name === $method;
            }))
            ->willReturn(
                [
                    new stdClass(),
                    null,
                    $authorization
                ]
            );

        $resourceManager = new AnnotatedResourceManager(
            AnnotatedResourceManager::MODE_STRICT,
            Policy::POLICY_REJECT,
            $mockReader
        );
        $resource = $resourceManager->getResource($mockResourceIdentifier);
        $this->assertTrue(
            $resource instanceof Resource
        );
        $this->assertEquals(
            new Resource(
                $mockResourceIdentifier,
                new Policy(Policy::POLICY_AUTHORIZE),
                $perm
            ),
            $resource
        );
    }

    /**
     * @throws AccessControlException
     * @throws AuthorizationException
     * @throws ReflectionException
     */
    public function testGetResourceAuthorizeWithWildcardLink()
    {
        $controller = DummyController::class;
        $method = 'dummyAction';
        $link = null;
        $perm = 'dummy-perm';
        $authorization = new Authorization();
        $authorization->link = $link;
        $authorization->policy = 'Authorize';
        $authorization->permission = $perm;
        $mockReader = $this->createMock(
            Reader::class
        );
        $mockResourceIdentifier = $this->createMock(
            LinkAwareResourceIdentifier::class
        );
        $mockResourceIdentifier->expects($this->once())
            ->method('getLink')
            ->willReturn(null); // has to be different than the annotation link
        $mockResourceIdentifier->expects($this->once())
            ->method('getController')
            ->willReturn($controller);
        $mockResourceIdentifier->expects($this->once())
            ->method('getMethod')
            ->willReturn($method);
        $mockReader->expects($this->once())
            ->method('getMethodAnnotations')
            ->with($this->callback(function (\ReflectionMethod $rm) use ($method) {
                $name = $rm->getName();
                return $name === $method;
            }))
            ->willReturn(
                [
                    new stdClass(),
                    null,
                    $authorization
                ]
            );

        $resourceManager = new AnnotatedResourceManager(
            AnnotatedResourceManager::MODE_STRICT,
            Policy::POLICY_REJECT,
            $mockReader
        );
        $resource = $resourceManager->getResource($mockResourceIdentifier);
        $this->assertTrue(
            $resource instanceof Resource
        );
        $this->assertEquals(
            new Resource(
                $mockResourceIdentifier,
                new Policy(Policy::POLICY_AUTHORIZE),
                $perm
            ),
            $resource
        );
    }

    /**
     * @dataProvider dataProvider
     * @param $mode
     * @param $policy
     * @param $reader
     * @throws AccessControlException
     * @throws AuthorizationException
     */
    public function testAll($mode, $policy, $reader)
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