<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Auth;

interface TokenStorage
{
    /**
     * Retrieve stored token data
     *
     * @param string $key Storage key
     * @return string|null Token data as JSON string, or null if not found
     */
    public function get(string $key): ?string;

    /**
     * Store token data
     *
     * @param string $key Storage key
     * @param string $value Token data as JSON string
     * @return void
     */
    public function set(string $key, string $value): void;

    /**
     * Delete stored token data
     *
     * @param string $key Storage key
     * @return void
     */
    public function delete(string $key): void;
}
