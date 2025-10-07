<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Services;

use AlchemicStudio\Ponto\Exceptions\ApiException;
use AlchemicStudio\Ponto\Exceptions\NotFoundException;
use AlchemicStudio\Ponto\Exceptions\ValidationException;
use AlchemicStudio\Ponto\Http\HttpClientInterface;
use AlchemicStudio\Ponto\Models\PaginatedCollection;
use AlchemicStudio\Ponto\Models\Transaction;

class TransactionService
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * List transactions for an account
     *
     * @param string $accountId Account UUID
     * @param array{
     *     limit?: int,
     *     after?: string,
     *     before?: string,
     *     since?: string,
     *     until?: string
     * } $filters Optional filters
     * @return PaginatedCollection<Transaction>
     * @throws NotFoundException if account not found
     * @throws ValidationException if filters invalid
     * @throws ApiException on API errors
     */
    public function list(string $accountId, array $filters = []): PaginatedCollection
    {
        $limit = $filters['limit'] ?? 20;

        if ($limit < 1 || $limit > 100) {
            throw new ValidationException('Limit must be between 1 and 100');
        }

        $query = ['page' => ['limit' => $limit]];

        if (isset($filters['after'])) {
            $query['page']['after'] = $filters['after'];
        }

        if (isset($filters['before'])) {
            $query['page']['before'] = $filters['before'];
        }

        if (isset($filters['since'])) {
            $query['filter']['since'] = $filters['since'];
        }

        if (isset($filters['until'])) {
            $query['filter']['until'] = $filters['until'];
        }

        $response = $this->httpClient->get('/accounts/' . $accountId . '/transactions', $query);

        $transactions = array_map(
            fn (array $transactionData) => Transaction::fromArray($transactionData),
            $response['data'] ?? []
        );

        return PaginatedCollection::fromArray(
            $transactions,
            $response['meta'] ?? [],
            $response['links'] ?? []
        );
    }

    /**
     * Get single transaction
     *
     * @param string $accountId Account UUID
     * @param string $transactionId Transaction UUID
     * @return Transaction
     * @throws NotFoundException if not found
     * @throws ApiException on API errors
     */
    public function get(string $accountId, string $transactionId): Transaction
    {
        $response = $this->httpClient->get('/accounts/' . $accountId . '/transactions/' . $transactionId);

        if (! isset($response['data'])) {
            throw new NotFoundException('Transaction not found');
        }

        return Transaction::fromArray($response['data']);
    }

    /**
     * List pending transactions
     *
     * @param string $accountId Account UUID
     * @param int $limit Results per page (1-100)
     * @param string|null $after Cursor for next page
     * @param string|null $before Cursor for previous page
     * @return PaginatedCollection<Transaction>
     * @throws NotFoundException if account not found
     * @throws ApiException on API errors
     */
    public function listPending(
        string $accountId,
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

        $response = $this->httpClient->get('/accounts/' . $accountId . '/pending-transactions', $query);

        $transactions = array_map(
            fn (array $transactionData) => Transaction::fromArray($transactionData),
            $response['data'] ?? []
        );

        return PaginatedCollection::fromArray(
            $transactions,
            $response['meta'] ?? [],
            $response['links'] ?? []
        );
    }

    /**
     * Get updated transactions from a synchronization
     *
     * @param string $synchronizationId Synchronization UUID
     * @return array<Transaction>
     * @throws NotFoundException if sync not found
     * @throws ApiException on API errors
     */
    public function getUpdatedFromSync(string $synchronizationId): array
    {
        $response = $this->httpClient->get('/synchronizations/' . $synchronizationId . '/updated-transactions');

        return array_map(
            fn (array $transactionData) => Transaction::fromArray($transactionData),
            $response['data'] ?? []
        );
    }
}
