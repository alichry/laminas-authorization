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
 * Date: 2019-08-21
 * Time: 10:12
 */

namespace AliChry\Laminas\Authorization;

class AuthorizationException extends \Exception
{
    const ANY = 0;
    const AR_INVALID_CODE = 1;
    const AR_INVALID_AUTH_RESULT = 2;
    const AR_MISSING_AUTH_LINK = 3;
    const AC_INVALID_OPERATOR = 4;
    const AC_DUPLICATE_LINK_NAME = 5;

    const ARM_BAD_MODE = 6;
    const ARM_BAD_RESOURCE_IDENTIFIER = 7;
    const ARM_UNDEFINED_ANNOTATION = 8;
    const ARM_DUPLICATE_ANNOTATION = 9;

    const AS_UNDEFINED_ROUTE_MATCH = 10;
    const AS_INVALID_TARGET_CONTROLLER = 11;
    const AS_UNDEFINED_ACTION_PARAM = 12;
}