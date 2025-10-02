<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Utils;

use DateTimeImmutable;
use DateTimeInterface;

class DateHelper
{
    /**
     * Format date for API request (ISO 8601)
     */
    public static function formatForApi(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }

    /**
     * Format datetime for API request (ISO 8601 with time)
     */
    public static function formatDateTimeForApi(DateTimeInterface $dateTime): string
    {
        return $dateTime->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Parse API date string to DateTimeImmutable
     */
    public static function parseFromApi(string $dateString): DateTimeImmutable
    {
        return new DateTimeImmutable($dateString);
    }

    /**
     * Get current date formatted for API
     */
    public static function today(): string
    {
        return (new DateTimeImmutable())->format('Y-m-d');
    }

    /**
     * Get date N days ago formatted for API
     */
    public static function daysAgo(int $days): string
    {
        return (new DateTimeImmutable())->modify("-{$days} days")->format('Y-m-d');
    }

    /**
     * Get date N days from now formatted for API
     */
    public static function daysFromNow(int $days): string
    {
        return (new DateTimeImmutable())->modify("+{$days} days")->format('Y-m-d');
    }

    /**
     * Get first day of current month formatted for API
     */
    public static function firstDayOfMonth(): string
    {
        return (new DateTimeImmutable())->modify('first day of this month')->format('Y-m-d');
    }

    /**
     * Get last day of current month formatted for API
     */
    public static function lastDayOfMonth(): string
    {
        return (new DateTimeImmutable())->modify('last day of this month')->format('Y-m-d');
    }
}
