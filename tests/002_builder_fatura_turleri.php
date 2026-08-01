<?php
require_once __DIR__.'/../vendor/autoload.php';

use QnbSolutions\QnbEsolutions\builder\invoice_builder;

$ok = 0;
$fail = 0;

function test(string $label, string $xml, string $xpath, string $beklenen): void
{
    global $ok, $fail;
    $dom = new DOMDocument;
    $dom->loadXML($xml);
    $x = new DOMXPath($dom);
    $x->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
    $x->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

    $nodes = $x->query($xpath);
    $deger = $nodes->length > 0 ? $nodes->item(0)->textContent : '(yok)';

    if ($deger === $beklenen) {
        echo "  ✓ {$label}\n";
        $ok++;
    } else {
        echo "  ✗ {$label}: beklenen={$beklenen}, alinan={$deger}\n";
        $fail++;
    }
}

function test_var(string $label, string $xml, string $xpath): void
{
    global $ok, $fail;
    $dom = new DOMDocument;
    $dom->loadXML($xml);
    $x = new DOMXPath($dom);
    $x->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
    $x->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

    $nodes = $x->query($xpath);
    if ($nodes->length > 0) {
        echo "  ✓ {$label}: {$nodes->item(0)->textContent}\n";
        $ok++;
    } else {
        echo "  ✗ {$label}: (yok)\n";
        $fail++;
    }
}

echo "=== Fatura Türleri Test ===\n";

// 1. İade Faturası
$xml = (new invoice_builder)
    ->set_fatura_no('IADE2025001')
    ->set_tarih('2025-01-15')
    ->set_fatura_turu(invoice_builder::TYPE_IADE)
    ->set_satici(vkn: '1234567890', unvan: 'SATICI A.Ş.')
    ->set_alici(vkn: '9876543210', unvan: 'ALICI LTD.')
    ->add_satir(isim: 'İade Ürün', miktar: 1, birim_fiyat: 300.00, kdv_oran: 20)
    ->build();

test('iade fatura turu', $xml, '//cbc:InvoiceTypeCode', 'IADE');
test('iade mal toplam', $xml, '//cac:LegalMonetaryTotal//cbc:LineExtensionAmount', '300');
test('iade KDV', $xml, '//cac:TaxTotal//cbc:TaxAmount', '60');

// 2. Tevkifat
$xml = (new invoice_builder)
    ->set_fatura_no('TEVK2025001')
    ->set_tarih('2025-01-15')
    ->set_fatura_turu(invoice_builder::TYPE_TEVKIFAT)
    ->set_satici(vkn: '1234567890', unvan: 'SATICI A.Ş.')
    ->set_alici(vkn: '9876543210', unvan: 'ALICI LTD.')
    ->add_satir(isim: 'TEVKİFAT', miktar: 1, birim_fiyat: 1000.00, kdv_oran: 20)
    ->build();

test('tevkifat turu', $xml, '//cbc:InvoiceTypeCode', 'TEVKIFAT');

// 3. KDV Muafiyet
$xml = (new invoice_builder)
    ->set_fatura_no('KDVMF2025001')
    ->set_tarih('2025-01-15')
    ->set_fatura_turu(invoice_builder::TYPE_KDV_MUAFIYETI)
    ->set_satici(vkn: '1234567890', unvan: 'SATICI A.Ş.')
    ->set_alici(vkn: '9876543210', unvan: 'ALICI LTD.')
    ->add_satir(isim: 'Eğitim', miktar: 1, birim_fiyat: 1000.00, kdv_oran: 0,
        kdv_muafiyet_kodu: '301', kdv_muafiyet_aciklama: 'Eğitim muafiyeti')
    ->build();

test('muafiyet turu', $xml, '//cbc:InvoiceTypeCode', 'ISTISNA');
test('muafiyet kodu', $xml, '//cac:InvoiceLine[1]//cac:TaxTotal//cbc:TaxExemptionReasonCode', '301');
test('muafiyet aciklama', $xml, '//cac:InvoiceLine[1]//cac:TaxTotal//cbc:TaxExemptionReason', 'Eğitim muafiyeti');
test('muafiyet KDV 0', $xml, '//cac:TaxTotal//cbc:TaxAmount', '0');

// 4. İhracat
$xml = (new invoice_builder)
    ->set_fatura_no('IHR2025001')
    ->set_tarih('2025-01-15')
    ->set_fatura_turu(invoice_builder::TYPE_IHRACAT)
    ->set_satici(vkn: '1234567890', unvan: 'SATICI A.Ş.')
    ->set_alici(vkn: '9876543210', unvan: 'ALICI LTD.')
    ->add_satir(isim: 'İhracat', miktar: 100, birim_fiyat: 10.00, kdv_oran: 0)
    ->build();

test('ihracat turu', $xml, '//cbc:InvoiceTypeCode', 'IHRACAT');

// 5. Ticari profil
$xml = (new invoice_builder)
    ->set_fatura_no('TIC2025001')
    ->set_tarih('2025-01-15')
    ->set_fatura_turu(invoice_builder::TYPE_TICARI)
    ->set_profil(invoice_builder::PROFILE_TICARI)
    ->set_satici(vkn: '1234567890', unvan: 'SATICI A.Ş.')
    ->set_alici(vkn: '9876543210', unvan: 'ALICI LTD.')
    ->add_satir(isim: 'Ticari', miktar: 1, birim_fiyat: 100.00, kdv_oran: 20)
    ->build();

test('ticari profil', $xml, '//cbc:ProfileID', 'TICARIFATURA');
test('ticari turu', $xml, '//cbc:InvoiceTypeCode', 'SATIS');

// 6. Tashih (düzeltme)
$xml = (new invoice_builder)
    ->set_fatura_no('TAS2025001')
    ->set_tarih('2025-01-15')
    ->set_fatura_turu(invoice_builder::TYPE_TASHIH)
    ->set_satici(vkn: '1234567890', unvan: 'SATICI A.Ş.')
    ->set_alici(vkn: '9876543210', unvan: 'ALICI LTD.')
    ->add_satir(isim: 'Düzeltme', miktar: 1, birim_fiyat: 50.00, kdv_oran: 20)
    ->build();

test('tashih turu', $xml, '//cbc:InvoiceTypeCode', 'TASHIH');

// 7. İstisna
$xml = (new invoice_builder)
    ->set_fatura_no('IST2025001')
    ->set_tarih('2025-01-15')
    ->set_fatura_turu(invoice_builder::TYPE_ISTISNA)
    ->set_satici(vkn: '1234567890', unvan: 'SATICI A.Ş.')
    ->set_alici(vkn: '9876543210', unvan: 'ALICI LTD.')
    ->add_satir(isim: 'İstisna', miktar: 1, birim_fiyat: 200.00, kdv_oran: 0)
    ->build();

test('istisna turu', $xml, '//cbc:InvoiceTypeCode', 'ISTISNA');

// 8. TCKN (gerçek kişi alıcı)
$xml = (new invoice_builder)
    ->set_fatura_no('TCKN2025001')
    ->set_tarih('2025-01-15')
    ->set_fatura_turu(invoice_builder::TYPE_SATIS)
    ->set_satici(vkn: '1234567890', unvan: 'SATICI A.Ş.')
    ->set_alici(vkn: '10000000146', unvan: 'ALİ VELİ')
    ->add_satir(isim: 'Perakende', miktar: 1, birim_fiyat: 200.00, kdv_oran: 20)
    ->build();

test('TCKN alici', $xml, '//cac:AccountingCustomerParty//cbc:ID', '10000000146');
test('TCKN attribute', $xml, '//cac:AccountingCustomerParty//cbc:ID/@schemeID', 'TCKN');

echo "\n";
echo "Sonuc: {$ok} gecti, {$fail} kaldi\n";
exit($fail > 0 ? 1 : 0);
