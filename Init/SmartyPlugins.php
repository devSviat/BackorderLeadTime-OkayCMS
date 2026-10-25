<?php

use Okay\Core\OkayContainer\Reference\ServiceReference as SR;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\BackorderLeadTime;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\LeadTimeLabels;
use Okay\Modules\Sviat\BackorderLeadTime\Plugins\BackorderLeadPlugin;

return [
    BackorderLeadPlugin::class => [
        'class' => BackorderLeadPlugin::class,
        'arguments' => [
            new SR(BackorderLeadTime::class),
            new SR(LeadTimeLabels::class),
        ],
    ],
];
