<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Requests;

/**
 * @internal
 */
final class PutBlockRequestBody
{
    /**
     * @param  string[]  $base64BlockIds
     */
    public function __construct(
        public array $base64BlockIds,
    ) {}

    public function toXml(): \SimpleXMLElement
    {
        $xml = new \SimpleXMLElement('<BlockList></BlockList>');

        foreach ($this->base64BlockIds as $base64BlockId) {
            $xml->addChild('Latest', $base64BlockId);
        }

        return $xml;
    }
}
