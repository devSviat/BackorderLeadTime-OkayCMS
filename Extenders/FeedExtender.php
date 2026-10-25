<?php

namespace Okay\Modules\Sviat\BackorderLeadTime\Extenders;

use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\BackorderLeadTime;

class FeedExtender implements ExtensionInterface
{
    public function __construct(private BackorderLeadTime $backorderLeadTime)
    {
    }

    /** Фіди читають рядки небуферизованим запитом: терміни брендів вантажимо до циклу, не посеред нього. */
    public function warmUpTerms(mixed $query): mixed
    {
        $this->backorderLeadTime->brandTerms();

        return $query;
    }

    public function googleMerchantItem(array $result, object $product): array
    {
        return $this->applyBackorder($result, $product);
    }

    public function feedsGoogleItem(array $items, object $product): array
    {
        if (isset($items[0]['data']) && is_array($items[0]['data'])) {
            $items[0]['data'] = $this->applyBackorder($items[0]['data'], $product);
        }

        return $items;
    }

    private function applyBackorder(array $data, object $product): array
    {
        $backorder = $this->backorderLeadTime->forItem(
            isset($product->brand_id) ? (int) $product->brand_id : null,
            $product->stock ?? null,
        );
        if ($backorder !== null) {
            $data['g:availability']['data'] = 'backorder';
            $data['g:availability_date']['data'] = $backorder->availabilityDate();
        }

        return $data;
    }
}
