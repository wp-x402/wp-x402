<?php

declare(strict_types=1);

namespace WpX402\WpX402\Models\PaymentRequired;

use TheFrosty\WpUtilities\Models\BaseModel;

/**
 * Class Authorization
 * @package WpX402\WpX402\Models\PaymentRequired
 */
class Authorization extends BaseModel
{

    protected string $from;

    public function getFrom(): string
    {
        return $this->from;
    }

    public function setFrom(string $from): void
    {
        $this->from = $from;
    }

    protected string $to;

    public function getTo(): string
    {
        return $this->to;
    }

    public function setTo(string $to): void
    {
        $this->to = $to;
    }

    protected string $value;

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    protected string $validAfter;

    public function getValidAfter(): string
    {
        return $this->validAfter;
    }

    public function setValidAfter(string $validAfter): void
    {
        $this->validAfter = $validAfter;
    }

    protected string $validBefore;

    public function getValidBefore(): string
    {
        return $this->validBefore;
    }

    public function setValidBefore(string $validBefore): void
    {
        $this->validBefore = $validBefore;
    }

    protected string $nonce;

    public function getNonce(): string
    {
        return $this->nonce;
    }

    public function setNonce(string $nonce): void
    {
        $this->nonce = $nonce;
    }
}
