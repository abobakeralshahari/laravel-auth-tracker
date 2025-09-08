# دليل المساهمة

شكراً لك على اهتمامك بالمساهمة في Laravel Auth Tracker! 

## 🤝 طرق المساهمة

### 1. الإبلاغ عن الأخطاء
- استخدم [GitHub Issues](https://github.com/abobakeralshahari/laravel-auth-tracker/issues)
- اتبع قالب الإبلاغ عن الأخطاء
- قدم معلومات مفصلة عن المشكلة

### 2. اقتراح ميزات جديدة
- افتح Issue جديد مع وصف الميزة
- اشرح الفائدة من الميزة
- قدم أمثلة على الاستخدام

### 3. المساهمة بالكود
- Fork المشروع
- أنشئ branch جديد للميزة
- اتبع معايير الكود
- أضف اختبارات للميزة الجديدة

## 📋 معايير الكود

### PHP Standards
- اتبع PSR-12
- استخدم Type Hints
- أضف PHPDoc comments
- استخدم meaningful variable names

### Laravel Standards
- اتبع Laravel conventions
- استخدم Eloquent relationships بشكل صحيح
- استخدم Laravel's built-in features
- اتبع security best practices

### Git Standards
- استخدم commit messages واضحة
- استخدم conventional commits
- اجعل commits صغيرة ومحددة
- اكتب descriptive pull request

## 🧪 الاختبار

### متطلبات الاختبار
- جميع الميزات الجديدة يجب أن تحتوي على اختبارات
- يجب أن تمر جميع الاختبارات الموجودة
- استخدم PHPUnit للاختبارات

### تشغيل الاختبارات
```bash
# تشغيل جميع الاختبارات
./vendor/bin/phpunit

# تشغيل اختبارات محددة
./vendor/bin/phpunit tests/Feature/AuthTrackerTest.php
```

## 📝 التوثيق

### تحديث التوثيق
- حدث README عند إضافة ميزات جديدة
- أضف أمثلة للاستخدام
- حدث CHANGELOG.md
- أضف PHPDoc comments

### معايير التوثيق
- استخدم اللغة العربية في التوثيق
- اجعل الأمثلة واضحة ومفهومة
- أضف روابط للموارد الخارجية
- استخدم Markdown formatting

## 🔒 الأمان

### إبلاغ مشاكل الأمان
- لا تفتح public issues للمشاكل الأمنية
- أرسل بريد إلكتروني إلى: abobaker.m2017@gmail.com
- اشرح المشكلة بالتفصيل
- انتظر الرد قبل النشر العام

### معايير الأمان
- لا تضع credentials في الكود
- استخدم validation مناسب
- اتبع Laravel security guidelines
- استخدم HTTPS في الأمثلة

## 🚀 عملية المساهمة

### 1. إعداد البيئة
```bash
# Clone المشروع
git clone https://github.com/abobakeralshahari/laravel-auth-tracker.git

# تثبيت dependencies
composer install

# إعداد البيئة
cp .env.example .env
php artisan key:generate
```

### 2. إنشاء Branch
```bash
# إنشاء branch جديد
git checkout -b feature/your-feature-name

# أو
git checkout -b bugfix/your-bugfix-name
```

### 3. تطوير الميزة
- اكتب الكود
- أضف الاختبارات
- حدث التوثيق
- تأكد من جودة الكود

### 4. اختبار التغييرات
```bash
# تشغيل الاختبارات
./vendor/bin/phpunit

# فحص جودة الكود
./vendor/bin/phpcs

# فحص الأمان
./vendor/bin/phpstan
```

### 5. إنشاء Pull Request
- اكتب وصف واضح للتغييرات
- اربط Issues ذات الصلة
- أضف screenshots إذا لزم الأمر
- انتظر المراجعة

## 📋 قوالب

### قالب Issue
```markdown
## الوصف
وصف مختصر للمشكلة أو الميزة

## الخطوات لإعادة إنتاج المشكلة
1. اذهب إلى...
2. اضغط على...
3. لاحظ أن...

## السلوك المتوقع
ما الذي توقعته أن يحدث؟

## السلوك الفعلي
ما الذي حدث فعلاً؟

## معلومات إضافية
- إصدار Laravel:
- إصدار PHP:
- نوع قاعدة البيانات:
- معلومات أخرى ذات صلة
```

### قالب Pull Request
```markdown
## الوصف
وصف مختصر للتغييرات

## نوع التغيير
- [ ] إصلاح خطأ
- [ ] ميزة جديدة
- [ ] تحسين
- [ ] توثيق
- [ ] اختبارات

## الاختبارات
- [ ] تم اختبار التغييرات محلياً
- [ ] تم تشغيل جميع الاختبارات
- [ ] تم اختبار التوافق مع Laravel versions

## التوثيق
- [ ] تم تحديث README
- [ ] تم تحديث CHANGELOG
- [ ] تم إضافة PHPDoc comments
```

## 🎯 أولويات التطوير

### عالية الأولوية
- إصلاح الأخطاء الحرجة
- تحسينات الأمان
- تحسينات الأداء
- دعم Laravel versions الجديدة

### متوسطة الأولوية
- ميزات جديدة مفيدة
- تحسينات في UX
- تحسينات في التوثيق
- إضافة اختبارات

### منخفضة الأولوية
- تحسينات في الكود
- إضافة أمثلة
- تحسينات في UI
- ميزات تجريبية

## 📞 التواصل

### قنوات التواصل
- GitHub Issues للمناقشات العامة
- البريد الإلكتروني للمسائل الخاصة
- GitHub Discussions للمناقشات الطويلة

### أوقات الاستجابة
- Issues: خلال 48 ساعة
- Pull Requests: خلال أسبوع
- البريد الإلكتروني: خلال 24 ساعة

## 🙏 شكر وتقدير

نقدر مساهماتكم ونشكركم على جعل هذا المشروع أفضل!

---

**تذكر: كل مساهمة، مهما كانت صغيرة، مهمة ومقدرة!** 🌟
