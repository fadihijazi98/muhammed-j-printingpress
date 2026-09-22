<?php

declare(strict_types=1);

namespace App;

/**
 * Staff are allowed to keep sale prices up to date, but not to add or remove
 * job types — that stays with the manager.
 */
final class PricingController extends Controller
{
    public function index(): void
    {
        $this->view(
            'pages/pricing',
            [
                'title'    => 'أسعار البيع',
                'pageName' => 'pricing',
                'jobTypes' => SettingsRepository::jobTypes(),
            ],
        );
    }

    public function update(): void
    {
        $id      = $this->postInt('id');
        $jobType = SettingsRepository::findJobType($id);

        if ($jobType === null) {
            $this->notFound('نوع العمل غير موجود.');
        }

        $validator = $this->validator();
        $price     = $validator->money('default_unit_price', 'سعر الوحدة', true, true);

        if (! $validator->passes()) {
            $this->rejectForm($validator, '/pricing', 'pricing-' . $id);
        }

        Database::transaction(
            static function () use ($id, $jobType, $price): void {
                Database::update(
                    'job_types',
                    $id,
                    [
                        'default_unit_price' => $price,
                        'updated_by'         => Auth::id(),
                        'updated_at'         => date('Y-m-d H:i:s'),
                    ],
                );

                Audit::log(
                    Audit::UPDATED,
                    'سعر بيع',
                    $id,
                    'تعديل سعر ' . $jobType['name'] . ' من ' . Money::display((int) $jobType['default_unit_price'])
                    . ' إلى ' . Money::display($price),
                );
            },
        );

        $this->saved('تم تحديث السعر.', '/pricing');
    }
}
