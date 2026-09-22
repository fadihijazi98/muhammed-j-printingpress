<?php

declare(strict_types=1);

namespace App;

/** Manager-only configuration: job types, materials and workers. */
final class SettingsController extends Controller
{
    public function index(): void
    {
        $this->view(
            'pages/settings',
            [
                'title'     => 'الإعدادات',
                'pageName'  => 'settings',
                'jobTypes'  => SettingsRepository::jobTypes(),
                'materials' => SettingsRepository::materials(),
                'workers'   => SettingsRepository::workers(),
            ],
        );
    }

    public function storeJobType(): void
    {
        $validator = $this->validator();

        $name  = $validator->text('name', 'اسم نوع العمل');
        $unit  = $validator->text('unit', 'الوحدة', true, 40);
        $price = $validator->money('default_unit_price', 'سعر الوحدة', true, true);

        if (! $validator->passes()) {
            $this->rejectForm($validator, '/settings', 'job-type-create');
        }

        Database::transaction(
            static function () use ($name, $unit, $price): void {
                $id = Database::insert(
                    'job_types',
                    [
                        'name'               => $name,
                        'unit'               => $unit,
                        'default_unit_price' => $price,
                        'created_by'         => Auth::id(),
                    ],
                );

                Audit::log(Audit::CREATED, 'نوع عمل', $id, 'إضافة نوع عمل: ' . $name);
            },
        );

        $this->saved('تمت إضافة نوع العمل.', '/settings');
    }

    public function updateJobType(): void
    {
        $id      = $this->postInt('id');
        $jobType = SettingsRepository::findJobType($id);

        if ($jobType === null) {
            $this->notFound('نوع العمل غير موجود.');
        }

        $validator = $this->validator();

        $name     = $validator->text('name', 'اسم نوع العمل');
        $unit     = $validator->text('unit', 'الوحدة', true, 40);
        $price    = $validator->money('default_unit_price', 'سعر الوحدة', true, true);
        $isActive = isset(Request::post()['is_active']) ? 1 : 0;

        if (! $validator->passes()) {
            $this->rejectForm($validator, '/settings', 'job-type-' . $id);
        }

        Database::transaction(
            static function () use ($id, $name, $unit, $price, $isActive): void {
                Database::update(
                    'job_types',
                    $id,
                    [
                        'name'               => $name,
                        'unit'               => $unit,
                        'default_unit_price' => $price,
                        'is_active'          => $isActive,
                        'updated_by'         => Auth::id(),
                        'updated_at'         => date('Y-m-d H:i:s'),
                    ],
                );

                Audit::log(Audit::UPDATED, 'نوع عمل', $id, 'تعديل نوع عمل: ' . $name);
            },
        );

        $this->saved('تم حفظ نوع العمل.', '/settings');
    }

    public function destroyJobType(): void
    {
        $id      = $this->postInt('id');
        $jobType = SettingsRepository::findJobType($id);

        if ($jobType === null) {
            $this->notFound('نوع العمل غير موجود.');
        }

        if (SettingsRepository::isInUse('sales', 'job_type_id', $id)) {
            Session::flash('لا يمكن حذف نوع عمل مستخدم في مبيعات سابقة. أزل علامة "مُفعّل" لإخفائه.', 'error');

            Response::redirect('/settings');
        }

        Database::transaction(
            static function () use ($id, $jobType): void {
                Database::delete('job_types', $id);

                Audit::log(Audit::DELETED, 'نوع عمل', $id, 'حذف نوع عمل: ' . $jobType['name']);
            },
        );

        $this->saved('تم حذف نوع العمل.', '/settings');
    }

    public function storeMaterial(): void
    {
        $validator = $this->validator();

        $name = $validator->text('name', 'اسم المادة');
        $unit = $validator->text('unit', 'الوحدة', true, 40);
        $cost = $validator->money('default_unit_cost', 'سعر الوحدة', true, true);

        if (! $validator->passes()) {
            $this->rejectForm($validator, '/settings', 'material-create');
        }

        Database::transaction(
            static function () use ($name, $unit, $cost): void {
                $id = Database::insert(
                    'materials',
                    [
                        'name'              => $name,
                        'unit'              => $unit,
                        'default_unit_cost' => $cost,
                        'created_by'        => Auth::id(),
                    ],
                );

                Audit::log(Audit::CREATED, 'مادة', $id, 'إضافة مادة: ' . $name);
            },
        );

        $this->saved('تمت إضافة المادة.', '/settings');
    }

    public function updateMaterial(): void
    {
        $id       = $this->postInt('id');
        $material = SettingsRepository::findMaterial($id);

        if ($material === null) {
            $this->notFound('المادة غير موجودة.');
        }

        $validator = $this->validator();

        $name     = $validator->text('name', 'اسم المادة');
        $unit     = $validator->text('unit', 'الوحدة', true, 40);
        $cost     = $validator->money('default_unit_cost', 'سعر الوحدة', true, true);
        $isActive = isset(Request::post()['is_active']) ? 1 : 0;

        if (! $validator->passes()) {
            $this->rejectForm($validator, '/settings', 'material-' . $id);
        }

        Database::transaction(
            static function () use ($id, $name, $unit, $cost, $isActive): void {
                Database::update(
                    'materials',
                    $id,
                    [
                        'name'              => $name,
                        'unit'              => $unit,
                        'default_unit_cost' => $cost,
                        'is_active'         => $isActive,
                        'updated_by'        => Auth::id(),
                        'updated_at'        => date('Y-m-d H:i:s'),
                    ],
                );

                Audit::log(Audit::UPDATED, 'مادة', $id, 'تعديل مادة: ' . $name);
            },
        );

        $this->saved('تم حفظ المادة.', '/settings');
    }

    public function destroyMaterial(): void
    {
        $id       = $this->postInt('id');
        $material = SettingsRepository::findMaterial($id);

        if ($material === null) {
            $this->notFound('المادة غير موجودة.');
        }

        if (SettingsRepository::isInUse('material_purchases', 'material_id', $id)) {
            Session::flash('لا يمكن حذف مادة لها مصاريف مسجلة. أزل علامة "مُفعّلة" لإخفائها.', 'error');

            Response::redirect('/settings');
        }

        Database::transaction(
            static function () use ($id, $material): void {
                Database::delete('materials', $id);

                Audit::log(Audit::DELETED, 'مادة', $id, 'حذف مادة: ' . $material['name']);
            },
        );

        $this->saved('تم حذف المادة.', '/settings');
    }

    public function storeWorker(): void
    {
        $validator = $this->validator();

        $name  = $validator->text('name', 'اسم العامل');
        $phone = $validator->text('phone', 'رقم الهاتف', false, 40);
        $rate  = $validator->money('hourly_rate', 'أجرة الساعة', true, true);

        if (! $validator->passes()) {
            $this->rejectForm($validator, '/settings', 'worker-create');
        }

        Database::transaction(
            static function () use ($name, $phone, $rate): void {
                $id = Database::insert(
                    'workers',
                    [
                        'name'        => $name,
                        'phone'       => $phone ?: null,
                        'hourly_rate' => $rate,
                        'created_by'  => Auth::id(),
                    ],
                );

                Audit::log(Audit::CREATED, 'عامل', $id, 'إضافة عامل: ' . $name);
            },
        );

        $this->saved('تمت إضافة العامل.', '/settings');
    }

    public function updateWorker(): void
    {
        $id     = $this->postInt('id');
        $worker = SettingsRepository::findWorker($id);

        if ($worker === null) {
            $this->notFound('العامل غير موجود.');
        }

        $validator = $this->validator();

        $name     = $validator->text('name', 'اسم العامل');
        $phone    = $validator->text('phone', 'رقم الهاتف', false, 40);
        $rate     = $validator->money('hourly_rate', 'أجرة الساعة', true, true);
        $isActive = isset(Request::post()['is_active']) ? 1 : 0;

        if (! $validator->passes()) {
            $this->rejectForm($validator, '/settings', 'worker-' . $id);
        }

        Database::transaction(
            static function () use ($id, $name, $phone, $rate, $isActive): void {
                Database::update(
                    'workers',
                    $id,
                    [
                        'name'        => $name,
                        'phone'       => $phone ?: null,
                        'hourly_rate' => $rate,
                        'is_active'   => $isActive,
                        'updated_by'  => Auth::id(),
                        'updated_at'  => date('Y-m-d H:i:s'),
                    ],
                );

                Audit::log(Audit::UPDATED, 'عامل', $id, 'تعديل عامل: ' . $name);
            },
        );

        $this->saved('تم حفظ بيانات العامل.', '/settings');
    }

    public function destroyWorker(): void
    {
        $id     = $this->postInt('id');
        $worker = SettingsRepository::findWorker($id);

        if ($worker === null) {
            $this->notFound('العامل غير موجود.');
        }

        if (SettingsRepository::isInUse('worker_payments', 'worker_id', $id)) {
            Session::flash('لا يمكن حذف عامل له أجور مسجلة. أزل علامة "مُفعّل" لإخفائه.', 'error');

            Response::redirect('/settings');
        }

        Database::transaction(
            static function () use ($id, $worker): void {
                Database::delete('workers', $id);

                Audit::log(Audit::DELETED, 'عامل', $id, 'حذف عامل: ' . $worker['name']);
            },
        );

        $this->saved('تم حذف العامل.', '/settings');
    }
}
