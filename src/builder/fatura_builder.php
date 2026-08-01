<?php

namespace QnbSolutions\QnbEsolutions\builder;

use QnbSolutions\QnbEsolutions\client;
use QnbSolutions\QnbEsolutions\exception\validation_exception;
use QnbSolutions\QnbEsolutions\model\archive_result;

/**
 * create_efatura / create_earsiv için ortak zincirleme taban.
 *
 * Firma (satıcı/alıcı) ve ürün bilgileri entity nesnelerle verilir;
 * belge detayları (document_no, issue_date, invoice_type, erp_code vb.) zincirleme ayarlanır.
 * `build_xml()` UBL XML üretir (göndermez), `send()` üretip gönderir.
 */
abstract class fatura_builder
{
    /** Alt sınıf varsayılan profilini belirler (TICARIFATURA / EARSIVFATURA). */
    protected const VARSAYILAN_PROFIL = '';

    protected my_company $satici;
    protected customer_company $alici;
    protected products $urunler;
    protected client $client;

    protected string $belge_no = '';
    protected string $tarih = '';
    protected string $saat = '';
    protected string $fatura_turu = invoice_builder::TYPE_SATIS;
    protected string $profil = '';
    protected string $para_birimi = 'TRY';
    protected string $erp_kodu = 'ERP1';
    protected ?string $aciklama = null;
    protected int $teslim_gun = 0;

    protected ?string $xslt_adi = null;
    protected ?string $xslt_veri_base64 = null;

    public function __construct(
        my_company $satici,
        customer_company $alici,
        products $urunler,
        client $client,
    ) {
        $this->satici = $satici;
        $this->alici = $alici;
        $this->urunler = $urunler;
        $this->client = $client;

        $this->profil = static::VARSAYILAN_PROFIL;
    }

    // ─── Zincirleme belge detayları ──────────────────────────────────────

    public function document_no(string $no): static
    {
        $this->belge_no = $no;
        return $this;
    }

    public function issue_date(string $tarih, string $saat = ''): static
    {
        $this->tarih = $tarih;
        $this->saat = $saat;
        return $this;
    }

    public function invoice_type(string $tur): static
    {
        $this->fatura_turu = $tur;
        return $this;
    }

    public function profile(string $profil): static
    {
        $this->profil = $profil;
        return $this;
    }

    public function currency(string $para): static
    {
        $this->para_birimi = $para;
        return $this;
    }

    public function erp_code(string $kod): static
    {
        $this->erp_kodu = $kod;
        return $this;
    }

    public function note(?string $aciklama): static
    {
        $this->aciklama = $aciklama;
        return $this;
    }

    public function due_days(int $gun): static
    {
        $this->teslim_gun = $gun;
        return $this;
    }

    /** Özel fatura tasarımı (XSLT) belirtir. Base64 içerik bekler. */
    public function xslt(string $adi, string $veri_base64): static
    {
        $this->xslt_adi = $adi;
        $this->xslt_veri_base64 = $veri_base64;
        return $this;
    }

    // ─── Üretim ──────────────────────────────────────────────────────────

    /**
     * Belgeyi göndermeden önce doğrular.
     *
     * GİB sıkı kuralı: muafiyet/istisna kodlu satır varsa
     *  - kod, GİB'in resmi kdvIstisnaKodu listesinde olmalı (kdv_istisna_kodlari::KODLAR);
     *  - fatura tipi istisna ailesinden olmalı (ISTISNA, IADE, IHRACKAYITLI, SGK, YTBIADE, YTBISTISNA);
     *  - vergi istisna/muafiyet sebebi (TaxExemptionReason) boş olamaz — kullanıcı açıklama
     *    vermezse resmi açıklama (kdv_istisna_kodlari::aciklama) otomatik doldurulur.
     *
     * @return list<string> hata mesajları (boş = geçerli)
     */
    public function validate(): array
    {
        $hatalar = [];
        $uyumlu = invoice_builder::MUAFIYET_UYUMLU_TIPLER;

        foreach ($this->urunler->lines() as $i => $satir) {
            $kod = $satir['vat_exemption_code'];
            if ($kod === '') {
                continue;
            }

            $satir_no = $i + 1;

            if (!kdv_istisna_kodlari::gecerli($kod)) {
                $hatalar[] = "Satır {$satir_no}: bilinmeyen vergi istisna/muafiyet kodu '{$kod}' — "
                    . 'geçerli GİB kodları için kdv_istisna_kodlari::KODLAR listesine bakınız.';
                continue;
            }

            if (!in_array($this->fatura_turu, $uyumlu, true)) {
                $hatalar[] = "Satır {$satir_no}: vergi istisna/muafiyet kodu '{$kod}' fatura tipi '{$this->fatura_turu}' ile uyumsuz. "
                    . 'Muafiyet kodları için fatura tipi ISTISNA, IADE, IHRACKAYITLI, SGK, YTBIADE veya YTBISTISNA olmalı.';
            }

            // Kullanıcı açıklama vermezse resmi açıklama otomatik dolduğundan,
            // yalnızca koddan da çıkmıyorsa hata üret.
            if ($satir['vat_exemption_description'] === '' && kdv_istisna_kodlari::aciklama($kod) === '') {
                $hatalar[] = "Satır {$satir_no}: muafiyet kodu '{$kod}' için vergi istisna/muafiyet sebebi bulunamadı — "
                    . 'set_vat_exemption_description(...) ile bir açıklama giriniz.';
            }
        }

        return $hatalar;
    }

    /**
     * UBL XML üretir. Göndermez; send() buna hash+base64 uygular.
     * document_no verilmemişse otomatik tekil üretilir.
     */
    public function build_xml(): string
    {
        $hatalar = $this->validate();
        if ($hatalar !== []) {
            throw new validation_exception($hatalar);
        }

        $belge_no = $this->gecerli_belge_no();

        $builder = (new invoice_builder)
            ->set_fatura_no($belge_no)
            ->set_tarih($this->tarih !== '' ? $this->tarih : date('Y-m-d'), $this->saat)
            ->set_fatura_turu($this->fatura_turu)
            ->set_profil($this->profil)
            ->set_parabirimi($this->para_birimi)
            ->set_satici(vkn: $this->satici->tax_number(), unvan: $this->satici->company_name(), etiket: $this->satici->label())
            ->set_satici_adres($this->satici->address(), $this->satici->district(), $this->satici->city(), $this->satici->country())
            ->set_satici_vergi_dairesi($this->satici->tax_office())
            ->set_alici(vkn: $this->alici->tax_number(), unvan: $this->alici->company_name(), ad: $this->alici->first_name(), soyad: $this->alici->last_name())
            ->set_alici_adres($this->alici->address(), $this->alici->district(), $this->alici->city(), $this->alici->country())
            ->set_alici_vergi_dairesi($this->alici->tax_office())
            ->set_aciklama($this->aciklama);

        if ($this->teslim_gun > 0) {
            $builder->set_teslim_gun($this->teslim_gun);
        }

        foreach ($this->urunler->lines() as $satir) {
            // Açıklama verilmemişse resmi GİB açıklamasını otomatik doldur
            // (kullanıcı yalnızca muafiyet kodunu girer).
            $muafiyet_aciklama = $satir['vat_exemption_description'];
            if ($muafiyet_aciklama === '' && $satir['vat_exemption_code'] !== '') {
                $muafiyet_aciklama = kdv_istisna_kodlari::aciklama($satir['vat_exemption_code']);
            }

            $builder->add_satir(
                isim: $satir['name'],
                miktar: $satir['quantity'],
                birim: $satir['unit'],
                birim_fiyat: $satir['unit_price'],
                kdv_oran: $satir['vat_rate'],
                iskonto: $satir['discount'],
                kdv_muafiyet_kodu: $satir['vat_exemption_code'],
                kdv_muafiyet_aciklama: $muafiyet_aciklama,
            );
        }

        return $builder->build();
    }

    /**
     * Faturayı keser ve gönderir.
     *
     * @return string|archive_result e-Fatura'da belgeOid, e-Arşiv'de archive_result
     */
    abstract public function send(): string|archive_result;

    /** Kesme sırasında gönderilecek belge numarası (XML ile aynı olmalı). */
    protected function gecerli_belge_no(): string
    {
        if ($this->belge_no === '') {
            $this->belge_no = $this->otomatik_belge_no();
        }
        return $this->belge_no;
    }

    /**
     * GİB zorunlu belge numarası formatı: 3 harf + yıl + 9 hane seri (16 karakter).
     * Örnek: NXT2026000000002
     */
    private function otomatik_belge_no(): string
    {
        $seri = substr((string) ((int) round(microtime(true) * 1000000)), -9);
        return 'NXT' . date('Y') . $seri;
    }
}
