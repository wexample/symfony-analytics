<?php

namespace Wexample\SymfonyAnalytics;

use Wexample\SymfonyHelpers\Class\AbstractBundle;
use Wexample\SymfonyHelpers\Helper\BundleHelper;
use Wexample\SymfonyHelpers\Interface\LoaderBundleInterface;
use Wexample\SymfonyPseudocode\Interface\PseudocodeBundleInterface;

class WexampleSymfonyAnalyticsBundle extends AbstractBundle implements LoaderBundleInterface, PseudocodeBundleInterface
{
    public static function getLoaderFrontPaths(): array
    {
        return [
            BundleHelper::getBundleCssAlias(static::class) => __DIR__ . '/../assets/',
        ];
    }

    public static function getPseudocodeSourcePaths(): array
    {
        return [__DIR__ . '/'];
    }
}
