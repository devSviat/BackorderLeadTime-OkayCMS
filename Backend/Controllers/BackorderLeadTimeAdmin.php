<?php

namespace Okay\Modules\Sviat\BackorderLeadTime\Backend\Controllers;

use InvalidArgumentException;
use Okay\Admin\Controllers\IndexAdmin;
use Okay\Core\EntityFactory;
use Okay\Entities\BrandsEntity;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\BackorderLeadTime;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\LeadTerm;
use Okay\Modules\Sviat\BackorderLeadTime\Helpers\LeadTimeSettings;

class BackorderLeadTimeAdmin extends IndexAdmin
{
    public function fetch(BackorderLeadTime $service, LeadTimeSettings $settings, EntityFactory $entityFactory)
    {
        $brands = $entityFactory->get(BrandsEntity::class)->noLimit()->find();
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

            if (!$hasErrors) {
                $this->postRedirectGet->storeMessageSuccess('saved');
                $this->postRedirectGet->redirect();
            }
            $this->design->assign('message_error', 'has_errors');
        }

        $this->design->assign('lt_default', $defaultRow);
        $this->design->assign('lt_brands', $brands);
        $this->design->assign('lt_rows', $rows);
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

    private function postString(string $name): string
    {
        return $this->scalarString($this->request->post($name));
    }

    private function scalarString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
