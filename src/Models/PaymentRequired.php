<?php

declare(strict_types=1);

namespace WpX402\WpX402\Models;

use TheFrosty\WpUtilities\Models\BaseModel;
use WpX402\WpX402\Models\PaymentRequired\Accepts;
use WpX402\WpX402\Models\PaymentRequired\Payload;
use WpX402\WpX402\Models\PaymentRequired\UrlResource;

/**
 * Class PaymentRequired
 * @package WpX402\WpX402\Models
 */
class PaymentRequired extends BaseModel
{

    public const string VERSION = 'x402Version';
    public const string ERROR = 'error';
    public const string RESOURCE = 'resource';
    public const string ACCEPTS = 'accepts';
    public const string PAYLOAD = 'payload';

    protected int $x402Version = 2;

    public function getX402Version(): int
    {
        return $this->x402Version;
    }

    public function setX402Version(int $x402Version = 2): void
    {
        $this->x402Version = $x402Version;
    }

    protected string $error;

    public function getError(): string
    {
        return $this->error;
    }

    public function setError(string $error): void
    {
        $this->error = $error;
    }

    protected UrlResource $resource;

    public function getResource(): UrlResource
    {
        return $this->resource;
    }

    public function setResource(array $resource): void
    {
        $this->resource = new UrlResource($resource);
    }

    /** @var Accepts[] $accepts */
    protected array $accepts;

    /** @return Accepts[] */
    public function getAccepts(): array
    {
        return $this->accepts;
    }

    public function setAccepts(array $accepts): void
    {
        foreach ($accepts as $accept) {
            $this->accepts[] = new Accepts($accept);
        }
    }

    protected ?Payload $payload = null;

    public function getPayload(): ?Payload
    {
        return $this->payload;
    }

    public function setPayload(?array $payload): void
    {
        $this->payload = new Payload($payload);
    }

    public function toArray(): array
    {
        return array_filter(parent::toArray());
    }

    protected function getSerializableFields(): array
    {
        return [
            self::VERSION,
            self::RESOURCE,
            self::ACCEPTS,
            self::PAYLOAD,
        ];
    }
}
