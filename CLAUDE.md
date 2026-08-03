# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Source of truth

**Read `AGENTS.md` first.** It is the authoritative, well-maintained reference for this repo — architecture, naming, endpoints, test credentials, and the verified gotchas. This file only adds the operational essentials; `AGENTS.md` has the full detail.

## What this is

QNB eSolutions Özel Entegratör SOAP API'leri için PHP istemci kütüphanesi: e-Fatura, e-İrsaliye, e-Arşiv, e-Defter. PHP >=8.2, ext-soap + ext-mbstring gerekli. PSR-4 namespace `QnbSolutions\QnbEsolutions\` → `src/`.

## Commands

```bash
php tests/001_builder_basit.php        # builder testleri (001–003; standalone script, PHPUnit DEĞİL)
php tests/004_service_invoice.php      # mock SoapClient ile service testi (canlı çağrı yok)
composer validate                      # composer.json doğrulama
composer dump-autoload                 # autoload güncelle
```

`composer test` (=`phpunit`), `composer lint` (=`phpcs`), `composer stan` (=`phpstan`) **çalışmaz** — bunlar standalone script'lerdir; phpcs/phpstan kurulu değil, phpunit.xml yok. Bu komutlara güvenme.

## Naming & formatting

- Her şey **snake_case** (dosya, sınıf, metot, değişken, namespace). Sadece kök vendor namespace `QnbSolutions\QnbEsolutions\` PascalCase. Sınıf sabitleri **UPPER_SNAKE** (`client::ENV_TEST`, `invoice_builder::TYPE_SATIS`).
- Girinti dosyaya göre değişir ve **korunmalıdır**: `src/client.php` ve `index.php` → **tabs**; `src/service/*`, `src/builder/*`, `tests/*` → **4 boşluk**. Düzenlediğin dosyayı eşleştir.
- Servis metotları **named args** (snake_case) alır, SOAP'a camelCase'e çevrilir (`vergi_tc_kimlik_no` → `vergiTcKimlikNo`).

## Architecture (kısa)

- `client` SOAP bağlantıları + lazy servis örneklemesini yönetir. Named args: `new client(username:, password:, environment:, auth_method:)`.
- İki auth modu: `AUTH_WSSE` (her istekte WSSE header; `authenticate()` gerekmez) ve `AUTH_COOKIE` (wsLogin + session cookie; `authenticate()` şart).
- Servisler API ürün gruplarıyla 1:1: `user()`, `invoice()`, `despatch()`, `archive()`, `ledger()`. Her biri bir `\SoapClient` alır, parametre map'leme + model dönüşü yapar.
- e-Fatura ve e-İrsaliye aynı `connectorService`'i paylaşır, `belgeTuru` farklıdır. e-Arşiv senkron. e-Defter ayrı `e_defter_*` metotları.
- UBL XML `src/builder/invoice_builder.php` fluent API ile üretilir; `index.php` scratch/demo (gerçek SOAP çağrısı yapar).

## Kritik gotchas (özet — detay AGENTS.md'de)

- **Çift-base64 tuzağı**: WSDL'de `veri`/`belgeIcerigi`/`dosya`/`xsltVeri` `base64Binary` tiptir; PHP `SoapClient` string değeri kendi encode eder. Caller `base64_decode($veri_base64, true)` sonucunu geçirmeli, yoksa "Hash hatası" döner.
- **Hash formülü**: `belge_hash_md5 = strtoupper(md5($xml))` — ham UBL XML üzerinde, BÜYÜK harf hex.
- **ERP kodu**: canlı ortamda hesaba özel bir ERP kodu gerekir; `ERP1`/`ERP2` reddedilir. Kendi kodunu `erp_kodu` parametresinden ver.
- **InvoiceTypeCode sıkı GİB listesidir**: `TICARI`, `KDV_MUAFIYETI` geçersiz. `TYPE_TICARI='SATIS'`, `TYPE_KDV_MUAFIYETI='ISTISNA'`.
- **test1 içi gönderim yasak**: alıcı diğer ortamın VKN'si olmalı.
- Endpoint URL'lerinin tek kaynağı `src/client.php` `client::URLS`. Kimlik bilgileri ve tam endpoint tablosu `AGENTS.md`'de.
