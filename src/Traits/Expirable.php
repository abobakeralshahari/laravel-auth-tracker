<?php

namespace OwaisKit\AuthTracker\Traits;

use OwaisKit\AuthTracker\QueryBuilders\ExpirableEloquentQueryBuilder;
use OwaisKit\AuthTracker\Scopes\ExpirationScope;
use Carbon\Carbon;
use Illuminate\Support\Collection as BaseCollection;

trait Expirable
{
    /**
     * Boot the trait.
     *
     * @return void
     */
    public static function bootExpirable()
    {
        static::addGlobalScope(new ExpirationScope);

        static::creating(function ($model) {
            if (! array_key_exists($model::getExpirationAttribute(), $model->attributes)) {
                $model->attributes[$model::getExpirationAttribute()] = $model::defaultExpiresAt();
            }
        });
    }

    /**
     * Set the expiration date and return the instance.
     *
     * @param  object|null  $expirationDate
     * @return self
     */
    public function expiresAt($expirationDate)
    {
        $this->{self::getExpirationAttribute()} = $expirationDate;

        return $this;
    }

    /**
     * Set the lifetime in a more human readable way and return the instance.
     *
     * @param  string|null  $period
     * @return self
     */
    public function lifetime($period)
    {
        $this->{self::getExpirationAttribute()} = is_string($period) ? Carbon::now()->add($period) : null;

        return $this;
    }

    /**
     * Revive an expired model.
     *
     * @param  object|string|null  $newExpirationDate
     * @return bool
     */
    public function revive($newExpirationDate = null)
    {
        if (! $this->isExpired()) {
            return false;
        }

        if (is_string($newExpirationDate)) {
            $newExpirationDate = Carbon::now()->add($newExpirationDate);
        } elseif (is_null($newExpirationDate)) {
            $newExpirationDate = self::defaultExpiresAt();
        }

        $this->{self::getExpirationAttribute()} = $newExpirationDate;

        return $this->save();
    }

    /**
     * Make a model eternal (set expiration date to null).
     *
     * @return bool
     */
    public function makeEternal()
    {
        $this->{self::getExpirationAttribute()} = null;

        return $this->save();
    }

    /**
     * Set the status to "expired" at the current timestamp.
     *
     * @return bool
     */
    public function expire()
    {
        $this->{self::getExpirationAttribute()} = Carbon::now();

        return $this->save();
    }

    /**
     * Set the status to "expired" for the given model IDs.
     *
     * @param  \Illuminate\Support\Collection|array|int  $ids
     * @return int
     */
    public static function expireByKey($ids)
    {
        if ($ids instanceof BaseCollection) {
            $ids = $ids->all();
        }

        $ids = is_array($ids) ? $ids : func_get_args();

        $key = ($instance = new static)->getKeyName();

        return $instance->whereIn($key, $ids)->expire();
    }

    /**
     * Check for expired model.
     *
     * @return bool
     */
    public function isExpired()
    {
        $expiresAt = $this->{self::getExpirationAttribute()};

        return ! is_null($expiresAt) && Carbon::parse($expiresAt) <= Carbon::now();
    }

    /**
     * Check if the model is eternal.
     *
     * @return bool
     */
    public function isEternal()
    {
        return is_null($this->{self::getExpirationAttribute()});
    }

    /**
     * Get the name of the "expires_at" column.
     *
     * @return string
     */
    public static function getExpirationAttribute()
    {
        return defined('static::EXPIRES_AT') ? static::EXPIRES_AT : 'expires_at';
    }

    /**
     * The default expiration date.
     *
     * @return object|null
     */
    public static function defaultExpiresAt()
    {
        return null;
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new ExpirableEloquentQueryBuilder($query);
    }
}
