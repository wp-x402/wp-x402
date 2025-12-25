<?php

declare(strict_types=1);

namespace TheFrosty\WpX402\Models;

use TheFrosty\WpUtilities\Models\BaseModel;
use TheFrosty\WpX402\Networks\Network;

/**
 * Class Accepts
 * @package TheFrosty\WpX402\Models
 */
class Accepts extends BaseModel
{

    protected string $scheme = 'exact';

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function setScheme(string $scheme): void
    {
        $this->scheme = $scheme;
    }

    protected Network $network;

    public function getNetwork(): Network
    {
        return $this->network;
    }

    public function setNetwork(Network $network): void
    {
        $this->network = $network;
    }

    protected int $amount;

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    protected string $asset;

    public function getAsset(): string
    {
        return $this->asset;
    }

    public function setAsset(string $asset): void
    {
        $this->asset = $asset;
    }

    protected string $payTo;

    public function getPayTo(): string
    {
        return $this->payTo;
    }

    public function setPayTo(string $payTo): void
    {
        $this->payTo = $payTo;
    }

    protected ?int $maxTimeoutSeconds = null;

    public function getMaxTimeoutSeconds(): ?int
    {
        return $this->maxTimeoutSeconds;
    }

    public function setMaxTimeoutSeconds(?int $maxTimeoutSeconds): void
    {
        $this->maxTimeoutSeconds = $maxTimeoutSeconds;
    }

    protected ?array $extra = null;

    public function getExtra(): ?array
    {
        return $this->extra;
    }

    public function setExtra(?array $extra): void
    {
        $this->extra = $extra;
    }
}
