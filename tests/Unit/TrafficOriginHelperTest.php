<?php

namespace Wexample\SymfonyAnalytics\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wexample\SymfonyAnalytics\Enum\TrafficSource;
use Wexample\SymfonyAnalytics\Helper\TrafficOriginHelper;

class TrafficOriginHelperTest extends TestCase
{
    private const string HOST = 'weeger.io';

    public function testNothingIsDirect(): void
    {
        $this->assertSame(TrafficSource::DIRECT, TrafficOriginHelper::read(null, [], self::HOST)->source);
    }

    public function testTheSiteItselfIsNoOrigin(): void
    {
        $this->assertSame(TrafficSource::DIRECT, TrafficOriginHelper::read('https://www.weeger.io/fr/', [], self::HOST)->source);
    }

    public function testASearchEngineGivesItsNameAndKeywordWhenItPassesIt(): void
    {
        $origin = TrafficOriginHelper::read('https://www.bing.com/search?q=design+system', [], self::HOST);

        $this->assertSame(TrafficSource::SEARCH, $origin->source);
        $this->assertSame('Bing', $origin->searchEngine);
        $this->assertSame('design system', $origin->keyword);

        $google = TrafficOriginHelper::read('https://www.google.co.uk/', [], self::HOST);
        $this->assertSame('Google', $google->searchEngine);
        $this->assertNull($google->keyword);
    }

    public function testAnAssistantIsAiBeforeBeingAnEngine(): void
    {
        $this->assertSame(TrafficSource::AI, TrafficOriginHelper::read('https://gemini.google.com/app', [], self::HOST)->source);
        $this->assertSame('Perplexity', TrafficOriginHelper::read('https://www.perplexity.ai/search/x', [], self::HOST)->searchEngine);
    }

    public function testChatGptIsReadFromItsUtmSource(): void
    {
        $origin = TrafficOriginHelper::read(null, ['utm_source' => 'chatgpt.com'], self::HOST);

        $this->assertSame(TrafficSource::AI, $origin->source);
        $this->assertSame('ChatGPT', $origin->searchEngine);
    }

    public function testSocialReferralAndCampaign(): void
    {
        $this->assertSame(TrafficSource::SOCIAL, TrafficOriginHelper::read('https://www.linkedin.com/feed/', [], self::HOST)->source);
        $this->assertSame(TrafficSource::REFERRAL, TrafficOriginHelper::read('https://example.org/links', [], self::HOST)->source);

        $campaign = TrafficOriginHelper::read(null, ['utm_source' => 'newsletter', 'utm_campaign' => 'october', 'utm_term' => 'ds'], self::HOST);
        $this->assertSame(TrafficSource::CAMPAIGN, $campaign->source);
        $this->assertSame('october', $campaign->campaign);
        $this->assertSame('ds', $campaign->keyword);
    }
}
