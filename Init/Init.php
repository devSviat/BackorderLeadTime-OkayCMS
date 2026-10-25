<?php

namespace Okay\Modules\Sviat\BackorderLeadTime\Init;

use Okay\Core\Modules\AbstractInit;
use Okay\Core\Modules\EntityField;
use Okay\Modules\OkayCMS\Feeds\Core\Presets\Adapters\GoogleMerchantAdapter;
use Okay\Modules\OkayCMS\GoogleMerchant\Helpers\GoogleMerchantHelper;
use Okay\Modules\Sviat\BackorderLeadTime\Entities\BackorderLeadTimesEntity;
use Okay\Modules\Sviat\BackorderLeadTime\Extenders\FeedExtender;

class Init extends AbstractInit
{
    public const PERMISSION = 'sviat__backorder_lead_time';

    public function install()
    {
        $this->setBackendMainController('BackorderLeadTimeAdmin');

        $this->migrateEntityTable(BackorderLeadTimesEntity::class, [
            new EntityField('id')->setIndexPrimaryKey()->setTypeInt(11, false)->setAutoIncrement(),
            new EntityField('brand_id')->setTypeInt(11)->setIndexUnique(),
            new EntityField('days_from')->setTypeInt(11),
            new EntityField('days_to')->setTypeInt(11),
        ]);
    }

    public function init()
    {
        $this->addPermission(self::PERMISSION);
        $this->registerBackendController('BackorderLeadTimeAdmin');
        $this->addBackendControllerPermission('BackorderLeadTimeAdmin', self::PERMISSION);
        $this->extendBackendMenu('left_catalog', [
            'sviat__backorder_lead_time__menu' => ['BackorderLeadTimeAdmin'],
        ]);

        $this->registerFeedExtenders();
    }

    /** Кожен фід-модуль може бути не встановлений: реєструємося лише на ті класи, що існують. */
    private function registerFeedExtenders(): void
    {
        if (class_exists(GoogleMerchantHelper::class)) {
            $this->registerChainExtension(
                ['class' => GoogleMerchantHelper::class, 'method' => 'getQuery'],
                ['class' => FeedExtender::class, 'method' => 'warmUpTerms']
            );
            $this->registerChainExtension(
                ['class' => GoogleMerchantHelper::class, 'method' => 'getItem'],
                ['class' => FeedExtender::class, 'method' => 'googleMerchantItem']
            );
        }

        if (class_exists(GoogleMerchantAdapter::class)) {
            $this->registerChainExtension(
                ['class' => GoogleMerchantAdapter::class, 'method' => 'getQuery'],
                ['class' => FeedExtender::class, 'method' => 'warmUpTerms']
            );
            $this->registerChainExtension(
                ['class' => GoogleMerchantAdapter::class, 'method' => 'getItem'],
                ['class' => FeedExtender::class, 'method' => 'feedsGoogleItem']
            );
        }
    }
}
