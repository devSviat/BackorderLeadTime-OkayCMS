<?php

namespace Okay\Modules\Sviat\BackorderLeadTime\Backend\Controllers;

use DateTimeImmutable;
use InvalidArgumentException;
use Okay\Admin\Controllers\IndexAdmin;
use Okay\Core\EntityFactory;
use Okay\Entities\BrandsEntity;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\BackorderCounts;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\BackorderLeadTime;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\LeadTerm;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\LeadTimeLabels;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\LeadTimeSettings;

class BackorderLeadTimeAdmin extends IndexAdmin
{
    public function fetch(
        BackorderLeadTime $service,
        LeadTimeSettings $settings,
        EntityFactory $entityFactory,
        LeadTimeLabels $labels,
        BackorderCounts $counts,
    ) {
        $brands = $entityFactory->get(BrandsEntity::class)->noLimit()->find();
        // За назвою, а не за позицією: у сотні брендів менеджер шукає свій очима.
        usort($brands, fn($a, $b) => strnatcasecmp(mb_strtolower((string) $a->name), mb_strtolower((string) $b->name)));
        $saved = $service->brandTerms();
        $default = $settings->getDefault();

        $defaultRow = ['from' => $default?->from, 'to' => $default?->to, 'error' => false];
        $rows = [];
        foreach ($brands as $brand) {
            $term = $saved[(int) $brand->id] ?? null;
            $rows[(int) $brand->id] = ['from' => $term?->from, 'to' => $term?->to, 'error' => false];
        }

        if ($this->request->method('post')) {
            $posted = (array) $this->request->post('brands');
            $terms = [];

            [$defaultTerm, $defaultRow] = $this->parseRow(
                $this->postString('default_from'),
                $this->postString('default_to'),
                $default,
            );
            $hasErrors = $defaultRow['error'];

            foreach (array_keys($rows) as $brandId) {
                $input = is_array($posted[$brandId] ?? null) ? $posted[$brandId] : [];
                [$terms[$brandId], $rows[$brandId]] = $this->parseRow(
                    $this->scalarString($input['from'] ?? ''),
                    $this->scalarString($input['to'] ?? ''),
                    $saved[$brandId] ?? null,
                );
                $hasErrors = $hasErrors || $rows[$brandId]['error'];
            }

            // Рядок з помилкою лишає попередній термін, а не стирає його.
            $service->saveBrandTerms($terms);
            $settings->setDefault($defaultTerm);
            $settings->setExcludesFromAds($this->request->post('exclude_from_ads') === '1');

            if (!$hasErrors) {
                $this->postRedirectGet->storeMessageSuccess('saved');
                $this->postRedirectGet->redirect();
            }
            $this->design->assign('message_error', 'has_errors');
        }

        $now = new DateTimeImmutable('now');
        $effectiveDefault = $this->termOf($defaultRow);
        $defaultRow['sentence'] = $effectiveDefault !== null ? $labels->sentence($effectiveDefault, $now) : null;
        $defaultRow['preview'] = $this->preview($labels, $effectiveDefault, $now);

        $backorders = $counts->variantsByBrand();
        foreach ($rows as $brandId => $row) {
            $own = (string) $row['from'] !== '' || (string) $row['to'] !== '';
            $rows[$brandId]['own'] = $own;
            $rows[$brandId]['backorders'] = $backorders[$brandId] ?? 0;
            $rows[$brandId]['preview'] = $row['error']
                ? null
                : $this->preview($labels, $own ? $this->termOf($row) : $effectiveDefault, $now);
        }

        $this->design->assign('lt_default', $defaultRow);
        $this->design->assign('lt_brands', $brands);
        $this->design->assign('lt_rows', $rows);
        $this->design->assign('lt_preorder', $service->isPreorderEnabled());
        $this->design->assign('lt_exclude_ads', $settings->excludesFromAds());
        $this->design->assign('lt_today', $now->format('Y-m-d'));
        $this->design->assign('lt_max_days', LeadTerm::MAX_DAYS);
        $this->design->assign('lt_texts', json_encode($labels->forScript(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $this->response->setContent($this->design->fetch('backorder_lead_time.tpl'));
    }

    /**
     * @return array{0: ?LeadTerm, 1: array{from: mixed, to: mixed, error: bool}} термін до збереження й дані для форми
     */
    private function parseRow(string $from, string $to, ?LeadTerm $fallback): array
    {
        try {
            $term = LeadTerm::parse($from, $to);

            return [$term, ['from' => $term?->from, 'to' => $term?->to, 'error' => false]];
        } catch (InvalidArgumentException) {
            return [$fallback, ['from' => $from, 'to' => $to, 'error' => true]];
        }
    }

    /** @param array{from: mixed, to: mixed, error: bool} $row */
    private function termOf(array $row): ?LeadTerm
    {
        if ($row['error']) {
            return null;
        }
        try {
            return LeadTerm::parse((string) $row['from'], (string) $row['to']);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /** @return ?array{term: string, date: string} */
    private function preview(LeadTimeLabels $labels, ?LeadTerm $term, DateTimeImmutable $now): ?array
    {
        if ($term === null) {
            return null;
        }

        return ['term' => $labels->termText($term), 'date' => $labels->dateLabel($term->shipDate($now))];
    }

    private function postString(string $name): string
    {
        return $this->scalarString($this->request->post($name));
    }

    private function scalarString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
