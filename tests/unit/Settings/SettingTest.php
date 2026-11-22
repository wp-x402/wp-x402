<?php

declare(strict_types=1);

namespace WpX402\WpX402\Tests\Settings;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $this->assertSame('10000', Setting::getAmount());
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
        $this->assertSame('1500000', Setting::getAmount());
        $this->assertSame('150', Setting::getAmount(2));
    }

    /**
     * Price, expected atomic amount (6 decimals).
     * @return array<string, array{string, string}>
     */
    public static function amountProvider(): array
    {
        return [
            'one cent' => ['0.01', '10000'],
            'tenth of a cent' => ['0.001', '1000'],
            'one dollar' => ['1', '1000000'],
            'float rounding' => ['0.07', '70000'],
            'negative is zero' => ['-1', '0'],
            'not a number is zero' => ['abc', '0'],
        ];
    }

    /**
     * Test getAmount() converts the price to atomic units.
     */
    #[DataProvider('amountProvider')]
    public function testGetAmount(string $price, string $expected): void
    {
        $this->setGeneralSettings([General::PRICE => $price]);
        $this->assertSame($expected, Setting::getAmount());
    }
}
