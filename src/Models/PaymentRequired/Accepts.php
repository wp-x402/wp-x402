<?php

declare(strict_types=1);

namespace WpX402\WpX402\Models\PaymentRequired;

use TheFrosty\WpUtilities\Models\BaseModel;
use WpX402\WpX402\Schema\Payment;

/**
 * Class Accepts
 * @package WpX402\WpX402\Models
 */
class Accepts extends BaseModel
{

    public const string SCHEME = 'scheme';
    public const string NETWORK = 'network';
    public const string AMOUNT = 'amount';
    public const string ASSET = 'asset';
    public const string PAY_TO = 'payTo';
    public const string MAX_TIMEOUT_SECONDS = 'maxTimeoutSeconds';
    public const string EXTRA = 'extra';

    protected Payment|string|null $scheme = 'exact';

    public function getScheme(): string
    {
        return $this->scheme instanceof Payment ? $this->scheme->value : ($this->scheme ?? 'exact');
    }

    public function setScheme(Payment|string $scheme = 'exact'): void
    {
        $this->scheme = $scheme instanceof Payment ? $scheme : Payment::tryFrom($scheme)->value;
    }

    protected string $network;

    public function getNetwork(): string
    {
        return $this->network;
    }

    public function setNetwork(string $network): void
    {
        $this->network = $network;
    }

    protected int|string $amount;

    public function getAmount(): int|string
    {
        return $this->amount;
    }

    public function setAmount(int|string $amount): void
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

    protected function getSerializableFields(): array
    {
        return [
            self::SCHEME,
            self::NETWORK,
            self::AMOUNT,
            self::ASSET,
            self::PAY_TO,
            self::MAX_TIMEOUT_SECONDS,
            self::EXTRA,
        ];
    }
}
