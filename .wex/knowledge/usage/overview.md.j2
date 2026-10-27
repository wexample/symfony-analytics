`wexample/symfony-analytics` counts the audience of a site on the server: visitors, visits, pages viewed, where visits come from, from which country, on which device. Nothing runs in the browser and nothing is left on it.

## What is counted

`PageViewRecordSubscriber` records a page once its response has left (`kernel.terminate`): a GET answered with HTML, neither XHR nor prefetch, outside `/_`, not from an account of `excluded_roles` (the administrators by default).

A robot's page is counted apart, in `analytics_robot_day` (`AnalyticsRobotDay`): a sum a day per robot, kept like the months, read as the `robot` dimension of the breakdown — its pages alone, a robot having neither visitor nor visit. A robot is one its user agent names (`UserAgentHelper::getRobot()`: GPTBot, ClaudeBot, GoogleOther, Googlebot…, `other` for the rest, a missing user agent included), or a visitor reading more than `robot_hourly_views` pages within an hour (60 by default, 0 turns it off), counted as `cadence`: a crawler dressed as a browser, which no user agent gives away. The pages it read before crossing the line stay in the audience.

- **Visitor**: a hash of the address and the user agent under the kernel secret and the day — no cookie, nothing reversible kept, someone else the next day. A period of several days counts a returning person once per day.
- **Visit**: the run of pages of a visitor without a pause longer than `visit_timeout` (30 minutes); each page carries the origin of the first.
- **Origin** (`TrafficOriginHelper`): `direct`, `search` (engine, and keyword where the engine still passes it, or `utm_term`), `social`, `ai` (ChatGPT, Perplexity, Claude, Gemini… also read from `utm_source=chatgpt.com`), `referral`, `campaign` (`utm_*`).
- **Country**: through symfony-geo's `IpLocatorInterface` — symfony-remote-db-ip — when installed; the address itself is never stored.

## Keeping

Pages viewed are kept `retention_months` (13). Every finished month is summed up per dimension (`analytics_monthly`, its first 1000 lines per dimension) as soon as it is over; the pages older than the retention are then deleted. A period reaching back past it is read month by month from the sums. `analytics:maintain` does both; the bundle runs it by itself once a day after a response (`auto_maintenance`).

## Reading

For the holders of `access_role` (ROLE_ADMIN):

- `GET /_api/analytics-breakdown/list?dimension=&from=&to=` — `AnalyticsBreakdown` entities, computed, never stored: one line per page, source, referrer, search engine, keyword, campaign, country or device, with its visitors, visits and views; `dimension=total` gives the period as one line. Sorted and paged like any list.
- Charts (symfony-charts): `analytics-traffic` (visitors, visits, views per day, per month over 92 days) and `analytics-countries` (visits per country, for a map).

symfony-analytics-ds draws them.

```yaml
wexample_symfony_analytics:
  access_role: ROLE_ADMIN
  excluded_roles: [ROLE_ADMIN]
  visit_timeout: 30
  retention_months: 13
```
