<?php

declare(strict_types=1);

namespace WpX402\WpX402\Networks;

use WebdevCave\EnumIndexAccessor\BackedEnumIndexAccessor;
use function strtoupper;

/**
 * Live, fully operational blockchain with real transactions and actual assets.
 * EVM Networks: eip155:{chainId} where chainId is the EVM chain ID
 * Solana Networks: solana:{genesisHash} where genesisHash is a truncated genesis hash
 * @ref https://docs.cdp.coinbase.com/get-started/supported-networks
 * @ref https://docs.cdp.coinbase.com/x402/quickstart-for-sellers#network-identifiers-caip-2
 */
enum Mainnet: string implements Network
{
    use BackedEnumIndexAccessor;

    // USDC Contract Addresses: https://developers.circle.com/stablecoins/usdc-contract-addresses.
    case ASSET_BASE = '0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913'; // phpcs:ignore
    case ASSET_SOLANA = 'EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v'; // phpcs:ignore

    case BASE = 'eip155:8453';
    case SOLANA = 'solana:5eykt4UsFv8P8NJdTREpY1vzqKqZKvdp';

    case FACILITATOR = 'https://api.cdp.coinbase.com/platform/v2/x402';

    public static function getAsset(string $asset): self
    {
        return self::{'ASSET_' . strtoupper($asset)};
    }

    public static function getBase(string $asset): self
    {
        return self::{strtoupper($asset)};
    }
}
