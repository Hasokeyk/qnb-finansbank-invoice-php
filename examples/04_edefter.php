<?php
/**
 * e-Defter örneği (düşük seviye servis).
 *
 * e-Defter fatura değildir; CSV/XML defter dosyalarını içeren bir ZIP'in
 * yüklenmesi ve ardından "işleme" çağrısı ile çalışır.
 *
 * Kimlik bilgilerini kendi değerlerinizle değiştirin.
 */

require __DIR__.'/../vendor/autoload.php';

use QnbSolutions\QnbEsolutions\client;

$c = new client(
    username: 'KULLANICI_ADI',
    password: 'SIFRE',
    environment: client::ENV_TEST2,
);

$donem = '202601';   // Yıl + Ay (örn: 202501)
$zip_icerigi = '... CSV/XML dosyalarını içeren zip verisi ...'; // gerçek zip

// 1) Defter zip dosyasını yükle
$sonuc = $c->ledger()->e_defter_csv_file_yukle(
    donem: $donem,
    vkn_tckn: 'SIRKET_VKN',
    sube_kodu: '0000',
    dosya_ismi: "defter_{$donem}.zip",
    dosya_base64: base64_encode($zip_icerigi),
);
echo "Yükleme sonucu kod : {$sonuc['sonuc_kodu']}\n";
echo "Yükleme açıklaması : {$sonuc['sonuc_aciklama']}\n";

// 2) Yükleme durumunu sorgula
$durum = $c->ledger()->e_defter_csv_durum_sorgula(
    donem: $donem,
    vkn_tckn: 'SIRKET_VKN',
);
echo "Sorgu sonucu: {$durum['sonuc_aciklama']}\n";

// 3) Tüm dosyalar yüklendikten sonra defteri işleme çağrısı yap
$isle = $c->ledger()->e_defter_csv_dosya_isle(
    donem: $donem,
    vkn_tckn: 'SIRKET_VKN',
    sube_kodu: '0000',
);
echo "İşleme sonucu: {$isle['sonuc_aciklama']}\n";
