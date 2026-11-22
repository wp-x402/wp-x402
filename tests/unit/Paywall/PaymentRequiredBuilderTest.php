<?php

declare(strict_types=1);

namespace WpX402\WpX402\Tests\Paywall;

use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\UsesClass;
use WpX402\WpX402\Models\PaymentRequired;
use WpX402\WpX402\Models\PaymentRequired\Accepts;
use WpX402\WpX402\Models\PaymentRequired\UrlResource;
use WpX402\WpX402\Networks\Mainnet;
use WpX402\WpX402\Networks\Testnet;
use WpX402\WpX402\Paywall\AbstractPaywall;
use WpX402\WpX402\Paywall\Entitlement;
use WpX402\WpX402\Paywall\ForBots;
use WpX402\WpX402\Settings\General;
use WpX402\WpX402\Settings\Setting;
use WpX402\WpX402\Tests\TestCase;
use function sprintf;

/**
 * Class PaymentRequiredBuilderTest
 * @package WpX402\WpX402\Tests\Paywall
 */
#[CoversMethod(AbstractPaywall::class, 'getPaymentRequired')]
#[CoversMethod(AbstractPaywall::class, 'getExtra')]
#[UsesClass(PaymentRequired::class)]
#[UsesClass(Accepts::class)]
#[UsesClass(UrlResource::class)]
#[UsesClass(Mainnet::class)]
#[UsesClass(Testnet::class)]
#[UsesClass(Entitlement::class)]
#[UsesClass(Setting::class)]
class PaymentRequiredBuilderTest extends TestCase
{

    private const string EVM_WALLET = '0x209693Bc6afc0C5328bA36FaF03C514EF312287C';

    /**
     * Test the default (testnet, EVM) requirements.
     */
    public function testTestnetEvm(): void
    {
        $this->setGeneralSettings([
            General::ACCOUNT => 'base',
            sprintf(General::WALLET, 'base') => self::EVM_WALLET,
        ]);

        $model = $this->build('https://example.org/post/');
        $this->assertSame('https://example.org/post/', $model->getResource()->getUrl());
        $this->assertSame('text/html', $model->getResource()->getMimeType());

        $accepts = $model->getAccepts()[0];
        $this->assertSame('exact', $accepts->getScheme());
        $this->assertSame(Testnet::BASE->value, $accepts->getNetwork());
        $this->assertSame(Testnet::ASSET_BASE->value, $accepts->getAsset());
        $this->assertSame('10000', $accepts->getAmount());
        $this->assertSame(self::EVM_WALLET, $accepts->getPayTo());
        $this->assertSame(60, $accepts->getMaxTimeoutSeconds());
        $this->assertSame(['name' => 'USDC', 'version' => '2'], $accepts->getExtra());
    }

    /**
     * Test mainnet EVM requirements use the Base USDC EIP-712 domain name.
     */
    public function testMainnetEvm(): void
    {
        $this->setGeneralSettings([
            General::ACCOUNT => 'base',
            General::NETWORK => Mainnet::class,
            sprintf(General::WALLET, 'base') => self::EVM_WALLET,
        ]);

        $accepts = $this->build('https://example.org/post/')->getAccepts()[0];
        $this->assertSame(Mainnet::BASE->value, $accepts->getNetwork());
        $this->assertSame(Mainnet::ASSET_BASE->value, $accepts->getAsset());
        $this->assertSame(['name' => 'USD Coin', 'version' => '2'], $accepts->getExtra());
    }

    /**
     * Test mainnet Solana requirements.
     */
    public function testMainnetSolana(): void
    {
        $wallet = 'EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v';
        $this->setGeneralSettings([
            General::ACCOUNT => 'solana',
            General::NETWORK => Mainnet::class,
            General::PRICE => '0.25',
            sprintf(General::WALLET, 'solana') => $wallet,
        ]);

        $accepts = $this->build('https://example.org/post/')->getAccepts()[0];
        $this->assertSame(Mainnet::SOLANA->value, $accepts->getNetwork());
        $this->assertSame(Mainnet::ASSET_SOLANA->value, $accepts->getAsset());
        $this->assertSame('250000', $accepts->getAmount());
        $this->assertSame($wallet, $accepts->getPayTo());
        $this->assertNull($accepts->getExtra());
    }

    /**
     * Build the model through a paywall.
     * @param string $url
     * @return PaymentRequired
     */
    private function build(string $url): PaymentRequired
    {
        return $this->invoke(new ForBots(), 'getPaymentRequired', $url);
    }
}
