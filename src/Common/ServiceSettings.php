<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common;

class ServiceSettings
{
    public function __construct(
        public string $fileEndpoint,
        public string $blobEndpoint,
        public string $accountName,
        public string $accountKey,
    ) {}

    /**
     * @see https://learn.microsoft.com/en-us/azure/storage/common/storage-configure-connection-string
     */
    public static function createFromConnectionString(string $connectionString): self
    {
        if ($connectionString === 'UseDevelopmentStorage=true') { // @todo fixme with DI
            return self::createForDevAccount();
        }

        $settings = [];
        foreach (explode(';', $connectionString) as $segment) {
            [$key, $value] = explode('=', $segment, 2);
            $settings[$key] = $value;
        }

        dd($settings);
        $fileEndpoint = $settings['fileEndpoint'] ?? $settings['blobEndpoint'] ??
            sprintf(
                '%s://%s.file.core.windows.net',
                $settings['defaultEndpointsProtocol'] ?? 'http',
                $settings['AccountName'],
            );

        $blobEndpoint = $settings['blobEndpoint']
            ?? $settings['DefaultEndpointsProtocol'] . "://" . $settings['AccountName'] . '.blob.' . $settings['EndpointSuffix'];

        return new self(
            $fileEndpoint,
            $blobEndpoint,
            $settings['AccountName'],
            $settings['AccountKey'],
        );
    }

    public static function createForDevAccount(): self
    {
        return new self(
            'http://127.0.0.1:10000/devstoreaccount1',
            'devstoreaccount1',
            'Eby8vdM02xNOcqFlqUwJPLlmEtlCDXJ1OUzFT50uSRZ6IFsuFq2UVErCz4I6tq/K1SZFPTOtr/KBHBeksoGMGw==',
        );
    }
}
