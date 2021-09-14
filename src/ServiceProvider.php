<?php
/**
 * CacheWholePage package for Laravel framework.
 * 
 * @copyright   Copyright (C) 2021 Enikeishik <enikeishik@gmail.com>. All rights reserved.
 * @author      Enikeishik <enikeishik@gmail.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Enikeishik\CacheWithLock;

use Illuminate\Support\ServiceProvider as ServiceProvider;
use Enikeishik\CacheWithLock\CacheManager;

class ServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('Enikeishik\CacheWithLock\CacheManager', function($app) {
            return new CacheManager($app);
        });
    }
}
