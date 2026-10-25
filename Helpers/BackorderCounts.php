<?php

namespace Okay\Modules\Sviat\BackorderLeadTime\Helpers;

use Okay\Core\Database;
use Okay\Core\Modules\Extender\ExtenderFacade;
use Okay\Core\QueryFactory;
use Okay\Entities\ProductsEntity;
use Okay\Entities\VariantsEntity;

class BackorderCounts
{
    public function __construct(private QueryFactory $queryFactory, private Database $db)
    {
    }

    /**
     * Варіанти видимих товарів зі stock = 0 за брендами, одним запитом на всі бренди.
     * Не залежить від is_preorder: адмінці це довідка, скільки товарів зачепить термін.
     *
     * @return array<int, int> brand_id => кількість; бренди без таких варіантів відсутні
     */
    public function variantsByBrand(): array
    {
        $select = $this->queryFactory->newSelect();
        $select->cols(['p.brand_id AS brand_id', 'COUNT(v.id) AS variants'])
            ->from(VariantsEntity::getTable() . ' AS v')
            ->join('INNER', ProductsEntity::getTable() . ' AS p', 'p.id = v.product_id')
            ->where('p.visible = 1')
            ->where('v.stock = 0')
            ->groupBy(['p.brand_id']);
        $this->db->query($select);

        $counts = [];
        foreach ((array) $this->db->results() as $row) {
            $counts[(int) $row->brand_id] = (int) $row->variants;
        }

        return ExtenderFacade::execute(__METHOD__, $counts, func_get_args());
    }
}
