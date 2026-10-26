<?php

namespace Okay\Modules\Sviat\BackorderLeadTime\Helpers;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class LeadTerm
{
    // Google відхиляє availability_date далі ніж за рік.
    public const int MAX_DAYS = 365;

    public function __construct(public int $from, public int $to)
    {
        if ($from < 1 || $to < $from || $to > self::MAX_DAYS) {
            throw new InvalidArgumentException("Невалідний термін {$from}–{$to}");
        }
    }

    public function shipDate(DateTimeImmutable $now): DateTimeImmutable
    {
        return $now->modify("+{$this->to} days")->setTime(12, 0);
    }

    /**
     * Термін у робочих днях пн–пт — одиниці schema.org handlingTime, коли тема оголошує businessDays пн–пт.
     * Рахуються дні після $now до now + from / now + to; менше одного не буває — у день замовлення товар не їде.
     *
     * @return array{from: int, to: int}
     */
    public function businessDays(DateTimeImmutable $now): array
    {
        return ['from' => self::weekdaysWithin($now, $this->from), 'to' => self::weekdaysWithin($now, $this->to)];
    }

    private static function weekdaysWithin(DateTimeImmutable $now, int $days): int
    {
        $count = 0;
        for ($i = 1; $i <= $days; $i++) {
            if ((int) $now->modify("+{$i} days")->format('N') <= 5) {
                $count++;
            }
        }

        return max(1, $count);
    }

    /** Обидва порожні — терміну немає (null); одне порожнє чи нечислове — помилка. */
    public static function parse(string $from, string $to): ?self
    {
        $from = trim($from);
        $to = trim($to);
        if ($from === '' && $to === '') {
            return null;
        }
        if (!ctype_digit($from) || !ctype_digit($to)) {
            throw new InvalidArgumentException("Термін «{$from}»–«{$to}» має бути цілими днями");
        }

        return new self((int) $from, (int) $to);
    }
}
