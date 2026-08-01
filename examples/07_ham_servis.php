<?php
/**
 * Düşük seviye servis örneği: invoice_builder + invoice_service.
 *
 * Facade kullanmadan UBL XML'i üretip SOAP servisine doğrudan gönderir.
 * Bu yol, iki kritik tuzağı da gösterir:
 *
 *  - Çift-base64 tuzağı: servis `veri_base64`'i kendi içinde `base64_decode`
 *    edip SoapClient'ın tekrar encode etmesine bırakır. Ham XML'i
 *    `base64_encode()` ile geçirin, ÇÖZÜLMÜŞ halini değil.
 *  - Hash formülü: `belge_hash_md5 = strtoupper(md5($xml))` — ham XML üzerinde.
 *
 * Kimlik bilgilerini kendi değerlerinizle değiştirin.
 */

require __DIR__.'/../vendor/autoload.php';

use QnbSolutions\QnbEsolutions\builder\invoice_builder;
use QnbSolutions\QnbEsolutions\client;

$c = new client(
    username: 'KULLANICI_ADI',
    password: 'SIFRE',
    environment: client::ENV_TEST2,
);

$belge_no = 'NXT' . date('Y') . '000000001';

// UBL 2.1 fatura XML'i üret
$xml = (new invoice_builder)
    ->set_fatura_no($belge_no)
    ->set_tarih(date('Y-m-d'))
    ->set_fatura_turu(invoice_builder::TYPE_SATIS)
    ->set_profil(invoice_builder::PROFILE_TICARI)
    ->set_satici(vkn: 'SIRKET_VKN', unvan: 'SATICI A.Ş.')
    ->set_satici_adres('Adres Sokak No:1', 'Çankaya', 'Ankara')
    ->set_alici(vkn: 'ALICI_VKN', unvan: 'ALICI LTD.')
    ->set_alici_adres('Adres Sokak No:2', 'Kadıköy', 'İstanbul')
    ->add_satir(isim: 'Hizmet', miktar: 1, birim_fiyat: 1000.00, kdv_oran: 20)
    ->build();

// Gönder — base64 ve hash formüllerine dikkat
$oid = $c->invoice()->belge_gonder_ext(
    vergi_tc_kimlik_no: 'SIRKET_VKN',
    belge_no: $belge_no,
    veri_base64: base64_encode($xml),
    belge_hash_md5: strtoupper(md5($xml)),
    belge_versiyon: '2.1',
    erp_kodu: 'ERP_KODUNUZ',
);

echo "Gönderildi! belgeOid: {$oid}\n";
