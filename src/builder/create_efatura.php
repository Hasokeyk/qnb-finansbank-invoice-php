<?php

namespace QnbSolutions\QnbEsolutions\builder;

/**
 * e-Fatura keser (asenkron). kes() → belgeOid döner; durum daha sonra sorgulanır.
 *
 *     $client = new client(username: '...', password: '...', environment: client::ENV_TEST2);
 *     $create_efatura = new create_efatura($my_company, $customer_company, $products, $client);
 *     $oid = $create_efatura->kes();
 */
class create_efatura extends fatura_builder
{
    protected const VARSAYILAN_PROFIL = invoice_builder::PROFILE_TICARI;

    /**
     * Faturayı oluşturur, e-Fatura sistemine gönderir.
     *
     * @return string belgeOid (durum sorgulamada kullanılır)
     */
    public function send(): string
    {
        $xml = $this->build_xml();

        return $this->client->invoice()->belge_gonder_ext(
            vergi_tc_kimlik_no: $this->satici->tax_number(),
            belge_no: $this->gecerli_belge_no(),
            veri_base64: base64_encode($xml),
            belge_hash_md5: strtoupper(md5($xml)),
            belge_versiyon: '2.1',
            erp_kodu: $this->erp_kodu,
            xslt_adi: $this->xslt_adi,
            xslt_veri_base64: $this->xslt_veri_base64,
        );
    }
}
