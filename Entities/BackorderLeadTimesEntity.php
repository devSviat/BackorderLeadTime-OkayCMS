<?php

namespace Okay\Modules\Sviat\BackorderLeadTime\Entities;

use Okay\Core\Entity\Entity;

/** Термін поставки під замовлення для бренду, дні «від–до». */
class BackorderLeadTimesEntity extends Entity
{
    protected static $fields = [
        'id',
        'brand_id',
        'days_from',
        'days_to',
    ];

    protected static $defaultOrderFields = ['id ASC'];
    protected static $table = 'sviat__backorder_lead_times';
    protected static $tableAlias = 'blt';
}
