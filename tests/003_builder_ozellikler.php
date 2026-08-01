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

function test_yok(string $label, string $xml, string $xpath): void
{
    global $ok, $fail;
    $dom = new DOMDocument;
    $dom->loadXML($xml);
    $x = new DOMXPath($dom);
    $x->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
    $x->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

    $nodes = $x->query($xpath);
    if ($nodes->length === 0) {
        echo "  ✓ {$label}: (yok)\n";
        $ok++;
    } else {
        echo "  ✗ {$label}: beklenen=yok, alinan={$nodes->item(0)->textContent}\n";
        $fail++;
    }
}

echo "=== Builder Özellik Testleri ===\n";

// 1. Birden çok satır
$xml = (new invoice_builder)
    ->set_fatura_no('MLT2025001')
    ->set_tarih('2025-01-15')
    ->set_fatura_turu(invoice_builder::TYPE_SATIS)
    ->set_satici(vkn: '1234567890', unvan: 'SATICI A.Ş.')
    ->set_alici(vkn: '9876543210', unvan: 'ALICI LTD.')
    ->add_satir(isim: 'Ürün 1', miktar: 2, birim_fiyat: 100.00, kdv_oran: 20)
    ->add_satir(isim: 'Ürün 2', miktar: 3, birim_fiyat: 50.00, kdv_oran: 10)
    ->build();

test('coklu satir 1 isim', $xml, '//cac:InvoiceLine[1]//cac:Item/cbc:Name', 'Ürün 1');
test('coklu satir 2 isim', $xml, '//cac:InvoiceLine[2]//cac:Item/cbc:Name', 'Ürün 2');
test('coklu satir 1 toplam', $xml, '//cac:InvoiceLine[1]//cbc:LineExtensionAmount', '200');
test('coklu satir 2 toplam', $xml, '//cac:InvoiceLine[2]//cbc:LineExtensionAmount', '150');
test('coklu mal toplam', $xml, '//cac:LegalMonetaryTotal//cbc:LineExtensionAmount', '350');
test('coklu KDV', $xml, '//cac:TaxTotal//cbc:TaxAmount', '55'); // 40+15
test('coklu genel toplam', $xml, '//cac:LegalMonetaryTotal//cbc:PayableAmount', '405');

// 2. İskonto
$xml = (new invoice_builder)
    ->set_fatura_no('ISK2025001')
    ->set_tarih('2025-01-15')
    ->set_fatura_turu(invoice_builder::TYPE_TICARI)
    ->set_satici(vkn: '1234567890', unvan: 'SATICI A.Ş.')
    ->set_alici(vkn: '9876543210', unvan: 'ALICI LTD.')
    ->add_satir(isim: 'İndirimli', miktar: 1, birim_fiyat: 1000.00, kdv_oran: 20,
        iskonto: 100.00)
    ->build();

test('iskonto satir toplam', $xml, '//cac:InvoiceLine[1]//cbc:LineExtensionAmount', '900');
test('iskonto tutari', $xml, '//cac:InvoiceLine[1]//cac:AllowanceCharge/cbc:Amount', '100');

// 3. Teslim günü
$xml = (new invoice_builder)
    ->set_fatura_no('TESL2025001')
    ->set_tarih('2025-01-15')
    ->set_fatura_turu(invoice_builder::TYPE_SATIS)
    ->set_satici(vkn: '1234567890', unvan: 'SATICI A.Ş.')
    ->set_alici(vkn: '9876543210', unvan: 'ALICI LTD.')
    ->add_satir(isim: 'Teslim', miktar: 1, birim_fiyat: 500.00, kdv_oran: 20)
    ->set_teslim_gun(7)
    ->build();

test('teslim gunu', $xml, '//cbc:PaymentDueDate', '2025-01-22');

// 4. Açıklama
$xml = (new invoice_builder)
    ->set_fatura_no('NOT2025001')
    ->set_tarih('2025-01-15')
    ->set_fatura_turu(invoice_builder::TYPE_SATIS)
    ->set_satici(vkn: '1234567890', unvan: 'SATICI A.Ş.')
    ->set_alici(vkn: '9876543210', unvan: 'ALICI LTD.')
    ->add_satir(isim: 'Açıklamalı', miktar: 1, birim_fiyat: 100.00, kdv_oran: 20)
    ->set_aciklama('Teslimat elden yapılmıştır.')
    ->build();

test('aciklama', $xml, '//cbc:Note', 'Teslimat elden yapılmıştır.');

// 6. Alıcı adresi
$xml = (new invoice_builder)
    ->set_fatura_no('ADR2025001')
    ->set_tarih('2025-01-15')
    ->set_fatura_turu(invoice_builder::TYPE_SATIS)
    ->set_satici(vkn: '1234567890', unvan: 'SATICI A.Ş.')
    ->set_alici(vkn: '9876543210', unvan: 'ALICI LTD.')
    ->set_alici_adres(adres: 'Ankara Cad. No:1', ilce: 'Çankaya', il: 'Ankara', ulke: 'Türkiye')
    ->add_satir(isim: 'Adres', miktar: 1, birim_fiyat: 100.00, kdv_oran: 20)
    ->build();

test('alici adres', $xml, '//cac:AccountingCustomerParty//cac:PostalAddress/cbc:StreetName', 'Ankara Cad. No:1');
test('alici ilce', $xml, '//cac:AccountingCustomerParty//cac:PostalAddress/cbc:CitySubdivisionName', 'Çankaya');
test('alici il', $xml, '//cac:AccountingCustomerParty//cac:PostalAddress/cbc:CityName', 'Ankara');
test('alici ulke', $xml, '//cac:AccountingCustomerParty//cac:PostalAddress/cac:Country/cbc:Name', 'Türkiye');

// 7. Açıklama sadece bir Note oluşturur
$xml = (new invoice_builder)
    ->set_fatura_no('ACIK2225001')
    ->set_tarih('2025-01-15')
    ->set_fatura_turu(invoice_builder::TYPE_SATIS)
    ->set_satici(vkn: '1234567890', unvan: 'SATICI A.Ş.')
    ->set_alici(vkn: '9876543210', unvan: 'ALICI LTD.')
    ->add_satir(isim: 'Test', miktar: 1, birim_fiyat: 100.00, kdv_oran: 20)
    ->set_aciklama('Sadece bu')
    ->build();

test('tek not', $xml, '//cbc:Note', 'Sadece bu');

// 8. Muafiyet kodu var ama açıklama boş → TaxExemptionReason eklenmez
$xml = (new invoice_builder)
    ->set_fatura_no('MUAFBOS2025001')
    ->set_tarih('2025-01-15')
    ->set_fatura_turu(invoice_builder::TYPE_KDV_MUAFIYETI)
    ->set_satici(vkn: '1234567890', unvan: 'SATICI A.Ş.')
    ->set_alici(vkn: '9876543210', unvan: 'ALICI LTD.')
    ->add_satir(isim: 'Muaf', miktar: 1, birim_fiyat: 100.00, kdv_oran: 0,
        kdv_muafiyet_kodu: '301')
    ->build();

test_yok('muafiyet aciklama bos eklenmez', $xml,
    '//cac:InvoiceLine[1]//cac:TaxTotal//cbc:TaxExemptionReason');
test('muafiyet kod var', $xml, '//cac:InvoiceLine[1]//cac:TaxTotal//cbc:TaxExemptionReasonCode', '301');

echo "\n";
echo "Sonuc: {$ok} gecti, {$fail} kaldi\n";
exit($fail > 0 ? 1 : 0);
