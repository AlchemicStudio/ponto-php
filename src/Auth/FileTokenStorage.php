<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Auth;

use RuntimeException;

class FileTokenStorage implements TokenStorage
{
    private string $directory;

    public function __construct(?string $directory = null)
    {
        $this->directory = $directory ?? sys_get_temp_dir() . '/ponto-php';

        // Create directory if it doesn't exist
        if (! is_dir($this->directory)) {
            if (! mkdir($this->directory, 0700, true) && ! is_dir($this->directory)) {
                throw new RuntimeException('Failed to create token storage directory: ' . $this->directory);
            }
        }
    }

    public function get(string $key): ?string
    {
        $filePath = $this->getFilePath($key);

        if (! file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            return null;
        }

        return $content;
    }

    public function set(string $key, string $value): void
    {
        $filePath = $this->getFilePath($key);

        if (file_put_contents($filePath, $value, LOCK_EX) === false) {
            throw new RuntimeException('Failed to write token to storage: ' . $filePath);
        }

        // Set restrictive permissions
        chmod($filePath, 0600);
    }

    public function delete(string $key): void
    {
        $filePath = $this->getFilePath($key);

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    private function getFilePath(string $key): string
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);

        return $this->directory . '/' . $safeKey . '.token';
    }
}
