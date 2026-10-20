# symfony-analytics

Version: 2.0.1

`wexample/symfony-analytics` counts the audience of a site on the server: visitors, visits, pages viewed, where visits come from, from which country, on which device. Nothing runs in the browser and nothing is left on it.

## What is counted

`PageViewRecordSubscriber` records a page once its response has left (`kernel.terminate`): a GET answered with HTML, neither XHR nor prefetch, outside `/_`, not from a robot (crawlers, assistants' fetchers, tools — `UserAgentHelper`), not from an account of `excluded_roles` (the administrators by default).

- **Visitor**: a hash of the address and the user agent under the kernel secret and the day — no cookie, nothing reversible kept, someone else the next day. A period of several days counts a returning person once per day.
- **Visit**: the run of pages of a visitor without a pause longer than `visit_timeout` (30 minutes); each page carries the origin of the first.
- **Origin** (`TrafficOriginHelper`): `direct`, `search` (engine, and keyword where the engine still passes it, or `utm_term`), `social`, `ai` (ChatGPT, Perplexity, Claude, Gemini… also read from `utm_source=chatgpt.com`), `referral`, `campaign` (`utm_*`).
- **Country**: through symfony-geo's `IpLocatorInterface` — symfony-remote-db-ip — when installed; the address itself is never stored.

## Keeping

Pages viewed are kept `retention_months` (13). Every finished month is summed up per dimension (`analytics_monthly`, its first 1000 lines per dimension) as soon as it is over; the pages older than the retention are then deleted. A period reaching back past it is read month by month from the sums. `analytics:maintain` does both; the bundle runs it by itself once a day after a response (`auto_maintenance`).

## Reading

For the holders of `access_role` (ROLE_ADMIN):

- `GET /api/analytics-breakdown/list?dimension=&from=&to=` — `AnalyticsBreakdown` entities, computed, never stored: one line per page, source, referrer, search engine, keyword, campaign, country or device, with its visitors, visits and views; `dimension=total` gives the period as one line. Sorted and paged like any list.
- Charts (symfony-charts): `analytics-traffic` (visitors, visits, views per day, per month over 92 days) and `analytics-countries` (visits per country, for a map).

symfony-analytics-ds draws them.

```yaml
wexample_symfony_analytics:
  access_role: ROLE_ADMIN
  excluded_roles: [ROLE_ADMIN]
  visit_timeout: 30
  retention_months: 13
```

## Table of Contents

- [What is counted](#what-is-counted)
- [Keeping](#keeping)
- [Reading](#reading)
- [Integration in the Suite](#integration-in-the-suite)
- [Dependencies](#dependencies)
- [Versioning & Compatibility Policy](#versioning--compatibility-policy)
- [License](#license)
- [About us](#about-us)
- [Migration Notes](#migration-notes)

## Integration in the Suite

This package is part of the Wexample Suite — a collection of high-quality, modular tools designed to work seamlessly together across multiple languages and environments.

### Related Packages

The suite includes packages for configuration management, file handling, prompts, and more. Each package can be used independently or as part of the integrated suite.

## Dependencies

- php: >=8.5
- doctrine/orm: ^3.0
- doctrine/doctrine-bundle: ^2.13 || ^3.0
- symfony/security-bundle: ^7.4 || ^8.0
- symfony/uid: ^7.4 || ^8.0
- wexample/php-pseudocode: >=2.2.0
- wexample/symfony-api: >=14.0.0
- wexample/symfony-charts: >=3.0.0
- wexample/symfony-geo: >=8.0.0
- wexample/symfony-helpers: >=18.0.0
- wexample/symfony-pseudocode: >=4.0.0

## Versioning & Compatibility Policy

Wexample packages follow **Semantic Versioning** (SemVer):

- **MAJOR**: Breaking changes
- **MINOR**: New features, backward compatible
- **PATCH**: Bug fixes, backward compatible

We maintain backward compatibility within major versions and provide clear migration guides for breaking changes.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

Free to use in both personal and commercial projects.

## About us

[Wexample](https://wexample.com) stands as a cornerstone of the digital ecosystem — a collective of seasoned engineers, researchers, and creators driven by a relentless pursuit of technological excellence. More than a media platform, it has grown into a vibrant community where innovation meets craftsmanship, and where every line of code reflects a commitment to clarity, durability, and shared intelligence.

This packages suite embodies this spirit. Trusted by professionals and enthusiasts alike, it delivers a consistent, high-quality foundation for modern development — open, elegant, and battle-tested. Its reputation is built on years of collaboration, refinement, and rigorous attention to detail, making it a natural choice for those who demand both robustness and beauty in their tools.

Wexample cultivates a culture of mastery. Each package, each contribution carries the mark of a community that values precision, ethics, and innovation — a community proud to shape the future of digital craftsmanship.

Built and maintained by [Romain Weeger](https://weeger.io).

## Migration Notes

When upgrading between major versions, refer to the migration guides in the documentation.

Breaking changes are clearly documented with upgrade paths and examples.
