<?php

declare(strict_types=1);

namespace WpX402\WpX402\Tests\Models;

use PHPUnit\Framework\Attributes\CoversClass;
use WpX402\WpX402\Models\PaymentRequired;
use WpX402\WpX402\Models\PaymentRequired\Accepts;
use WpX402\WpX402\Models\PaymentRequired\UrlResource;
use WpX402\WpX402\Schema\Payment;
use WpX402\WpX402\Tests\TestCase;

/**
 * Class PaymentRequiredTest
 * @package WpX402\WpX402\Tests\Models
 */
#[CoversClass(PaymentRequired::class)]
#[CoversClass(Accepts::class)]
#[CoversClass(UrlResource::class)]
class PaymentRequiredTest extends TestCase
{

    /**
     * Test toArray() builds the x402 v2 PaymentRequired shape.
     */
    public function testToArray(): void
    {
        $model = new PaymentRequired([
            PaymentRequired::ERROR => 'PAYMENT-SIGNATURE header is required',
            PaymentRequired::RESOURCE => [
                UrlResource::URL => 'https://example.org/post/',
                UrlResource::DESCRIPTION => 'Payment Required',
                UrlResource::MIME_TYPE => 'text/html',
            ],
            PaymentRequired::ACCEPTS => [
                [
                    Accepts::SCHEME => Payment::EXACT->value,
                    Accepts::NETWORK => 'eip155:84532',
                    Accepts::AMOUNT => '10000',
                    Accepts::ASSET => '0x036CbD53842c5426634e7929541eC2318f3dCF7e',
                    Accepts::PAY_TO => '0x209693Bc6afc0C5328bA36FaF03C514EF312287C',
                    Accepts::MAX_TIMEOUT_SECONDS => 60,
                    Accepts::EXTRA => ['name' => 'USDC', 'version' => '2'],
                ],
            ],
        ]);

        $this->assertSame(2, $model->getX402Version());
        $this->assertSame('PAYMENT-SIGNATURE header is required', $model->getError());
        $this->assertNull($model->getPayload());
        $this->assertCount(1, $model->getAccepts());
        $this->assertInstanceOf(Accepts::class, $model->getAccepts()[0]);

        $this->assertSame(
            [
                'x402Version' => 2,
                'resource' => [
                    'url' => 'https://example.org/post/',
                    'description' => 'Payment Required',
                    'mimeType' => 'text/html',
                ],
                'accepts' => [
                    [
                        'scheme' => 'exact',
                        'network' => 'eip155:84532',
                        'amount' => '10000',
                        'asset' => '0x036CbD53842c5426634e7929541eC2318f3dCF7e',
                        'payTo' => '0x209693Bc6afc0C5328bA36FaF03C514EF312287C',
                        'maxTimeoutSeconds' => 60,
                        'extra' => ['name' => 'USDC', 'version' => '2'],
                    ],
                ],
            ],
            $model->toArray()
        );
    }

    /**
     * Test the Accepts scheme accessors.
     */
    public function testAcceptsScheme(): void
    {
        $accepts = new Accepts();
        $this->assertSame('exact', $accepts->getScheme());

        $accepts->setScheme(Payment::UPTO);
        $this->assertSame('upto', $accepts->getScheme());

        $accepts->setScheme('exact');
        $this->assertSame('exact', $accepts->getScheme());
    }
}
