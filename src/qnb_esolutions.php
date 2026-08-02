<?php

namespace QnbSolutions\QnbEsolutions;

use QnbSolutions\QnbEsolutions\builder\create_earsiv;
use QnbSolutions\QnbEsolutions\builder\create_efatura;
use QnbSolutions\QnbEsolutions\builder\customer_company;
use QnbSolutions\QnbEsolutions\builder\my_company;
use QnbSolutions\QnbEsolutions\builder\products;
use QnbSolutions\QnbEsolutions\exception\validation_exception;
use QnbSolutions\QnbEsolutions\model\archive_result;
use QnbSolutions\QnbEsolutions\model\document_status;
use QnbSolutions\QnbEsolutions\model\registered_user;

/**
 * Ana facade: e-Fatura / e-Arşiv için tek giriş noktası.
 *
 *     $qnb = new qnb_esolutions($username, $password, $erp_kodu);
 *
 *     $qnb->my_company()->set_company_name('Firma')->set_tax_number('...');
 *     $qnb->customer_company()->set_company_name('Alıcı')->set_tax_number('...');
 *     $qnb->add_product()->set_product_name('Hizmet')->set_quantity(1)->set_unit_price(100)->set_vat_rate(20);
 *
 *     $oid = $qnb->create_invoice('efatura')->send();  // belgeOid (e-Fatura)
 *     $res = $qnb->create_invoice('earsiv')->send();   // archive_result (e-Arşiv)
 *     // 'auto' → alıcının VKN'sinden e-Fatura mükellefi mi diye belirlenir
 */
class qnb_esolutions
{
    private my_company $my_company;
    private customer_company $customer_company;
    private products $products;
    private client $client;
    private string $erp_kodu;

    // Passthrough belge ayarları (create_invoice() içinde create_* nesnesine uygulanır)
    private string $document_no = '';
    private string $issue_date = '';
    private string $issue_time = '';
    private string $invoice_type = '';
    private string $profile = '';
    private string $currency = '';
    private ?string $note = null;
    private int $due_days = 0;
    private ?string $xslt_adi = null;
    private ?string $xslt_veri_base64 = null;

    public function __construct(
        string $username,
        string $password,
        string $erp_kodu = 'ERP1',
        string $environment = client::ENV_TEST2,
        ?client $client = null,
    ) {
        $this->my_company = new my_company();
        $this->customer_company = new customer_company();
        $this->products = new products();
        $this->client = $client ?? new client(username: $username, password: $password, environment: $environment);
        $this->erp_kodu = $erp_kodu;
    }

    public function my_company(): my_company
    {
        return $this->my_company;
    }

    public function customer_company(): customer_company
    {
        return $this->customer_company;
    }

    /**
     * Yeni ürün satırı başlatır ve products nesnesini döndürür (döngü dostu).
     */
    public function product(): products
    {
        return $this->products->add_product();
    }

    /** Yeni ürün satırı ekler (product() ile aynı davranış). */
    public function add_product(): products
    {
        return $this->products->add_product();
    }

    // ─── Passthrough belge ayarları ──────────────────────────────────────

    public function document_no(string $no): static
    {
        $this->document_no = $no;
        return $this;
    }

    public function issue_date(string $tarih, string $saat = ''): static
    {
        $this->issue_date = $tarih;
        $this->issue_time = $saat;
        return $this;
    }

    /**
     * GİB InvoiceTypeCode (SATIS, ISTISNA, IADE ...).
     * NOT: Bu belge türü değildir; belge türü create_invoice()'te seçilir.
     */
    public function invoice_type(string $tur): static
    {
        $this->invoice_type = $tur;
        return $this;
    }

    public function profile(string $profil): static
    {
        $this->profile = $profil;
        return $this;
    }

    public function currency(string $para): static
    {
        $this->currency = $para;
        return $this;
    }

    public function note(?string $aciklama): static
    {
        $this->note = $aciklama;
        return $this;
    }

    public function due_days(int $gun): static
    {
        $this->due_days = $gun;
        return $this;
    }

    /** Özel fatura tasarımı (XSLT). Base64 içerik bekler. */
    public function xslt(string $adi, string $veri_base64): static
    {
        $this->xslt_adi = $adi;
        $this->xslt_veri_base64 = $veri_base64;
        return $this;
    }

    // ─── Üretim ──────────────────────────────────────────────────────────

    /**
     * Faturayı hazırlar, doğrular ve gönderilmeye hazır builder'ı döndürür.
     *
     * Doğrulama bu metot içinde yapılır: hata varsa `validation_exception`
     * fırlatılır (try/catch ile yakalanır); `send()` yalnızca geçerli belgede çalışır.
     *
     * @param string $type 'auto' | 'efatura' | 'earsiv'
     *   'auto' → alıcının VKN'sinden e-Fatura mükellefi mi diye belirlenir;
     *   mükellefse e-Fatura, değilse e-Arşiv.
     * @return create_efatura|create_earsiv send() çağıran tarafından yapılır
     * @throws validation_exception doğrulama hataları varsa
     * @throws \InvalidArgumentException geçersiz type değerinde
     */
    public function create_invoice(string $type = 'auto'): create_efatura|create_earsiv
    {
        $turu = $this->resolve_type($type);

        // Explicit 'efatura' seçildiyse alıcı e-Fatura mükellefi mi diye göndermeden önce kontrol et.
        // (1172 TPS_POSTA_KUTUSU_YETKISI_YOK hatasına karşı — mükellef değilse GİB zarfı reddeder.)
        if ($type === 'efatura' && $this->customer_company->tax_number() !== '' && !$this->is_efatura_taxpayer()) {
            throw new validation_exception([
                "Alıcı '{$this->customer_company->tax_number()}' e-Fatura mükellefi değil — e-Fatura gönderilemez. "
                . "Alıcı mükellef değilse 'earsiv' kullanın ya da alıcı VKN'yi doğrulayın (1172 TPS_POSTA_KUTUSU_YETKISI_YOK).",
            ]);
        }

        $builder = $turu === 'earsiv'
            ? new create_earsiv($this->my_company, $this->customer_company, $this->products, $this->client)
            : new create_efatura($this->my_company, $this->customer_company, $this->products, $this->client);

        $builder->erp_code($this->erp_kodu);
        if ($this->document_no !== '') {
            $builder->document_no($this->document_no);
        }
        if ($this->issue_date !== '') {
            $builder->issue_date($this->issue_date, $this->issue_time);
        }
        if ($this->invoice_type !== '') {
            $builder->invoice_type($this->invoice_type);
        }
        if ($this->profile !== '') {
            $builder->profile($this->profile);
        }
        if ($this->currency !== '') {
            $builder->currency($this->currency);
        }
        if ($this->note !== null) {
            $builder->note($this->note);
        }
        if ($this->due_days > 0) {
            $builder->due_days($this->due_days);
        }
        if ($this->xslt_adi !== null) {
            $builder->xslt($this->xslt_adi, $this->xslt_veri_base64 ?? '');
        }

        // Doğrulama create_invoice içinde — hata varsa exception fırlatır
        $hatalar = $builder->validate();
        if ($hatalar !== []) {
            throw new validation_exception($hatalar);
        }

        return $builder;
    }

    /**
     * Gönderilen belgenin işlem durumunu sorgular (e-Fatura asenkrondur,
     * panelde görünmesi için işlenmesi gerekir).
     */
    public function status(string $belge_no, string $belge_no_tip = 'OID'): document_status
    {
        return $this->client->invoice()->giden_belge_durum_sorgula_ext(
            vergi_tc_kimlik_no: $this->my_company->tax_number(),
            belge_no: $belge_no,
            belge_no_tip: $belge_no_tip,
        );
    }

    /**
     * Alıcının e-Fatura mükellefi olup olmadığını sorgular (canlı/GİB).
     *
     * @param string|null $vergi_tc_kimlik_no VKN/TCKN; verilmezse customer_company()'den alınır
     */
    public function is_efatura_taxpayer(?string $vergi_tc_kimlik_no = null): bool
    {
        $alici_vkn = $vergi_tc_kimlik_no ?? $this->customer_company->tax_number();
        if ($alici_vkn === '') {
            return false;
        }

        $info = $this->client->invoice()->efatura_kullanici_bilgisi($alici_vkn);
        return $info->unvan !== '' || $info->etiket !== '' || $info->kayit_zamani !== '';
    }

    /** Alıcının e-Fatura kayıt bilgilerini sorgular (unvan, etiket, kayıt zamanı). */
    public function efatura_kullanici_bilgisi(string $vergi_tc_kimlik_no): registered_user
    {
        return $this->client->invoice()->efatura_kullanici_bilgisi($vergi_tc_kimlik_no);
    }

    /**
     * Belge türünü çözümler: 'auto' → alıcı mükellefiyetine göre 'efatura'/'earsiv'.
     *
     * @param string $type 'auto' | 'efatura' | 'earsiv'
     */
    public function resolve_type(string $type): string
    {
        if ($type === 'efatura' || $type === 'earsiv') {
            return $type;
        }

        if ($type !== 'auto') {
            throw new \InvalidArgumentException("Geçersiz belge türü: '{$type}' — 'auto', 'efatura' veya 'earsiv' olmalı.");
        }

        // auto: alıcı e-Fatura mükellefi mi?
        return $this->is_efatura_taxpayer() ? 'efatura' : 'earsiv';
    }
}
