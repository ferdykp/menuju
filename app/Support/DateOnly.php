<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

final class DateOnly
{
    public static function value(Model $model, string $field): ?string
    {
        $value = $model->getRawOriginal($field);

        return $value === null ? null : substr((string) $value, 0, 10);
    }

    public static function model(Model $model, array $fields): array
    {
        $data = $model->toArray();

        foreach ($fields as $field) {
            $data[$field] = self::value($model, $field);
        }

        return $data;
    }
}
