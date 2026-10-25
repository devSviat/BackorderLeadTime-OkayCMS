<?php

namespace Modules\Sviat\BackorderLeadTime;

use Aura\SqlQuery\QueryFactory as AuraQueryFactory;
use Aura\SqlQuery\QueryInterface;
use Okay\Core\Database;
use Okay\Core\QueryFactory;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\BackorderCounts;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class BackorderCountsTest extends TestCase
{
    /** @var list<string> */
    private array $queries = [];

    public function testOneGroupedQueryForAllBrands(): void
    {
        $counts = $this->counts([
            (object) ['brand_id' => '1', 'variants' => '1301'],
            (object) ['brand_id' => '9', 'variants' => '646'],
            (object) ['brand_id' => '0', 'variants' => '4'],
        ]);

        $this->assertSame([1 => 1301, 9 => 646, 0 => 4], $counts->variantsByBrand());
        $this->assertCount(1, $this->queries);

        $sql = str_replace('`', '', preg_replace('/\s+/', ' ', $this->queries[0]));
        $this->assertStringContainsString('FROM __variants AS v', $sql);
        $this->assertStringContainsString('INNER JOIN __products AS p ON p.id = v.product_id', $sql);
        $this->assertStringContainsString('p.visible = 1', $sql);
        $this->assertStringContainsString('v.stock = 0', $sql);
        $this->assertStringContainsString('GROUP BY p.brand_id', $sql);
    }

    public function testNoRowsMeansEmptyMap(): void
    {
        $this->assertSame([], $this->counts([])->variantsByBrand());
    }

    /** @param list<object> $rows */
    private function counts(array $rows): BackorderCounts
    {
        $this->queries = [];
        $aura = new AuraQueryFactory('mysql');
        $queryFactory = $this->createStub(QueryFactory::class);
        $queryFactory->method('newSelect')->willReturnCallback(fn() => $aura->newSelect());

        $db = $this->createStub(Database::class);
        $db->method('query')->willReturnCallback(function (QueryInterface $query): bool {
            $this->queries[] = (string) $query;

            return true;
        });
        $db->method('results')->willReturn($rows);
        // Деструктор Database кличе $this->pdo->disconnect(), а в стабі pdo — null.
        new ReflectionProperty(Database::class, 'pdo')->setValue($db, new class {
            public function disconnect(): void
            {
            }
        });

        return new BackorderCounts($queryFactory, $db);
    }
}
