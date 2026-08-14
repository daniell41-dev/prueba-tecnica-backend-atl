<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Support\Config;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Fábrica de la conexión PDO, construida a partir de `config/database.php`.
 *
 * Único lugar del código que sabe armar un DSN. Cambiar de SQLite a MySQL es
 * cambiar `DB_DRIVER` (variable de entorno) — nada más se entera del cambio.
 */
final class Connection
{
    private static ?PDO $instance = null;

    public static function make(): PDO
    {
        return self::$instance ??= self::connect();
    }

    /** Fuerza una conexión nueva (usado por los tests, que quieren SQLite en memoria). */
    public static function reset(): void
    {
        self::$instance = null;
    }

    private static function connect(): PDO
    {
        $driver = Config::get('database.driver', 'sqlite');

        $pdo = match ($driver) {
            'sqlite' => self::connectSqlite(),
            'mysql' => self::connectMysql(),
            default => throw new RuntimeException("Driver de base de datos no soportado: \"{$driver}\"."),
        };

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }

    private static function connectSqlite(): PDO
    {
        $path = Config::get('database.sqlite.path');

        if ($path !== ':memory:') {
            $directory = dirname($path);
            if (!is_dir($directory)) {
                mkdir($directory, 0775, true);
            }
        }

        try {
            $pdo = new PDO("sqlite:{$path}");
        } catch (PDOException $exception) {
            throw new RuntimeException("No se pudo conectar a SQLite ({$path}): {$exception->getMessage()}", previous: $exception);
        }

        // Sin esto, SQLite ignora `ON DELETE CASCADE` y deja teléfonos huérfanos.
        $pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    }

    private static function connectMysql(): PDO
    {
        $config = Config::get('database.mysql');
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset'],
        );

        try {
            return new PDO($dsn, $config['username'], $config['password']);
        } catch (PDOException $exception) {
            throw new RuntimeException("No se pudo conectar a MySQL: {$exception->getMessage()}", previous: $exception);
        }
    }
}
