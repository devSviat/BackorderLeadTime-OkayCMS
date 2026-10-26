<?php

namespace Okay\Modules\Sviat\BackorderLeadTime\Helpers;

use InvalidArgumentException;
use Okay\Core\Settings;

class LeadTimeSettings
{
    public const PREFIX = 'sviat__backorder_lead_time__';

    public function __construct(private Settings $settings)
    {
    }

    public function getDefault(): ?LeadTerm
    {
        try {
            return LeadTerm::parse(
                (string) $this->settings->get(self::PREFIX . 'default_from'),
                (string) $this->settings->get(self::PREFIX . 'default_to'),
            );
        } catch (InvalidArgumentException) {
            // Зіпсоване значення в БД рівнозначне «не задано»: модуль нічого не змінює.
            return null;
        }
    }

    public function setDefault(?LeadTerm $term): void
    {
        $this->settings->set(self::PREFIX . 'default_from', $term?->from);
        $this->settings->set(self::PREFIX . 'default_to', $term?->to);
    }

    /**
     * Бренди, чиї товари із залишком 0 модуль не чіпає: вітрина, розмітка й фіди лишаються як у теми.
     *
     * @return array<int, true> brand_id => true
     */
    public function disabledBrandIds(): array
    {
        $stored = $this->settings->get(self::PREFIX . 'disabled_brands');
        $ids = [];
        foreach (is_array($stored) ? $stored : [] as $id) {
            if (is_numeric($id) && (int) $id > 0) {
                $ids[(int) $id] = true;
            }
        }

        return $ids;
    }

    /** @param list<int> $brandIds */
    public function setDisabledBrandIds(array $brandIds): void
    {
        $this->settings->set(self::PREFIX . 'disabled_brands', array_values(array_unique(array_map(intval(...), $brandIds))));
    }

    /** Не збережене значення — увімкнено: у рекламі Google лише товари в наявності, під замовлення — тільки у фіді. */
    public function excludesFromAds(): bool
    {
        return !in_array($this->settings->get(self::PREFIX . 'exclude_from_ads'), [0, '0'], true);
    }

    public function setExcludesFromAds(bool $exclude): void
    {
        $this->settings->set(self::PREFIX . 'exclude_from_ads', $exclude ? '1' : '0');
    }
}
