<?php

namespace QnbSolutions\QnbEsolutions\builder;

class invoice_builder
{
    public const TYPE_SATIS = 'SATIS';
    public const TYPE_IADE = 'IADE';
    public const TYPE_TICARI = 'TICARI';
    public const TYPE_KDV_MUAFIYETI = 'KDV_MUAFIYETI';
    public const TYPE_ISTISNA = 'ISTISNA';
    public const TYPE_TEVKIFAT = 'TEVKIFAT';
    public const TYPE_TASHIH = 'TASHIH';
    public const TYPE_IHRACAT = 'IHRACAT';
    public const TYPE_YOLCU_BERABERI = 'YOLCU_BERABERI';

    public const PROFILE_TEMEL = 'TEMELFATURA';
    public const PROFILE_TICARI = 'TICARIFATURA';
    public const PROFILE_EARSIV = 'EARSIVFATURA';
    public const PROFILE_IHRACAT = 'IHRACAT';

    private const TAX_TYPE_CODES = [1 => '0015', 8 => '0015', 10 => '0015', 18 => '0015', 20 => '0015'];

    private string $fatura_no = '';
    private string $fatura_tarih = '';
    private string $fatura_saat = '';
    private string $fatura_turu = self::TYPE_SATIS;
    private string $profil = self::PROFILE_EARSIV;
    private string $parabirimi = 'TRY';

    private string $satici_vkn = '';
    private string $satici_unvan = '';
    private string $satici_etiket = '';

    private string $alici_vkn = '';
    private string $alici_unvan = '';
    private string $alici_ad = '';
    private string $alici_soyad = '';

    private string $alici_ilce = '';
    private string $alici_il = '';
    private string $alici_adres = '';
    private string $alici_ulke = 'Türkiye';

    private array $satirlar = [];
    private string|null $aciklama = null;
    private int $teslim_gun = 0;

    public function set_fatura_no(string $no): static
    {
        $this->fatura_no = $no;
        return $this;
    }

    public function set_tarih(string $tarih, string $saat = ''): static
    {
        $this->fatura_tarih = $tarih;
        $this->fatura_saat = $saat;
        return $this;
    }

    public function set_fatura_turu(string $tur): static
    {
        $this->fatura_turu = $tur;
        return $this;
    }

    public function set_profil(string $profil): static
    {
        $this->profil = $profil;
        return $this;
    }

    public function set_parabirimi(string $para): static
    {
        $this->parabirimi = $para;
        return $this;
    }

    public function set_satici(string $vkn, string $unvan, string $etiket = ''): static
    {
        $this->satici_vkn = $vkn;
        $this->satici_unvan = $unvan;
        $this->satici_etiket = $etiket;
        return $this;
    }

    public function set_alici(string $vkn, string $unvan, string $ad = '', string $soyad = ''): static
    {
        $this->alici_vkn = $vkn;
        $this->alici_unvan = $unvan;
        $this->alici_ad = $ad;
        $this->alici_soyad = $soyad;
        return $this;
    }

    public function set_alici_adres(string $adres, string $ilce, string $il, string $ulke = 'Türkiye'): static
    {
        $this->alici_adres = $adres;
        $this->alici_ilce = $ilce;
        $this->alici_il = $il;
        $this->alici_ulke = $ulke;
        return $this;
    }

    public function set_aciklama(?string $aciklama): static
    {
        $this->aciklama = $aciklama;
        return $this;
    }

    public function set_teslim_gun(int $gun): static
    {
        $this->teslim_gun = $gun;
        return $this;
    }

    public function add_satir(
        string $isim,
        float $miktar,
        string $birim = 'C62',
        float $birim_fiyat = 0.0,
        float $kdv_oran = 20.0,
        float $iskonto = 0.0,
        string $kdv_muafiyet_kodu = '',
        string $kdv_muafiyet_aciklama = '',
    ): static {
        $this->satirlar[] = [
            'isim' => $isim,
            'miktar' => $miktar,
            'birim' => $birim,
            'birim_fiyat' => $birim_fiyat,
            'kdv_oran' => $kdv_oran,
            'iskonto' => $iskonto,
            'kdv_muafiyet_kodu' => $kdv_muafiyet_kodu,
            'kdv_muafiyet_aciklama' => $kdv_muafiyet_aciklama,
        ];
        return $this;
    }

    public function build(): string
    {
        $no = $this->fatura_no ?: 'NXT' . date('Ymd') . '001';
        $tarih = $this->fatura_tarih ?: date('Y-m-d');
        $saat = $this->fatura_saat;

        $kdv_toplam = 0.0;
        $mal_toplam = 0.0;
        $satir_xml = '';

        foreach ($this->satirlar as $i => $s) {
            $line_no = $i + 1;
            $ara_toplam = round($s['miktar'] * $s['birim_fiyat'], 2);
            $iskonto_sonrasi = $ara_toplam;
            $iskonto_xml = '';

            if ($s['iskonto'] > 0) {
                $iskonto_sonrasi = round($ara_toplam - $s['iskonto'], 2);
                $iskonto_xml = <<<XML
            <cac:AllowanceCharge>
               <cbc:ChargeIndicator>false</cbc:ChargeIndicator>
               <cbc:Amount currencyID="{$this->parabirimi}">{$s['iskonto']}</cbc:Amount>
               <cbc:BaseAmount currencyID="{$this->parabirimi}">{$ara_toplam}</cbc:BaseAmount>
            </cac:AllowanceCharge>
XML;
            }

            $muafiyetli = $s['kdv_muafiyet_kodu'] !== '';

            if ($muafiyetli) {
                $kdv_tutar = 0.0;
            } else {
                $kdv_tutar = round($iskonto_sonrasi * $s['kdv_oran'] / 100, 2);
            }

            $kdv_toplam += $kdv_tutar;
            $mal_toplam += $iskonto_sonrasi;

            $muafiyet_xml = '';
            if ($muafiyetli) {
                $aciklama = $s['kdv_muafiyet_aciklama'] !== ''
                    ? "\n               <cbc:TaxExemptionReason>{$s['kdv_muafiyet_aciklama']}</cbc:TaxExemptionReason>"
                    : '';
                $muafiyet_xml = <<<XML
               <cbc:TaxExemptionReasonCode>{$s['kdv_muafiyet_kodu']}</cbc:TaxExemptionReasonCode>{$aciklama}
XML;
            }

            $satir_xml .= <<<XML
            <cac:InvoiceLine>
               <cbc:ID>{$line_no}</cbc:ID>
               <cbc:InvoicedQuantity unitCode="{$s['birim']}">{$s['miktar']}</cbc:InvoicedQuantity>
               <cbc:LineExtensionAmount currencyID="{$this->parabirimi}">{$iskonto_sonrasi}</cbc:LineExtensionAmount>
{$iskonto_xml}
               <cac:TaxTotal>
                  <cbc:TaxAmount currencyID="{$this->parabirimi}">{$kdv_tutar}</cbc:TaxAmount>
                  <cac:TaxSubtotal>
                     <cbc:TaxableAmount currencyID="{$this->parabirimi}">{$iskonto_sonrasi}</cbc:TaxableAmount>
                     <cbc:TaxAmount currencyID="{$this->parabirimi}">{$kdv_tutar}</cbc:TaxAmount>
                     <cbc:Percent>{$s['kdv_oran']}</cbc:Percent>
                     <cac:TaxCategory>
{$muafiyet_xml}
                        <cac:TaxScheme>
                           <cbc:Name>KDV</cbc:Name>
                           <cbc:TaxTypeCode>0015</cbc:TaxTypeCode>
                        </cac:TaxScheme>
                     </cac:TaxCategory>
                  </cac:TaxSubtotal>
               </cac:TaxTotal>
               <cac:Item>
                  <cbc:Name>{$s['isim']}</cbc:Name>
               </cac:Item>
               <cac:Price>
                  <cbc:PriceAmount currencyID="{$this->parabirimi}">{$s['birim_fiyat']}</cbc:PriceAmount>
               </cac:Price>
            </cac:InvoiceLine>
XML;
        }

        $kdv_toplam = round($kdv_toplam, 2);
        $genel_toplam = round($mal_toplam + $kdv_toplam, 2);

        $alici_xml = $this->build_alici();
        $aciklama_xml = $this->aciklama ? "   <cbc:Note>{$this->aciklama}</cbc:Note>\n" : '';
        $odeme_xml = '';
        if ($this->teslim_gun > 0) {
            $bit_tarih = date('Y-m-d', strtotime($tarih . ' +' . $this->teslim_gun . ' days'));
            $odeme_xml = <<<XML
            <cac:PaymentMeans>
               <cbc:PaymentMeansCode>ZZZ</cbc:PaymentMeansCode>
               <cbc:PaymentDueDate>{$bit_tarih}</cbc:PaymentDueDate>
            </cac:PaymentMeans>
XML;
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
         xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
         xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
         xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">
   <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
   <cbc:CustomizationID>TR1.2</cbc:CustomizationID>
   <cbc:ProfileID>{$this->profil}</cbc:ProfileID>
   <cbc:ID>{$no}</cbc:ID>
   <cbc:IssueDate>{$tarih}</cbc:IssueDate>
{$aciklama_xml}
   <cbc:InvoiceTypeCode>{$this->fatura_turu}</cbc:InvoiceTypeCode>
   <cbc:DocumentCurrencyCode>{$this->parabirimi}</cbc:DocumentCurrencyCode>
   <cac:AccountingSupplierParty>
      <cac:Party>
         <cac:PartyIdentification>
            <cbc:ID schemeID="VKN">{$this->satici_vkn}</cbc:ID>
         </cac:PartyIdentification>
         <cac:PartyName>
            <cbc:Name>{$this->satici_unvan}</cbc:Name>
         </cac:PartyName>
      </cac:Party>
   </cac:AccountingSupplierParty>
{$alici_xml}
   <cac:TaxTotal>
      <cbc:TaxAmount currencyID="{$this->parabirimi}">{$kdv_toplam}</cbc:TaxAmount>
   </cac:TaxTotal>
   <cac:LegalMonetaryTotal>
      <cbc:LineExtensionAmount currencyID="{$this->parabirimi}">{$mal_toplam}</cbc:LineExtensionAmount>
      <cbc:TaxExclusiveAmount currencyID="{$this->parabirimi}">{$mal_toplam}</cbc:TaxExclusiveAmount>
      <cbc:TaxInclusiveAmount currencyID="{$this->parabirimi}">{$genel_toplam}</cbc:TaxInclusiveAmount>
      <cbc:PayableAmount currencyID="{$this->parabirimi}">{$genel_toplam}</cbc:PayableAmount>
   </cac:LegalMonetaryTotal>
{$odeme_xml}
{$satir_xml}
</Invoice>
XML;
    }

    private function build_alici(): string
    {
        $vkn_tckn = strlen($this->alici_vkn) <= 11 && is_numeric($this->alici_vkn) && strlen($this->alici_vkn) === 11
            ? 'TCKN'
            : 'VKN';

        $ad_soyad = '';
        if ($this->alici_ad !== '') {
            $ad_soyad = $this->alici_soyad !== ''
                ? "{$this->alici_ad} {$this->alici_soyad}"
                : $this->alici_ad;
        }

        $kisi = $ad_soyad !== '' ? <<<XML
            <cac:Contact>
               <cbc:Name>{$ad_soyad}</cbc:Name>
            </cac:Contact>
XML : '';

        $adres = $this->alici_adres !== '' ? <<<XML
            <cac:PostalAddress>
               <cbc:StreetName>{$this->alici_adres}</cbc:StreetName>
               <cbc:CitySubdivisionName>{$this->alici_ilce}</cbc:CitySubdivisionName>
               <cbc:CityName>{$this->alici_il}</cbc:CityName>
            </cac:PostalAddress>
XML : '';

        return <<<XML
   <cac:AccountingCustomerParty>
      <cac:Party>
         <cac:PartyIdentification>
            <cbc:ID schemeID="{$vkn_tckn}">{$this->alici_vkn}</cbc:ID>
         </cac:PartyIdentification>
         <cac:PartyName>
            <cbc:Name>{$this->alici_unvan}</cbc:Name>
         </cac:PartyName>
{$kisi}
{$adres}
      </cac:Party>
   </cac:AccountingCustomerParty>
XML;
    }
}
