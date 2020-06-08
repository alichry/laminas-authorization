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
 * Date: 2020-05-30
 * Time: 15:13
 */

namespace AliChry\Laminas\Authorization;

use Laminas\EventManager\EventInterface;
use Laminas\ModuleManager\Feature\BootstrapListenerInterface;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;

class Module implements BootstrapListenerInterface
{
    const DISPATCH_PRIORITY = 100;

    /**
     * Listen to the bootstrap event
     *
     * @param EventInterface $e
     * @return void
     * @throws AuthorizationException
     */
    public function onBootstrap(EventInterface $e)
    {
        // TODO: provide option to specify dispatch priority in config
        /**
         * @var Application
         */
        $application = $e->getParam('application');
        if (
            ! is_object($application)
            || ! $application instanceof Application
        ) {
            throw new AuthorizationException(
                sprintf(
                    'Bootstrap error, expecting application to be an instance '
                    . 'of %s, got %s',
                    Application::class,
                    is_object($application)
                        ? get_class($application)
                        : gettype($application)
                )
            );
        }
        $serviceManager = $application->getServiceManager();
        $eventManager = $application->getEventManager();
        $sharedEventManager = $eventManager->getSharedManager();
        // Get authorization service instance
        $authorizationService = $serviceManager->get(AuthorizationService::class);
        $sharedEventManager->attach(
            AbstractController::class,
            MvcEvent::EVENT_DISPATCH,
            [$authorizationService, 'onDispatch'],
            self::DISPATCH_PRIORITY
        );
    }
}
