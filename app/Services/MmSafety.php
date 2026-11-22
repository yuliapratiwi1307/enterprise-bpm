<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class MmSafety
{
    public static function check(?Model $model = null, ?string $table = null): void
    {
        $name = DB::getDefaultConnection();
        $connection = DB::connection($name);
        $database = $connection->getDatabaseName();
        $allowedDatabases = ['enterprise_bpm_browser'];

        if (app()->environment('testing')) {
            $allowedDatabases[] = 'enterprise_bpm_test';
        }

        if (
            $name !== 'pgsql'
            || $connection->getDriverName() !== 'pgsql'
            || ! in_array($database, $allowedDatabases, true)
        ) {
            throw new RuntimeException('MM operation blocked: unsafe database connection.');
        }
        if ($model !== null) {
            if ($model->getConnectionName() !== null && $model->getConnectionName() !== $name) {
                throw new RuntimeException('MM operation blocked: unsafe model connection name.');
            }

            $modelConnection = $model->getConnection();
            $modelDatabase = $modelConnection->getDatabaseName();

            if (
                ! $model->exists
                || $model->getKey() === null
                || $model->getTable() !== $table
                || $modelConnection->getDriverName() !== 'pgsql'
                || $modelDatabase !== $database
                || ! in_array($modelDatabase, $allowedDatabases, true)
            ) {
                throw new RuntimeException('MM operation blocked: unsafe model instance.');
            }
        }
    }

    public static function active(string $table, int $id, string $field): void
    {
        if (! DB::table($table)->where('id', $id)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([$field => 'Referensi harus ada dan aktif.']);
        }
    }

    public static function validate(array $data, array $rules): array
    {
        return Validator::make($data, $rules)->validate();
    }

    public static function actor(?int $actor): void
    {
        if ($actor !== null && $actor <= 0) {
            throw ValidationException::withMessages(['actor' => 'ID pengguna tidak valid.']);
        }
    }
}
