# Laravel Auth Tracker - متتبع جلسات المستخدمين

[![Latest Version](https://img.shields.io/badge/version-2.0.0-blue.svg)](https://github.com/abobakeralshahari/laravel-auth-tracker)
[![Laravel Version](https://img.shields.io/badge/Laravel-5.8%2B-red.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-7.2%2B-purple.svg)](https://php.net)

## 📋 نظرة عامة

**Laravel Auth Tracker** هو بكج متقدم لتتبع وإدارة جلسات المستخدمين في تطبيقات Laravel. يوفر البكج نظاماً شاملاً لتتبع تسجيلات الدخول، إدارة الأجهزة، والتحكم في الجلسات مع دعم كامل لـ Laravel Passport و Sanctum.

## ✨ المميزات الرئيسية

### 🔐 تتبع شامل للجلسات
- تتبع جلسات الويب (Sessions)
- دعم Laravel Passport للـ API
- دعم Laravel Sanctum للـ API
- تتبع مفصل لكل تسجيل دخول

### 📱 إدارة الأجهزة
- ربط الأجهزة بالمستخدمين
- تتبع تفاصيل الجهاز (نظام التشغيل، الموديل، إلخ)
- دعم تطبيقات الجوال والويب
- إدارة FCM tokens للإشعارات

### 🌍 تتبع الموقع الجغرافي
- استخراج الموقع من عنوان IP
- دعم مزودي خدمات الموقع المتعددة
- إمكانية إضافة مزودي خدمات مخصصين

### 🛡️ أمان متقدم
- إلغاء جلسات محددة أو جميعها
- تتبع محاولات تسجيل الدخول المشبوهة
- حماية من هجمات session fixation
- Rate limiting للطلبات

### 📊 لوحة تحكم شاملة
- عرض الجلسات النشطة
- سجل تسجيلات الدخول
- إحصائيات مفصلة
- واجهة مستخدم سهلة الاستخدام

## 🚀 التثبيت

### المتطلبات
- PHP 7.2 أو أحدث
- Laravel 5.8 أو أحدث
- MySQL/PostgreSQL/SQLite

### التثبيت عبر Composer

```bash
composer require abobakeralshahari/laravel-auth-tracker
```

### نشر ملفات التكوين

```bash
php artisan vendor:publish --provider="Alshahari\AuthTracker\AuthTrackerServiceProvider" --tag="config"
```

### تشغيل المايجريشن

```bash
php artisan migrate
```

## ⚙️ التكوين

### 1. إعداد النماذج

أضف الـ trait `AuthTracking` إلى نموذج المستخدم:

```php
<?php

namespace App;

use Alshahari\AuthTracker\Traits\AuthTracking;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use AuthTracking;
    
    // باقي الكود...
}
```

### 2. تحديث LoginController

استبدل `AuthenticatesUsers` بـ `AuthenticatesWithTracking`:

```php
<?php

namespace App\Http\Controllers\Auth;

use Alshahari\AuthTracker\Traits\AuthenticatesWithTracking;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesWithTracking;
    
    // باقي الكود...
}
```

### 3. تكوين User Provider

في `config/auth.php`:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent-tracked',
        'model' => App\User::class,
    ],
],
```

### 4. تثبيت Parser للـ User Agent

اختر أحد الخيارات التالية:

#### Option A: WhichBrowser
```bash
composer require whichbrowser/parser
```

#### Option B: Agent
```bash
composer require jenssegers/agent
```

ثم حدث التكوين في `config/auth_tracker.php`:

```php
'parser' => 'whichbrowser', // أو 'agent'
```

## 📖 الاستخدام

### استرجاع الجلسات

```php
// الحصول على جميع الجلسات
$logins = auth()->user()->logins;

// الحصول على الجلسة الحالية
$currentLogin = auth()->user()->currentLogin();

// الجلسات النشطة فقط
$activeLogins = auth()->user()->activeLogin();

// سجل الجلسات المنتهية
$historyLogins = auth()->user()->historyLogin();
```

### إدارة الجلسات

```php
// إلغاء جلسة محددة
auth()->user()->logout($loginId);

// إلغاء الجلسة الحالية
auth()->user()->logout();

// إلغاء جميع الجلسات
auth()->user()->logoutAll();

// إلغاء جميع الجلسات عدا الحالية
auth()->user()->logoutOthers();
```

### تتبع الأجهزة

```php
// في Middleware
Route::middleware(['store.device'])->group(function () {
    // Routes that require device tracking
});

// الحصول على معلومات الجهاز
$device = request()->device;
echo $device->os; // Android, iOS, Windows, etc.
echo $device->model; // iPhone 12, Samsung Galaxy, etc.
```

## 🔧 التكوين المتقدم

### إعداد تتبع الموقع الجغرافي

في `config/auth_tracker.php`:

```php
'ip_lookup' => [
    'provider' => 'ip-api', // أو 'ip2location-lite'
    'timeout' => 5.0,
    'environments' => ['production'],
],
```

### إضافة مزود موقع مخصص

```php
<?php

namespace App\Providers;

use Alshahari\AuthTracker\Interfaces\IpProvider;
use Alshahari\AuthTracker\Traits\MakesApiCalls;

class CustomIpProvider implements IpProvider
{
    use MakesApiCalls;

    public function getRequest()
    {
        return new Request('GET', 'https://api.example.com/ip/' . request()->ip());
    }

    public function getCountry()
    {
        return $this->result->get('country');
    }

    public function getRegion()
    {
        return $this->result->get('region');
    }

    public function getCity()
    {
        return $this->result->get('city');
    }
}
```

## 🎨 واجهة المستخدم

### Blade Directives

```blade
@tracked
    <a href="{{ route('login.list') }}">إدارة الجلسات</a>
@endtracked

@ipLookup
    <p>الموقع: {{ $login->location }}</p>
@endipLookup
```

### Routes المتاحة

```php
// إضافة routes تلقائياً
Route::authTracker();

// أو إضافة يدوياً
Route::prefix('security')->group(function () {
    Route::get('/', 'Auth\AuthTrackingController@listLogins')->name('login.list');
    Route::post('logout/all', 'Auth\AuthTrackingController@logoutAll')->name('logout.all');
    Route::post('logout/others', 'Auth\AuthTrackingController@logoutOthers')->name('logout.others');
    Route::post('logout/{id}', 'Auth\AuthTrackingController@logoutById')->name('logout.id');
});
```

## 📊 Events المتاحة

### Login Event

```php
use Alshahari\AuthTracker\Events\Login;

Event::listen(Login::class, function ($event) {
    $user = $event->user;
    $context = $event->context;
    
    // معلومات الجهاز
    $device = $context->parser()->getDevice();
    $platform = $context->parser()->getPlatform();
    
    // معلومات الموقع
    if ($context->ip()) {
        $country = $context->ip()->getCountry();
        $city = $context->ip()->getCity();
    }
});
```

## 🔒 الأمان

### Rate Limiting

```php
// في Middleware
Route::middleware(['throttle:device-tracking'])->group(function () {
    // Routes
});
```

### Validation

```php
// التحقق من صحة بيانات الجهاز
$request->validate([
    'x-device-udid' => 'required|string|max:40',
    'x-device-os' => 'nullable|string|max:15',
    'x-device-model' => 'nullable|string|max:100',
]);
```

## 🧪 الاختبار

```bash
# تشغيل الاختبارات
php artisan test

# أو باستخدام PHPUnit
./vendor/bin/phpunit
```

## 📈 الأداء

### تحسين الاستعلامات

```php
// استخدام Eager Loading
$logins = auth()->user()->logins()->with('device')->get();

// استخدام Select محدد
$logins = auth()->user()->logins()
    ->select(['id', 'created_at', 'ip', 'device_type'])
    ->get();
```

### إضافة Indexes

```php
// في migration
$table->index(['authenticatable_type', 'authenticatable_id']);
$table->index(['device_id']);
$table->index(['logout_at', 'cleared_by_user']);
```

## 🐛 استكشاف الأخطاء

### مشاكل شائعة

1. **لا يتم تتبع الجلسات**
   - تأكد من إضافة `AuthTracking` trait
   - تحقق من تكوين user provider

2. **مشاكل في تتبع الأجهزة**
   - تأكد من إرسال headers المطلوبة
   - تحقق من تكوين middleware

3. **مشاكل في الموقع الجغرافي**
   - تأكد من تفعيل IP lookup
   - تحقق من اتصال الإنترنت

## 🤝 المساهمة

نرحب بمساهماتكم! يرجى قراءة [دليل المساهمة](CONTRIBUTING.md) قبل البدء.

### خطوات المساهمة

1. Fork المشروع
2. إنشاء branch للميزة الجديدة
3. Commit التغييرات
4. Push إلى الـ branch
5. إنشاء Pull Request

## 📝 الترخيص

هذا المشروع مرخص تحت [MIT License](LICENSE).

## 👨‍💻 المؤلف

**أبوبكر الشهاري**
- البريد الإلكتروني: abobaker.m2017@gmail.com
- GitHub: [@abobakeralshahari](https://github.com/abobakeralshahari)

## 🙏 شكر وتقدير

- فريق Laravel
- مجتمع PHP
- جميع المساهمين

## 📞 الدعم

إذا واجهت أي مشاكل أو لديك اقتراحات، يرجى:

1. فتح [Issue](https://github.com/abobakeralshahari/laravel-auth-tracker/issues)
2. مراسلتنا على البريد الإلكتروني
3. الانضمام لمناقشات GitHub

---

**⭐ إذا أعجبك المشروع، لا تنس إعطاؤه نجمة!**
