<?php
/**
 * Gönderim öncesi doğrulama örneği.
 *
 * Doğrulama create_invoice() İÇİNDE yapılır: belge geçersizse
 * `validation_exception` fırlatılır — try/catch ile yakalanır, gönderim olmaz.
 *
 * Bu örnek bilinçli olarak hatalı bir kombinasyon kurar.
 */

require __DIR__.'/../vendor/autoload.php';

use QnbSolutions\QnbEsolutions\builder\invoice_builder;
use QnbSolutions\QnbEsolutions\exception\validation_exception;
use QnbSolutions\QnbEsolutions\qnb_esolutions;

$qnb = new qnb_esolutions('KULLANICI_ADI', 'SIFRE', 'ERP_KODUNUZ');

$qnb->issue_date(date('Y-m-d'))
    ->invoice_type(invoice_builder::TYPE_SATIS)   // muafiyet kodu ile UYUMSUZ
    ->profile(invoice_builder::PROFILE_TICARI)
    ->currency('TRY');

$qnb->my_company()->set_company_name('SATICI A.Ş.')->set_tax_number('SIRKET_VKN')
    ->set_address('Adres Sokak No:1', 'Çankaya', 'Ankara');
$qnb->customer_company()->set_company_name('ALICI LTD.')->set_tax_number('ALICI_VKN')
    ->set_address('Adres Sokak No:2', 'Kadıköy', 'İstanbul');

// Bilinçli hata: bilinmeyen muafiyet kodu + SATIS tipi uyumsuzluğu
$qnb->add_product()->set_product_name('Muafiyetli')->set_quantity(1)->set_unit_price(100.00)->set_vat_rate(0)
    ->set_vat_exemption_code('999');

// create_invoice içinde doğrulama yapılır — hata varsa validation_exception fırlatır
try {
    $builder = $qnb->create_invoice('efatura');
    echo "Doğrulama geçti — XML üretiliyor...\n";
    $xml = $builder->build_xml();
    echo "XML üretildi (uzunluk: ".strlen($xml).")\n";
} catch (validation_exception $e) {
    echo "validation_exception yakalandı:\n";
    foreach ($e->errors as $h) {
        echo "  - {$h}\n";
    }
}
