# QNB eSolutions PHP SOAP Client

**QNB eSolutions Özel Entegratör SOAP API'leri için PHP istemci kütüphanesi**

e-Fatura, e-İrsaliye, e-Arşiv ve e-Defter işlemlerini tek bir kütüphane üzerinden yürütmenizi sağlar. UBL 2.1 fatura XML'lerini fluent bir builder ile üretir, SOAP servislerine gönderir ve sonuçları tipik model nesneleri olarak döndürür.

> 🌐 İngilizce sürüm için bkz. [`README.en.md`](README.en.md).

---

## İçindekiler

- [Özellikler](#özellikler)
- [Gereksinimler](#gereksinimler)
- [Kurulum](#kurulum)
- [Hızlı Başlangıç](#hızlı-başlangıç)
- [Mimari](#mimari)
- [Kullanım Rehberi](#kullanım-rehberi)
- [Önemli Notlar](#önemli-notlar)
- [Testler](#testler)
- [Dokümantasyon](#dokümantasyon)
- [Katkı](#katkı)
- [Lisans](#lisans)

---

## Özellikler

| Modül | Servis | Açıklama |
|-------|--------|----------|
| **e-Fatura** | `invoice()` | UBL fatura gönderme, durum sorgulama, gelen/giden belge listeleme & indirme |
| **e-İrsaliye** | `despatch()` | İrsaliye gönderme, durum sorgulama, gelen/giden belge listeleme & indirme |
| **e-Arşiv** | `archive()` | Senkron `fatura_olustur_ext` — UBL/HTML/PDF dönüş |
| **e-Defter** | `ledger()` | CSV/XML defter dosyası yükleme, durum sorgulama, işleme |

- **Fluent UBL 2.1 builder** (`invoice_builder`) — satırlar, KDV, iskonto, muafiyet/istisna kodları, teslim/ödeme koşulları
- **İki kimlik doğrulama yöntemi**: WSSE UsernameToken (her istekte header) veya Cookie oturumu
- **Üç ortam**: Test, Test2 ve Canlı — `client::ENV_TEST`, `client::ENV_TEST2`, `client::ENV_PROD`
- **Tipik model dönüşleri**: `registered_user`, `document_status`, `incoming_document`, `archive_result`
- **Özel fatura tasarımı**: `belge_gonder_ext` ile `xsltAdi` + `xsltVeri` (bkz. `invoice-templates/efatura.xslt`)

---

## Gereksinimler

- PHP **>= 8.2**
- `ext-soap`
- `ext-mbstring`
- `composer`

---

## Kurulum

```bash
composer require hasokeyk/qnb-finansbank-invoice
```

> Paket şu anda Packagist üzerinde yayınlanmamışsa, `composer.json`'da bir VCS (git) repository kaynağı tanımlayıp aynı komutla kurabilirsiniz.

---

## Hızlı Başlangıç

```php
<?php

require 'vendor/autoload.php';

use QnbSolutions\QnbEsolutions\client;
use QnbSolutions\QnbEsolutions\builder\invoice_builder;

// 1) İstemciyi oluştur — kimlik bilgilerini kendi değerlerinizle değiştirin
$c = new client(
    username: 'KULLANICI_ADI',
    password: 'SIFRE',
    environment: client::ENV_TEST,   // ENV_TEST | ENV_TEST2 | ENV_PROD
    auth_method: client::AUTH_WSSE,  // AUTH_WSSE | AUTH_COOKIE
);

// 2) UBL 2.1 fatura XML'i üret (fluent builder)
$xml = (new invoice_builder)
    ->set_fatura_no('NXT2026000000001')
    ->set_tarih(date('Y-m-d'))
    ->set_fatura_turu(invoice_builder::TYPE_SATIS)
    ->set_profil(invoice_builder::PROFILE_TICARI)
    ->set_satici(vkn: 'VKN', unvan: 'FIRMA ÜNVANI')
    ->set_satici_adres('Adres Sokak No:1', 'İlçe', 'İl')
    ->set_alici(vkn: 'ALICI_VKN', unvan: 'ALICI ÜNVANI')
    ->add_satir(isim: 'Hizmet', miktar: 1, birim_fiyat: 1000.00, kdv_oran: 20)
    ->build();

// 3) Gönder — hash formülüne dikkat: strtoupper(md5($xml))
$oid = $c->invoice()->belge_gonder_ext(
    vergi_tc_kimlik_no: 'VKN',
    belge_no: 'NXT2026000000001',
    veri_base64: base64_encode($xml),
    belge_hash_md5: strtoupper(md5($xml)),
    belge_versiyon: '2.1',
    erp_kodu: 'ERP_KODU',
);

echo "Gönderildi! Belge OID: {$oid}\n";
```

---

## Mimari

```
src/
├── client.php                 # Ana entrypoint — SoapClient + WSSE + lazy servisler
├── builder/
│   └── invoice_builder.php    # UBL 2.1 fatura XML üretici (fluent API)
├── service/                   # API ürün gruplarıyla 1:1 eşleşen servisler
│   ├── user_service.php       #   wsLogin / logout
│   ├── invoice_service.php    #   e-Fatura
│   ├── despatch_service.php   #   e-İrsaliye
│   ├── archive_service.php    #   e-Arşiv
│   └── ledger_service.php     #   e-Defter
├── model/                     # Tipik dönüş nesneleri (document_status, incoming_document, …)
├── enum/                      # Tip/format/statü sabitleri
└── exception/                 # auth_exception, api_exception
```

**`client`**: SOAP bağlantılarını ve lazy servis örneklemesini yönetir. Her servis, ortama göre doğru WSDL'ye bağlanan ayrı bir `\SoapClient` üzerinde çalışır. URL'lerin tek kaynağı `client::URLS`'tir.

**Servisler**: Her biri bir `\SoapClient` alır; parametre eşleme ve model dönüşünü üstlenir. Metotlar **named args** (snake_case) alır ve SOAP parametrelerine **camelCase** olarak çevrilir (örn. `vergi_tc_kimlik_no` → `vergiTcKimlikNo`).

**e-Fatura ve e-İrsaliye** aynı SOAP `connectorService`'i paylaşır; `belge_turu` değerine göre ayrışır. **e-Arşiv** (`fatura_olustur_ext`) senkrondur — sonucu doğrudan döndürür. **e-Defter** aynı connector servisi üzerinde ayrı `e_defter_*` metotları kullanır.

---

## Kullanım Rehberi

### Kimlik Doğrulama

İki yöntem vardır:

- **`AUTH_WSSE`** (varsayılan): Her SOAP isteğine bir `wsse:Security` UsernameToken başlığı eklenir. `authenticate()` çağrısı **gerekmez**.
- **`AUTH_COOKIE`**: Önce `ws_login` ile oturum açılır, dönen session cookie'si sonraki isteklerde kullanılır. Bu modda `authenticate()` **şarttır**.

```php
$c = new client(username: 'U', password: 'P', environment: client::ENV_TEST, auth_method: client::AUTH_COOKIE);
$c->authenticate();
```

### e-Fatura

```php
$svc = $c->invoice();

// Alıcı mükellef mi?
$u = $svc->efatura_kullanici_bilgisi('1234567890');
echo $u->unvan;

// Kayıtlı kullanıcı listesi (base64 zip)
$zip = $svc->kayitli_kullanici_listele_extended();

// Durum sorgula
$st = $svc->giden_belge_durum_sorgula_ext('1234567890', 'NXT2026000000001', 'OID');
echo $st->gonderim_cevabi_kodu;

// Gelen belgeleri listele
$docs = $svc->gelen_belgeleri_listele_ext('1234567890');
foreach ($docs as $d) {
    echo $d->ettn . ' — ' . $d->satici_unvan . PHP_EOL;
}

// Gelen belgeyi indir (base64 zip)
$zip = $svc->gelen_belgeleri_indir_ext('1234567890', $ettn);
```

### e-Arşiv (senkron)

```php
use QnbSolutions\QnbEsolutions\enum\archive_output_format;

$res = $c->archive()->fatura_olustur_ext(
    belge_icerigi_base64: base64_encode($xml),
    donen_belge_formati: archive_output_format::PDF,
    vkn: '1234567890',
    erp_kodu: 'ERP_KODU',
    numara_verilsin_mi: 1,   // 1: sistem üretsin, 0: ERP göndersin
);

echo $res->fatura_no;          // e-Arşiv fatura numarası
echo $res->fatura_url;         // görüntüleme URL'si
echo $res->output_base64;      // istenen formatta dönüş belgesi (base64)
```

### e-Defter

```php
$r = $c->ledger()->e_defter_csv_file_yukle(
    donem: '202601',
    vkn_tckn: '1234567890',
    sube_kodu: '0000',
    dosya_ismi: 'defter_202601.zip',
    dosya_base64: base64_encode($zip),
);
echo $r['sonuc_kodu'] . ' ' . $r['sonuc_aciklama'];
```

### Özel Fatura Tasarımı

`belge_gonder_ext` ile kendi XSLT'nizi gönderebilirsiniz (bkz. `invoice-templates/efatura.xslt`):

```php
$xslt = file_get_contents(__DIR__ . '/invoice-templates/efatura.xslt');

$oid = $c->invoice()->belge_gonder_ext(
    vergi_tc_kimlik_no: 'VKN',
    belge_no: 'NXT2026000000001',
    veri_base64: base64_encode($xml),
    belge_hash_md5: strtoupper(md5($xml)),
    belge_versiyon: '2.1',
    erp_kodu: 'ERP_KODU',
    xslt_adi: 'efatura.xslt',
    xslt_veri_base64: base64_encode($xslt),
);
```

---

## Önemli Notlar

> Canlı test ile doğrulanmış kritik noktalar. Daha fazla ayrıntı için `AGENTS.md`'ye bakın.

- **Çift-base64 tuzağı**: WSDL'de `veri`/`belgeIcerigi`/`dosya`/`xsltVeri` alanları `base64Binary` tiptedir; PHP `SoapClient` string değerini kendi base64'ler. Bu yüzden servisler, sizin verdiğiniz `*_base64`'i **kendi içinde `base64_decode`** edip SoapClient'ın tekrar encode etmesine bırakır. Base64'ü çözmeden geçerseniz çift-encode oluşur ve sunucu "Hash hatası" döner.
- **Hash formülü**: `belge_hash_md5 = strtoupper(md5($xml))` — **ham UBL XML** üzerinde, **BÜYÜK harf** hex MD5. Küçük harf veya farklı içerik → `SoapFault "Hash hatası"`.
- **ERP kodu**: Özel entegratör ERP kodunuzu kullanın (`erp_kodu` veya `erpBilgileriBelirle`). `ERP1`/`ERP2` gibi değerler sunucu tarafından reddedilir.
- **InvoiceTypeCode GİB'in sıkı kod listesidir**: `TICARI` ve `KDV_MUAFIYETI` **geçersizdir** (şema kontrolü hata verir). `TICARIFATURA` bir `ProfileID`'dir, tip değildir. Geçerli tipler: `SATIS, IADE, ISTISNA, TEVKIFAT, IHRACAT, …`. Builder sabitleri: `TYPE_TICARI='SATIS'`, `TYPE_KDV_MUAFIYETI='ISTISNA'`. KDV muafiyet/istisna kodlu satır içeren faturalar `MUAFIYET_UYUMLU_TIPLER` listesindeki tiplerden biri olmalıdır.
- **Test ortamları yalnızca birbirine gönderebilir**: Test1 ve Test2 VKN'leri yalnızca **karşı ortama** gönderim kabul eder; aynı ortam içi gönderim teslim edilemez (`gonderimCevabiKodu 1172`). Başarılı teslimat: `durum:3` + `gonderimCevabiKodu:0` + boş detay.
- **Rate limit**: Saniyede 5 istek.
- **e-Arşiv ile e-Fatura/İrsaliye kullanıcıları farklıdır**: e-Arşiv için portal/WS kullanıcı adı genellikle `VKN.portaltest` gibi ayrı bir kimliktir.

---

## Testler

Testler **standalone PHP script'leridir** — PHPUnit değil. PHPUnit/phpcs/phpstan komutları (`composer test/lint/stan`) bu repo'da çalışmaz.

```bash
php tests/001_builder_basit.php        # builder testleri (001–003)
php tests/004_service_invoice.php      # mock SoapClient ile service testi (canlı çağrı yok)
```

- `001`–`003`: `invoice_builder`'ın UBL üretimini doğrular.
- `004`: `invoice_service`'i sahte `SoapClient` ile test eder — gerçek ağ çağrısı yapmaz.

---

## Dokümantasyon

Kapsamlı API dokümantasyonu `docs/` altındadır:

- [`docs/01-baslangic.md`](docs/01-baslangic.md) — başlangıç, kimlik doğrulama, URL'ler
- [`docs/02-efatura.md`](docs/02-efatura.md) — e-Fatura
- [`docs/03-eirsaliye.md`](docs/03-eirsaliye.md) — e-İrsaliye
- [`docs/04-earsiv.md`](docs/04-earsiv.md) — e-Arşiv
- [`docs/05-edefter.md`](docs/05-edefter.md) — e-Defter

Sistem operasyonel detayları (endpoint'ler, test kimlikleri, doğrulanmış gotchas) için [`AGENTS.md`](AGENTS.md) ve `docs/` dosyalarına bakın.

Resmi API referansı (SOAP XML, C#, Java örnekleri): <https://www.qnbesolutions.com.tr/api-docs-tr-final.html>

---

## Katkı

1. Repo'yu fork edin ve özellik dalı açın.
2. Kodu **snake_case** ve mevcut **girinti konvansiyonuna** uyarak yazın: `src/client.php` ve `index.php` **tab**, `src/service/*`, `src/builder/*`, `tests/*` **4 boşluk** kullanır — düzenlediğiniz dosyayı eşleştirin.
3. Standalone test script'leriyle değişikliklerinizi doğrulayın (`php tests/...`).
4. Değişiklik yapıyorsanız SOAP çağrılarını **mock** ile test edin; canlı ortama gerçek gönderim yapmaktan kaçının (test ortamları yalnızca karşı ortama gönderim kabul eder).

Detaylı geliştirici rehberi için [`AGENTS.md`](AGENTS.md) dosyasına bakın.

---

## Lisans

[MIT](LICENSE) lisansıyla dağıtılmaktadır.
