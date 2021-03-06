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
use Throwable;
use Illuminate\Cache\CacheManager as BaseCacheManager;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Log;

/**
 * This class overrides remember method using lock mechanism 
 * to avoid multiply generation of the same data 
 * (as a result of race condition) when cache becomes invalid.
 */
class CacheManager extends BaseCacheManager
{
    /**
     * @var string
     */
    protected const LOG_MESSAGE_PREFIX = "CACHEWITHLOCK\t";
    
    /**
     * @var string
     */
    protected const DATA_GENERATION_SKIPPED = self::LOG_MESSAGE_PREFIX . 
        'Generation skipped for ';
    
    /**
     * @var string
     */
    protected const LOCK_TIMEOUT_EXCEPTION = self::LOG_MESSAGE_PREFIX . 
        'LockTimeoutException for ';
    
    /**
     * @var string
     */
    protected const UNKNOWN_EXCEPTION = self::LOG_MESSAGE_PREFIX . 
        'Catch Throwable for ';
    
    /**
     * @var int
     */
    protected const LOCK_TIMEOUT = 5;
    
    /**
     * Timeout of lock, in seconds.
     * 
     * @var int
     */
    protected int $lockTimeout = self::LOCK_TIMEOUT;

    /**
     * Logging every skip of data generation (for statistics purposes).
     * 
     * @var bool
     */
    protected bool $usageLogging = true;
    
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

        $this->lockTimeout = (int) config('cachewithlock.lock_timeout');
        if ($this->lockTimeout < 1) {
            $this->lockTimeout = self::LOCK_TIMEOUT;
        }

        $this->usageLogging = true === config('cachewithlock.usage_logging');
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
        $store = $this->store()->getStore();
        
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
                    if ($this->usageLogging) {
                        Log::info(self::DATA_GENERATION_SKIPPED . $key);
                    }
                    return $value;
                }
                
                $value = $callback();
            }
        } catch (LockTimeoutException $e) {
            Log::notice(self::LOCK_TIMEOUT_EXCEPTION . $key . "\t" . $e->getMessage());
        } catch (Throwable $e) {
            Log::error(self::UNKNOWN_EXCEPTION . $key . "\t" . $e->getMessage());
        } finally {
            $lock->release();
        }
        
        if (null !== $value) {
            $store->put($key, $value, $ttl);
        }
        
        return $value;
    }
}
