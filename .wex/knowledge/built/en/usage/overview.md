`wexample/symfony-analytics` counts the audience of a site on the server: visitors, visits, pages viewed, where visits come from, from which country, on which device. Nothing runs in the browser and nothing is left on it. The robots are counted too, apart from the people: what reads the site is worth knowing, not only who.

## What is counted

`PageViewRecordSubscriber` records a page once its response has left (`kernel.terminate`): a GET answered with HTML, neither XHR nor prefetch, outside `/_`, not from an account of `excluded_roles` (the administrators by default).

Every page falls into one of three audiences (`AnalyticsAudience`):

- **human**: a person, kept in `analytics_page_view`.
- **suspect**: a browser's user agent reading as no person does, kept in `analytics_page_view` as well, with the reason in its `robot` column:
  - `headers`: no `Accept-Language`, or a recent Chromium without `Sec-Fetch-Mode` — a script dressed as a browser;
  - `burst`: more than `robot_minute_views` pages within a minute (15 by default, 0 turns it off);
  - `cadence`: more than `robot_hourly_views` pages within an hour (60, 0 turns it off).

  A visitor caught is caught for the day, and the pages it read before crossing the line are moved to it.
- **declared**: a robot its user agent names (`UserAgentHelper::getRobot()`: GPTBot, ClaudeBot, GoogleOther… then the names Matomo's device-detector knows, `other` for the rest, a missing user agent included). By far the most pages, so not kept one by one: a sum a day per robot, route and country in `analytics_robot_day`, and a sum per five minutes in `analytics_robot_slot`, kept two days, for the chart of the day.

The words it counts with:

- **Visitor**: a hash of the address and the user agent under the kernel secret and the day — no cookie, nothing reversible kept, someone else the next day. A period of several days counts a returning person once per day.
- **Visit**: the run of pages of a visitor without a pause longer than `visit_timeout` (30 minutes); each page carries the origin of the first.
- **Origin** (`TrafficOriginHelper`): `direct`, `search` (engine, and keyword where the engine still passes it, or `utm_term`), `social`, `ai` (ChatGPT, Perplexity, Claude, Gemini… also read from `utm_source=chatgpt.com`), `referral`, `campaign` (`utm_*`).
- **Country**: through symfony-geo's `IpLocatorInterface` — symfony-remote-db-ip — when installed; the address itself is never stored.

## Keeping

Every finished month is summed up per audience and dimension (`analytics_monthly`, its first 1000 lines per dimension) as soon as it is over. Each audience is then kept day by day for its own retention, and read month by month from the sums before that:

- the people's pages `retention_months` (13);
- the suspected robots' pages `suspect_retention_months` (1);
- the declared robots' days `robot_retention_months` (3; 0 does not record them at all).

`analytics:maintain` does both; the bundle runs it by itself once a day after a response (`auto_maintenance`). `AnalyticsStorageService::getSizes()` tells what the tables weigh, on PostgreSQL.

## Reading

For the holders of `access_role` (ROLE_ADMIN):

- `GET /_api/analytics-breakdown/list?dimension=&audience=&from=&to=` — `AnalyticsBreakdown` entities, computed, never stored: one line per page, route, source, referrer, search engine, keyword, campaign, country, device or robot, with its visitors, visits and views; `dimension=total` gives the period as one line. `audience` is `humans` (by default), `robots` or `all` (`AnalyticsAudienceView`). A declared robot has neither visitor nor visit, and only its robot, route and country: the other dimensions see the people and the suspected robots. Sorted and paged like any list.
- Charts (symfony-charts): `analytics-traffic` (the people's visitors, visits, views per day, per month over 92 days), `analytics-composition` (every page read per day, a series per audience, the raw traffic) and `analytics-countries` (per country, for a map, of the audience its `scope` says: the people's visits by default, the pages read with `robots` or `all`).

What is going on now is told by `AnalyticsTodayService`: the pages per audience a few minutes at a time over the last hours, the visitors active within 30 minutes, the people's pages within the last sixty minutes against the same ones yesterday, the busiest robot of those minutes, the people's latest pages. Its chart is `analytics-today`, `scope` `24h` (15-minute bars, by default) or `2h` (5-minute bars), whatever the period. `summarize()` gives the rest whole — what the page is drawn with and what `/_api/analytics-today/summary` answers, to the holders of `access_role`, for a page reading it again while it is open.

symfony-analytics-ds draws them.

```yaml
wexample_symfony_analytics:
  access_role: ROLE_ADMIN
  excluded_roles: [ROLE_ADMIN]
  visit_timeout: 30
  robot_minute_views: 15
  robot_hourly_views: 60
  retention_months: 13
  suspect_retention_months: 1
  robot_retention_months: 3
```
