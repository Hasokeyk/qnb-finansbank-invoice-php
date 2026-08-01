<?php
/**
 * Test: Basit fatura XML üretimi
 * Gerçek SOAP çağrısı yapılmaz, sadece builder test edilir.
 */

require_once __DIR__.'/../vendor/autoload.php';

use QnbSolutions\QnbEsolutions\builder\invoice_builder;

$ok = 0;
$fail = 0;

function test(string $label, string $xml, string $xpath, string $beklenen): void
{
    global $ok, $fail;
    // Basit XML parse — DOMDocument ile
    $dom = new DOMDocument;
    $dom->loadXML($xml);

    $x = new DOMXPath($dom);
    $x->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
    $x->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

    $nodes = $x->query($xpath);
    $deger = $nodes->length > 0 ? $nodes->item(0)->textContent : '(yok)';

    if ($deger === $beklenen) {
        echo "  ✓ {$label}: {$deger}\n";
        $ok++;
    } else {
        echo "  ✗ {$label}: beklenen={$beklenen}, alinan={$deger}\n";
        $fail++;
    }
}

echo "=== Builder Temel Test ===\n";

// 1. En basit fatura
$xml = (new invoice_builder)
    ->set_fatura_no('TEST2025001')
    ->set_tarih('2025-01-15')
    ->set_fatura_turu(invoice_builder::TYPE_SATIS)
    ->set_satici(vkn: '1234567890', unvan: 'SATICI A.Ş.')
    ->set_alici(vkn: '9876543210', unvan: 'ALICI LTD.')
    ->add_satir(isim: 'Hizmet', miktar: 1, birim_fiyat: 500.00, kdv_oran: 20)
    ->build();

test('fatura no', $xml, '//cbc:ID', 'TEST2025001');
test('tarih', $xml, '//cbc:IssueDate', '2025-01-15');
test('fatura turu', $xml, '//cbc:InvoiceTypeCode', 'SATIS');
test('para birimi', $xml, '//cbc:DocumentCurrencyCode', 'TRY');
test('profil', $xml, '//cbc:ProfileID', 'EARSIVFATURA');
test('satici VKN', $xml, '//cac:AccountingSupplierParty//cbc:ID', '1234567890');
test('satici unvan', $xml, '//cac:AccountingSupplierParty//cac:PartyName/cbc:Name', 'SATICI A.Ş.');
test('alici VKN', $xml, '//cac:AccountingCustomerParty//cbc:ID', '9876543210');
test('alici unvan', $xml, '//cac:AccountingCustomerParty//cac:PartyName/cbc:Name', 'ALICI LTD.');
test('satir isim', $xml, '//cac:InvoiceLine[1]//cac:Item/cbc:Name', 'Hizmet');
test('satir miktar', $xml, '//cac:InvoiceLine[1]//cbc:InvoicedQuantity', '1');
test('birim fiyat', $xml, '//cac:InvoiceLine[1]//cac:Price/cbc:PriceAmount', '500');
test('line toplam', $xml, '//cac:InvoiceLine[1]//cbc:LineExtensionAmount', '500');
test('KDV oran', $xml, '//cac:InvoiceLine[1]//cac:TaxTotal//cbc:Percent', '20');
test('mal toplam', $xml, '//cac:LegalMonetaryTotal//cbc:LineExtensionAmount', '500');
test('KDV toplam', $xml, '//cac:TaxTotal//cbc:TaxAmount', '100');
test('genel toplam', $xml, '//cac:LegalMonetaryTotal//cbc:PayableAmount', '600');

echo "\n";
echo "Sonuc: {$ok} gecti, {$fail} kaldi\n";
exit($fail > 0 ? 1 : 0);
