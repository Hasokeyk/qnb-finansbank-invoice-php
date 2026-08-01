<?php
/**
 * e-Arşiv örneği (yüksek seviye facade).
 *
 * e-Arşiv SENKRONDUR: send() sonucu doğrudan döner — archive_result
 * (fatura_no, fatura_url, uuid, output_base64).
 *
 * Kimlik bilgilerini kendi değerlerinizle değiştirin.
 * Not: e-Arşiv kullanıcısı genellikle e-Fatura'dan ayrıdır (örn. VKN.portaltest).
 */

require __DIR__.'/../vendor/autoload.php';

use QnbSolutions\QnbEsolutions\builder\invoice_builder;
use QnbSolutions\QnbEsolutions\exception\validation_exception;
use QnbSolutions\QnbEsolutions\qnb_esolutions;

$username = 'KULLANICI_ADI';   // e-Arşiv portal kullanıcısı
$password = 'SIFRE';
$erp_kodu = 'ERP_KODUNUZ';

// Varsayılan ortam: test (client::ENV_TEST)
$qnb = new qnb_esolutions($username, $password, $erp_kodu);

$qnb->issue_date(date('Y-m-d'), date('H:i:s'))
    ->invoice_type(invoice_builder::TYPE_SATIS)
    ->profile(invoice_builder::PROFILE_EARSIV)
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

$qnb->add_product()->set_product_name('Hizmet')->set_quantity(1)->set_unit_price(500.00)->set_vat_rate(20);

// create_invoice içinde doğrulama yapılır; hata varsa validation_exception fırlatır
try {
    $sonuc = $qnb->create_invoice('earsiv')->send();   // archive_result

    echo "Fatura no : {$sonuc->fatura_no}\n";
    echo "Fatura URL: {$sonuc->fatura_url}\n";
    echo "UUID      : {$sonuc->uuid}\n";
    echo "İşlem ID  : {$sonuc->islem_id}\n";
    if ($sonuc->output_base64 !== null && $sonuc->output_base64 !== '') {
        echo "Output    : ".substr($sonuc->output_base64, 0, 60)."… (base64)\n";
    }
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
