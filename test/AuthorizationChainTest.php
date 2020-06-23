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
 * Date: 2020-06-02
 * Time: 12:38
 */
declare(strict_types=1);

namespace AliChry\Laminas\Authorization\Test;

use AliChry\Laminas\AccessControl\AccessControlException;
use AliChry\Laminas\Authorization\AuthorizationChain;
use AliChry\Laminas\Authorization\AuthorizationException;
use AliChry\Laminas\Authorization\AuthorizationLink;
use AliChry\Laminas\Authorization\AuthorizationResult;
use Laminas\Http\Header\Authorization;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthorizationChainTest extends TestCase
{
    public function testConstructorOperator()
    {
        $operator1 = AuthorizationChain::OPERATOR_OR;
        $operator2 = AuthorizationChain::OPERATOR_AND;

        $chain1 = new AuthorizationChain($operator1);
        $chain2 = new AuthorizationChain($operator2);

        $this->assertSame(
            $operator1,
            $chain1->getOperator()
        );
        $this->assertSame(
            $operator2,
            $chain2->getOperator()
        );

        $badOperator = -10;
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AC_INVALID_OPERATOR);
        $chain2->setOperator($badOperator);
    }

    public function testGetSetOperator()
    {
        $chain = new AuthorizationChain();
        $chain->setOperator(AuthorizationChain::OPERATOR_AND);
        $this->assertSame(
            AuthorizationChain::OPERATOR_AND,
            $chain->getOperator()
        );
        $chain->setOperator(AuthorizationChain::OPERATOR_OR);
        $this->assertSame(
            AuthorizationChain::OPERATOR_OR,
            $chain->getOperator()
        );

        $badOperator = -10;
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AC_INVALID_OPERATOR);
        $chain->setOperator($badOperator);
    }

    public function testConstructorLinks()
    {
        $links = [
            'link1' => $this->createMock(AuthorizationLink::class),
            'link2' => $this->createMock(AuthorizationLink::class)
        ];
        $chain = new AuthorizationChain(AuthorizationChain::OPERATOR_OR, $links);
        $this->assertSame(
            $links,
            $chain->getLinks()
        );
        $this->assertSame(
            $links['link1'],
            $chain->getLink('link1')
        );
        $this->assertSame(
            $links['link1'],
            $chain->getLink(0)
        );
        $this->assertSame(
            $links['link2'],
            $chain->getLink('link2')
        );
        $this->assertSame(
            $links['link2'],
            $chain->getLink(1)
        );
    }

    public function testGetSetLinks()
    {
        $links = [
            'link1' => $this->createMock(AuthorizationLink::class),
            'link2' => $this->createMock(AuthorizationLink::class),
            'bad-link' => null
        ];
        $chain = new AuthorizationChain();
        $chain->setLinks($links);
        $this->assertSame(
            $links,
            $chain->getLinks()
        );
        $this->assertSame(
            $links['link1'],
            $chain->getLink('link1')
        );
        $this->assertSame(
            $links['link1'],
            $chain->getLink(0)
        );
        $this->assertSame(
            $links['link2'],
            $chain->getLink('link2')
        );
        $this->assertSame(
            $links['link2'],
            $chain->getLink(1)
        );
        $this->expectException(AuthorizationException::class);
        $chain->getLink('bad-link');
    }

    public function testAddGetlinks()
    {
        $link1 = $this->createMock(AuthorizationLink::class);
        $link2 = $this->createMock(AuthorizationLink::class);
        $link1->expects($this->any())
            ->method('getName')
            ->willReturn('link1');
        $link2->expects($this->any())
            ->method('getName')
            ->willReturn('link2');
        $links = [
            'link1' => $link1,
            'link2' => $link2
        ];
        $chain = new AuthorizationChain();
        $chain->addLink($links['link1']);
        $chain->addLink($links['link2']);
        $this->assertSame(
            $links,
            $chain->getLinks()
        );
        $this->assertSame(
            $links['link1'],
            $chain->getLink('link1')
        );
        $this->assertSame(
            $links['link1'],
            $chain->getLink(0)
        );
        $this->assertSame(
            $links['link2'],
            $chain->getLink('link2')
        );
        $this->assertSame(
            $links['link2'],
            $chain->getLink(1)
        );
        $this->expectException(\TypeError::class);
        $chain->addLink(null);
    }

    public function testAddLinkWithDuplicateLinkName()
    {
        $linkName = 'link';

        $link1 = $this->createMock(AuthorizationLink::class);
        $link2 = $this->createMock(AuthorizationLink::class);

        $link1->expects($this->once())
            ->method('getName')
            ->willReturn($linkName);
        $link2->expects($this->once())
            ->method('getName')
            ->willReturn($linkName);
        $chain = new AuthorizationChain();
        $chain->addLink($link1);
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AC_DUPLICATE_LINK_NAME);
        $chain->addLink($link2);
    }

    /**
     * @dataProvider isAuthorizedProvider
     * @param $operator
     * @param null|string $controller
     * @param null|string $method
     * @param array|?AuthorizationLink[] $links
     * @param array $expected
     * @throws AuthorizationException
     * @throws AccessControlException
     */
    public function testIsAuthorized(
        $operator,
        $controller,
        $method,
        array $links,
        array $expected
    )
    {
        $expectingException = isset($expected['exception']);
        if ($expectingException) {
            $this->expectException($expected['exception']);
        }
        $chain = new AuthorizationChain($operator, $links);
        $result = $chain->isAuthorized($controller, $method);
        if (! $expectingException) {
            $this->assertSame(
                $expected['result'],
                $result
            );
            $this->assertEquals(
                $expected['valid'],
                $result->isValid()
            );
        }
    }

    /**
     * @throws AuthorizationException
     * @throws AccessControlException
     */
    public function testIsAuthorizedWithBadLinkObject()
    {
        $chain = new AuthorizationChain(
            AuthorizationChain::OPERATOR_AND,
            [
                new \stdClass()
            ]
        );
        $this->expectException(AuthorizationException::class);
        $chain->isAuthorized('TestController');
    }

    public function testIsAuthorizedBadOperator()
    {
        $controllerName = 'TestController';
        $method = 'test';
        /**
         * @var $controller MockObject|AuthorizationChain
         */
        $controller = $this->getMockBuilder(AuthorizationChain::class)
            ->setMethods(['getOperator'])
            ->getMock();
        $link = $this->createMock(AuthorizationLink::class);
        $authResult = $this->createMock(AuthorizationResult::class);
        $link->expects($this->once())
            ->method('isAuthorized')
            ->with(
                $this->identicalTo($controllerName),
                $this->identicalTo($method)
            )->willReturn($authResult);

        $controller->addLink($link);
        $controller->expects($this->once())
            ->method('getOperator')
            ->willReturn(null);
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionCode(AuthorizationException::AC_INVALID_OPERATOR);
        $controller->isAuthorized($controllerName, $method);
    }

    /**
     * @throws AuthorizationException
     */
    public function testIsEmpty()
    {
        $emptyChain = new AuthorizationChain();
        $this->assertTrue($emptyChain->isEmpty());
        $nonEmptyChain = new AuthorizationChain(
            AuthorizationChain::OPERATOR_OR,
            [
                $this->createMock(AuthorizationLink::class)
            ]
        );
        $this->assertFalse($nonEmptyChain->isEmpty());
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function isAuthorizedProvider()
    {
        // enumerate some instances
        $operators = [
            AuthorizationChain::OPERATOR_OR,
            AuthorizationChain::OPERATOR_AND
        ];
        $controllers = [
            null,
            'TestController'
        ];
        $methods = [
            null,
            'getAction'
        ];
        $linkCounts = [
            0,
            null,
            1,
            2,
            3,
        ];
        $data = [];
        foreach ($operators as $operator) {
            foreach ($controllers as $controller) {
                foreach ($methods as $method) {
                    foreach ($linkCounts as $linkCount) {
                        $datum = [
                            $operator,
                            $controller,
                            $method
                        ];
                        $links = [];
                        if (null === $linkCount) {
                            $links = [
                                null
                            ];
                        }
                        if (! is_string($controller)) {
                            $datum[] = $links;
                            $datum[] = [
                                'exception' => \TypeError::class
                            ];
                            $data[] = $datum;
                            continue;
                        }
                        if ($linkCount === null || $linkCount === 0) {
                            $datum[] = $links;
                            $datum[] = [
                                'exception' => AuthorizationException::class
                            ];
                            $data[] = $datum;
                            continue;
                        }

                        // for each link, we need to determine if the
                        // returned result (AuthResult) by the link
                        // should be valid or not (true or false)
                        // that's 2^$count possibilities
                        for ($c = 0; $c < pow(2, $linkCount); $c++) {
                            $returnData = null;
                            $links = [];
                            for ($i = 0; $i < $linkCount; $i++) {
                                $links[] = $this->createMock(
                                    AuthorizationLink::class
                                );
                            }
                            for ($i = 0; $i < $linkCount; $i++) {
                                // treat each link as a bit..
                                $validResult = (bool) ($c & pow(2, $i));
                                $link = $links[$i];
                                $mockAuthResult = $this->createMock(
                                    AuthorizationResult::class
                                );
                                $mockAuthResult->expects($this->atLeastOnce())
                                    ->method('isValid')
                                    ->willReturn($validResult);

                                $link->expects($this->once())
                                    ->method('isAuthorized')
                                    ->with($controller, $method)
                                    ->willReturn($mockAuthResult);

                                switch ($operator) {
                                    case AuthorizationChain::OPERATOR_OR:
                                        if ($validResult === true) {
                                            $returnData = [
                                                'valid' => true,
                                                'result' => $mockAuthResult
                                            ];
                                            break 2;
                                        }
                                        if (! isset($returnData)) {
                                            $returnData = [
                                                'valid' => false,
                                                'result' => $mockAuthResult
                                            ];
                                        }
                                        break;
                                    case AuthorizationChain::OPERATOR_AND:
                                        if (false === $validResult) {
                                            $returnData = [
                                                'valid' => false,
                                                'result' => $mockAuthResult
                                            ];
                                            break 2;
                                        }
                                        $returnData = [
                                            'valid' => true,
                                            'result' => $mockAuthResult
                                        ];
                                        break;
                                    default:
                                        throw new \Exception(
                                            'Unexpected operator: ' . $operator
                                        );
                                }
                            }
                            if (! isset($returnData)) {
                                throw new \Exception('unexpected branch');
                            }
                            // don't overwrite datum, each of 2^$count is an
                            // instance that depends on the same datum
                            $data[] = \array_merge(
                                $datum,
                                [
                                    $links
                                ],
                                [
                                    $returnData
                                ]
                            );
                        }
                    }
                }
            }
        }
        return $data;
    }
}