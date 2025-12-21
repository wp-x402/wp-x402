<?php

declare(strict_types=1);

namespace TheFrosty\WpX402\Networks;

/**
 * Live, fully operational blockchain with real transactions and actual assets.
 * EVM Networks: eip155:{chainId} where chainId is the EVM chain ID
 * Solana Networks: solana:{genesisHash} where genesisHash is a truncated genesis hash
 * @ref https://docs.cdp.coinbase.com/get-started/supported-networks
 * @ref https://docs.cdp.coinbase.com/x402/quickstart-for-sellers#network-identifiers-caip-2
 */
enum Mainnet: string
{
    // USDC Contract Addresses: https://developers.circle.com/stablecoins/usdc-contract-addresses
    case ASSET_BASE = '0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913';
    case ASSET_ETHEREUM = '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48';

    case BASE = 'eip155:8453';
    case ETHEREUM = 'eip155:1';
    case SOLANA = 'solana:5eykt4UsFv8P8NJdTREpY1vzqKqZKvdp';
}
