<?php
/**
 * CacheWithLock package for Laravel framework.
 * 
 * @copyright   Copyright (C) 2021 Enikeishik <enikeishik@gmail.com>. All rights reserved.
 * @author      Enikeishik <enikeishik@gmail.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Enikeishik\CacheWithLock;

use Closure;
use Illuminate\Cache\CacheManager as BaseCacheManager;

/**
 * This class overrides remember method using lock mechanism 
 * to avoid multiply generation of the same data 
 * (as a result of race condition) when cache becomes invalid
 */
class CacheManager extends BaseCacheManager
{
    protected int $lockTimeout = 10;
    
    public function setLockTimeout(int $lockTimeout): void
    {
        $this->lockTimeout = $lockTimeout;
    }
    
    public function getLockTimeout(): int
    {
        return $this->lockTimeout;
    }
    
    public function remember(string $key, $ttl, Closure $callback)
    {
        $value = parent::get($key);
        if (null !== $value) {
            return $value;
        }
        
        $lock = parent::lock($key . '_lock', $this->lockTimeout);
        try {
            if ($lock->block($this->lockTimeout)) {
                //the main idea of this method is
                //if cache already generated in another thread
                //just get it and return without generation
                $value = parent::get($key);
                if (null !== $value) {
                    return $value;
                }
                
                $value = $callback();
            }
        } finally {
            $lock->release();
        }
        
        if (null !== $value) {
            parent::put($key, $value, $ttl);
        }
        
        return $value;
    }
}
