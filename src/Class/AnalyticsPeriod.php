<?php

namespace Wexample\SymfonyAnalytics\Class;

use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * The days the audience is read over: `from` included, `to` excluded, as the
 * period-nav of the design system announces them.
 */
final readonly class AnalyticsPeriod
{
    /** Beyond, a day by day series would not be read: it goes month by month. */
    public const int DAILY_MAX_DAYS = 92;

    private const int DEFAULT_DAYS = 30;

    private const int MAX_DAYS = 3660;

    public function __construct(
        public DateTimeImmutable $from,
        public DateTimeImmutable $to,
    ) {
    }

    /**
     * The last thirty days up to today when nothing is asked.
     *
     * @throws BadRequestHttpException for a bound that is no date, or a period upside down or too long
     */
    public static function fromQuery(?string $from, ?string $to): self
    {
        if (! $from && ! $to) {
            $end = new DateTimeImmutable('tomorrow');

            return new self($end->modify('-' . self::DEFAULT_DAYS . ' days'), $end);
        }

        $period = new self(self::date($from), self::date($to));
        $days = (int) $period->from->diff($period->to)->format('%r%a');

        if ($days < 1 || $days > self::MAX_DAYS) {
            throw new BadRequestHttpException('The period runs from `from` to a later `to`, ten years at most.');
        }

        return $period;
    }

    public function isDaily(): bool
    {
        return (int) $this->from->diff($this->to)->format('%a') <= self::DAILY_MAX_DAYS;
    }

    public function getKey(): string
    {
        return $this->from->format('Y-m-d') . '/' . $this->to->format('Y-m-d');
    }

    private static function date(?string $value): DateTimeImmutable
    {
        $date = is_string($value) ? DateTimeImmutable::createFromFormat('!Y-m-d', $value) : false;

        if (false === $date || $date->format('Y-m-d') !== $value) {
            throw new BadRequestHttpException('`from` and `to` are dates written YYYY-MM-DD.');
        }

        return $date;
    }
}
