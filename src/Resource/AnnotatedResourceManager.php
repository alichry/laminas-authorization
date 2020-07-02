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

namespace AliChry\Laminas\Authorization\Resource;

use AliChry\Laminas\AccessControl\AccessControlException;
use AliChry\Laminas\AccessControl\Policy\Policy;
use AliChry\Laminas\AccessControl\Resource\Resource;
use AliChry\Laminas\AccessControl\Resource\ResourceInterface;
use AliChry\Laminas\AccessControl\Resource\ResourceManagerInterface;
use AliChry\Laminas\Authorization\Annotation\Authorization;
use AliChry\Laminas\Authorization\AuthorizationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use ReflectionClass;
use ReflectionException;

class AnnotatedResourceManager implements ResourceManagerInterface
{
    const MODE_STRICT = 0;
    const MODE_CHILL = 1;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var int
     */
    private $mode;

    /**
     * @var int
     */
    private $policy;

    /**
     * AnnotatedResourceManager constructor.
     * @param int $mode
     * @param int $policy
     * @param AnnotationReader $reader
     * @throws AuthorizationException
     * @throws AccessControlException
     */
    public function __construct(
        $mode = null,
        $policy = null,
        $reader = null
    )
    {
        if (null === $mode) {
            $mode = self::MODE_STRICT;
        }
        if (null === $policy) {
            $policy = Policy::POLICY_REJECT;
        }
        $reader = $reader ?? new AnnotationReader();
        $this->setMode($mode);
        $this->setPolicy($policy);
        $this->setReader($reader);
    }

    /**
     * @return int
     */
    public function getMode(): int
    {
        return $this->mode;
    }

    /**
     * @param int $mode
     * @throws AuthorizationException
     */
    public function setMode(int $mode)
    {
        if (! $this->checkMode($mode)) {
            throw new AuthorizationException(
                sprintf(
                    'Invalid mode: %d',
                    $mode
                ),
                AuthorizationException::ARM_BAD_MODE
            );
        }
        $this->mode = $mode;
    }

    /**
     * @return int
     */
    public function getPolicy(): int
    {
        return $this->policy;
    }

    /**
     * @param int $policy
     * @throws AuthorizationException
     * @throws AccessControlException
     */
    public function setPolicy(int $policy)
    {
        if (! Policy::checkType($policy)) {
            throw new AccessControlException(
                sprintf(
                    'Bad policy value: %s',
                    print_r($policy, true)
                )
            );
        }
        if ($policy === Policy::POLICY_AUTHORIZE) {
            throw new AuthorizationException(
                sprintf(
                    'Specified default policy cannot be %d (%s), only %d (%s), '
                    . '%d (%s) and %d (%s) are allowed',
                    $policy, Policy::class . '::POLICY_AUTHORIZE',
                    Policy::POLICY_REJECT, Policy::class . '::POLICY_REJECT',
                    Policy::POLICY_AUTHENTICATE, Policy::class . '::POLICY_AUTHENTICATE',
                    Policy::POLICY_ALLOW, Policy::class . '::POLICY_ALLOW'
                )
            );
        }
        $this->policy = $policy;
    }

    /**
     * @return Reader
     */
    public function getReader(): Reader
    {
        return $this->reader;
    }

    public function setReader(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param LinkAwareResourceIdentifier $resourceIdentifier
     * @return ResourceInterface
     * @throws ReflectionException
     * @throws AuthorizationException
     * @throws AccessControlException
     */
    public function getResource($resourceIdentifier)
    {
        if (! $resourceIdentifier instanceof LinkAwareResourceIdentifier) {
            throw new AuthorizationException(
                sprintf(
                    'Expecting passed resource identifier to be an instance of'
                    . ' %s, got %s',
                    LinkAwareResourceIdentifier::class,
                    is_object($resourceIdentifier)
                        ? get_class($resourceIdentifier)
                        : gettype($resourceIdentifier)
                ),
                AuthorizationException::ARM_BAD_RESOURCE_IDENTIFIER
            );
        }
        $link = $resourceIdentifier->getLink();
        $controller = $resourceIdentifier->getController();
        $method = $resourceIdentifier->getMethod();
        $reflectionClass = new ReflectionClass(
            $controller
        );
        $reflectionMethod = $reflectionClass->getMethod(
            $method
        );
        $classAnnotations = $this->reader->getClassAnnotations($reflectionClass);
        $methodAnnotations = $this->reader->getMethodAnnotations($reflectionMethod);
        $classLinkMatch = null;
        $methodLinkMatch = null;
        foreach ($classAnnotations as $annotation) {
            if (! $annotation instanceof Authorization) {
                continue;
            }
            if ($link === $annotation->link) {
                $classLinkMatch = $annotation;
                break;
            }
            if (null === $annotation->link) {
                if (null !== $classLinkMatch) {
                    throw new AuthorizationException(
                        sprintf(
                            'Duplicate authorization annotation for class %s',
                            $controller
                        ),
                        AuthorizationException::ARM_DUPLICATE_ANNOTATION
                    );
                }
                // closest match
                $classLinkMatch = $annotation;
            }
        }
        foreach ($methodAnnotations as $annotation) {
            if (! $annotation instanceof Authorization) {
                continue;
            }
            if ($link === $annotation->link) {
                $methodLinkMatch = $annotation;
                break;
            }
            if (null === $annotation->link) {
                if (null !== $methodLinkMatch) {
                    throw new AuthorizationException(
                        sprintf(
                            'Duplicate authorization annotation for method %s:%s',
                            $controller, $method
                        ),
                        AuthorizationException::ARM_DUPLICATE_ANNOTATION
                    );
                }
                // keep match as an annotation with no specified link
                // considered as wildcard (this is closest match)
                $methodLinkMatch = $annotation;
            }
        }
        if ($methodLinkMatch) {
            $match = $methodLinkMatch;
        } else {
            $match = $classLinkMatch;
        }
        if (null === $match) {
            // throw exception if strict mode is used
            if (self::MODE_STRICT === $this->mode) {
                throw new AuthorizationException(
                    sprintf(
                        'Undefined authorization annotation for %s method'
                        . ' of %s, unable to complete authorization process',
                        $controller,
                        $method
                    ),
                    AuthorizationException::ARM_UNDEFINED_ANNOTATION
                );
            }
            // fallback to provided policy
            return new Resource(
                $resourceIdentifier,
                new Policy($this->policy)
            );
        }

        return new Resource(
            $resourceIdentifier,
            new Policy($match->getPolicyType()),
            $match->permission
        );
    }

    /**
     * @param int $mode
     * @return bool
     */
    private function checkMode(int $mode): bool
    {
        return $mode === self::MODE_STRICT
            || $mode === self::MODE_CHILL;
    }
}