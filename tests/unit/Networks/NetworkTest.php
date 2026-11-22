<?php

declare(strict_types=1);

namespace WpX402\WpX402\Tests\Networks;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use WpX402\WpX402\Networks\Mainnet;
use WpX402\WpX402\Networks\Network;
use WpX402\WpX402\Networks\Testnet;
use WpX402\WpX402\Tests\TestCase;

/**
 * Class NetworkTest
 * @package WpX402\WpX402\Tests\Networks
 */
#[CoversClass(Mainnet::class)]
#[CoversClass(Testnet::class)]
class NetworkTest extends TestCase
{

    /**
     * Network enum, account, expected CAIP-2 network ID, expected USDC asset.
     * @return array<string, array{class-string<Network>, string, string, string}>
     */
    public static function networkProvider(): array
    {
        return [
            'mainnet base' => [
                Mainnet::class,
                'base',
                'eip155:8453',
                '0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913',
            ],
            'mainnet solana' => [
                Mainnet::class,
                'solana',
                'solana:5eykt4UsFv8P8NJdTREpY1vzqKqZKvdp',
                'EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v',
            ],
            'testnet base' => [
                Testnet::class,
                'base',
                'eip155:84532',
                '0x036CbD53842c5426634e7929541eC2318f3dCF7e',
            ],
            'testnet solana' => [
                Testnet::class,
                'solana',
                'solana:EtWTRABZaYq6iMfeYKouRu166VU2xqa1',
                '4zMMC9srt5Ri5X14GAgXhaHii3GnPAEERYPJgZJDncDU',
            ],
        ];
    }

    /**
     * Test getBase() and getAsset().
     * @param class-string<Network> $network
     */
    #[DataProvider('networkProvider')]
    public function testGetBaseAndAsset(string $network, string $account, string $base, string $asset): void
    {
        $this->assertSame($base, $network::getBase($account)->value);
        $this->assertSame($asset, $network::getAsset($account)->value);
        // Lookups are case-insensitive.
        $this->assertSame($base, $network::getBase(strtoupper($account))->value);
    }

    /**
     * Test the facilitator URLs.
     */
    public function testFacilitator(): void
    {
        $this->assertSame('https://api.cdp.coinbase.com/platform/v2/x402', Mainnet::FACILITATOR->value);
        $this->assertSame('https://x402.org/facilitator', Testnet::FACILITATOR->value);
    }
}
