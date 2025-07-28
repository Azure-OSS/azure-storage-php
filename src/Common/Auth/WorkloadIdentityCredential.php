<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Azure Workload Identity credential for Kubernetes environments
 * 
 * @see https://azure.github.io/azure-workload-identity/docs/
 */
final class WorkloadIdentityCredential implements CredentialInterface
{
    private ?string $accessToken = null;
    private ?int $tokenExpiry = null;
    private readonly Client $httpClient;

    public function __construct(
        public readonly string $storageAccountName,
        public readonly ?string $tenantId = null,
        public readonly ?string $clientId = null,
        public readonly ?string $federatedTokenFile = null,
        public readonly int $httpTimeoutSeconds = 10,
    ) {
        $this->httpClient = new Client(['timeout' => $this->httpTimeoutSeconds]);
    }

    /**
     * Create WorkloadIdentityCredential from environment variables
     */
    public static function fromEnvironment(string $storageAccountName): self
    {
        return new self(
            storageAccountName: $storageAccountName,
            tenantId: $_ENV['AZURE_TENANT_ID'] ?? null,
            clientId: $_ENV['AZURE_CLIENT_ID'] ?? null,
            federatedTokenFile: $_ENV['AZURE_FEDERATED_TOKEN_FILE'] ?? null,
        );
    }

    /**
     * Get Azure AD access token using Workload Identity
     * 
     * @throws \RuntimeException
     */
    public function getAccessToken(): string
    {
        // Return cached token if valid (with 5min buffer)
        if ($this->accessToken && $this->tokenExpiry && time() < $this->tokenExpiry - 300) {
            return $this->accessToken;
        }

        $this->refreshAccessToken();
        
        if (!$this->accessToken) {
            throw new \RuntimeException('Failed to obtain access token');
        }

        return $this->accessToken;
    }

    /**
     * Check if token is available and valid
     */
    public function hasValidToken(): bool
    {
        return $this->accessToken !== null && 
               $this->tokenExpiry !== null && 
               time() < $this->tokenExpiry - 300;
    }

    /**
     * Force token refresh
     */
    public function refreshAccessToken(): void
    {
        $tenantId = $this->tenantId ?? $this->getTenantIdFromEnvironment();
        $clientId = $this->clientId ?? $this->getClientIdFromEnvironment();
        $federatedTokenFile = $this->federatedTokenFile ?? $this->getFederatedTokenFileFromEnvironment();

        if (!$tenantId || !$clientId || !$federatedTokenFile) {
            throw new \RuntimeException('Workload Identity environment variables not found');
        }

        if (!file_exists($federatedTokenFile)) {
            throw new \RuntimeException("Federated token file not found: $federatedTokenFile");
        }

        $federatedToken = file_get_contents($federatedTokenFile);
        if (!$federatedToken) {
            throw new \RuntimeException('Failed to read federated token');
        }

        $this->exchangeFederatedTokenForAccessToken(trim($federatedToken), $tenantId, $clientId);
    }

    private function getTenantIdFromEnvironment(): ?string
    {
        return $_ENV['AZURE_TENANT_ID'] ?? null;
    }

    private function getClientIdFromEnvironment(): ?string
    {
        return $_ENV['AZURE_CLIENT_ID'] ?? null;
    }

    private function getFederatedTokenFileFromEnvironment(): ?string
    {
        return $_ENV['AZURE_FEDERATED_TOKEN_FILE'] ?? null;
    }

    private function exchangeFederatedTokenForAccessToken(string $federatedToken, string $tenantId, string $clientId): void
    {
        $tokenEndpoint = "https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token";

        try {
            $response = $this->httpClient->post($tokenEndpoint, [
                'form_params' => [
                    'client_id' => $clientId,
                    'client_assertion' => $federatedToken,
                    'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
                    'scope' => 'https://storage.azure.com/.default',
                    'grant_type' => 'client_credentials'
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ]
            ]);

            $responseBody = $response->getBody()->getContents();
            $tokenData = json_decode($responseBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON response from token endpoint: ' . json_last_error_msg());
            }
            
            if (!isset($tokenData['access_token'])) {
                throw new \RuntimeException('Invalid token response: missing access_token');
            }

            $this->accessToken = $tokenData['access_token'];
            $this->tokenExpiry = time() + ($tokenData['expires_in'] ?? 3600);

        } catch (RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response';
            throw new \RuntimeException("Failed to get access token: " . $e->getMessage() . " - " . $errorBody);
        }
    }

    /**
     * Clear cached token (useful for testing)
     */
    public function clearToken(): void
    {
        $this->accessToken = null;
        $this->tokenExpiry = null;
    }
}