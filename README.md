# Akademik Takip — Kurulum Kılavuzu

## Gereksinimler
- PHP 7.4+
- MySQL 5.7+ veya MariaDB
- Apache / Nginx (XAMPP / WAMP / LAMP)

---

## 1. Veritabanı Kurulumu

```bash
mysql -u root -p < akademik_takip.sql
```

Veya phpMyAdmin'den `akademik_takip.sql` dosyasını import edin.

---

## 2. Veritabanı Bağlantısı

`includes/db.php` dosyasını açın ve bilgilerinize göre düzenleyin:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');     // MySQL kullanıcı adı
define('DB_PASS', '');         // MySQL şifresi
define('DB_NAME', 'akademik_takip');
```

---

## 3. Proje Dizini

Projeyi şu konuma kopyalayın:
- **XAMPP**: `C:/xampp/htdocs/webProgramlama/Lab8/`
- **WAMP**: `C:/wamp64/www/webProgramlama/Lab8/`
- **Linux**: `/var/www/html/webProgramlama/Lab8/`

---

## 4. uploads/ Klasörü İzinleri (Linux/Mac)

```bash
chmod 755 uploads/
```

---

## 5. Tarayıcıda Açma

```
http://localhost/webProgramlama/Lab8/odev/login.php
```

---

## Varsayılan Test Kullanıcısı

| Alan | Değer |
|------|-------|
| E-posta | test@test.com |
| Şifre | password |

---

## Dosya Yapısı

```
Lab8/
├── akademik_takip.sql        ← Veritabanı şeması
├── index.php                 ← Otomatik yönlendirme
├── includes/
│   ├── db.php                ← Veritabanı bağlantısı
│   ├── auth.php              ← Session yönetimi
│   └── navbar.php            ← Ortak navigasyon
├── odev/
│   ├── style.css             ← Global stiller
│   ├── login.php             ← Giriş sayfası
│   ├── register.php          ← Kayıt sayfası
│   ├── logout.php            ← Çıkış
│   ├── projects.php          ← Proje yönetimi
│   ├── tasks.php             ← Görev yönetimi
│   ├── files.php             ← Dosya yönetimi
│   └── profile.php           ← Profil & Ayarlar
└── uploads/                  ← Yüklenen dosyalar (otomatik)
```

---

## Özellikler

- ✅ Kullanıcı kaydı ve girişi (şifreli, güvenli)
- ✅ "Beni Hatırla" cookie desteği
- ✅ Proje oluşturma, listeleme, silme
- ✅ Görev ekleme, durum güncelleme (Beklemede → Devam ediyor → Tamamlandı), silme
- ✅ Dosya yükleme ve indirme
- ✅ Profil bilgisi güncelleme
- ✅ Şifre değiştirme
- ✅ Açık / Koyu tema desteği
- ✅ Güvenli çıkış
