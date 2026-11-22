<?php

declare(strict_types=1);

namespace WpX402\WpX402\Tests\Settings;

use PHPUnit\Framework\Attributes\CoversClass;
use WpX402\WpX402\Networks\Mainnet;
use WpX402\WpX402\Networks\Testnet;
use WpX402\WpX402\Settings\General;
use WpX402\WpX402\Settings\Misc;
use WpX402\WpX402\Settings\Setting;
use WpX402\WpX402\Tests\TestCase;
use function sprintf;

/**
 * Class SettingTest
 * @package WpX402\WpX402\Tests\Settings
 */
#[CoversClass(Setting::class)]
class SettingTest extends TestCase
{

    /**
     * Test the defaults when nothing is saved.
     */
    public function testDefaults(): void
    {
        $this->assertSame(['base', 'solana'], array_keys(Setting::getAccounts()));
        $this->assertSame('base', Setting::getAccount());
        $this->assertSame('', Setting::getWallet());
        $this->assertSame(Testnet::class, Setting::getNetwork());
        $this->assertFalse(Setting::isMainnet());
        $this->assertFalse(Setting::allowTelemetry());
        $this->assertSame('$0.01', Setting::getPrice());
    }

    /**
     * Test saved settings are returned.
     */
    public function testSavedSettings(): void
    {
        $wallet = 'EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v';
        $this->setGeneralSettings([
            General::ACCOUNT => 'solana',
            sprintf(General::WALLET, 'solana') => $wallet,
            General::NETWORK => Mainnet::class,
            General::PRICE => '1.5',
        ]);
        $this->setMiscSettings([Misc::TELEMETRY => 'on']);

        $this->assertSame('solana', Setting::getAccount());
        $this->assertSame($wallet, Setting::getWallet());
        $this->assertSame(Mainnet::class, Setting::getNetwork());
        $this->assertTrue(Setting::isMainnet());
        $this->assertTrue(Setting::allowTelemetry());
        $this->assertSame('$1.50', Setting::getPrice());
    }
}
