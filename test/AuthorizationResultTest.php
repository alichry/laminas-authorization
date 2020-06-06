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
use AliChry\Laminas\Authorization\AuthorizationException;
use AliChry\Laminas\Authorization\AuthorizationLink;
use PHPUnit\Framework\TestCase;
use AliChry\Laminas\Authorization\AuthorizationResult;

class AuthorizationResultTest extends TestCase
{
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
            $this->createMock(AuthorizationLink::class)
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
     * @param null|AuthorizationLink $link
     * @param array $resultMessages
     * @param bool|array $expected
     * @throws AuthorizationException
     */
    public function testResult(
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
        } else {
            $accessStatusMock->expects($this->once())
                ->method('getCode')
                ->willReturn($status);
        }

        $result = new AuthorizationResult(
            $accessStatusMock,
            $link,
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
                    ? AuthorizationResult::RESULT_ALLOWED
                    : AuthorizationResult::RESULT_REJECTED,
                $result->getCode()
            );
        }
    }
}