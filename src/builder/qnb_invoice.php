<?php

namespace QnbSolutions\QnbEsolutions\builder;

use QnbSolutions\QnbEsolutions\client;
use QnbSolutions\QnbEsolutions\model\archive_result;
use QnbSolutions\QnbEsolutions\model\document_status;

/**
 * Hafif facade: şifre kullanıcıdan alınır, gömülü yoktur.
 *
 *     $qnb = qnb_invoice($username, $password, $erp_kodu);
 *
 *     $qnb->my_company()->set_company_name('Firma')->set_tax_number('...')->set_address('...', 'Çankaya', 'Ankara');
 *     $qnb->customer_company()->set_company_name('Alıcı')->set_tax_number('...');
 *     $qnb->product()->set_product_name('Hizmet')->set_quantity(1)->set_unit_price(100)->set_vat_rate(20);
 *     $oid = $qnb->send();   // → belgeOid
 *
 * e-Arşiv için: qnb_earsiv($username, $password, $erp_kodu) → send() → archive_result.
 */
class qnb_invoice
{
    private my_company $my_company;
    private customer_company $customer_company;
    private products $products;
    private client $client;
    private string $erp_kodu;
    private string $tur;

    // Passthrough belge ayarları (olustur() içinde create_* nesnesine uygulanır)
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
        string $tur = 'efatura',
        ?client $client = null,
    ) {
        $this->my_company = new my_company();
        $this->customer_company = new customer_company();
        $this->products = new products();
        $this->client = $client ?? new client(username: $username, password: $password, environment: $environment);
        $this->erp_kodu = $erp_kodu;
        $this->tur = $tur;
    }

    /** Facade'ın kendisini döndürür: `$qnb->invoice()->my_company()` kullanımı için. */
    public function invoice(): static
    {
        return $this;
    }

    public function efatura(): static
    {
        $this->tur = 'efatura';
        return $this;
    }

    public function earsiv(): static
    {
        $this->tur = 'earsiv';
        return $this;
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
     * Yeni ürün satırı başlatır ve products nesnesini döndürür.
     *
     * Her çağrı yeni bir satır ekler — döngü dostudur:
     *
     *     foreach ($urunler as $u) {
     *         $qnb->product()->set_product_name($u['isim'])->set_unit_price($u['fiyat']);
     *     }
     */
    public function product(): products
    {
        return $this->products->add_product();
    }

    /** Türkçe zincir alternatifi: `->urun()->set_product_name(...)`. */
    public function urun(): products
    {
        return $this->products->add_product();
    }

    /** product() ile aynı davranış: yeni ürün satırı başlatır (geriye dönük uyumluluk). */
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

    public function xslt(string $adi, string $veri_base64): static
    {
        $this->xslt_adi = $adi;
        $this->xslt_veri_base64 = $veri_base64;
        return $this;
    }

    // ─── Üretim ──────────────────────────────────────────────────────────

    /** UBL XML üretir (göndermez). */
    public function build_xml(): string
    {
        return $this->olustur()->build_xml();
    }

    /** Faturayı oluşturur ve gönderir. e-Fatura → belgeOid, e-Arşiv → archive_result. */
    public function send(): string|archive_result
    {
        return $this->olustur()->send();
    }

    /**
     * Belgeyi göndermeden önce doğrular.
     *
     * @return list<string> hata mesajları (boş = geçerli)
     */
    public function validate(): array
    {
        return $this->olustur()->validate();
    }

    /**
     * Gönderilen belgenin işlem durumunu sorgular (e-Fatura asenkrondur,
     * panelde görünmesi için işlenmesi gerekir).
     *
     * @param string $belge_no OID (varsayılan) veya yerel belge no
     */
    public function status(string $belge_no, string $belge_no_tip = 'OID'): document_status
    {
        return $this->client->invoice()->giden_belge_durum_sorgula_ext(
            vergi_tc_kimlik_no: $this->my_company->tax_number(),
            belge_no: $belge_no,
            belge_no_tip: $belge_no_tip,
        );
    }

    /** Verilen entity'lerle bir e-Fatura nesnesi kurar. */
    public function create_efatura(my_company $my_company, customer_company $customer_company, products $products): create_efatura
    {
        return (new create_efatura($my_company, $customer_company, $products, $this->client))
            ->erp_code($this->erp_kodu);
    }

    /** Verilen entity'lerle bir e-Arşiv nesnesi kurar. */
    public function create_earsiv(my_company $my_company, customer_company $customer_company, products $products): create_earsiv
    {
        return (new create_earsiv($my_company, $customer_company, $products, $this->client))
            ->erp_code($this->erp_kodu);
    }

    private function olustur(): create_efatura|create_earsiv
    {
        $builder = $this->tur === 'earsiv'
            ? $this->create_earsiv($this->my_company, $this->customer_company, $this->products)
            : $this->create_efatura($this->my_company, $this->customer_company, $this->products);

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

        return $builder;
    }
}
