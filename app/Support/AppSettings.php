<?php

namespace App\Support;

use App\Models\AppSetting;

class AppSettings
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return AppSetting::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value === '' ? null : (string) $value],
        );
    }

    public static function boolean(string $key, bool $default = false): bool
    {
        return (bool) filter_var(self::get($key, $default ? '1' : '0'), FILTER_VALIDATE_BOOLEAN);
    }
}
