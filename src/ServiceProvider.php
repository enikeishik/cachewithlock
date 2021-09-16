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

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Enikeishik\CacheWithLock\CacheManager;
use Illuminate\Cache\CacheManager as BaseCacheManager;

class ServiceProvider extends BaseServiceProvider
{
    public function register()
    {
        $this->app->singleton('Enikeishik\CacheWithLock\CacheManager', function($app) {
            return new CacheManager($app);
        });
        
        $this->app->extend(BaseCacheManager::class, function ($service, $app) {
            return new CacheManager($app);
        });
    }
}
