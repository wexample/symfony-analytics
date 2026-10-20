<?php

namespace Wexample\SymfonyAnalytics\Helper;

use Wexample\SymfonyAnalytics\Class\TrafficOrigin;
use Wexample\SymfonyAnalytics\Enum\TrafficSource;

/**
 * Reads where a visit comes from out of its referrer and its `utm_*`
 * parameters. A search engine's keyword survives only where the engine still
 * passes it — rarely: Google and DuckDuckGo keep it to themselves —, or in
 * `utm_term`.
 */
final class TrafficOriginHelper
{
    /** Host suffix => [name, the query parameter holding the keyword]. */
    private const array SEARCH_ENGINES = [
        'google.' => ['Google', 'q'],
        'bing.com' => ['Bing', 'q'],
        'duckduckgo.com' => ['DuckDuckGo', 'q'],
        'search.yahoo.' => ['Yahoo', 'p'],
        'yandex.' => ['Yandex', 'text'],
        'baidu.com' => ['Baidu', 'wd'],
        'ecosia.org' => ['Ecosia', 'q'],
        'qwant.com' => ['Qwant', 'q'],
        'search.brave.com' => ['Brave', 'q'],
        'startpage.com' => ['Startpage', 'query'],
        'lilo.org' => ['Lilo', 'q'],
        'naver.com' => ['Naver', 'query'],
    ];

    /** Host suffix => name. Checked before the engines: gemini.google.com is no search. */
    private const array AI_ASSISTANTS = [
        'chatgpt.com' => 'ChatGPT',
        'chat.openai.com' => 'ChatGPT',
        'perplexity.ai' => 'Perplexity',
        'claude.ai' => 'Claude',
        'gemini.google.com' => 'Gemini',
        'copilot.microsoft.com' => 'Copilot',
        'chat.mistral.ai' => 'Le Chat',
        'chat.deepseek.com' => 'DeepSeek',
        'you.com' => 'You.com',
        'phind.com' => 'Phind',
        'poe.com' => 'Poe',
    ];

    private const array SOCIAL_NETWORKS = [
        'facebook.com', 'fb.com', 'instagram.com', 'linkedin.com', 'lnkd.in', 't.co', 'twitter.com', 'x.com',
        'reddit.com', 'youtube.com', 'youtu.be', 'bsky.app', 'threads.net', 'pinterest.com', 'tiktok.com',
        'mastodon.social', 'news.ycombinator.com', 'whatsapp.com', 'telegram.org', 'discord.com',
    ];

    /**
     * @param array<string, mixed> $query the query of the page landed on
     * @param string $host the site's own host: a referrer there is no origin
     */
    public static function read(?string $referrer, array $query, string $host): TrafficOrigin
    {
        $referrerHost = self::host($referrer);

        if ($referrerHost === self::host('//' . $host)) {
            $referrerHost = null;
        }

        $utmSource = self::text($query['utm_source'] ?? null);
        $campaign = self::text($query['utm_campaign'] ?? null) ?? $utmSource;
        $term = self::text($query['utm_term'] ?? null);

        // ChatGPT tags its links `utm_source=chatgpt.com` and sends no referrer.
        foreach ([$referrerHost, $utmSource] as $candidate) {
            if (null !== $candidate && null !== $name = self::match($candidate, self::AI_ASSISTANTS)) {
                return new TrafficOrigin(TrafficSource::AI, $referrerHost ?? strtolower($candidate), $name, $term, $campaign);
            }
        }

        if (null !== $referrerHost) {
            foreach (self::SEARCH_ENGINES as $suffix => [$name, $parameter]) {
                if (self::matches($referrerHost, $suffix)) {
                    parse_str((string) parse_url((string) $referrer, PHP_URL_QUERY), $referrerQuery);

                    return new TrafficOrigin(TrafficSource::SEARCH, $referrerHost, $name, self::text($referrerQuery[$parameter] ?? null) ?? $term, $campaign);
                }
            }

            foreach (self::SOCIAL_NETWORKS as $suffix) {
                if (self::matches($referrerHost, $suffix)) {
                    return new TrafficOrigin(TrafficSource::SOCIAL, $referrerHost, null, $term, $campaign);
                }
            }

            return new TrafficOrigin(TrafficSource::REFERRAL, $referrerHost, null, $term, $campaign);
        }

        if (null !== $utmSource || null !== $campaign) {
            return new TrafficOrigin(TrafficSource::CAMPAIGN, null, null, $term, $campaign);
        }

        return new TrafficOrigin(TrafficSource::DIRECT);
    }

    private static function host(?string $url): ?string
    {
        $host = is_string($url) ? parse_url($url, PHP_URL_HOST) : null;

        return is_string($host) && '' !== $host ? preg_replace('/^www\./', '', strtolower($host)) : null;
    }

    private static function matches(string $host, string $suffix): bool
    {
        // `google.` stands for every country of it: google.fr, google.co.uk.
        if (str_ends_with($suffix, '.')) {
            return str_starts_with($host, $suffix) || str_contains($host, '.' . $suffix);
        }

        return $host === $suffix || str_ends_with($host, '.' . $suffix);
    }

    /**
     * @param array<string, string> $names
     */
    private static function match(string $host, array $names): ?string
    {
        $host = preg_replace('/^www\./', '', strtolower($host));

        foreach ($names as $suffix => $name) {
            if (self::matches($host, $suffix)) {
                return $name;
            }
        }

        return null;
    }

    private static function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim(mb_substr($value, 0, 255));

        return '' === $value ? null : $value;
    }
}
