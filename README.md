# Barbershop SaaS - Backend API

نظام إدارة الحلاقة المتكامل مع دعم Multi-tenancy و Stripe Integration.

## 🚀 الميزات الرئيسية

- ✅ **المصادقة الآمنة** - Laravel Sanctum مع JWT Tokens
- ✅ **مصادقة ثنائية (2FA)** - TOTP و Backup Codes
- ✅ **أجهزة موثوقة** - Trust Device للتخطي السريع
- ✅ **نظام الاشتراكات** - Plans متعددة مع Stripe Integration
- ✅ **نظام الكوبونات** - Discount Codes و Promotional Offers
- ✅ **Multi-tenancy** - كل عميل لديه قاعدة بيانات منفصلة
- ✅ **إدارة الملفات الشخصية** - Profile Completion Tracking
- ✅ **معالجة الأخطاء الموحدة** - Consistent Error Responses

## 📋 المتطلبات

- PHP 8.1+
- Laravel 10
- MySQL 8.0+
- Redis (اختياري، للـ Caching)
- Composer

## 🔧 التثبيت

### 1. استنساخ المشروع

```bash
git clone <repository-url>
cd barbershop-saas/backend
```

### 2. تثبيت المكتبات

```bash
composer install
```

### 3. إعداد متغيرات البيئة

```bash
cp .env.example .env
php artisan key:generate
```

### 4. تحديث ملف `.env`

```env
APP_NAME="Barbershop SaaS"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=barbershop_master
DB_USERNAME=root
DB_PASSWORD=

STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

### 5. تشغيل الترحيلات

```bash
php artisan migrate
```

### 6. بدء خادم التطوير

```bash
php artisan serve
```

سيكون التطبيق متاحاً على `http://localhost:8000`

## 📚 API Documentation

### المصادقة (Authentication)

#### تسجيل مستخدم جديد
```http
POST /api/v1/auth/signup
Content-Type: application/json

{
  "full_name": "أحمد محمد",
  "business_name": "حلاقة الملك",
  "business_address": "شارع النيل",
  "email": "ahmed@example.com",
  "phone": "+966501234567",
  "password": "SecurePassword123",
  "password_confirmation": "SecurePassword123"
}
```

**الرد:**
```json
{
  "success": true,
  "message": "Registration successful. Please verify your email.",
  "user": { ... },
  "token": "1|abc123..."
}
```

#### تسجيل الدخول
```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "ahmed@example.com",
  "password": "SecurePassword123"
}
```

**الرد (بدون 2FA):**
```json
{
  "success": true,
  "requires_two_factor": false,
  "user": { ... },
  "token": "1|abc123..."
}
```

**الرد (مع 2FA):**
```json
{
  "success": true,
  "requires_two_factor": true,
  "user_id": 1
}
```

#### التحقق من كود 2FA
```http
POST /api/v1/auth/verify-2fa
Content-Type: application/json

{
  "user_id": 1,
  "code": "123456",
  "trust_device": true
}
```

#### الحصول على بيانات المستخدم الحالي
```http
GET /api/v1/auth/me
Authorization: Bearer {token}
```

### الاشتراكات (Subscriptions)

#### الحصول على حالة الـ Checkout
```http
GET /api/v1/checkout/status
Authorization: Bearer {token}
```

**الرد:**
```json
{
  "success": true,
  "has_active_subscription": false,
  "active_subscription": null,
  "pending_payment": {
    "subscription_id": 1,
    "plan_id": "professional",
    "price": 79.00,
    "failed_attempts": 1,
    "message": "لديك محاولة شراء معلقة..."
  }
}
```

#### إنشاء جلسة Checkout
```http
POST /api/v1/checkout/create-session
Authorization: Bearer {token}
Content-Type: application/json

{
  "plan_id": "professional",
  "coupon_id": null
}
```

**الرد:**
```json
{
  "success": true,
  "session_id": "cs_test_...",
  "subscription_id": 1
}
```

#### تطبيق كوبون
```http
POST /api/v1/checkout/apply-coupon
Content-Type: application/json

{
  "plan_id": "professional",
  "coupon_code": "SAVE20"
}
```

### Webhooks

#### معالجة أحداث Stripe
```http
POST /api/v1/webhooks/stripe
Content-Type: application/json
Stripe-Signature: t=...,v1=...

{
  "id": "evt_...",
  "type": "checkout.session.completed",
  "data": { ... }
}
```

## 🏗️ البنية المعمارية

### Models
- **User** - المستخدم الرئيسي
- **Tenant** - صاحب الحلاقة (مستأجر)
- **Subscription** - الاشتراك
- **Payment** - السجل المالي
- **Coupon** - الكوبونات
- **TrustedDevice** - الأجهزة الموثوقة

### Services
- **AuthService** - منطق المصادقة
- **SubscriptionService** - إدارة الاشتراكات
- **SubscriptionValidationService** - التحقق من الاشتراكات
- **TwoFactorAuthService** - المصادقة الثنائية
- **TrustedDeviceService** - إدارة الأجهزة الموثوقة
- **PaymentFailureService** - معالجة فشل الدفع
- **PasswordResetService** - إعادة تعيين كلمة المرور

### Controllers
- **AuthController** - نقاط نهاية المصادقة
- **CheckoutController** - نقاط نهاية الدفع
- **SubscriptionController** - إدارة الاشتراكات
- **StripeWebhookController** - معالجة أحداث Stripe

## 🔐 الأمان

### معايير الأمان المطبقة

1. **تشفير كلمات المرور** - bcrypt
2. **JWT Tokens** - Laravel Sanctum
3. **CSRF Protection** - Laravel CSRF Middleware
4. **Rate Limiting** - على نقاط النهاية الحساسة
5. **Email Verification** - التحقق من البريد الإلكتروني
6. **2FA** - مصادقة ثنائية اختيارية
7. **Trusted Devices** - تخطي 2FA على الأجهزة الموثوقة

## 📊 قاعدة البيانات

### الترحيلات الرئيسية
- `2024_01_01_000001_create_users_table.php`
- `2024_01_01_000002_create_tenants_table.php`
- `2024_01_01_000003_create_subscriptions_table.php`
- `2024_01_01_000004_create_coupons_table.php`
- `2024_01_01_000005_create_payments_table.php`
- `2025_12_11_152247_add_grace_period_and_suspension_to_subscriptions_table.php`
- `2025_12_11_162557_add_two_factor_auth_to_users_table.php`
- `2025_12_12_094925_create_trusted_devices_table.php`
- `2025_12_17_000001_add_pending_subscription_tracking.php` (جديد)

## 🔄 Stripe Integration

### الأحداث المدعومة
- `checkout.session.completed` - إنشاء اشتراك
- `customer.subscription.updated` - تحديث الاشتراك
- `customer.subscription.deleted` - حذف الاشتراك
- `invoice.payment_failed` - فشل الدفع

### إعداد Webhooks
1. انتقل إلى [Stripe Dashboard](https://dashboard.stripe.com)
2. اذهب إلى Webhooks
3. أضف نقطة نهاية جديدة: `https://yourdomain.com/api/v1/webhooks/stripe`
4. اختر الأحداث المطلوبة
5. انسخ Signing Secret إلى `.env`

## 🧪 الاختبار

### تشغيل الاختبارات
```bash
php artisan test
```

### اختبار Stripe Webhooks محلياً
```bash
stripe listen --forward-to localhost:8000/api/v1/webhooks/stripe
```

## 📝 الملاحظات المهمة

### الميزات الجديدة (2025-12-17)

1. **منع الاشتراكات المتعددة**
   - المستخدم لا يمكنه شراء اشتراك جديد إذا كان لديه اشتراك نشط
   - يتم إرجاع رسالة خطأ واضحة مع تفاصيل الاشتراك الحالي

2. **آلية استكمال الشراء بعد فشل الدفع**
   - إذا فشل الدفع، يتم حفظ الاشتراك بحالة `payment_failed`
   - المستخدم يمكنه الدخول للداشبورد (بعد التحقق من البريد)
   - يمكنه استكمال الشراء من الداشبورد أو من صفحة الدفع

3. **تتبع محاولات الدفع الفاشلة**
   - حقل `failed_payment_attempts` لتتبع عدد المحاولات
   - حقل `last_payment_error` لتسجيل سبب الفشل

## 🐛 استكشاف الأخطاء

### الأخطاء الشائعة

**خطأ: SQLSTATE[HY000] [2002]**
- تأكد من تشغيل MySQL
- تحقق من بيانات الاتصال في `.env`

**خطأ: Stripe API Key not found**
- تأكد من تعيين `STRIPE_KEY` و `STRIPE_SECRET` في `.env`

**خطأ: Email verification not working**
- تأكد من إعدادات البريد في `.env`
- استخدم `MAIL_MAILER=log` للتطوير

## 📞 الدعم

للمساعدة أو الإبلاغ عن مشاكل، يرجى فتح issue على GitHub.

---

**آخر تحديث:** 17 ديسمبر 2025
