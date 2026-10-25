<?php

namespace Okay\Modules\Sviat\BackorderLeadTime;

use Okay\Core\EntityFactory;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;
use Okay\Core\Settings;
use Okay\Modules\Sviat\BackorderLeadTime\Extenders\FeedExtender;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\BackorderLeadTime;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\LeadTimeSettings;

return [
    LeadTimeSettings::class => [
        'class' => LeadTimeSettings::class,
        'arguments' => [
            new SR(Settings::class),
        ],
    ],
    BackorderLeadTime::class => [
        'class' => BackorderLeadTime::class,
        'arguments' => [
            new SR(EntityFactory::class),
            new SR(LeadTimeSettings::class),
            new SR(Settings::class),
        ],
    ],
    FeedExtender::class => [
        'class' => FeedExtender::class,
        'arguments' => [
            new SR(BackorderLeadTime::class),
        ],
    ],
];
