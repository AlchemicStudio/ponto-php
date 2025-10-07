<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Services;

use AlchemicStudio\Ponto\Exceptions\ApiException;
use AlchemicStudio\Ponto\Exceptions\NotFoundException;
use AlchemicStudio\Ponto\Exceptions\RateLimitException;
use AlchemicStudio\Ponto\Exceptions\ValidationException;
use AlchemicStudio\Ponto\Http\HttpClientInterface;
use AlchemicStudio\Ponto\Models\Account;
use AlchemicStudio\Ponto\Models\PaginatedCollection;
use AlchemicStudio\Ponto\Models\Synchronization;

class AccountService
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * List all accounts
     *
     * @param int $limit Number of results per page (1-100)
     * @param string|null $after Cursor for next page
     * @param string|null $before Cursor for previous page
     * @return PaginatedCollection<Account>
     * @throws ValidationException if limit out of range
     * @throws ApiException|RateLimitException on API errors
     */
    public function list(
        int $limit = 20,
        ?string $after = null,
        ?string $before = null
    ): PaginatedCollection {
        if ($limit < 1 || $limit > 100) {
            throw new ValidationException('Limit must be between 1 and 100');
        }

        $query = ['page' => ['limit' => $limit]];

        if ($after !== null) {
            $query['page']['after'] = $after;
        }

        if ($before !== null) {
            $query['page']['before'] = $before;
        }

        $response = $this->httpClient->get('/accounts', $query);

        $accounts = array_map(
            fn (array $accountData) => Account::fromArray($accountData),
            $response['data'] ?? []
        );

        return PaginatedCollection::fromArray(
            $accounts,
            $response['meta'] ?? [],
            $response['links'] ?? []
        );
    }

    /**
     * Get single account by ID
     *
     * @param string $accountId UUID of the account
     * @return Account
     * @throws NotFoundException if account not found
     * @throws ApiException on API errors
     */
    public function get(string $accountId): Account
    {
        if (empty(trim($accountId))) {
            throw new ValidationException('Account ID cannot be empty');
        }

        $response = $this->httpClient->get('/accounts/' . $accountId);

        if (! isset($response['data'])) {
            throw new NotFoundException('Account not found');
        }

        return Account::fromArray($response['data']);
    }

    /**
     * Get synchronization metadata for an account
     *
     * @param string $accountId
     * @return array{synchronizedAt: string, latestSynchronization: Synchronization}
     * @throws NotFoundException if account not found
     */
    public function getSyncMetadata(string $accountId): array
    {
        $response = $this->httpClient->get('/accounts/' . $accountId);

        if (! isset($response['data'])) {
            throw new NotFoundException('Account not found');
        }

        $meta = $response['data']['meta'] ?? [];

        $synchronizedAt = $meta['synchronizedAt'] ?? '';

        $latestSyncData = $meta['latestSynchronization'] ?? null;

        return [
            'synchronizedAt' => $synchronizedAt,
            'latestSynchronization' => $latestSyncData ? Synchronization::fromArray($latestSyncData) : null,
        ];
    }
}
