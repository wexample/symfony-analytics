<?php

namespace Wexample\SymfonyAnalytics\Helper;

use DeviceDetector\Parser\Bot;
use Wexample\SymfonyAnalytics\Enum\DeviceType;

/**
 * What a user agent says of who is asking: a program — a crawler, an
 * assistant's fetcher, a monitor —, or a person on a kind of device.
 */
final class UserAgentHelper
{
    /** A robot no name below fits, or a request saying nothing of itself. */
    public const string ROBOT_OTHER = 'other';

    /** A visitor reading more pages within an hour than a person does. */
    public const string ROBOT_CADENCE = 'cadence';

    /** A visitor reading more pages within a minute than a person does. */
    public const string ROBOT_BURST = 'burst';

    /** A browser's user agent without the headers every browser sends. */
    public const string ROBOT_HEADERS = 'headers';

    /** From this version on, a Chromium browser sends `Sec-Fetch-Mode` on every page it opens. */
    private const int CHROMIUM_SEC_FETCH_VERSION = 80;

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
     * The robot asking, by its name — the ones above first, then the ones
     * Matomo's device-detector knows, `other` for one with no name at all or
     * no user agent —, null for a person.
     */
    public static function getRobot(?string $userAgent): ?string
    {
        if (null === $userAgent || '' === trim($userAgent)) {
            return self::ROBOT_OTHER;
        }

        $generic = 1 === preg_match(self::ROBOT, $userAgent);

        if ($generic) {
            foreach (self::NAMED as $name => $token) {
                if (false !== stripos($userAgent, $token)) {
                    return $name;
                }
            }
        }

        $parser = new Bot($userAgent);
        $known = $parser->parse()['name'] ?? null;

        if (is_string($known) && '' !== $known) {
            return $known;
        }

        return $generic ? self::ROBOT_OTHER : null;
    }

    /**
     * `headers` for a page asked by a browser's user agent without what
     * every browser sends when it opens one — the languages its reader
     * accepts, and for a recent Chromium its `Sec-Fetch-Mode` —, null
     * otherwise: a script dressed as a browser, which the user agent alone
     * does not give away.
     */
    public static function getHeadersRobot(?string $userAgent, ?string $acceptLanguage, ?string $secFetchMode): ?string
    {
        if (null === $acceptLanguage || '' === trim($acceptLanguage)) {
            return self::ROBOT_HEADERS;
        }

        if (null === $secFetchMode
            && 1 === preg_match('/Chrome\/(\d+)/', (string) $userAgent, $matches)
            && (int) $matches[1] >= self::CHROMIUM_SEC_FETCH_VERSION) {
            return self::ROBOT_HEADERS;
        }

        return null;
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
