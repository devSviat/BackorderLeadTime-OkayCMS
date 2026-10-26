<?php

namespace Okay\Modules\Sviat\BackorderLeadTime\Extenders;

use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\BackorderLeadTime;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\LeadTimeSettings;

class FeedExtender implements ExtensionInterface
{
    /**
     * Рекламні напрями Merchant Center; Free_listings свідомо не тут — товар лишається в безкоштовних оголошеннях.
     * Display_ads не прибирає динамічний ремаркетинг Performance Max — так задокументовано в Google.
     */
    public const array AD_DESTINATIONS = ['Shopping_ads', 'Display_ads', 'Local_inventory_ads'];

    public function __construct(
        private BackorderLeadTime $backorderLeadTime,
        private LeadTimeSettings $leadTimeSettings,
    ) {
    }

    /** Фіди читають рядки небуферизованим запитом: терміни й знятих з виробництва вантажимо до циклу, не посеред нього. */
    public function warmUpTerms(mixed $query): mixed
    {
        $this->backorderLeadTime->brandTerms();
        $this->backorderLeadTime->discontinuedProductIds();

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
        if (isset($product->product_id) && isset($this->backorderLeadTime->discontinuedProductIds()[(int) $product->product_id])) {
            return $data;
        }

        $backorder = $this->backorderLeadTime->forItem(
            isset($product->brand_id) ? (int) $product->brand_id : null,
            $product->stock ?? null,
        );
        if ($backorder !== null) {
            $data['g:availability']['data'] = 'backorder';
            $data['g:availability_date']['data'] = $backorder->availabilityDate();
            if ($this->leadTimeSettings->excludesFromAds()) {
                $data = $this->excludeFromAds($data);
            }
        }

        return $data;
    }

    /** Атрибут повторюваний: кожен напрям — окремий тег, ключ лише тримає їх різними в масиві. */
    private function excludeFromAds(array $data): array
    {
        foreach (self::AD_DESTINATIONS as $destination) {
            $data["g:excluded_destination:{$destination}"] = ['tag' => 'g:excluded_destination', 'data' => $destination];
        }

        return $data;
    }
}
