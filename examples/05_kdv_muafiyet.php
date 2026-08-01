<?php
/**
 * KDV istisna/muafiyet kodlu fatura örneği.
 *
 * Kurallar:
 *  - Muafiyet kodu içeren satır için fatura tipi istisna ailesinden olmalıdır:
 *    ISTISNA, IADE, IHRACKAYITLI, SGK, YTBIADE, YTBISTISNA.
 *  - Kullanıcı yalnızca muafiyet KODUNU girer; açıklama (TaxExemptionReason)
 *    GİB listesinden (kdv_istisna_kodlari) OTOMATIK doldurulur.
 *
 * Kimlik bilgilerini kendi değerlerinizle değiştirin.
 */

require __DIR__.'/../vendor/autoload.php';

use QnbSolutions\QnbEsolutions\builder\invoice_builder;
use QnbSolutions\QnbEsolutions\exception\validation_exception;
use QnbSolutions\QnbEsolutions\qnb_esolutions;

$qnb = new qnb_esolutions('KULLANICI_ADI', 'SIFRE', 'ERP_KODUNUZ');

$qnb->document_no('NXT' . date('Y') . '000000001')
    ->issue_date(date('Y-m-d'))
    ->invoice_type(invoice_builder::TYPE_ISTISNA)   // muafiyet için zorunlu aile
    ->profile(invoice_builder::PROFILE_TICARI)
    ->currency('TRY');

$qnb->my_company()
    ->set_company_name('SATICI A.Ş.')
    ->set_tax_number('SIRKET_VKN')
    ->set_tax_office('ÇANKAYA')
    ->set_address('Adres Sokak No:1', 'Çankaya', 'Ankara', 'Türkiye');

$qnb->customer_company()
    ->set_company_name('ALICI LTD.')
    ->set_tax_number('ALICI_VKN')
    ->set_tax_office('ÇANKAYA')
    ->set_address('Adres Sokak No:2', 'Kadıköy', 'İstanbul', 'Türkiye');

// Normal KDV'li satır
$qnb->add_product()->set_product_name('Eğitim Hizmeti')->set_quantity(1)->set_unit_price(1000.00)->set_vat_rate(20);

// Muafiyetli satır — sadece kod girilir, açıklama resmi listeden otomatik dolar
$qnb->add_product()->set_product_name('Müşavirlik')->set_quantity(1)->set_unit_price(500.00)->set_vat_rate(0)
    ->set_vat_exemption_code('223');   // Geçici 20/1 Teknoloji geliştirme bölgeleri

// create_invoice içinde doğrulama yapılır (bilinmeyen kod / tip uyumsuzluğu burada yakalanır)
try {
    $oid = $qnb->create_invoice('efatura')->send();
    echo "Gönderildi! belgeOid: {$oid}\n";
} catch (validation_exception $e) {
    echo "Doğrulama hataları:\n";
    foreach ($e->errors as $h) {
        echo "  - {$h}\n";
    }
    exit(1);
} catch (\Throwable $e) {
    echo "Gönderim hatası: ".$e->getMessage()."\n";
    exit(1);
}
