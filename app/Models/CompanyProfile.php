<?php

namespace App\Models;

use Database\Factories\CompanyProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['name', 'tagline', 'description', 'founded_year', 'mission', 'email', 'phone', 'address'])]
class CompanyProfile extends Model
{
    /** @use HasFactory<CompanyProfileFactory> */
    use HasFactory;

    /**
     * The cache key holding the site's single company profile.
     */
    public const CACHE_KEY = 'company_profile';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'founded_year' => 'integer',
        ];
    }

    /**
     * Register model events that keep the cached profile in sync with the database.
     */
    protected static function booted(): void
    {
        static::saved(fn (): bool => Cache::forget(self::CACHE_KEY));
        static::deleted(fn (): bool => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Get the site's company profile from the cache, falling back to an unsaved
     * profile named after the application when no record exists.
     *
     * Only the raw attributes are cached because the cache refuses to unserialize
     * PHP objects (see the "serializable_classes" cache option).
     */
    public static function current(): self
    {
        $attributes = Cache::rememberForever(
            self::CACHE_KEY,
            fn (): ?array => static::query()->first()?->getAttributes(),
        );

        if ($attributes === null) {
            return new static(['name' => config('app.name')]);
        }

        return (new static)->newFromBuilder($attributes);
    }

    /**
     * Get the phone number as a tap-to-call link target.
     */
    public function telephoneHref(): string
    {
        return 'tel:'.preg_replace('/[^0-9+]/', '', (string) $this->phone);
    }
}
