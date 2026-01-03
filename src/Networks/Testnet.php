<?php

declare(strict_types=1);

namespace WpX402\WpX402\Networks;

use WebdevCave\EnumIndexAccessor\BackedEnumIndexAccessor;

/**
 * Sandbox environments for testing smart contracts, dapps, and other blockchain functionality without risking real
 * funds. You can obtain test currencies from faucets.
 * EVM Networks: eip155:{chainId} where chainId is the EVM chain ID
 * Solana Networks: solana:{genesisHash} where genesisHash is a truncated genesis hash
 * @ref https://docs.cdp.coinbase.com/get-started/supported-networks
 * @ref https://docs.cdp.coinbase.com/x402/quickstart-for-sellers#network-identifiers-caip-2
 */
enum Testnet: string implements Network
{
    use BackedEnumIndexAccessor;

    // USDC Contract Addresses: https://developers.circle.com/stablecoins/usdc-contract-addresses.
    case ASSET_BASE = '0x036CbD53842c5426634e7929541eC2318f3dCF7e'; // phpcs:ignore
    case ASSET_SOLANA = '4zMMC9srt5Ri5X14GAgXhaHii3GnPAEERYPJgZJDncDU'; // phpcs:ignore

    case BASE = 'eip155:84532';
    case SOLANA = 'solana:EtWTRABZaYq6iMfeYKouRu166VU2xqa1';

    case FACILITATOR = 'https://x402.org/facilitator';
}
