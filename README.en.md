# QNB eSolutions PHP SOAP Client

**PHP client library for QNB eSolutions' Özel Entegratör SOAP APIs**

Handles e-Invoice (e-Fatura), e-Despatch (e-İrsaliye), e-Archive (e-Arşiv) and e-Ledger (e-Defter) in a single library. It builds UBL 2.1 invoice XML with a fluent builder, submits to the SOAP services, and returns typed model objects.

> 🌐 Turkish version: [`README.tr.md`](README.tr.md).

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Architecture](#architecture)
- [Usage Guide](#usage-guide)
- [Important Notes](#important-notes)
- [Tests](#tests)
- [Documentation](#documentation)
- [Contributing](#contributing)
- [License](#license)

---

## Features

| Module | Service | Description |
|--------|---------|-------------|
| **e-Fatura** (e-Invoice) | `invoice()` | Send UBL invoices, query status, list & download inbound/outbound documents |
| **e-İrsaliye** (e-Despatch) | `despatch()` | Send despatch advices, query status, list & download inbound/outbound documents |
| **e-Arşiv** (e-Archive) | `archive()` | Synchronous `fatura_olustur_ext` — UBL/HTML/PDF output |
| **e-Defter** (e-Ledger) | `ledger()` | Upload CSV/XML ledger files, query status, process |

- **Fluent UBL 2.1 builder** (`invoice_builder`) — line items, VAT, discounts, exemption/exception codes, delivery/payment terms
- **Two authentication methods**: WSSE UsernameToken (header on every request) or Cookie session
- **Three environments**: Test, Test2 and Live — `client::ENV_TEST`, `client::ENV_TEST2`, `client::ENV_PROD`
- **Typed model returns**: `registered_user`, `document_status`, `incoming_document`, `archive_result`
- **Custom invoice layout**: send `xsltAdi` + `xsltVeri` via `belge_gonder_ext` (see `invoice-templates/efatura.xslt`)

---

## Requirements

- PHP **>= 8.2**
- `ext-soap`
- `ext-mbstring`
- `composer`

---

## Installation

```bash
composer require hasokeyk/qnb-finansbank-invoice
```

> If the package is not yet published on Packagist, define a VCS (git) repository source in `composer.json` and install it with the same command.

---

## Quick Start

```php
<?php

require 'vendor/autoload.php';

use QnbSolutions\QnbEsolutions\client;
use QnbSolutions\QnbEsolutions\builder\invoice_builder;

// 1) Create the client — replace credentials with your own values
$c = new client(
    username: 'USERNAME',
    password: 'PASSWORD',
    environment: client::ENV_TEST,   // ENV_TEST | ENV_TEST2 | ENV_PROD
    auth_method: client::AUTH_WSSE,  // AUTH_WSSE | AUTH_COOKIE
);

// 2) Build the UBL 2.1 invoice XML (fluent builder)
$xml = (new invoice_builder)
    ->set_fatura_no('NXT2026000000001')
    ->set_tarih(date('Y-m-d'))
    ->set_fatura_turu(invoice_builder::TYPE_SATIS)
    ->set_profil(invoice_builder::PROFILE_TICARI)
    ->set_satici(vkn: 'VKN', unvan: 'SELLER NAME')
    ->set_satici_adres('1 Address St', 'District', 'City')
    ->set_alici(vkn: 'BUYER_VKN', unvan: 'BUYER NAME')
    ->add_satir(isim: 'Service', miktar: 1, birim_fiyat: 1000.00, kdv_oran: 20)
    ->build();

// 3) Send — note the hash formula: strtoupper(md5($xml))
$oid = $c->invoice()->belge_gonder_ext(
    vergi_tc_kimlik_no: 'VKN',
    belge_no: 'NXT2026000000001',
    veri_base64: base64_encode($xml),
    belge_hash_md5: strtoupper(md5($xml)),
    belge_versiyon: '2.1',
    erp_kodu: 'ERP_CODE',
);

echo "Sent! Document OID: {$oid}\n";
```

### Facade: `qnb_esolutions` (recommended)

Single entry point; validation happens inside `create_invoice()`:

```php
<?php

require 'vendor/autoload.php';

use QnbSolutions\QnbEsolutions\builder\invoice_builder;
use QnbSolutions\QnbEsolutions\qnb_esolutions;

$qnb = new qnb_esolutions('USERNAME', 'PASSWORD', 'ERP_CODE');

$qnb->document_no('NXT' . date('Y') . '000000001')   // GİB format: 3 letters + year + 9 digits
    ->issue_date(date('Y-m-d'))
    ->invoice_type(invoice_builder::TYPE_SATIS)      // SATIS | ISTISNA | IADE | ...
    ->profile(invoice_builder::PROFILE_TICARI)       // TICARIFATURA | TEMELFATURA | ...
    ->currency('TRY');

$qnb->my_company()
    ->set_company_name('SELLER LTD.')
    ->set_tax_number('VKN')
    ->set_tax_office('ÇANKAYA')
    ->set_address('1 Address St', 'Çankaya', 'Ankara', 'Türkiye');

$qnb->customer_company()
    ->set_company_name('BUYER LTD.')
    ->set_tax_number('BUYER_VKN')
    ->set_tax_office('ÇANKAYA')
    ->set_address('2 Address St', 'Kadıköy', 'İstanbul', 'Türkiye');

$qnb->add_product()
    ->set_product_name('Service')
    ->set_quantity(1)
    ->set_unit_price(1000.00)
    ->set_vat_rate(20);

// 'auto' | 'efatura' | 'earsiv'
$result = $qnb->create_invoice('auto')->send();
```

- `create_invoice('auto')` → issues e-Fatura if the buyer is an e-Fatura taxpayer, otherwise e-Arşiv.
- `create_invoice('efatura')` → throws `validation_exception` if the buyer is not a taxpayer (before the 1172 `TPS_POSTA_KUTUSU_YETKISI_YOK` error).
- **Validation happens inside `create_invoice`**: any missing required field (seller/buyer VKN, name, address, city; product name, quantity, price, VAT; document number format) throws `validation_exception` and no send happens.

---

## Architecture

```
src/
├── client.php                 # Main entrypoint — SoapClient + WSSE + lazy services
├── builder/
│   └── invoice_builder.php    # UBL 2.1 invoice XML generator (fluent API)
├── service/                   # Services mapped 1:1 to API product groups
│   ├── user_service.php       #   wsLogin / logout
│   ├── invoice_service.php    #   e-Fatura (e-Invoice)
│   ├── despatch_service.php   #   e-İrsaliye (e-Despatch)
│   ├── archive_service.php    #   e-Arşiv (e-Archive)
│   └── ledger_service.php     #   e-Defter (e-Ledger)
├── model/                     # Typed return objects (document_status, incoming_document, …)
├── enum/                      # Type/format/status constants
└── exception/                 # auth_exception, api_exception
```

**`client`**: Manages SOAP connections and lazy service instantiation. Each service works on a separate `\SoapClient` bound to the correct WSDL for the environment. The single source of truth for URLs is `client::URLS`.

**Services**: Each takes a `\SoapClient` and handles parameter mapping plus model returns. Methods accept **named args** (snake_case) that map to **camelCase** SOAP parameters (e.g. `vergi_tc_kimlik_no` → `vergiTcKimlikNo`).

**e-Fatura and e-İrsaliye** share the same SOAP `connectorService`; they differ by the `belge_turu` value. **e-Arşiv** (`fatura_olustur_ext`) is synchronous — it returns its result directly. **e-Defter** uses separate `e_defter_*` methods on the same connector service.

---

## Usage Guide

### Authentication

Two methods are available:

- **`AUTH_WSSE`** (default): Adds a `wsse:Security` UsernameToken header to every SOAP request. `authenticate()` is **not required**.
- **`AUTH_COOKIE`**: First opens a session via `ws_login`; the returned session cookie is used on subsequent requests. `authenticate()` is **required** in this mode.

```php
$c = new client(username: 'U', password: 'P', environment: client::ENV_TEST, auth_method: client::AUTH_COOKIE);
$c->authenticate();
```

### e-Invoice

```php
$svc = $c->invoice();

// Is the buyer a registered taxpayer?
$u = $svc->efatura_kullanici_bilgisi('1234567890');
echo $u->unvan;

// Registered user list (base64 zip)
$zip = $svc->kayitli_kullanici_listele_extended();

// Query document status
$st = $svc->giden_belge_durum_sorgula_ext('1234567890', 'NXT2026000000001', 'OID');
echo $st->gonderim_cevabi_kodu;

// List inbound documents
$docs = $svc->gelen_belgeleri_listele_ext('1234567890');
foreach ($docs as $d) {
    echo $d->ettn . ' — ' . $d->satici_unvan . PHP_EOL;
}

// Download an inbound document (base64 zip)
$zip = $svc->gelen_belgeleri_indir_ext('1234567890', $ettn);
```

### e-Archive (synchronous)

```php
use QnbSolutions\QnbEsolutions\enum\archive_output_format;

$res = $c->archive()->fatura_olustur_ext(
    belge_icerigi_base64: base64_encode($xml),
    donen_belge_formati: archive_output_format::PDF,
    vkn: '1234567890',
    erp_kodu: 'ERP_CODE',
    numara_verilsin_mi: 1,   // 1: system assigns, 0: ERP provides
);

echo $res->fatura_no;          // e-Archive invoice number
echo $res->fatura_url;         // viewing URL
echo $res->output_base64;      // returned document in the requested format (base64)
```

### e-Ledger

```php
$r = $c->ledger()->e_defter_csv_file_yukle(
    donem: '202601',
    vkn_tckn: '1234567890',
    sube_kodu: '0000',
    dosya_ismi: 'ledger_202601.zip',
    dosya_base64: base64_encode($zip),
);
echo $r['sonuc_kodu'] . ' ' . $r['sonuc_aciklama'];
```

### Custom Invoice Layout

Send your own XSLT via `belge_gonder_ext` (see `invoice-templates/efatura.xslt`):

```php
$xslt = file_get_contents(__DIR__ . '/invoice-templates/efatura.xslt');

$oid = $c->invoice()->belge_gonder_ext(
    vergi_tc_kimlik_no: 'VKN',
    belge_no: 'NXT2026000000001',
    veri_base64: base64_encode($xml),
    belge_hash_md5: strtoupper(md5($xml)),
    belge_versiyon: '2.1',
    erp_kodu: 'ERP_CODE',
    xslt_adi: 'efatura.xslt',
    xslt_veri_base64: base64_encode($xslt),
);
```

---

## Important Notes

> Critical points verified through live testing. See `AGENTS.md` for full detail.

- **Double-base64 pitfall**: The `veri`/`belgeIcerigi`/`dosya`/`xsltVeri` fields are of type `base64Binary` in the WSDL; PHP's `SoapClient` base64-encodes string values itself. The services therefore `base64_decode` the `*_base64` you pass and let `SoapClient` re-encode. Passing undecoded base64 causes double-encoding and the server returns "Hash hatası" (hash error).
- **Hash formula**: `belge_hash_md5 = strtoupper(md5($xml))` — over the **raw UBL XML**, **UPPERCASE** hex MD5. Lowercase or different content → `SoapFault "Hash hatası"`.
- **ERP code**: Use your own Özel Entegratör ERP code (`erp_kodu` or `erpBilgileriBelirle`). Values like `ERP1`/`ERP2` are rejected by the server.
- **InvoiceTypeCode is a strict GİB code list**: `TICARI` and `KDV_MUAFIYETI` are **invalid** (schema control fails). `TICARIFATURA` is a `ProfileID`, not a type. QNB panel **InvoiceTypeCode** list: `SATIS, IADE, TEVKIFAT, TEVKIFATIADE, ISTISNA, OZELMATRAH, IHRACKAYITLI, SGK, KOMISYONCU, KONAKLAMAVERGISI`. **ProfileID** list: `TEMELFATURA, TICARIFATURA, KAMU, IHRACAT, YOLCUBERABERFATURA, HKS, ENERJI, ILAC_TIBBICIHAZ, YATIRIMTESVIK, IDIS`. Builder lists: `invoice_builder::INVOICE_TYPE_CODES` and `invoice_builder::PROFILE_IDS`. Invoices with VAT-exempt/exempt-code lines must use one of the types in `MUAFIYET_UYUMLU_TIPLER`.
- **`create_invoice()` validation**: missing required fields throw `validation_exception` and no send happens — seller/buyer VKN, name, address, city; product name, quantity (>0), price (>0), VAT rate (>0 unless an exemption code is set); document number in GİB format (3 letters + year + 9 digits). Product defaults are `quantity=0`, `vat_rate=0` — validation rejects them until set. Explicit `'efatura'` also fails if the buyer is not an e-Fatura taxpayer (before the 1172 `TPS_POSTA_KUTUSU_YETKISI_YOK` error).
- **Test environments can only send to each other**: Test1 and Test2 VKNs only accept delivery to the **opposite** environment; same-environment delivery cannot be delivered (`gonderimCevabiKodu 1172`). Successful delivery: `durum:3` + `gonderimCevabiKodu:0` + empty detail.
- **Rate limit**: 5 requests per second.
- **e-Archive users differ from e-Fatura/Despatch users**: the e-Archive portal/WS username is usually a separate identity such as `VKN.portaltest`.

---

## Tests

The tests are **standalone PHP scripts** — not PHPUnit. The PHPUnit/phpcs/phpstan commands (`composer test/lint/stan`) do not work in this repo.

```bash
php tests/001_builder_basit.php        # builder tests (001–003)
php tests/004_service_invoice.php      # service test with a mock SoapClient (no live calls)
```

- `001`–`003`: verify `invoice_builder`'s UBL generation.
- `004`: tests `invoice_service` against a fake `SoapClient` — makes no real network calls.

---

## Documentation

Full API documentation lives under `docs/`:

- [`docs/01-baslangic.md`](docs/01-baslangic.md) — getting started, authentication, URLs
- [`docs/02-efatura.md`](docs/02-efatura.md) — e-Fatura (e-Invoice)
- [`docs/03-eirsaliye.md`](docs/03-eirsaliye.md) — e-İrsaliye (e-Despatch)
- [`docs/04-earsiv.md`](docs/04-earsiv.md) — e-Arşiv (e-Archive)
- [`docs/05-edefter.md`](docs/05-edefter.md) — e-Defter (e-Ledger)

For operational details (endpoints, test credentials, verified gotchas), see [`AGENTS.md`](AGENTS.md) and the `docs/` files.

Official API reference (SOAP XML, C#, Java samples): <https://www.qnbesolutions.com.tr/api-docs-tr-final.html>

---

## Contributing

1. Fork the repo and open a feature branch.
2. Write code in **snake_case** and follow the existing **indentation convention**: `src/client.php` and `index.php` use **tabs**, while `src/service/*`, `src/builder/*` and `tests/*` use **4 spaces** — match the file you are editing.
3. Verify your changes with the standalone test scripts (`php tests/...`).
4. When making changes, test SOAP calls with **mocks**; avoid real deliveries to live environments (test environments only accept delivery to the opposite environment).

See [`AGENTS.md`](AGENTS.md) for the detailed developer guide.

---

## License

Distributed under the [MIT](LICENSE) license.
