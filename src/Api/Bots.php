<?php

declare(strict_types=1);

namespace WpX402\WpX402\Api;

use JsonException;
use TheFrosty\WpUtilities\Api\TransientsTrait;
use TheFrosty\WpUtilities\Utils\AbstractSingleton;
use function is_wp_error;
use function wp_remote_get;
use function wp_remote_retrieve_body;

/**
 * Class Bots
 * @package WpX402\WpX402\Api
 */
class Bots extends AbstractSingleton
{

    use TransientsTrait;

    protected const string ROBOTS = 'ai-robots-txt/ai.robots.txt/refs/heads/main/robots.json';
    protected const string PREFIX = '_x402_agents_';

    /**
     * Return an array of Ai Agents (bots).
     * @return array<string, array>|null
     */
    public static function getAgents(): ?array
    {
        $instance = self::getInstance();
        $transient = $instance->getTransientKey(self::getRobotsUrl(), self::PREFIX);
        $robots = $instance->getTransient($transient);
        if (!$robots) {
            $response = wp_remote_get(self::getRobotsUrl());
            if (!is_wp_error($response)) {
                try {
                    $robots = json_decode(wp_remote_retrieve_body($response), true, flags: JSON_THROW_ON_ERROR);
                    $instance->setTransient($transient, $robots, WEEK_IN_SECONDS);
                } catch (JsonException) {
                    return null;
                }
            }
        }

        return $robots;
    }

    /**
     * Return the raw GitHub User Content URL.
     * @return string
     */
    private static function getRobotsUrl(): string
    {
        return sprintf('https://raw.githubusercontent.com/%s', self::ROBOTS);
    }
}
