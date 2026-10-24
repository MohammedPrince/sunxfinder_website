<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;

/**
 * Full MySQL export to a .sql file, written in PHP (no mysqldump needed: shared
 * hosting often blocks exec()). Files live in storage/app/private/db, which is not
 * web-accessible and not in git; admins download them through an admin route.
 */
class DatabaseBackup
{
    /** Rows per INSERT statement. */
    private const ROWS_PER_INSERT = 200;

    public static function directory(): string
    {
        return storage_path('app/private/db');
    }

    /** e.g. ejupmdly_sunxfinder_2026_10_24-09-15-30.sql (hosting) or sunxfinder_... (local). */
    public static function newFileName(): string
    {
        $database = preg_replace('/[^A-Za-z0-9_]/', '_', (string) config('database.connections.' . config('database.default') . '.database'));

        return $database . '_' . now()->format('Y_m_d-H-i-s') . '.sql';
    }

    /** Only our own backup file names, so a request can never reach other files. */
    public static function isValidName(string $name): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_]+_\d{4}_\d{2}_\d{2}-\d{2}-\d{2}-\d{2}\.sql$/', $name);
    }

    public static function path(string $name): ?string
    {
        if (!self::isValidName($name)) {
            return null;
        }

        $path = self::directory() . DIRECTORY_SEPARATOR . $name;

        return is_file($path) ? $path : null;
    }

    /** @return array<int, array{name: string, size: int, created_at: Carbon}> newest first */
    public static function all(): array
    {
        if (!is_dir(self::directory())) {
            return [];
        }

        return collect(File::files(self::directory()))
            ->filter(fn($file) => self::isValidName($file->getFilename()))
            ->map(fn($file) => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'created_at' => Carbon::createFromTimestamp($file->getMTime()),
            ])
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }

    /** Writes a new backup and returns its file name. */
    public static function create(): string
    {
        @set_time_limit(0);

        File::ensureDirectoryExists(self::directory(), 0750);

        $name = self::newFileName();
        $path = self::directory() . DIRECTORY_SEPARATOR . $name;
        $tmp = $path . '.part';

        $out = fopen($tmp, 'wb');

        if (!$out) {
            throw new RuntimeException('Cannot write to ' . self::directory());
        }

        try {
            self::dump($out);
            fclose($out);
            rename($tmp, $path);
        } catch (\Throwable $e) {
            if (is_resource($out)) {
                fclose($out);
            }
            @unlink($tmp);

            throw $e;
        }

        return $name;
    }

    public static function delete(string $name): bool
    {
        $path = self::path($name);

        return $path ? @unlink($path) : false;
    }

    /** @param resource $out */
    private static function dump($out): void
    {
        $pdo = DB::connection()->getPdo();
        $database = DB::connection()->getDatabaseName();

        fwrite($out, "-- SunXFinder database backup\n");
        fwrite($out, "-- Database: `{$database}`\n");
        fwrite($out, '-- Created: ' . now()->toDateTimeString() . ' (' . config('app.timezone') . ")\n");
        fwrite($out, '-- Server: MySQL ' . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n\n");
        fwrite($out, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\nSET time_zone='+00:00';\n\n");

        $objects = collect(DB::select('SHOW FULL TABLES'))
            ->map(fn($row) => array_values((array) $row))
            ->map(fn($row) => ['name' => $row[0], 'type' => $row[1]]);

        // Tables first (structure + data), then views (they may depend on tables).
        foreach ($objects->where('type', 'BASE TABLE') as $table) {
            self::dumpTable($out, $pdo, $table['name']);
        }

        foreach ($objects->where('type', 'VIEW') as $view) {
            $create = (array) DB::selectOne("SHOW CREATE VIEW `{$view['name']}`");
            fwrite($out, "DROP VIEW IF EXISTS `{$view['name']}`;\n" . ($create['Create View'] ?? '') . ";\n\n");
        }

        fwrite($out, "SET FOREIGN_KEY_CHECKS=1;\n");
    }

    /** @param resource $out */
    private static function dumpTable($out, PDO $pdo, string $table): void
    {
        $create = (array) DB::selectOne("SHOW CREATE TABLE `{$table}`");

        fwrite($out, "-- --------------------------------------------------------\n-- Table `{$table}`\n\n");
        fwrite($out, "DROP TABLE IF EXISTS `{$table}`;\n" . $create['Create Table'] . ";\n\n");

        // Stream rows without loading the whole table into memory.
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        try {
            $statement = $pdo->query("SELECT * FROM `{$table}`");
            $columns = null;
            $batch = [];

            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $columns ??= '`' . implode('`, `', array_keys($row)) . '`';
                $batch[] = '(' . implode(', ', array_map(fn($value) => self::sqlValue($pdo, $value), $row)) . ')';

                if (count($batch) >= self::ROWS_PER_INSERT) {
                    fwrite($out, "INSERT INTO `{$table}` ({$columns}) VALUES\n" . implode(",\n", $batch) . ";\n");
                    $batch = [];
                }
            }

            if ($batch) {
                fwrite($out, "INSERT INTO `{$table}` ({$columns}) VALUES\n" . implode(",\n", $batch) . ";\n");
            }

            $statement->closeCursor();
        } finally {
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }

        fwrite($out, "\n");
    }

    private static function sqlValue(PDO $pdo, $value): string
    {
        return match (true) {
            $value === null => 'NULL',
            is_int($value), is_float($value) => (string) $value,
            default => $pdo->quote((string) $value),
        };
    }
}
