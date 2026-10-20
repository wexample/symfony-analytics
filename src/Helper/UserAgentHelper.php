<?php

namespace Wexample\SymfonyAnalytics\Helper;

use Wexample\SymfonyAnalytics\Enum\DeviceType;

/**
 * What a user agent says of who is asking: a program — a crawler, an
 * assistant's fetcher, a monitor —, or a person on a kind of device.
 */
final class UserAgentHelper
{
    private const string ROBOT = '/bot|crawl|spider|slurp|scrap|fetch|preview|headless|lighthouse|pingdom|uptime|monitor|curl|wget|python|java\/|go-http|okhttp|axios|node-fetch|httpclient|libwww|facebookexternalhit|embedly|whatsapp|telegram|discord|chatgpt|openai|claude|anthropic|perplexity|bytespider|ccbot|google-extended|meta-externalagent|amazonbot|applebot|yandex|baidu|semrush|ahrefs|mj12|dotbot|petalbot/i';

    public static function isRobot(?string $userAgent): bool
    {
        return null === $userAgent || '' === trim($userAgent) || 1 === preg_match(self::ROBOT, $userAgent);
    }

    public static function getDevice(?string $userAgent): DeviceType
    {
        $userAgent = (string) $userAgent;

        if (1 === preg_match('/ipad|tablet|kindle|silk|playbook|android(?!.*mobile)/i', $userAgent)) {
            return DeviceType::TABLET;
        }

        return 1 === preg_match('/mobi|iphone|ipod|android|blackberry|opera mini|iemobile/i', $userAgent)
            ? DeviceType::MOBILE
            : DeviceType::DESKTOP;
    }
}
