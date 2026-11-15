<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MigratePublicStorageCommand extends Command
{
    protected $signature = 'storage:migrate-public-uploads
                            {--force : Overwrite files that already exist under public/storage}';

    protected $description = 'Copy legacy storage/app/public uploads into public/storage (GoDaddy-safe, no symlink)';

    public function handle(): int
    {
        $source = storage_path('app/public');
        $target = public_path('storage');

        if (! is_dir($source)) {
            $this->warn("Nothing to migrate: {$source} does not exist.");

            return self::SUCCESS;
        }

        $sourceReal = realpath($source) ?: $source;
        $targetReal = realpath($target) ?: null;

        if ($this->isSymlinkOrJunction($target) || ($targetReal !== null && $targetReal === $sourceReal)) {
            $this->warn('public/storage already points at storage/app/public (symlink/junction). Nothing to migrate on this machine.');

            return self::SUCCESS;
        }

        if (! is_dir($target)) {
            if (file_exists($target) || is_link($target)) {
                $this->error("{$target} exists but is not a usable directory. Remove it, create a real public/storage folder, then re-run.");

                return self::FAILURE;
            }

            if (! mkdir($target, 0755, true) && ! is_dir($target)) {
                $this->error("Could not create {$target}.");

                return self::FAILURE;
            }
        }

        $copied = 0;
        $skipped = 0;

        foreach (File::allFiles($source) as $file) {
            if ($file->getFilename() === '.gitignore') {
                continue;
            }

            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($sourceReal))), '/');
            if ($relative === '') {
                continue;
            }

            $destination = $target.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

            if (is_file($destination) && ! $this->option('force')) {
                $skipped++;

                continue;
            }

            File::ensureDirectoryExists(dirname($destination));
            File::copy($file->getPathname(), $destination);
            $copied++;
        }

        $this->info("Copied {$copied} file(s) to public/storage.".($skipped > 0 ? " Skipped {$skipped} existing." : ''));

        return self::SUCCESS;
    }

    private function isSymlinkOrJunction(string $path): bool
    {
        if (! file_exists($path) && ! is_link($path)) {
            return false;
        }

        if (is_link($path)) {
            return true;
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            return false;
        }

        $lstat = @lstat($path);

        // Windows reparse point (symlink/junction): FILE_ATTRIBUTE_REPARSE_POINT = 0x400
        return is_array($lstat) && (($lstat['mode'] ?? 0) & 0xF000) === 0xA000;
    }
}
