<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Acceso de solo lectura a los archivos de `config/*.php`, con cache en memoria
 * por request para no volver a incluir el archivo en cada llamada.
 */
final class Config
{
    /** @var array<string, array<string, mixed>> */
    private static array $cache = [];

    /**
     * Lee una clave con notación de puntos, p. ej. `Config::get('database.driver')`.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        [$file, $path] = array_pad(explode('.', $key, 2), 2, null);

        $data = self::load($file);
        if ($path === null) {
            return $data;
        }

        $value = $data;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /** @return array<string, mixed> */
    private static function load(string $file): array
    {
        if (!isset(self::$cache[$file])) {
            $path = dirname(__DIR__, 2) . "/config/{$file}.php";
            self::$cache[$file] = is_file($path) ? require $path : [];
        }

        return self::$cache[$file];
    }
}
