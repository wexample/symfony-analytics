<?php

namespace Wexample\SymfonyAnalytics\Helper;

use Wexample\SymfonyAnalytics\Enum\DeviceType;

/**
 * What a user agent says of who is asking: a program — a crawler, an
 * assistant's fetcher, a monitor —, or a person on a kind of device.
 */
final class UserAgentHelper
{
    /** A robot no name below fits, or a request saying nothing of itself. */
    public const string ROBOT_OTHER = 'other';

    /** A visitor reading faster than a person: no user agent gave it away. */
    public const string ROBOT_CADENCE = 'cadence';

    private const string ROBOT = '/bot|crawl|spider|slurp|scrap|fetch|preview|headless|lighthouse|pingdom|uptime|monitor|curl|wget|python|java\/|go-http|okhttp|axios|node-fetch|httpclient|libwww|facebookexternalhit|embedly|whatsapp|telegram|discord|chatgpt|openai|claude|anthropic|perplexity|bytespider|ccbot|google-|googleother|adsbot|mediapartners|feedfetcher|meta-externalagent|amazonbot|applebot|yandex|baidu|semrush|ahrefs|mj12|dotbot|petalbot|dataforseo|ia_archiver/i';

    /**
     * The robots told apart, by the name they give themselves; the AI ones
     * first, their fetchers acting for a person included. Matched in order.
     */
    private const array NAMED = [
        'GPTBot' => 'gptbot',
        'OAI-SearchBot' => 'oai-searchbot',
        'ChatGPT-User' => 'chatgpt-user',
        'ClaudeBot' => 'claudebot',
        'Claude-User' => 'claude-user',
        'Claude-SearchBot' => 'claude-searchbot',
        'PerplexityBot' => 'perplexitybot',
        'Perplexity-User' => 'perplexity-user',
        'meta-externalagent' => 'meta-externalagent',
        'Bytespider' => 'bytespider',
        'CCBot' => 'ccbot',
        'Amazonbot' => 'amazonbot',
        'Applebot' => 'applebot',
        'GoogleOther' => 'googleother',
        'Google-InspectionTool' => 'google-inspectiontool',
        'Googlebot' => 'googlebot',
        'bingbot' => 'bingbot',
        'DuckDuckBot' => 'duckduckbot',
        'YandexBot' => 'yandex',
        'Baiduspider' => 'baidu',
        'AhrefsBot' => 'ahrefsbot',
        'SemrushBot' => 'semrushbot',
        'DataForSeoBot' => 'dataforseobot',
        'MJ12bot' => 'mj12bot',
        'DotBot' => 'dotbot',
        'PetalBot' => 'petalbot',
    ];

    public static function isRobot(?string $userAgent): bool
    {
        return null !== self::getRobot($userAgent);
    }

    /**
     * The robot asking, by its name — `other` for one with none of the
     * names above, or with no user agent at all —, null for a person.
     */
    public static function getRobot(?string $userAgent): ?string
    {
        if (null === $userAgent || '' === trim($userAgent)) {
            return self::ROBOT_OTHER;
        }

        if (1 !== preg_match(self::ROBOT, $userAgent)) {
            return null;
        }

        foreach (self::NAMED as $name => $token) {
            if (false !== stripos($userAgent, $token)) {
                return $name;
            }
        }

        return self::ROBOT_OTHER;
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
