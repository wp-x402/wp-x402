<?php

declare(strict_types=1);

namespace TheFrosty\WpX402\Models\PaymentRequired;

use TheFrosty\WpUtilities\Models\BaseModel;

/**
 * Class UrlResource
 * @package TheFrosty\WpX402\Models
 */
class UrlResource extends BaseModel
{

    public const string URL = 'url';
    public const string DESCRIPTION = 'description';
    public const string MIME_TYPE = 'mimeType';

    protected string $url;

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    protected string $description;

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    protected string $mime_type;

    public function getMimeType(): string
    {
        return $this->mime_type;
    }

    public function setMimeType(string $mime_type): void
    {
        $this->mime_type = $mime_type;
    }

    public function getSerializableFields(): array
    {
        return [
            self::URL,
            self::DESCRIPTION,
            self::MIME_TYPE,
        ];
    }
}
