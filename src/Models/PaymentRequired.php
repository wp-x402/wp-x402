<?php

declare(strict_types=1);

namespace TheFrosty\WpX402\Models;

use TheFrosty\WpUtilities\Models\BaseModel;

/**
 * Class PaymentRequired
 * @package TheFrosty\WpX402\Models
 */
class PaymentRequired extends BaseModel
{

    protected int $version = 2;

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): void
    {
        $this->version = $version;
    }

    protected ?string $error;

    public function getError(): string
    {
        return $this->error ?? esc_html__('PAYMENT-SIGNATURE header is required', 'wp-x402');
    }

    public function setError(string $error): void
    {
        $this->error = $error;
    }

    protected Resource $resource;

    public function getResource(): Resource
    {
        return $this->resource;
    }

    public function setResource(array $resource): void
    {
        $this->resource = new Resource($resource);
    }

    protected array $accepts;

    public function getAccepts(): array
    {
        return $this->accepts;
    }

    public function setAccepts(array $accepts): void
    {
        $this->accepts[] = new Accepts($accepts);
    }
}
