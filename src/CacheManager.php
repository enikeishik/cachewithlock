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

use Closure;
use Illuminate\Cache\CacheManager as BaseCacheManager;
use Illuminate\Contracts\Cache\LockProvider;

/**
 * This class overrides remember method using lock mechanism 
 * to avoid multiply generation of the same data 
 * (as a result of race condition) when cache becomes invalid.
 */
class CacheManager extends BaseCacheManager
{
    /**
     * Timeout of lock, in seconds.
     * 
     * @var int
     */
    protected int $lockTimeout = 5;
    
    /**
     * Overrides constructor with type hint,
     * to prevent BindingResolutionException with message 'Unresolvable dependency resolving...'
     * parent class not provide type hint.
     * 
     * @see parent
     */
    public function __construct(\Illuminate\Contracts\Foundation\Application $app)
    {
        parent::__construct($app);
    }
    
    /**
     * Sets lock timeout.
     * 
     * @param int $lockTimeout
     * @return void
     */
    public function setLockTimeout(int $lockTimeout): void
    {
        $this->lockTimeout = $lockTimeout;
    }
    
    /**
     * Return lock timeout.
     * 
     * @return int
     */
    public function getLockTimeout(): int
    {
        return $this->lockTimeout;
    }
    
    /**
     * @see parent
     */
    public function remember(string $key, $ttl, Closure $callback)
    {
        $store = $this->store();
        
        $value = $store->get($key);
        if (null !== $value) {
            return $value;
        }
        
        if (!($store instanceof LockProvider)) {
            //if store not supports locks, makes the same as base method
            $store->put($key, $value = $callback(), $ttl);
            return $value;
        }

        $lock = $store->lock($key . '_lock', $this->lockTimeout);
        try {
            if ($lock->block($this->lockTimeout)) {
                //the main idea of this method is:
                //if cache already generated in another thread
                //just get it and return without generation
                $value = $store->get($key);
                if (null !== $value) {
                    return $value;
                }
                
                $value = $callback();
            }
        } finally {
            $lock->release();
        }
        
        if (null !== $value) {
            $store->put($key, $value, $ttl);
        }
        
        return $value;
    }
}
