# مطبعة محمد — برنامج إدارة الحسابات

برنامج بسيط يعمل على جهاز المكتب لتسجيل المبيعات، الدفعات، الخصومات،
مصاريف المواد، وأجور العمال — بالعربية بالكامل.

---

## التشغيل (٣ خطوات)

### على الماك

1. انسخ مجلد البرنامج إلى الجهاز.
2. اضغط مرتين على **`start.command`**.
3. سيفتح المتصفح تلقائياً على العنوان `http://localhost:8123`.

> في أول مرة قد يقول الماك «لا يمكن فتح الملف».
> اضغط بالزر الأيمن على `start.command` ← **Open** ← **Open**.

### على ويندوز

1. انسخ مجلد البرنامج إلى الجهاز.
2. اضغط مرتين على **`start.bat`**.
3. سيفتح المتصفح تلقائياً على العنوان `http://localhost:8123`.

**لإيقاف البرنامج:** أغلق نافذة التشغيل السوداء، أو اضغط `Control + C`.

### المتطلب الوحيد

**PHP 8.1 أو أحدث** مثبّت على الجهاز.

* الماك: `brew install php`
* ويندوز: نزّل PHP من [windows.php.net](https://windows.php.net/download/) وأضفه إلى `PATH`.

لا حاجة لأي شيء آخر: لا إنترنت، لا قاعدة بيانات منفصلة، ولا برامج إضافية.

---

## حسابات الدخول

| الصلاحية | البريد الإلكتروني | كلمة المرور |
| --- | --- | --- |
| مدير | `muhammed@admin.com` | `admin123` |
| موظف | `any@staff.com` | `staff_password` |

> **مهم:** غيّر كلمات المرور من صفحة **المستخدمون** بعد أول دخول.

---

## من يستطيع ماذا

**الموظف يرى صفحتين فقط:** «المبيعات» و«أسعار البيع». كل ما عداهما للمدير.

| | المدير | الموظف |
| --- | :---: | :---: |
| تسجيل عملية بيع بتكاليفها وتعديلها | ✅ | ✅ |
| تعديل **سعر** الصنف | ✅ | ✅ |
| الرئيسية والتقارير | ✅ | ❌ |
| التجار وحساباتهم | ✅ | ❌ |
| الدفعات والخصومات | ✅ | ❌ |
| مصاريف المواد العامة | ✅ | ❌ |
| أجور العمال (سجل الأجور) | ✅ | ❌ |
| الأصناف والمواد والعمال والمستخدمون | ✅ | ❌ |
| حذف أي سجل | ✅ | ❌ |
| سجل العمليات والنسخ الاحتياطي | ✅ | ❌ |

ملاحظات مهمة:

* الموظف **يستطيع** إضافة تكلفة مواد وأجرة عامل **داخل عملية البيع**، لأنها جزء من
  حساب تكلفة العملية — لكنه لا يرى سجل الأجور ولا سجل المصاريف العامة.
* الموظف **لا يستطيع إضافة تاجر جديد**. عند وصول زبون جديد يضيفه المدير أولاً.
* الموظف يرى اسم التاجر في عملية البيع، لكنه لا يستطيع فتح حساب التاجر أو رؤية
  ما عليه من ذمم.
* بعد تسجيل الدخول: المدير يذهب إلى «الرئيسية»، والموظف إلى «المبيعات».
* كل عملية تسجّل اسم من أضافها ومن عدّلها.

## تسجيل عملية بيع — صفحة واحدة

كل شيء في صفحة واحدة مرتبة بثلاث خطوات، والملخص يتحدّث أمامك أثناء الكتابة.

**مثال كامل:**

| الخطوة | ما تُدخله | النتيجة |
| --- | --- | --- |
| ١ — البيع | بلوزة، العدد **30**، سعر القطعة **30** | المحسوب **900** |
| | عدّل الإجمالي النهائي إلى **850** | سعر البيع **850** |
| ٢ — المواد | دهان، **10** لتر × **15** | تكلفة **150** |
| ٣ — العمال | أحمد، **2** ساعة × **10** | أجور **20** |
| الملخص | | إجمالي التكلفة **170** |
| | | **صافي الربح 680** |

* **كل إجمالي قابل للتعديل.** يُعبّأ تلقائياً من الضرب، وتستطيع تغييره إذا اتفقت
  على سعر آخر أو أعطاك المورّد خصماً. الكمية والسعر يبقيان محفوظين كما هما للسجل.
* زر **إعادة الحساب** يرجّع الإجمالي إلى نتيجة الضرب.
* الأسعار تُعبّأ تلقائياً من إعدادات الأصناف والمواد والعمال.
* يمكن إضافة أكثر من مادة وأكثر من عامل للعملية الواحدة.

### حساب التاجر

```
المتبقي على التاجر = إجمالي المبيعات − الخصومات − الدفعات المستلمة
```

* **الدفعة:** يمكن تسجيل دفعة جزئية في أي وقت؛ الباقي يبقى على الحساب.
* **الخصم:** يُنقص المبلغ المستحق دون تغيير قيمة المبيعات المسجّلة.

### الربح

```
صافي ربح العملية = سعر البيع − (تكلفة موادها + أجور عمالها)

ربح الفترة = المبيعات − الخصومات − (كل مصاريف المواد + كل أجور العمال)
```

تكاليف العملية تُحسب مرة واحدة فقط: تظهر في تفاصيل العملية، وتدخل في تقرير
الفترة، ولا تُحسب مرتين. المصروف الذي لا يخص عملية معيّنة يُسجَّل من صفحة
**مصاريف المواد** ويظهر كـ «مصروف عام».

الربح يُحسب على أساس العمل المنفَّذ خلال الفترة، وليس على أساس النقد المقبوض.

---

## البيانات موجودة أين؟

كل شيء داخل ملف واحد:

```
data/app.sqlite
```

### النسخ الاحتياطي

من القائمة الجانبية (للمدير فقط): **💾 نسخة احتياطية** → ينزّل نسخة من الملف.
احفظها على فلاشة أو على الإنترنت بشكل دوري.

### الاستعادة

أغلق البرنامج، وانسخ ملف النسخة الاحتياطية مكان `data/app.sqlite`، ثم شغّل البرنامج.

### النقل إلى جهاز آخر

انسخ المجلد كاملاً (مع مجلد `data`) إلى الجهاز الجديد وشغّل ملف البدء.

---

## التثبيت كتطبيق على سطح المكتب (PWA)

بعد فتح البرنامج في Chrome أو Edge:

1. اضغط على أيقونة التثبيت ⊕ في شريط العنوان.
2. اختر **تثبيت**.

سيصبح للبرنامج أيقونة ونافذة مستقلة مثل أي تطبيق. البرنامج يحتاج أن يبقى
ملف التشغيل يعمل في الخلفية لأن قاعدة البيانات على نفس الجهاز.

---

## حل المشاكل

| المشكلة | الحل |
| --- | --- |
| «PHP غير موجود» | ثبّت PHP كما في قسم المتطلبات أعلاه. |
| المنفذ 8123 مشغول | افتح ملف البدء وغيّر الرقم `8123` إلى `8124` مثلاً. |
| ظهرت صفحة «حدث خطأ» | التفاصيل التقنية في ملف `data/error.log`. |
| نسيت كلمة مرور المدير | استخدم حساب مدير آخر لتغييرها من صفحة المستخدمون. |

---

# For developers

Plain PHP 8.1+ with SQLite. No Composer, no build step, no framework.

```
start.command / start.bat    one-click launcher (php -S … -t public router.php)
router.php                   built-in-server router: static files, else front controller
public/index.php             front controller
public/assets                app.css, app.js, sale-form.js, fonts/ (Amiri Quran, self-hosted)
public/manifest.webmanifest  PWA manifest
public/service-worker.js     shell cache, network-first
migrations/001_init.sql      schema (applied automatically on first run)
migrations/002_sale_costs.sql  links material/worker costs to a sale
src/                         Config, Database, Auth, Audit, Session, Router,
                             Validator, Form, FormState, Money, Quantity, View, Seeder
src/repositories/            one repository per feature area
src/controllers/             one controller per feature area
views/layout.php + views/pages/
tests/run.php                test suite
```

**Sale costing**: `material_purchases.sale_id` and `worker_payments.sale_id` are nullable.
A row with a sale id is a cost of that sale and shows in its net profit; a row without
one is a general shop expense. Either way the row is counted exactly once in the period
report, so per-sale costing never double counts. Editing a sale replaces its cost rows.

**The font** is Amiri Quran, self-hosted in `public/assets/fonts/` so the app needs no
internet. It ships in a single 400 weight; emphasis is by size and colour, and digits
fall back to a tabular system face (`--font-num`) so money columns line up.

**Money** is stored as integer أغورة (cents) everywhere — `Money::parse` / `Money::format`
convert at the edges, and both accept Arabic-Indic digits.

**Migrations** run on every request and skip files already recorded in the
`migrations` table, so adding `migrations/002_*.sql` is all that a schema change needs.

**Seed data** is only inserted into empty tables, so it never resurrects a
changed password or a deleted row.

Run the tests:

```bash
php tests/run.php
```

Run the server manually:

```bash
php -S 127.0.0.1:8123 -t public router.php
```

Point the app at a different database file:

```bash
APP_DATABASE=/path/to/other.sqlite php -S 127.0.0.1:8123 -t public router.php
```
