<?php

namespace Okay\Modules\Sviat\BackorderLeadTime\Helpers;

use DateTimeImmutable;

final readonly class Backorder
{
    public function __construct(public LeadTerm $term, public DateTimeImmutable $shipDate)
    {
    }

    /** ISO 8601 зі зсувом пояса дати відправки — формат availability_date для Google. */
    public function availabilityDate(): string
    {
        return $this->shipDate->format('Y-m-d\TH:iO');
    }
}
