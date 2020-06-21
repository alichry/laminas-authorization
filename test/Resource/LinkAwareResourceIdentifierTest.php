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

use AliChry\Laminas\Authorization\Resource\LinkAwareResourceIdentifier;
use PHPUnit\Framework\TestCase;

class LinkAwareResourceIdentifierTest extends TestCase
{
    public function testLink()
    {
        $link1 = 'link1';
        $link2 = 'link2';

        $identifier = new LinkAwareResourceIdentifier($link1, null);
        $this->assertSame(
            $link1,
            $identifier->getLink()
        );
        $identifier->setLink($link2);
        $this->assertSame(
            $link2,
            $identifier->getLink()
        );
    }

    public function testController()
    {
        $controller = 'TestController';
        $identifier = new LinkAwareResourceIdentifier(null, $controller);
        $this->assertSame(
            $controller,
            $identifier->getController()
        );
    }

    public function testMethod()
    {
        $method = 'get';
        $identifier = new LinkAwareResourceIdentifier(null, null, $method);
        $this->assertSame(
            $method,
            $identifier->getMethod()
        );
    }

    /**
     * @dataProvider dataProvider
     * @param string|null $link
     * @param string|null $controller
     * @param string|null $method
     */
    public function testAll($link, $controller, $method)
    {
        $identifier = new LinkAwareResourceIdentifier(
            $link,
            $controller,
            $method
        );
        $this->assertSame(
            $link,
            $identifier->getLink()
        );
        $this->assertSame(
            $controller,
            $identifier->getController()
        );
        $this->assertSame(
            $method,
            $identifier->getMethod()
        );

        $newLink = 'special-new-link';
        $identifier->setLink($newLink);
        $this->assertSame(
            $newLink,
            $identifier->getLink()
        );
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        $links = [
            null,
            'link1',
            'link2'
        ];
        $controllers = [
            null,
            'TestController',
            'CatController'
        ];
        $methods = [
            null,
            'eat',
            'play'
        ];
        $data = [];
        foreach ($links as $link) {
            foreach ($controllers as $controller) {
                foreach ($methods as $method) {
                    $data[] = [
                        $link,
                        $controller,
                        $methods
                    ];
                }
            }
        }
        return $data;
    }
}