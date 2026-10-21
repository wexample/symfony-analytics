<?php

namespace Wexample\SymfonyAnalytics\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wexample\SymfonyAnalytics\Enum\DeviceType;
use Wexample\SymfonyAnalytics\Helper\UserAgentHelper;

class UserAgentHelperTest extends TestCase
{
    private const string FIREFOX = 'Mozilla/5.0 (X11; Linux x86_64; rv:131.0) Gecko/20100101 Firefox/131.0';

    private const string IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1';

    private const string IPAD = 'Mozilla/5.0 (iPad; CPU OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Safari/604.1';

    public function testPeopleAreNoRobots(): void
    {
        $this->assertFalse(UserAgentHelper::isRobot(self::FIREFOX));
        $this->assertFalse(UserAgentHelper::isRobot(self::IPHONE));
    }

    public function testCrawlersAssistantsAndToolsAreRobots(): void
    {
        foreach ([
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.2; +https://openai.com/gptbot)',
            'Mozilla/5.0 (compatible; ClaudeBot/1.0; +claudebot@anthropic.com)',
            'Mozilla/5.0 (compatible; PerplexityBot/1.0)',
            'curl/8.5.0',
            '',
            null,
        ] as $userAgent) {
            $this->assertTrue(UserAgentHelper::isRobot($userAgent), (string) $userAgent);
        }
    }

    public function testARobotIsNamed(): void
    {
        $this->assertSame('GPTBot', UserAgentHelper::getRobot('Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.4; +https://openai.com/gptbot)'));
        // Dressed as a phone, and without "bot" in its name.
        $this->assertSame('GoogleOther', UserAgentHelper::getRobot('Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.8010.52 Mobile Safari/537.36 (compatible; GoogleOther)'));
        $this->assertSame(UserAgentHelper::ROBOT_OTHER, UserAgentHelper::getRobot('curl/8.5.0'));
        $this->assertNull(UserAgentHelper::getRobot(self::IPHONE));
    }

    public function testTheDevice(): void
    {
        $this->assertSame(DeviceType::DESKTOP, UserAgentHelper::getDevice(self::FIREFOX));
        $this->assertSame(DeviceType::MOBILE, UserAgentHelper::getDevice(self::IPHONE));
        $this->assertSame(DeviceType::TABLET, UserAgentHelper::getDevice(self::IPAD));
    }
}
