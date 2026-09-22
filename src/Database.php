<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $path = Config::databasePath();

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        try {
            $pdo = new PDO(
                'sqlite:' . $path,
                null,
                null,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ],
            );
        } catch (PDOException $exception) {
            throw new RuntimeException('تعذر فتح قاعدة البيانات: ' . $exception->getMessage(), 0, $exception);
        }

        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');

        self::$connection = $pdo;

        return $pdo;
    }

    /** Runs any migration file that has not been applied yet. Safe to call on every request. */
    public static function migrate(): void
    {
        $pdo = self::connection();

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                filename   TEXT PRIMARY KEY,
                applied_at TEXT NOT NULL DEFAULT (datetime(\'now\', \'localtime\'))
            )',
        );

        $applied = $pdo->query('SELECT filename FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
        $files   = glob(Config::basePath('migrations') . '/*.sql') ?: [];

        sort($files);

        foreach ($files as $file) {
            $name = basename($file);

            if (in_array($name, $applied, true)) {
                continue;
            }

            $pdo->exec('BEGIN');

            try {
                $pdo->exec((string) file_get_contents($file));

                $statement = $pdo->prepare('INSERT INTO migrations (filename) VALUES (?)');
                $statement->execute([$name]);

                $pdo->exec('COMMIT');
            } catch (PDOException $exception) {
                $pdo->exec('ROLLBACK');

                throw new RuntimeException('فشل تنفيذ ملف قاعدة البيانات ' . $name . ': ' . $exception->getMessage(), 0, $exception);
            }
        }
    }

    public static function select(string $sql, array $bindings = []): array
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    public static function selectOne(string $sql, array $bindings = []): ?array
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($bindings);

        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public static function scalar(string $sql, array $bindings = []): mixed
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchColumn();
    }

    public static function run(string $sql, array $bindings = []): int
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($bindings);

        return $statement->rowCount();
    }

    public static function insert(string $table, array $values): int
    {
        $columns      = array_keys($values);
        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);

        $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';

        $statement = self::connection()->prepare($sql);
        $statement->execute($values);

        return (int) self::connection()->lastInsertId();
    }

    public static function update(string $table, int $id, array $values): void
    {
        $assignments = array_map(
            static fn (string $column): string => $column . ' = :' . $column,
            array_keys($values),
        );

        $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $assignments) . ' WHERE id = :id';

        $statement = self::connection()->prepare($sql);
        $statement->execute($values + ['id' => $id]);
    }

    public static function delete(string $table, int $id): void
    {
        self::run('DELETE FROM ' . $table . ' WHERE id = ?', [$id]);
    }

    public static function transaction(callable $work): mixed
    {
        $pdo = self::connection();

        $pdo->beginTransaction();

        try {
            $result = $work();

            $pdo->commit();

            return $result;
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }
}
