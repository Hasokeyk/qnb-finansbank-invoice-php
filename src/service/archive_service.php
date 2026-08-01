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
     * WSDL yapısı: `faturaOlusturExt(input: JSON-string, fatura: belge, output: belge)`.
     * Mantıksal parametrelerin tamamı `input` JSON'ında gider; XML ham hali
     * `fatura.belgeIcerigi` (base64Binary) olarak gönderilir.
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
        $input = [
            'islemId' => $islem_id ?? $this->uuid(),
            'vkn' => $vkn,
            'sube' => $sube,
            'kasa' => $kasa,
            'erpKodu' => $erp_kodu,
            'donenBelgeFormati' => (string) $donen_belge_formati->value,
        ];

        if ($numara_verilsin_mi !== null) {
            $input['numaraVerilsinMi'] = $numara_verilsin_mi;
        }
        if ($fatura_seri !== null) {
            $input['faturaSeri'] = $fatura_seri;
        }
        if ($sablon_adi !== null) {
            $input['sablonAdi'] = $sablon_adi;
        }
        if ($taslaga_yonlendir !== null) {
            $input['taslagaYonlendir'] = $taslaga_yonlendir;
        }

        $params = [
            'input' => json_encode($input, JSON_UNESCAPED_UNICODE),
            // `belgeIcerigi` WSDL'de base64Binary; SoapClient ham değeri kendi encode eder.
            // Çift-base64 tuzağını önlemek için base64 çözülmüş ham XML geçilir.
            'fatura' => [
                'belgeFormati' => 'UBL',
                'belgeIcerigi' => base64_decode($belge_icerigi_base64, true),
            ],
        ];

        $result = $this->soap->faturaOlusturExt($params);

        // Cevap: belgeOutputWrapper { output: belge, return: earsivServiceResult }
        $servis_sonuc = $result->return ?? $result;

        $extra = [];
        if (isset($servis_sonuc->resultExtra->entry)) {
            $entries = is_array($servis_sonuc->resultExtra->entry)
                ? $servis_sonuc->resultExtra->entry
                : [$servis_sonuc->resultExtra->entry];
            foreach ($entries as $entry) {
                $extra[$entry->key] = $entry->value;
            }
        }

        // output.belgeIcerigi (base64Binary) SoapClient tarafından decode edilir;
        // archive_result.output_base64 base64 beklediği için tekrar encode edilir.
        $output_icerik = $result->output->belgeIcerigi ?? null;

        return new archive_result(
            output_base64: $output_icerik !== null ? base64_encode($output_icerik) : null,
            islem_id: $extra['islemID'] ?? '',
            fatura_url: $extra['faturaURL'] ?? '',
            uuid: $extra['uuid'] ?? '',
            fatura_no: $extra['faturaNo'] ?? '',
            iptal_tarihi: $extra['iptalTarihi'] ?? null,
        );
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return strtoupper(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)));
    }
}
