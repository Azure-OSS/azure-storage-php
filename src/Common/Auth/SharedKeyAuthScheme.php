<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Auth;

use AzureOss\Storage\Common\StorageServiceSettings;
use GuzzleHttp\Psr7\Query;
use Psr\Http\Message\RequestInterface;

/**
 * @see https://learn.microsoft.com/en-us/rest/api/storageservices/authorize-with-shared-key
 */
class SharedKeyAuthScheme implements AuthScheme
{
    public const INCLUDED_HEADERS = [
        'Content-Encoding',
        'Content-Language',
        'Content-Length',
        'Content-MD5',
        'Content-Type',
        'Date',
        'If-Modified-Since',
        'If-Match',
        'If-None-Match',
        'If-Unmodified-Since',
        'Range',
    ];

    private string $accountName;

    private string $accountKey;

    public function __construct(StorageServiceSettings $settings)
    {
        $this->accountName = $settings->accountName;
        $this->accountKey = $settings->accountKey;
    }

    public function computeAuthorizationHeader(RequestInterface $request): string
    {
        $stringToSign = $this->computeStringToSign(
            array_map(fn ($value) => implode(', ', $value), $request->getHeaders()),
            (string) $request->getUri(),
            Query::parse($request->getUri()->getQuery()),
            $request->getMethod()
        );
        $signature = $this->computeSignature($stringToSign);

        return 'SharedKey '.$this->accountName.':'.$signature;
    }

    private function computeSignature(string $stringToSign): string
    {
        $decodedAccountKey = base64_decode($this->accountKey, true);

        if($decodedAccountKey === false) {
            throw new \Exception("Account key should be a valid base64 string.");
        }

        return base64_encode(
            hash_hmac('sha256', $stringToSign, $decodedAccountKey, true)
        );
    }

    /**
     * @param array<string, string> $headers
     * @param string $url
     * @param array<string, string> $queryParams
     * @param string $httpMethod
     * @return string
     */
    private function computeStringToSign(
        array $headers,
        string $url,
        array $queryParams,
        string $httpMethod
    ): string {
        $stringToSign = [];

        $stringToSign[] = strtoupper($httpMethod);

        foreach (self::INCLUDED_HEADERS as $header) {
            $stringToSign[] = array_change_key_case($headers)[strtolower($header)] ?? null;
        }

        $stringToSign[] = $this->computeCanonicalizedHeaders($headers);
        $stringToSign[] = $this->computeCanonicalizedResource($url, $queryParams);

        return implode("\n", $stringToSign);
    }

    /**
     * @param array<string, string> $headers
     */
    private function computeCanonicalizedHeaders(array $headers): string
    {
        $normalizedHeaders = [];

        foreach ($headers as $header => $value) {
            // Convert header to lower case.
            $header = strtolower($header);

            // Retrieve all headers for the resource that begin with x-ms-,
            // including the x-ms-date header.
            if (str_starts_with($header, 'x-ms-')) {
                // Unfold the string by replacing any breaking white space
                // (meaning what splits the headers, which is \r\n) with a single
                // space.
                $value = str_replace("\r\n", ' ', $value);

                // Trim any white space around the colon in the header.
                $value = ltrim($value);
                $header = rtrim($header);

                $normalizedHeaders[$header] = $value;
            }
        }

        // Sort the headers lexicographically by header name, in ascending order.
        // Note that each header may appear only once in the string.
        ksort($normalizedHeaders);

        $canonicalizedHeaders = [];
        foreach ($normalizedHeaders as $key => $value) {
            $canonicalizedHeaders[] = $key.':'.$value;
        }

        return implode("\n", $canonicalizedHeaders);
    }

    /**
     * @param string $url
     * @param array<string, string> $queryParams
     * @return string
     */
    private function computeCanonicalizedResource(string $url, array $queryParams): string
    {
        $queryParams = array_change_key_case($queryParams);

        // 1. Beginning with an empty string (""), append a forward slash (/),
        //    followed by the name of the account that owns the accessed resource.
        $canonicalizedResource = '/'.$this->accountName;

        // 2. Append the resource's encoded URI path, without any query parameters.
        $canonicalizedResource .= parse_url($url, PHP_URL_PATH);

        // 3. Retrieve all query parameters on the resource URI, including the comp
        //    parameter if it exists.
        // 4. Sort the query parameters lexicographically by parameter name, in
        //    ascending order.
        ksort($queryParams);

        // 5. Convert all parameter names to lowercase.
        // 6. URL-decode each query parameter name and value.
        // 7. Append each query parameter name and value to the string in the
        //    following format:
        //      parameter-name:parameter-value
        // 9. Group query parameters
        // 10. Append a new line character (\n) after each name-value pair.
        foreach ($queryParams as $key => $value) {
            // $value must already be ordered lexicographically
            // See: ServiceRestProxy::groupQueryValues
            $canonicalizedResource .= "\n".$key.':'.$value;
        }

        return $canonicalizedResource;
    }
}
