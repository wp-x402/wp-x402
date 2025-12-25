<?php

declare(strict_types=1);

namespace TheFrosty\WpX402\Models\PaymentRequired;

/**
 * Class Payload
 * @package TheFrosty\WpX402\Models
 */
class Payload
{

    protected string $signature;

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function setSignature(string $signature): void
    {
        $this->signature = $signature;
    }

    protected Authorization $authorization;

    public function getAuthorization(): Authorization
    {
        return $this->authorization;
    }

    public function setAuthorization(array $authorization): void
    {
        $this->authorization = new Authorization($authorization);
    }
}
