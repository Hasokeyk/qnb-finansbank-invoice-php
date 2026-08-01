<?php

namespace QnbSolutions\QnbEsolutions\builder;

use QnbSolutions\QnbEsolutions\enum\archive_output_format;
use QnbSolutions\QnbEsolutions\model\archive_result;

/**
 * e-Arşiv faturası keser (senkron). kes() → archive_result döner
 * (fatura_no, fatura_url, uuid, PDF/UBL output).
 *
 *     $client = new client(username: '...', password: '...', environment: client::ENV_TEST);
 *     $create_earsiv = new create_earsiv($my_company, $customer_company, $products, $client);
 *     $sonuc = $create_earsiv->kes();
 *     echo $sonuc->fatura_no;  // NXT2026...
 *     echo $sonuc->fatura_url; // görüntüleme linki
 */
class create_earsiv extends fatura_builder
{
    protected const VARSAYILAN_PROFIL = invoice_builder::PROFILE_EARSIV;

    private archive_output_format $donen_format = archive_output_format::PDF;
    private string $sube = 'DFLT';
    private string $kasa = 'DFLT';

    public function donen_format(archive_output_format $format): static
    {
        $this->donen_format = $format;
        return $this;
    }

    public function sube(string $sube): static
    {
        $this->sube = $sube;
        return $this;
    }

    public function kasa(string $kasa): static
    {
        $this->kasa = $kasa;
        return $this;
    }

    /**
     * Faturayı oluşturur, e-Arşiv sistemine gönderir (senkron).
     *
     * @return archive_result
     */
    public function send(): archive_result
    {
        $xml = $this->build_xml();

        return $this->client->archive()->fatura_olustur_ext(
            belge_icerigi_base64: base64_encode($xml),
            donen_belge_formati: $this->donen_format,
            islem_id: $this->uuid(),
            vkn: $this->satici->tax_number(),
            sube: $this->sube,
            kasa: $this->kasa,
            erp_kodu: $this->erp_kodu,
            numara_verilsin_mi: 1,
            fatura_seri: 'NXT',
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
