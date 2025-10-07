<?php

declare(strict_types=1);

namespace AlchemicStudio\Ponto\Services;

use AlchemicStudio\Ponto\Exceptions\ApiException;
use AlchemicStudio\Ponto\Exceptions\AuthenticationException;
use AlchemicStudio\Ponto\Exceptions\NotFoundException;
use AlchemicStudio\Ponto\Exceptions\ValidationException;
use AlchemicStudio\Ponto\Http\HttpClientInterface;
use AlchemicStudio\Ponto\Models\Payment;
use AlchemicStudio\Ponto\Utils\Validator;

class PaymentService
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Create a payment
     *
     * @param string $accountId Debtor account UUID
     * @param array{
     *     amount: float,
     *     currency: string,
     *     creditorName: string,
     *     creditorAccountReference: string,
     *     creditorAccountReferenceType: string,
     *     creditorAgent: string,
     *     creditorAgentType: string,
     *     remittanceInformation: string,
     *     remittanceInformationType?: string,
     *     endToEndId?: string,
     *     requestedExecutionDate?: string,
     *     redirectUri?: string
     * } $paymentData Payment details
     * @return Payment
     * @throws ValidationException if data invalid
     * @throws AuthenticationException if 'pi' scope missing
     * @throws ApiException on API errors
     */
    public function create(string $accountId, array $paymentData): Payment
    {
        // Validate required fields
        $this->validatePaymentData($paymentData);

        // Build request body
        $body = [
            'data' => [
                'type' => 'payment',
                'attributes' => [
                    'amount' => $paymentData['amount'],
                    'currency' => Validator::validateCurrency($paymentData['currency']),
                    'creditorName' => $paymentData['creditorName'],
                    'creditorAccountReference' => $paymentData['creditorAccountReference'],
                    'creditorAccountReferenceType' => $paymentData['creditorAccountReferenceType'],
                    'creditorAgent' => $paymentData['creditorAgent'],
                    'creditorAgentType' => $paymentData['creditorAgentType'],
                    'remittanceInformation' => Validator::validateRemittanceInfo($paymentData['remittanceInformation']),
                    'remittanceInformationType' => $paymentData['remittanceInformationType'] ?? 'unstructured',
                ],
            ],
        ];

        // Add optional fields
        if (isset($paymentData['endToEndId'])) {
            $body['data']['attributes']['endToEndId'] = $paymentData['endToEndId'];
        }

        if (isset($paymentData['requestedExecutionDate'])) {
            $body['data']['attributes']['requestedExecutionDate'] = $paymentData['requestedExecutionDate'];
        }

        if (isset($paymentData['redirectUri'])) {
            $body['data']['attributes']['redirectUri'] = $paymentData['redirectUri'];
        }

        $response = $this->httpClient->post('/accounts/' . $accountId . '/payments', $body);

        if (! isset($response['data'])) {
            throw new ApiException('Invalid payment response');
        }

        return Payment::fromArray($response['data']);
    }

    /**
     * Get payment details
     *
     * @param string $accountId Account UUID
     * @param string $paymentId Payment UUID
     * @return Payment
     * @throws NotFoundException if not found
     * @throws ApiException on API errors
     */
    public function get(string $accountId, string $paymentId): Payment
    {
        $response = $this->httpClient->get('/accounts/' . $accountId . '/payments/' . $paymentId);

        if (! isset($response['data'])) {
            throw new NotFoundException('Payment not found');
        }

        return Payment::fromArray($response['data']);
    }

    /**
     * Delete (cancel) a payment
     *
     * @param string $accountId Account UUID
     * @param string $paymentId Payment UUID
     * @return void
     * @throws NotFoundException if not found
     * @throws ApiException on API errors
     */
    public function delete(string $accountId, string $paymentId): void
    {
        $this->httpClient->delete('/accounts/' . $accountId . '/payments/' . $paymentId);
    }

    /**
     * Validate payment data
     *
     * @throws ValidationException
     */
    private function validatePaymentData(array $paymentData): void
    {
        // Validate required fields
        $requiredFields = [
            'amount',
            'currency',
            'creditorName',
            'creditorAccountReference',
            'creditorAccountReferenceType',
            'creditorAgent',
            'creditorAgentType',
            'remittanceInformation',
        ];

        foreach ($requiredFields as $field) {
            if (! isset($paymentData[$field])) {
                throw new ValidationException("Missing required field: {$field}");
            }
        }

        // Validate amount
        Validator::validateAmount($paymentData['amount']);

        // Validate IBAN if reference type is IBAN
        if (strtoupper($paymentData['creditorAccountReferenceType']) === 'IBAN') {
            Validator::validateIban($paymentData['creditorAccountReference']);
        }

        // Validate BIC if agent type is BIC
        if (strtoupper($paymentData['creditorAgentType']) === 'BIC') {
            Validator::validateBic($paymentData['creditorAgent']);
        }
    }
}
