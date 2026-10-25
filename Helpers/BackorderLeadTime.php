<?php

namespace Okay\Modules\Sviat\BackorderLeadTime\Helpers;

use DateTimeImmutable;
use InvalidArgumentException;
use Okay\Core\EntityFactory;
use Okay\Core\Modules\Extender\ExtenderFacade;
use Okay\Core\Settings;
use Okay\Entities\ProductsEntity;
use Okay\Modules\Sviat\BackorderLeadTime\Entities\BackorderLeadTimesEntity;

class BackorderLeadTime
{
    /** @var array<int, LeadTerm>|null */
    private ?array $brandTerms = null;

    /** @var array<int, true>|null */
    private ?array $discontinuedIds = null;

    public function __construct(
        private EntityFactory $entityFactory,
        private LeadTimeSettings $leadTimeSettings,
        private Settings $settings,
    ) {
    }

    /** Термін бренду, інакше загальний; null — загального немає, модуль нічого не змінює. */
    public function termFor(?int $brandId): ?LeadTerm
    {
        $default = $this->leadTimeSettings->getDefault();
        if ($default === null) {
            return ExtenderFacade::execute(__METHOD__, null, func_get_args());
        }

        $term = $this->brandTerms()[(int) $brandId] ?? $default;

        return ExtenderFacade::execute(__METHOD__, $term, func_get_args());
    }

    public function isPreorderEnabled(): bool
    {
        $enabled = in_array($this->settings->get('is_preorder'), [1, '1'], true);

        return ExtenderFacade::execute(__METHOD__, $enabled, func_get_args());
    }

    /** Під замовлення — лише stock = 0 (рядком чи числом); NULL і додатний — у наявності. */
    public function isBackorder(mixed $stock): bool
    {
        $isBackorder = $this->isPreorderEnabled() && ($stock === 0 || $stock === '0');

        return ExtenderFacade::execute(__METHOD__, $isBackorder, func_get_args());
    }

    public function forItem(?int $brandId, mixed $stock, ?DateTimeImmutable $now = null): ?Backorder
    {
        $backorder = null;
        if ($this->isBackorder($stock)) {
            $term = $this->termFor($brandId);
            if ($term !== null) {
                $now ??= new DateTimeImmutable('now');
                $backorder = new Backorder($term, $now->modify("+{$term->to} days")->setTime(12, 0));
            }
        }

        return ExtenderFacade::execute(__METHOD__, $backorder, func_get_args());
    }

    /**
     * Читається одним запитом і кешується в об'єкті: фіди забороняють SQL посеред циклу.
     *
     * @return array<int, LeadTerm> brand_id => термін
     */
    public function brandTerms(): array
    {
        if ($this->brandTerms === null) {
            $this->brandTerms = [];
            foreach ($this->entityFactory->get(BackorderLeadTimesEntity::class)->noLimit()->find() as $row) {
                try {
                    $this->brandTerms[(int) $row->brand_id] = new LeadTerm((int) $row->days_from, (int) $row->days_to);
                } catch (InvalidArgumentException) {
                    // Зіпсований рядок у БД — бренд бере загальний термін, сторінка не падає.
                }
            }
        }

        return ExtenderFacade::execute(__METHOD__, $this->brandTerms, func_get_args());
    }

    /**
     * Товари, зняті з виробництва (поле `discontinued` від Sviat/ProductDiscontinued): вітрина їх не продає,
     * тож фід лишає їм `out of stock`. Без того модуля поле не зареєстроване і список порожній.
     * Читається одним запитом і кешується, як brandTerms().
     *
     * @return array<int, true> product_id => true
     */
    public function discontinuedProductIds(): array
    {
        if ($this->discontinuedIds === null) {
            $this->discontinuedIds = [];
            // Без перевірки незареєстроване поле фільтр мовчки ігнорує і повертає всі товари.
            if (in_array('discontinued', ProductsEntity::getFields(), true)) {
                $ids = $this->entityFactory->get(ProductsEntity::class)
                    ->cols(['id'])
                    ->noLimit()
                    ->find(['discontinued' => 1]);
                foreach ($ids as $id) {
                    $this->discontinuedIds[(int) $id] = true;
                }
            }
        }

        return ExtenderFacade::execute(__METHOD__, $this->discontinuedIds, func_get_args());
    }

    /**
     * Повна заміна: бренди з null (і відсутні в масиві) втрачають власний термін.
     *
     * @param array<int, ?LeadTerm> $terms brand_id => термін
     */
    public function saveBrandTerms(array $terms): void
    {
        $entity = $this->entityFactory->get(BackorderLeadTimesEntity::class);
        $existing = [];
        foreach ($entity->noLimit()->find() as $row) {
            $existing[(int) $row->brand_id] = (int) $row->id;
        }

        foreach ($terms as $brandId => $term) {
            $brandId = (int) $brandId;
            if ($term === null) {
                continue;
            }
            $data = ['brand_id' => $brandId, 'days_from' => $term->from, 'days_to' => $term->to];
            if (isset($existing[$brandId])) {
                $entity->update($existing[$brandId], $data);
                unset($existing[$brandId]);
            } else {
                $entity->add($data);
            }
        }

        if ($existing) {
            $entity->delete(array_values($existing));
        }

        $this->brandTerms = null;
        ExtenderFacade::execute(__METHOD__, null, func_get_args());
    }
}
