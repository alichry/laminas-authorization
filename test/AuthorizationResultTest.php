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
 * Time: 11:42
 */

namespace AliChry\Laminas\Authorization\Test;

use AliChry\Laminas\AccessControl\Status;
use AliChry\Laminas\Authorization\AuthorizationLink;
use AliChry\Laminas\Authorization\LinkInterface;
use PHPUnit\Framework\TestCase;
use AliChry\Laminas\Authorization\Result;

class AuthorizationResultTest extends TestCase
{
    public function testConstructor()
    {
        $link = $this->createMock(LinkInterface::class);
        $accessStatus = new Status(Status::UNAUTHORIZED, 'test', ['hello']);
        $rejected = Result::RESULT_REJECTED;
        $allowed = Result::RESULT_ALLOWED;
        $messages = ['hi'];
        $rejectedResult = new Result(
            $rejected,
            $link,
            $accessStatus,
            $messages
        );
        $allowedResult = new Result(
            $allowed,
            $link,
            $accessStatus,
            $messages
        );
        $this->assertSame(
            $rejected,
            $rejectedResult->getCode()
        );
        $this->assertSame(
            $link,
            $rejectedResult->getAuthLink()
        );
        $this->assertSame(
            $messages,
            $rejectedResult->getMessages()
        );
        $this->assertSame(
            $allowed,
            $allowedResult->getCode()
        );
        $this->assertSame(
            $link,
            $allowedResult->getAuthLink()
        );
        $this->assertSame(
            $messages,
            $allowedResult->getMessages()
        );
    }

    /**
     * @return array
     */
    public function enumerate()
    {
        // enumerate all
        $data = [];
        $allStatusCodes = [
            Status::UNAUTHORIZED,
            Status::REJECTED,
            Status::PUBLIC,
            Status::OK
        ];
        $someStatusMessages = [
            [
                'status is ok',
                'status is not ok'
            ],
            [
                '',
                'ok'
            ]
        ];
        $allAuths = [
            false,
            true
        ];
        $allLinks = [
            null,
            $this->createMock(LinkInterface::class)
        ];
        $someResultMessages = [
            null,
            [
                'hi1',
                'hi2',
                ''
            ],
            [
                'message1',
                '',
                'message3'
            ]
        ];
        foreach ($allStatusCodes as $status) { // 4
            foreach ($someStatusMessages as $statusMessages) { // 2
                foreach ($allAuths as $authenticated) { // 2
                    foreach ($allLinks as $link) { // 1
                        foreach ($someResultMessages as $resultMessages) { // 2
                            $datum = [
                                $status,
                                $statusMessages,
                                $authenticated,
                                $link,
                                $resultMessages
                            ];
                            // the last element in datum is the expected value
                            // that is true, false or an exception thrown
                            $rejected = $status < Status::PUBLIC
                                || (
                                    $status === Status::OK
                                    && true !== $authenticated
                                );

                            if (
                                ! isset(
                                    $status,
                                    $authenticated,
                                    $link,
                                    $resultMessages,
                                    $statusMessages
                                )
                            ) {
                                $datum[] = [
                                    'exception' => \TypeError::class,
                                ];
                            } else {
                                $datum[] = ! $rejected;
                            }
                            $data[] = $datum;
                        }
                    }
                }
            }
        }
        return $data;
    }

    /**
     * @dataProvider enumerate
     * @param int $status
     * @param array $statusMessages
     * @param bool|null $authenticated
     * @param null|LinkInterface $link
     * @param array $resultMessages
     * @param bool|array $expected
     */
    public function testCodeFromAccessStatus(
        $status,
        $statusMessages,
        $authenticated,
        $link,
        $resultMessages,
        $expected
    )
    {
        $expectingException = is_array($expected);
        $accessStatusMock = $this->createMock(Status::class);

        if ($expectingException) {
            $this->expectException(
                $expected['exception']
            );
            if (isset($expected['code'])) {
                $this->expectExceptionCode($expected['code']);
            }
        } else {
            $accessStatusMock->expects($this->once())
                ->method('getCode')
                ->willReturn($status);
        }

        $result = Result::fromAccessStatus(
            $link,
            $accessStatusMock,
            $authenticated,
            $resultMessages
        );

        $this->assertEquals(
            $accessStatusMock,
            $result->getAccessStatus()
        );

        $this->assertEquals(
            $link,
            $result->getAuthLink()
        );

        $valid = $result->isValid();

        if (! $expectingException) {
            $accessStatusMock->expects($this->once())
                ->method('getMessages')
                ->willReturn($statusMessages);
            $this->assertEquals(
                $resultMessages,
                $result->getMessages()
            );
            $this->assertEquals(
                \array_merge($resultMessages, $statusMessages),
                $result->getAllMessages()
            );

            $this->assertEquals(
                $expected,
                $valid
            );
            $this->assertEquals(
                $expected
                    ? Result::RESULT_ALLOWED
                    : Result::RESULT_REJECTED,
                $result->getCode()
            );
        }
    }
}