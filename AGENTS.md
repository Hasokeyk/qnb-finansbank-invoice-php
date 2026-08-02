# AGENTS.md

## Library: QNB eSolutions PHP SOAP Client

QNB eSolutions Özel Entegratör SOAP API'leri için PHP istemci kütüphanesi. e-Fatura, e-İrsaliye, e-Arşiv ve e-Defter işlemlerini kapsar.

- PHP >=8.2, ext-soap ve ext-mbstring gerekli
- Test ortamı: `erpefaturatest1.qnbesolutions.com.tr`
- Canlı: `connector.qnbesolutions.com.tr`, `earsiv.qnbesolutions.com.tr`

## Project structure

```
.
├── composer.json
├── AGENTS.md
├── index.php                            # GİTLİNİGORE: local scratch/demo, gerçek SOAP çağrısı yapar
├── src/
│   ├── client.php                       # Ana entrypoint (SoapClient + WSSE + lazy servisler)
│   ├── builder/
│   │   └── invoice_builder.php          # UBL fatura XML üretici (fluent API)
│   ├── exception/
│   │   ├── auth_exception.php
│   │   └── api_exception.php
│   ├── enum/
│   │   ├── document_type.php
│   │   ├── document_format.php
│   │   ├── archive_output_format.php
│   │   └── status_code.php
│   ├── model/
│   │   ├── document_status.php
│   │   ├── registered_user.php
│   │   ├── incoming_document.php
│   │   └── archive_result.php
│   └── service/
│       ├── user_service.php
│       ├── invoice_service.php
│       ├── despatch_service.php
│       ├── archive_service.php
│       └── ledger_service.php
├── tests/                                # Standalone PHP script'leri (PHPUnit DEĞİL)
│   ├── 001_builder_basit.php
│   ├── 002_builder_fatura_turleri.php
│   ├── 003_builder_ozellikler.php
│   └── 004_service_invoice.php           # Mock SoapClient ile
├── docs/
│   ├── 01-baslangic.md
│   ├── 02-efatura.md
│   ├── 03-eirsaliye.md
│   ├── 04-earsiv.md
│   └── 05-edefter.md
├── invoice-templates/
│   └── efatura.xslt                     # Özel tasarım XSLT (belge_gonder_ext xsltAdi+xsltVeri ile gönderilir)
└── .idea/                                # PHPStorm IDE config (PHP 8.2)
```

## Naming convention

All names: **snake_case** — files, classes, methods, variables, and namespaces. No PascalCase except the root vendor namespace (`QnbSolutions\QnbEsolutions\`). Class constants are **UPPER_SNAKE** (`invoice_builder::TYPE_SATIS`, `client::ENV_TEST`).

## Formatting gotcha

Indentation is inconsistent across files and must be preserved per-file: `src/client.php` and `index.php` use **tabs**; `src/service/*`, `src/builder/*`, and `tests/*` use **4 spaces**. Match the file you're editing.

## Key architecture

- `client` manages SOAP connections, auth, and lazy-service initialization. Construct with named args: `new client(username: ..., password: ..., environment: ..., auth_method: ...)`.
- Two auth modes: `AUTH_WSSE` (WSSE header per request) and `AUTH_COOKIE` (wsLogin + session cookie). WSSE credentials go in the header — `authenticate()` is only needed for cookie mode.
- Service classes map 1:1 with API product groups. They receive a `\SoapClient` and handle parameter mapping + response models. Methods accept **named args** (snake_case) that map to camelCase SOAP params (`vergi_tc_kimlik_no` → `vergiTcKimlikNo`).
- e-Fatura and e-İrsaliye share the same SOAP `connectorService` but differ in `belgeTuru` values.
- e-Arşiv (`fatura_olustur_ext`) is synchronous — returns result inline.
- e-Defter uses separate `e_defter_*` methods on the same connector service.
- ERP Kodu: test ortamında `ERP1` veya `ERP2` kullanılır (`erp_kodu` parametresinde veya `erpBilgileriBelirle` metodunda). ERP kodu hesaba özeldir; canlı ortamda projeye tanımlı farklı bir kod olur.
- Rate limit: saniyede 5 request.
- Test1 ve Test2 yalnızca birbirleriyle belge alışverişi yapabilir (test1→test2, test2→test1). Aynı ortam içi gönderim mümkün değildir.
- WS için ayrı kullanıcı oluşturma: `wsKullanicisiKaydet` metodu veya portal üzerinden Yönetim/Genel/Kullanıcı Tanımları.
- Uyarı: Portal şifre değişikliği WS şifresini etkilemez, ancak eski şifreyle WS login yapan uygulama kullanıcıyı bloke edebilir. Portal ve WS kullanıcılarının ayrılması önerilir.

## Verified gotchas (canlı test ile doğrulandı)

- **SoapClient çift-base64 tuzağı**: WSDL'de `veri`/`belgeIcerigi`/`dosya`/`xsltVeri` alanları `base64Binary` tiptedir. PHP `SoapClient` string değeri **kendi base64'ler** → caller'ın verdiği base64 çift encode olur, server hash doğrulamasında "Hash hatası" döner. Çözüm: servisler `base64_decode($veri_base64, true)` sonucunu geçirir (SoapClient tekrar encode eder). `invoice_service` ve `despatch_service`'in `belge_gonder_ext`'inde (`veri` + `xsltVeri`) düzeltildi ve canlı test ile doğrulandı; `archive_service` (`belgeIcerigi`) ve `ledger_service` (`dosya`) de aynı riski taşır — test edilmedi.
- **Hash formülü**: `belge_hash_md5` = `strtoupper(md5($xml))` — ham UBL XML üzerinde, BÜYÜK harf hex MD5. Küçük harf veya farklı içerik → SoapFault "Hash hatası".
- **ERP kodu**: test ortamında `ERP1`/`ERP2` geçerlidir — `ERP1` ile test2 ortamında canlı doğrulandı (fatura işlendi, `durum:3`). ERP kodu **hesaba özeldir**: bir ortamda geçerli kod başkasında geçersiz olabilir, bu yüzden varsayım yapılmaz, müşteriden alınır.
- **InvoiceTypeCode sıkı GİB kod listesidir**: `TICARI` ve `KDV_MUAFIYETI` geçersiz (`schematronControl failed: Geçersiz cbc:InvoiceTypeCode`). `TICARIFATURA` bir `ProfileID`'dir, tip değil. QNB paneldeki **InvoiceTypeCode** listesi: `SATIS, IADE, TEVKIFAT, TEVKIFATIADE, ISTISNA, OZELMATRAH, IHRACKAYITLI, SGK, KOMISYONCU, KONAKLAMAVERGISI`. **ProfileID** listesi: `TEMELFATURA, TICARIFATURA, KAMU, IHRACAT, YOLCUBERABERFATURA, HKS, ENERJI, ILAC_TIBBICIHAZ, YATIRIMTESVIK, IDIS`. Builder listeleri: `invoice_builder::INVOICE_TYPE_CODES`, `invoice_builder::PROFILE_IDS`. KDV muafiyet kodu `301` olan fatura tipi `ISTISNA` (veya `IADE,IHRACKAYITLI,SGK,YTBIADE,YTBISTISNA`) olmalıdır.
- **test1 içi gönderim yasak**: aynı ortamda kendi VKN'ne gönderim belgeyi kabul ettirmez — ETN atanır, `durum:3`, ama `gonderimCevabiKodu 1172 (TPS_POSTA_KUTUSU_YETKISI_YOK)` ile teslim edilemez. Alıcı test2 VKN'si (6312064091) olmalıdır. Başarılı teslimat: `durum:3` + `gonderimCevabiKodu:0` + boş `gonderimCevabiDetayi`.
- **test2 WS kimliği**: eski kayıtlı şifre (`w5r4mr1v65lm01+`) `username.pwd.mismatch` veriyordu; kullanıcı tarafından güncel şifre `Prescripto2026.` olarak bildirildi.

## Endpoints

| Environment | Service | WSDL |
|-------------|---------|------|
| test1 | user / connector | `erpefaturatest1.../efatura/ws/{userService,connectorService}?wsdl` |
| test1 | archive user | `connectortest.../connector/ws/userService?wsdl` |
| test1 | archive | `earsivtest.../earsiv/ws/EarsivWebService?wsdl` |
| test2 | user / connector | `erpefaturatest2.../efatura/ws/{userService,connectorService}?wsdl` |
| test2 | archive user | `connectortest.../connector/ws/userService?wsdl` |
| test2 | archive | `earsivtest.../earsiv/ws/EarsivWebService?wsdl` |
| prod | user / connector | `connector.qnbesolutions.com.tr/connector/ws/connectorService?wsdl` |
| prod | archive | `earsiv.qnbesolutions.com.tr/earsiv/ws/EarsivWebService?wsdl` |

URLs are the single source of truth in `src/client.php` `client::URLS`.

## Test credentials

| Unit | VKN | User | Password | ERP Kodu |
|------|-----|------|----------|----------|
| Test1 (e-Fatura/İrsaliye/Defter) | 6312064090 | 6312064090 | Prescripto2026. | ERP1/ERP2 |
| Test2 (e-Fatura/İrsaliye/Defter) | 6312064091 | 6312064091 | Prescripto2026. | ERP1/ERP2 |
| e-Arşiv Test | 6312064090 | 6312064090.portaltest | 6bm3z9KL (Hasan Yüksektepe) | — |

## Commands

```bash
php tests/001_builder_basit.php        # builder testleri (001–003)
php tests/004_service_invoice.php      # mock SOAP ile service testi
composer validate                      # composer.json doğrulama
composer dump-autoload                 # autoload güncelle
```

Composer scripts **work only partially** — don't rely on them:
- `composer test` (=`phpunit`) runs nothing: `tests/` are standalone scripts, not PHPUnit; no `phpunit.xml` exists.
- `composer lint` (=`phpcs`) and `composer stan` (=`phpstan`) **fail**: phpcs/phpstan are not installed (only `phpunit/phpunit` is in require-dev).

## Doc sources

Full API reference (SOAP XML, C#, Java samples) at:
`https://www.qnbesolutions.com.tr/api-docs-tr-final.html`
