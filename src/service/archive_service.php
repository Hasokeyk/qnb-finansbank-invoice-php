<?php

namespace QnbSolutions\QnbEsolutions\service;

use QnbSolutions\QnbEsolutions\enum\archive_output_format;
use QnbSolutions\QnbEsolutions\model\archive_result;

class archive_service
{
    public function __construct(
        private readonly \SoapClient $soap,
    ) {
    }

    /**
     * e-Arşiv faturası oluşturur (senkron).
     *
     * @param string $belge_icerigi_base64 Fatura XML'inin base64 hali
     * @param archive_output_format $donen_belge_formati Dönen format (UBL/HTML/PDF/YOK)
     * @param string $islem_id UUID (ETTN ile aynı olması önerilir)
     * @param string $vkn Fatura düzenleyen firma VKN/TCKN
     * @param string $sube Şube kodu (örn: DFLT)
     * @param string $kasa Kasa kodu (örn: DFLT)
     * @param string $erp_kodu QNB eSolutions proje kodu
     * @param int|null $numara_verilsin_mi 1: sistem üretsin, 0: ERP göndersin
     * @param string|null $fatura_seri Sistem üretecekse seri kodu
     * @param string|null $sablon_adi XSLT şablon adı
     * @param int|null $taslaga_yonlendir 1: taslağa gönder, 0: otomatik imzala
     */
    public function fatura_olustur_ext(
        string $belge_icerigi_base64,
        archive_output_format $donen_belge_formati = archive_output_format::PDF,
        ?string $islem_id = null,
        string $vkn = '',
        string $sube = 'DFLT',
        string $kasa = 'DFLT',
        string $erp_kodu = '',
        ?int $numara_verilsin_mi = null,
        ?string $fatura_seri = null,
        ?string $sablon_adi = null,
        ?int $taslaga_yonlendir = null,
    ): archive_result {
        $params = [
            'donenBelgeFormati' => $donen_belge_formati->value,
            'islemId' => $islem_id,
            'vkn' => $vkn,
            'sube' => $sube,
            'kasa' => $kasa,
            'erpKodu' => $erp_kodu,
            'belgeFormati' => 'UBL',
            'belgeIcerigi' => $belge_icerigi_base64,
        ];

        if ($numara_verilsin_mi !== null) {
            $params['numaraVerilsinMi'] = $numara_verilsin_mi;
        }
        if ($fatura_seri !== null) {
            $params['faturaSeri'] = $fatura_seri;
        }
        if ($sablon_adi !== null) {
            $params['sablonAdi'] = $sablon_adi;
        }
        if ($taslaga_yonlendir !== null) {
            $params['taslagaYonlendir'] = $taslaga_yonlendir;
        }

        $result = $this->soap->faturaOlusturExt($params);

        $extra = [];
        if (isset($result->resultExtra)) {
            $entries = $result->resultExtra->entry ?? [];
            foreach ($entries as $entry) {
                $extra[$entry->key] = $entry->value;
            }
        }

        return new archive_result(
            output_base64: $result->output ?? null,
            islem_id: $extra['islemID'] ?? '',
            fatura_url: $extra['faturaURL'] ?? '',
            uuid: $extra['uuid'] ?? '',
            fatura_no: $extra['faturaNo'] ?? '',
            iptal_tarihi: $extra['iptalTarihi'] ?? null,
        );
    }
}
