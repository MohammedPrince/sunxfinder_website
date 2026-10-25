<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * The Android APK offered as a "Direct download" on the home page.
 *
 * The file lives in storage/app/private/apk (not web-accessible, not in git) and is
 * served only through the public download route while the admin has it switched on.
 * Uploads arrive in small chunks so any PHP upload_max_filesize works, then are joined.
 */
class ApkDownload
{
    public const CACHE_KEY = 'apk-download';

    /** Hard cap on the joined file. */
    public const MAX_BYTES = 300 * 1024 * 1024;

    private const FILE_NAME = 'sunxfinder.apk';

    public static function directory(): string
    {
        return storage_path('app/private/apk');
    }

    public static function path(): string
    {
        return self::directory() . DIRECTORY_SEPARATOR . self::FILE_NAME;
    }

    /**
     * Status for the admin page and the home page. Cached for a minute (read on every
     * home page view); cleared whenever the admin changes anything here.
     *
     * @return array{exists: bool, enabled: bool, visible: bool, size: int, version: ?string, uploaded_at: ?string}
     */
    public static function info(): array
    {
        return cache()->remember(self::CACHE_KEY, 60, function () {
            $values = Setting::query()
                ->where('key', 'like', 'apk_%')
                ->pluck('value', 'key');

            $exists = is_file(self::path());
            $enabled = (bool) ($values['apk_download_enabled'] ?? false);

            return [
                'exists' => $exists,
                'enabled' => $enabled,
                'visible' => $exists && $enabled,
                'size' => $exists ? (int) filesize(self::path()) : 0,
                'version' => $values['apk_version'] ?? null,
                'uploaded_at' => $values['apk_uploaded_at'] ?? null,
            ];
        });
    }

    /** e.g. SunXFinder-1.6.2.apk, the name the visitor's phone saves. */
    public static function downloadName(): string
    {
        $version = preg_replace('/[^0-9.]/', '', (string) self::info()['version']);

        return 'SunXFinder' . ($version !== '' ? '-' . $version : '') . '.apk';
    }

    /**
     * Largest chunk the browser should send: comfortably under both PHP limits,
     * so the upload works on any hosting without changing php.ini.
     */
    public static function chunkBytes(): int
    {
        $limit = min(
            self::iniBytes((string) ini_get('upload_max_filesize')),
            self::iniBytes((string) ini_get('post_max_size'))
        );

        return (int) max(256 * 1024, min(8 * 1024 * 1024, $limit * 0.8));
    }

    /** Upload ids come from the browser, so only this shape may become a folder name. */
    public static function isValidUploadId(string $uploadId): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9]{16,64}$/', $uploadId);
    }

    public static function storeChunk(string $uploadId, int $index, string $chunkPath): void
    {
        // A new upload: clear parts left by uploads abandoned more than a day ago.
        $tmpRoot = self::directory() . DIRECTORY_SEPARATOR . 'tmp';

        if ($index === 0 && is_dir($tmpRoot)) {
            foreach (File::directories($tmpRoot) as $old) {
                if (filemtime($old) < now()->subDay()->getTimestamp()) {
                    File::deleteDirectory($old);
                }
            }
        }

        $dir = self::tempDirectory($uploadId);
        File::ensureDirectoryExists($dir);

        File::move($chunkPath, $dir . DIRECTORY_SEPARATOR . $index . '.part');
    }

    /** Joins chunks 0..$total-1, checks it is an APK, and replaces the current file. */
    public static function assemble(string $uploadId, int $total): void
    {
        $dir = self::tempDirectory($uploadId);
        $joined = $dir . DIRECTORY_SEPARATOR . 'joined.apk';

        try {
            $out = fopen($joined, 'wb');

            if (!$out) {
                throw new RuntimeException('Could not write the uploaded file.');
            }

            for ($i = 0; $i < $total; $i++) {
                $part = $dir . DIRECTORY_SEPARATOR . $i . '.part';

                if (!is_file($part)) {
                    fclose($out);
                    throw new RuntimeException('Part of the upload is missing. Please upload again.');
                }

                $in = fopen($part, 'rb');
                stream_copy_to_stream($in, $out);
                fclose($in);
            }

            fclose($out);

            $size = filesize($joined);

            if ($size === 0 || $size > self::MAX_BYTES) {
                throw new RuntimeException('The APK must be smaller than ' . (self::MAX_BYTES / 1024 / 1024) . ' MB.');
            }

            // Every APK is a ZIP archive, which starts with "PK\x03\x04".
            $handle = fopen($joined, 'rb');
            $magic = fread($handle, 4);
            fclose($handle);

            if ($magic !== "PK\x03\x04") {
                throw new RuntimeException('This file is not a valid APK.');
            }

            File::ensureDirectoryExists(self::directory());
            File::move($joined, self::path());
        } finally {
            File::deleteDirectory($dir);
        }
    }

    public static function delete(): void
    {
        File::delete(self::path());
    }

    public static function forgetCache(): void
    {
        cache()->forget(self::CACHE_KEY);
    }

    private static function tempDirectory(string $uploadId): string
    {
        if (!self::isValidUploadId($uploadId)) {
            throw new RuntimeException('Invalid upload.');
        }

        return self::directory() . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . $uploadId;
    }

    /** "2M" → 2097152 */
    private static function iniBytes(string $value): int
    {
        $value = trim($value);
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number ?: PHP_INT_MAX,
        };
    }
}
