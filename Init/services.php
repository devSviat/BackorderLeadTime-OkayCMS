<?php

namespace Okay\Modules\Sviat\BackorderLeadTime;

use Okay\Core\Database;
use Okay\Core\EntityFactory;
use Okay\Core\FrontTranslations;
use Okay\Core\QueryFactory;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;
use Okay\Core\Settings;
use Okay\Modules\Sviat\BackorderLeadTime\Extenders\FeedExtender;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\BackorderCounts;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\BackorderLeadTime;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\LeadTimeLabels;
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
    LeadTimeLabels::class => [
        'class' => LeadTimeLabels::class,
        'arguments' => [
            new SR(FrontTranslations::class),
        ],
    ],
    BackorderCounts::class => [
        'class' => BackorderCounts::class,
        'arguments' => [
            new SR(QueryFactory::class),
            new SR(Database::class),
        ],
    ],
    FeedExtender::class => [
        'class' => FeedExtender::class,
        'arguments' => [
            new SR(BackorderLeadTime::class),
        ],
    ],
];
