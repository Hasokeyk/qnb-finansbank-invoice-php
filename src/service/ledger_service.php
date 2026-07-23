<?php

namespace QnbSolutions\QnbEsolutions\service;

use QnbSolutions\QnbEsolutions\exception\api_exception;

class ledger_service
{
    public function __construct(
        private readonly \SoapClient $soap,
    ) {
    }

    /**
     * e-Defter CSV/XML dosyasını zip olarak yükler.
     *
     * @param string $donem Yıl/Ay (örn: 202501)
     * @param string $vkn_tckn İşlem yapan firma VKN/TCKN
     * @param string $sube_kodu Şube kodu (yoksa 0000)
     * @param string $dosya_ismi Yüklenecek zip dosya adı
     * @param string $dosya_base64 base64 encode zip dosyası
     * @return array{sonuc_kodu: string, sonuc_aciklama: string}
     */
    public function e_defter_csv_file_yukle(
        string $donem,
        string $vkn_tckn,
        string $sube_kodu,
        string $dosya_ismi,
        string $dosya_base64,
    ): array {
        $result = $this->soap->eDefterCsvFileYukle([
            'donem' => $donem,
            'subeKodu' => $sube_kodu,
            'vknTckn' => $vkn_tckn,
            'parameterList' => [
                ['key' => 'dosyaIsmi', 'value' => $dosya_ismi],
                ['key' => 'dosya', 'value' => $dosya_base64],
            ],
        ]);

        return [
            'sonuc_kodu' => $result->sonucKodu ?? '',
            'sonuc_aciklama' => $result->sonucAciklama ?? '',
        ];
    }

    /**
     * Yüklenen CSV dosyasının işlem durumunu sorgular.
     */
    public function e_defter_csv_durum_sorgula(
        string $donem,
        string $vkn_tckn,
        string $yuklenen_defter_turu = 'CSV',
    ): array {
        $result = $this->soap->eDefterCsvDurumSorgula([
            'donem' => $donem,
            'vknTckn' => $vkn_tckn,
            'yuklenenDefterTuru' => $yuklenen_defter_turu,
        ]);

        return [
            'sonuc_kodu' => $result->sonucKodu ?? '',
            'sonuc_aciklama' => $result->sonucAciklama ?? '',
        ];
    }

    /**
     * Tüm dosyaların gönderimi tamamlandığında defter işleme çağrısı yapar.
     */
    public function e_defter_csv_dosya_isle(
        string $donem,
        string $vkn_tckn,
        string $sube_kodu,
        string $yuklenen_defter_turu = 'CSV',
    ): array {
        $result = $this->soap->eDefterCsvDosyaIsle([
            'donem' => $donem,
            'vknTckn' => $vkn_tckn,
            'subeKodu' => $sube_kodu,
            'yuklenenDefterTuru' => $yuklenen_defter_turu,
        ]);

        return [
            'sonuc_kodu' => $result->sonucKodu ?? '',
            'sonuc_aciklama' => $result->sonucAciklama ?? '',
        ];
    }
}
