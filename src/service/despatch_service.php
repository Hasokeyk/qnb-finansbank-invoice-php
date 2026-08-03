<?php

namespace QnbSolutions\QnbEsolutions\service;

use QnbSolutions\QnbEsolutions\model\document_status;
use QnbSolutions\QnbEsolutions\model\incoming_document;
use QnbSolutions\QnbEsolutions\model\registered_user;

class despatch_service
{
    public function __construct(
        private readonly \SoapClient $connector,
    ) {
    }

    public function efatura_user_info(string $vergi_tc_kimlik_no): registered_user
    {
        $result = $this->connector->efaturaKullaniciBilgisi([
            'vergiTcKimlikNo' => $vergi_tc_kimlik_no,
        ]);

        $data = $result->return ?? $result;

        return new registered_user(
            label: $data->etiket ?? '',
            is_public_institution: ($data->kamuKurulusu ?? false) === true,
            registration_time: $data->kayitZamani ?? '',
            title: $data->unvan ?? '',
        );
    }

    public function kayitli_kullanici_listele_extended(
        int $gecmis_eklensin = 0,
        string $urun = 'EIRSALIYE',
    ): string {
        $result = $this->connector->kayitliKullaniciListeleExtended([
            'gecmisEklensin' => (string) $gecmis_eklensin,
            'urun' => $urun,
        ]);

        return $result->return ?? '';
    }

    public function belge_gonder_ext(
        string $vergi_tc_kimlik_no,
        string $belge_no,
        string $veri_base64,
        string $belge_hash_md5,
        string $belge_turu = 'IRSALIYE_UBL',
        string $belge_versiyon = '1.2',
        ?string $alan_etiket = null,
        ?string $gonderen_etiket = null,
        ?string $erp_kodu = null,
        ?string $sube_kodu = null,
    ): string {
        $params = [
            'vergiTcKimlikNo' => $vergi_tc_kimlik_no,
            'belgeTuru' => $belge_turu,
            'belgeNo' => $belge_no,
            'veri' => base64_decode($veri_base64, true),
            'belgeHash' => $belge_hash_md5,
            'mimeType' => 'application_xml',
            'belgeVersiyon' => $belge_versiyon,
            'erpKodu' => $erp_kodu ?? '',
            'alanEtiket' => $alan_etiket ?? '',
            'gonderenEtiket' => $gonderen_etiket ?? '',
            'subeKodu' => $sube_kodu ?? '',
        ];

        $result = $this->connector->belgeGonderExt([
            'parametreler' => $params,
        ]);

        $data = $result->return ?? $result;

        return $data->belgeOid ?? '';
    }

    public function giden_belge_durum_sorgula_ext(
        string $vergi_tc_kimlik_no,
        string $belge_no,
        string $belge_no_tip,
        string $belge_turu = 'IRSALIYE_UBL',
    ): document_status {
        $result = $this->connector->gidenBelgeDurumSorgulaExt([
            'vergiTcKimlikNo' => $vergi_tc_kimlik_no,
            'parametreler' => [
                'belgeNo' => $belge_no,
                'belgeNoTipi' => $belge_no_tip,
                'belgeTuru' => $belge_turu,
            ],
        ]);

        $data = $result->return ?? $result;

        return new document_status(
            alim_tarihi: $data->alimTarihi ?? '',
            belge_no: $data->belgeNo ?? '',
            durum: (int) ($data->durum ?? 0),
            ettn: $data->ettn ?? '',
            gonderim_cevabi_detayi: $data->aciklama ?? $data->gonderimCevabiDetayi ?? '',
            gonderim_cevabi_kodu: $data->gonderimCevabiKodu ?? '',
            gonderim_durumu: (int) ($data->gonderimDurumu ?? 0),
            olusturulma_tarihi: $data->olusturulmaTarihi ?? '',
            yanit_detayi: $data->yanitDetayi ?? '',
            yanit_durumu: $data->yanitDurumu ?? '',
            ulasti_mi: ($data->ulastiMi ?? false) === true,
            yeniden_gonderilebilir_mi: ($data->yenidenGonderilebilirMi ?? false) === true,
            yerel_belge_oid: $data->yerelBelgeOid ?? '',
        );
    }

    public function giden_belgeleri_indir_ext(
        string $vergi_tc_kimlik_no,
        array $belge_oid_listesi,
        string $belge_turu = 'IRSALIYE',
        string $belge_formati = 'PDF',
    ): string {
        $result = $this->connector->gidenBelgeleriIndirExt([
            'vergiTcKimlikNo' => $vergi_tc_kimlik_no,
            'belgeOidListesi' => implode(',', $belge_oid_listesi),
            'belgeTuru' => $belge_turu,
            'belgeFormati' => $belge_formati,
        ]);

        return $result->return ?? '';
    }

    public function gelen_belgeleri_listele_ext(
        string $vergi_tc_kimlik_no,
        string $son_alinan_belge_sira_numarasi = '0',
        string $belge_turu = 'IRSALIYE',
        ?string $alan_etiket = null,
        ?bool $belgeler_alindi_mi = null,
        ?string $donus_tipi_versiyon = null,
        ?string $erp_kodu = null,
        ?array $ettn_listesi = null,
        ?string $fatura_tarihi_baslangic = null,
        ?string $fatura_tarihi_bitis = null,
        ?string $gelis_tarihi_baslangic = null,
        ?string $gelis_tarihi_bitis = null,
        ?string $onay_durum = null,
    ): array {
        $params = [
            'vergiTcKimlikNo' => $vergi_tc_kimlik_no,
            'sonAlinanBelgeSiraNumarasi' => $son_alinan_belge_sira_numarasi,
            'belgeTuru' => $belge_turu,
            'alanEtiket' => $alan_etiket ?? '',
            'belgelerAlindiMi' => $belgeler_alindi_mi ?? '',
            'donusTipiVersiyon' => $donus_tipi_versiyon ?? '',
            'erpKodu' => $erp_kodu ?? '',
            'ettn' => $ettn_listesi ?? [],
            'faturaTarihiBaslangic' => $fatura_tarihi_baslangic ?? '',
            'faturaTarihiBitis' => $fatura_tarihi_bitis ?? '',
            'gelisTarihiBaslangic' => $gelis_tarihi_baslangic ?? '',
            'gelisTarihiBitis' => $gelis_tarihi_bitis ?? '',
            'onayDurum' => $onay_durum ?? '',
        ];

        $result = $this->connector->gelenBelgeleriListeleExt($params);

        $documents = [];
        if (isset($result->return)) {
            $items = is_array($result->return) ? $result->return : [$result->return];
            foreach ($items as $item) {
                $documents[] = new incoming_document(
                    belge_no: $item->belgeNo ?? '',
                    belge_sira_no: $item->belgeSiraNo ?? '',
                    belge_tarihi: $item->belgeTarihi ?? '',
                    belge_turu: $item->belgeTuru ?? '',
                    ettn: $item->ettn ?? '',
                    gonderen_etiket: $item->gonderenEtiket ?? '',
                    gonderen_vkn_tckn: $item->gonderenVknTckn ?? '',
                    alan_etiket: $item->alanEtiket ?? '',
                    alici_unvan: $item->aliciUnvan ?? '',
                    satici_unvan: $item->saticiUnvan ?? '',
                    zarf_id: $item->zarfId ?? '',
                    odenecek_tutar: $item->odenecekTutar ?? '',
                    odenecek_tutar_doviz_cinsi: $item->odenecekTutarDovizCinsi ?? '',
                    arsivlenmis: $item->arsivlenmis ?? '',
                    belge_hash: $item->belgeHash ?? '',
                    fatura_gelis_tarihi: $item->faturaGelisTarihi ?? '',
                );
            }
        }

        return $documents;
    }

    public function gelen_belgeleri_indir_ext(
        string $vergi_tc_kimlik_no,
        string $belge_ettn,
        string $belge_turu = 'IRSALIYE',
        string $belge_formati = 'PDF',
    ): string {
        $result = $this->connector->gelenBelgeleriIndirExt([
            'vergiTcKimlikNo' => $vergi_tc_kimlik_no,
            'belgeEttn' => $belge_ettn,
            'belgeTuru' => $belge_turu,
            'belgeFormati' => $belge_formati,
        ]);

        return $result->return ?? '';
    }

    public function belgeleri_tekrar_gonder(array $belge_oid_listesi): void
    {
        $this->connector->belgeleriTekrarGonder([
            'belgeOidListesi' => $belge_oid_listesi,
        ]);
    }
}
