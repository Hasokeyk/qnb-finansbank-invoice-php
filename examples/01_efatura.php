<?php
/**
 * e-Fatura örneği (yüksek seviye facade).
 *
 * Akış: fatura oluştur → create_invoice (içinde doğrulama) → gönder → durum sorgula.
 * e-Fatura asenkrondur; send() kuyruk referansı (belgeOid) döner, işlenmesi
 * birkaç saniye sürebilir.
 *
 * Kimlik bilgilerini kendi değerlerinizle değiştirin.
 */

require __DIR__.'/../vendor/autoload.php';

use QnbSolutions\QnbEsolutions\builder\invoice_builder;
use QnbSolutions\QnbEsolutions\exception\validation_exception;
use QnbSolutions\QnbEsolutions\qnb_esolutions;

$username = 'KULLANICI_ADI';
$password = 'SIFRE';
$erp_kodu = 'ERP_KODUNUZ';   // ERP1 / ERP2 sunucu tarafından reddedilir

// Varsayılan ortam: test2 (client::ENV_TEST2)
$qnb = new qnb_esolutions($username, $password, $erp_kodu);

// Belge detayları
$qnb->document_no('NXT' . date('Y') . '000000001')
    ->issue_date(date('Y-m-d'), date('H:i:s'))
    ->invoice_type(invoice_builder::TYPE_SATIS)      // SATIS | ISTISNA | IADE | TEVKIFAT ...
    ->profile(invoice_builder::PROFILE_TICARI)       // TICARIFATURA | TEMELFATURA
    ->currency('TRY')
    ->due_days(15);

// Satıcı firma
$qnb->my_company()
    ->set_company_name('SATICI A.Ş.')
    ->set_tax_number('SIRKET_VKN')
    ->set_tax_office('ÇANKAYA')
    ->set_address('Adres Sokak No:1', 'Çankaya', 'Ankara', 'Türkiye');

// Alıcı firma
$qnb->customer_company()
    ->set_company_name('ALICI LTD.')
    ->set_tax_number('ALICI_VKN')
    ->set_tax_office('ÇANKAYA')
    ->set_address('Adres Sokak No:2', 'Kadıköy', 'İstanbul', 'Türkiye');

// Ürün/hizmet satırları — her ürün için add_product() yeni satır başlatır
$qnb->add_product()->set_product_name('Hizmet 1')->set_quantity(1)->set_unit_price(1000.00)->set_vat_rate(20);
$qnb->add_product()->set_product_name('Hizmet 2')->set_quantity(2)->set_unit_price(250.00)->set_vat_rate(20);

// create_invoice içinde doğrulama yapılır; hata varsa validation_exception fırlatır
try {
    $belge_oid = $qnb->create_invoice('efatura')->send();
    echo "Gönderildi! belgeOid: {$belge_oid}\n";
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

// Durum sorgula (işlenmesi birkaç saniye sürebilir)
try {
    $durum = $qnb->status($belge_oid);
    echo "Durum       : {$durum->durum} (1: Alındı, 2: İşleme Hatası, 3: İşlendi)\n";
    echo "Cevap kodu  : {$durum->gonderim_cevabi_kodu}\n";
    echo "ETTN        : {$durum->ettn}\n";
    echo "Ulaştı mı   : ".($durum->ulasti_mi ? 'EVET' : 'hayır')."\n";
} catch (\Throwable $e) {
    echo "Durum sorgulanamadı: ".$e->getMessage()."\n";
}
