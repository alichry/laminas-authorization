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
 * Date: 2019-06-08
 * Time: 14:27
 */

use AliChry\Laminas\AccessControl\IdentityAccessControlList;
use AliChry\Laminas\AccessControl\Policy\Policy;
use AliChry\Laminas\Authorization\AuthorizationChain;
use AliChry\Laminas\Authorization\AuthorizationLink;
use AliChry\Laminas\Authorization\AuthorizationService;
use AliChry\Laminas\Authorization\Factory\AnnotatedResourceManagerFactory;
use AliChry\Laminas\Authorization\Factory\AuthorizationServiceFactory;
use AliChry\Laminas\Authorization\Factory\AuthorizationLinkFactory;
use AliChry\Laminas\Authorization\Factory\AuthorizationChainFactory;
use AliChry\Laminas\Authorization\Resource\AnnotatedResourceManager;

return [
    'service_manager' => [
        'factories' => [
            AuthorizationService::class => AuthorizationServiceFactory::class,
            AuthorizationLink::class => AuthorizationLinkFactory::class,
            AuthorizationChain::class => AuthorizationChainFactory::class,
            AnnotatedResourceManager::class => AnnotatedResourceManagerFactory::class
        ]
    ],
    'alichry' => [
        'access_control' => [
            'resource_manager' => [
                'annotated' => [
                    'service' => AnnotatedResourceManager::class,
                    'options' => [
                        'mode' => AnnotatedResourceManager::MODE_CHILL,
                        'policy' => Policy::POLICY_REJECT
                    ]
                ]
            ],
            'list' => [
                'identity' => [
                    'service' => IdentityAccessControlList::class,
                    'options' => [
                        'resource_manager' => 'annotated'
                    ]
                ]
            ]
        ],
        'authorization' => [
            'operator' => AuthorizationChain::OPERATOR_AND,
            'chain' => []
        ]
    ]
];