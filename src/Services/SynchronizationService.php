<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Services;

use AlchemicStudio\Ponto\Exceptions\ApiException;
use AlchemicStudio\Ponto\Exceptions\NotFoundException;
use AlchemicStudio\Ponto\Exceptions\RateLimitException;
use AlchemicStudio\Ponto\Exceptions\ValidationException;
use AlchemicStudio\Ponto\Http\HttpClientInterface;
use AlchemicStudio\Ponto\Models\Synchronization;

class SynchronizationService
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Create synchronization job
     *
     * @param string $resourceType 'account' or 'transaction'
     * @param string $resourceId UUID of resource to sync
     * @param string $subtype 'accountDetails', 'accountTransactions', etc.
     * @param string|null $customerIpAddress Client IP for audit
     * @return Synchronization
     * @throws ValidationException if invalid parameters
     * @throws ApiException on API errors (e.g., sync too soon)
     */
    public function create(
        string $resourceType,
        string $resourceId,
        string $subtype,
        ?string $customerIpAddress = null
    ): Synchronization {
        // Validate resource type
        if (! in_array($resourceType, ['account', 'transaction'], true)) {
            throw new ValidationException('Resource type must be "account" or "transaction"');
        }

        $body = [
            'data' => [
                'type' => 'synchronization',
                'attributes' => [
                    'resourceType' => $resourceType,
                    'resourceId' => $resourceId,
                    'subtype' => $subtype,
                ],
            ],
        ];

        if ($customerIpAddress !== null) {
            $body['data']['attributes']['customerIpAddress'] = $customerIpAddress;
        }

        $response = $this->httpClient->post('/synchronizations', $body);

        if (isset($response['errors'])) {
            foreach ($response['errors'] as $error) {
                if ($error['code'] === "accountRecentlySynchronized") {
                    throw new RateLimitException('Account recently synchronized');
                }
            }
        }

        if (! isset($response['data'])) {
            throw new ApiException('Invalid synchronization response: ' . json_encode($response), 0);
        }

        return Synchronization::fromArray($response['data']);
    }

    /**
     * Get synchronization status
     *
     * @param string $synchronizationId Sync UUID
     * @return Synchronization
     * @throws NotFoundException if not found
     * @throws ApiException on API errors
     */
    public function get(string $synchronizationId): Synchronization
    {
        $response = $this->httpClient->get('/synchronizations/' . $synchronizationId);

        if (! isset($response['data'])) {
            throw new NotFoundException('Synchronization not found');
        }

        return Synchronization::fromArray($response['data']);
    }

    /**
     * Poll synchronization until complete or timeout
     *
     * @param string $synchronizationId Sync UUID
     * @param int $maxAttempts Maximum polling attempts
     * @param int $intervalSeconds Seconds between polls
     * @return Synchronization
     * @throws ApiException if timeout or sync fails
     */
    public function pollUntilComplete(
        string $synchronizationId,
        int $maxAttempts = 20,
        int $intervalSeconds = 3
    ): Synchronization {
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $sync = $this->get($synchronizationId);

            if ($sync->isComplete()) {
                if ($sync->hasErrors()) {
                    $errors = implode(', ', array_map(
                        fn ($error) => $error['detail'] ?? 'Unknown error',
                        $sync->errors
                    ));

                    throw new ApiException('Synchronization failed: ' . $errors);
                }

                return $sync;
            }

            $attempt++;

            if ($attempt < $maxAttempts) {
                sleep($intervalSeconds);
            }
        }

        throw new ApiException('Synchronization timeout after ' . $maxAttempts . ' attempts');
    }
}
