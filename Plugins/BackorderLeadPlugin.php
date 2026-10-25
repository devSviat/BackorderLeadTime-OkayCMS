<?php

namespace Okay\Modules\Sviat\BackorderLeadTime\Plugins;

use DateTimeImmutable;
use Okay\Core\FrontTranslations;
use Okay\Core\SmartyPlugins\Func;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\BackorderLeadTime;

/**
 * {sviat_backorder_lead brand_id=… [stock=…] assign=var}: у var — термін і дата відправки або null.
 * Без stock перевіряє лише is_preorder і термін: блок, який тема показує й ховає JS при зміні варіанта.
 */
class BackorderLeadPlugin extends Func
{
    protected $tag = 'sviat_backorder_lead';

    public function __construct(
        private BackorderLeadTime $leadTime,
        private FrontTranslations $translations,
        private ?DateTimeImmutable $now = null,
    ) {
    }

    public function run($params, $smarty = null)
    {
        $brandId = isset($params['brand_id']) ? (int) $params['brand_id'] : null;
        $stock = array_key_exists('stock', $params) ? $params['stock'] : 0;

        $lead = null;
        $backorder = $this->leadTime->forItem($brandId, $stock, $this->now);
        if ($backorder !== null) {
            $month = $this->translations->getTranslation('sviat__backorder_lead_time__month_' . $backorder->shipDate->format('n'));
            $lead = [
                'from' => $backorder->term->from,
                'to' => $backorder->term->to,
                'date_label' => $backorder->shipDate->format('j') . ' ' . $month,
                'date_iso' => $backorder->shipDate->format('Y-m-d'),
                'availability_date' => $backorder->availabilityDate(),
            ];
        }

        if (!empty($params['assign']) && $smarty !== null) {
            $smarty->assign($params['assign'], $lead);
        }

        return '';
    }
}
