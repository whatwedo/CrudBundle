<?php
/*
 * Copyright (c) 2017, whatwedo GmbH
 * All rights reserved
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace whatwedo\CrudBundle\Extension;

use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;
use WhiteOctober\BreadcrumbsBundle\WhiteOctoberBreadcrumbsBundle;

class BreadcrumbsExtension implements ExtensionInterface
{
    /**
     * @var Breadcrumbs
     */
    protected $breadcrumbs;

    /**
     * @var bool to only add it once
     */
    protected static $isStartPrepended = false;

    /**
     * @var bool|string
     */
    protected $startText = false;

    /**
     * @var bool|string
     */
    protected $startRoute = false;

    public function __construct(Breadcrumbs $breadcrumbs, $startText, $startRoute)
    {
        $this->breadcrumbs = $breadcrumbs;

        if ($startText) {
            $this->startText = $startText;
        }

        if ($startRoute) {
            $this->startRoute = $startRoute;
        }
    }

    public static function isEnabled($enabledBundles)
    {
        foreach ($enabledBundles as $bundles) {
            if (in_array(WhiteOctoberBreadcrumbsBundle::class, $bundles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Breadcrumbs
     */
    public function getBreadcrumbs()
    {
        // add Dashboard Link (needs to be here, because routing is not available in the constructor
        if (!static::$isStartPrepended
            && $this->startText) {
            static::$isStartPrepended = true;
            if ($this->startRoute) {
                $this->breadcrumbs->prependRouteItem($this->startText, $this->startRoute);
            } else {
                $this->breadcrumbs->prependItem($this->startText);
            }
        }

        return $this->breadcrumbs;
    }
}
