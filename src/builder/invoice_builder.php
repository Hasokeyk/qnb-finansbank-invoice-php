<?php

namespace QnbSolutions\QnbEsolutions\builder;

class invoice_builder
{
    public const TYPE_SATIS = 'SATIS';
    public const TYPE_IADE = 'IADE';
    public const TYPE_TICARI = 'SATIS';
    public const TYPE_KDV_MUAFIYETI = 'ISTISNA';
    public const TYPE_ISTISNA = 'ISTISNA';
    public const TYPE_TEVKIFAT = 'TEVKIFAT';
    public const TYPE_TASHIH = 'TASHIH';
    public const TYPE_IHRACAT = 'IHRACAT';
    public const TYPE_YOLCU_BERABERI = 'YOLCU_BERABERI';
    public const TYPE_IHRAC_KAYITLI = 'IHRACKAYITLI';
    public const TYPE_SGK = 'SGK';
    public const TYPE_YTBIADE = 'YTBIADE';
    public const TYPE_YTBISTISNA = 'YTBISTISNA';

    /** Muafiyet/istisna kodlu satır içeren faturaların geçerli olduğu fatura tipleri (GİB). */
    public const MUAFIYET_UYUMLU_TIPLER = [
        self::TYPE_ISTISNA,
        self::TYPE_IADE,
        self::TYPE_IHRAC_KAYITLI,
        self::TYPE_SGK,
        self::TYPE_YTBIADE,
        self::TYPE_YTBISTISNA,
    ];

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
    private string $satici_vergi_dairesi = 'ÇANKAYA';
    private string $satici_adres = '';
    private string $satici_ilce = '';
    private string $satici_il = '';
    private string $satici_ulke = 'Türkiye';

    private string $alici_vkn = '';
    private string $alici_unvan = '';
    private string $alici_ad = '';
    private string $alici_soyad = '';
    private string $alici_vergi_dairesi = 'ÇANKAYA';

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

    public function set_satici_vergi_dairesi(string $vergi_dairesi): static
    {
        $this->satici_vergi_dairesi = $vergi_dairesi;
        return $this;
    }

    public function set_satici_adres(string $adres, string $ilce, string $il, string $ulke = 'Türkiye'): static
    {
        $this->satici_adres = $adres;
        $this->satici_ilce = $ilce;
        $this->satici_il = $il;
        $this->satici_ulke = $ulke;
        return $this;
    }

    public function set_alici_vergi_dairesi(string $vergi_dairesi): static
    {
        $this->alici_vergi_dairesi = $vergi_dairesi;
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
        $kdv_gruplari = [];

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
               <cbc:MultiplierFactorNumeric>{$s['iskonto']}</cbc:MultiplierFactorNumeric>
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

            $grup_key = $s['kdv_oran'] . '|' . ($muafiyetli ? $s['kdv_muafiyet_kodu'] : '');
            if (!isset($kdv_gruplari[$grup_key])) {
                $kdv_gruplari[$grup_key] = [
                    'oran' => $s['kdv_oran'],
                    'muafiyet_kodu' => $s['kdv_muafiyet_kodu'],
                    'muafiyet_aciklama' => $s['kdv_muafiyet_aciklama'],
                    'taxable' => 0.0,
                    'tax' => 0.0,
                ];
            }
            $kdv_gruplari[$grup_key]['taxable'] += $iskonto_sonrasi;
            $kdv_gruplari[$grup_key]['tax'] += $kdv_tutar;

            $muafiyet_xml = '';
            if ($muafiyetli) {
                $aciklama = $s['kdv_muafiyet_aciklama'] !== ''
                    ? "\n                  <cbc:TaxExemptionReason>{$s['kdv_muafiyet_aciklama']}</cbc:TaxExemptionReason>"
                    : '';
                $muafiyet_xml = <<<XML
                  <cbc:TaxExemptionReasonCode>{$s['kdv_muafiyet_kodu']}</cbc:TaxExemptionReasonCode>{$aciklama}
XML;
            }

            $percent_xml = $muafiyetli ? '' : "                  <cbc:Percent>{$s['kdv_oran']}</cbc:Percent>\n";

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
{$percent_xml}                     <cac:TaxCategory>
{$muafiyet_xml}                        <cac:TaxScheme>
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

        $header_vergi_xml = '';
        foreach ($kdv_gruplari as $g) {
            $muafiyetli = $g['muafiyet_kodu'] !== '';
            $muafiyet_xml = '';
            if ($muafiyetli) {
                $aciklama = $g['muafiyet_aciklama'] !== ''
                    ? "\n                  <cbc:TaxExemptionReason>{$g['muafiyet_aciklama']}</cbc:TaxExemptionReason>"
                    : '';
                $muafiyet_xml = "                  <cbc:TaxExemptionReasonCode>{$g['muafiyet_kodu']}</cbc:TaxExemptionReasonCode>{$aciklama}\n";
            }
            $percent_xml = $muafiyetli ? '' : "                     <cbc:Percent>{$g['oran']}</cbc:Percent>\n";
            $header_vergi_xml .= <<<XML
         <cac:TaxSubtotal>
            <cbc:TaxableAmount currencyID="{$this->parabirimi}">{$g['taxable']}</cbc:TaxableAmount>
            <cbc:TaxAmount currencyID="{$this->parabirimi}">{$g['tax']}</cbc:TaxAmount>
{$percent_xml}            <cac:TaxCategory>
{$muafiyet_xml}               <cac:TaxScheme>
                  <cbc:Name>KDV</cbc:Name>
                  <cbc:TaxTypeCode>0015</cbc:TaxTypeCode>
               </cac:TaxScheme>
            </cac:TaxCategory>
         </cac:TaxSubtotal>

XML;
        }

        $satici_adres_xml = $this->build_adres(
            $this->satici_adres,
            $this->satici_ilce,
            $this->satici_il,
            $this->satici_ulke,
            3,
        );
        $alici_xml = $this->build_alici();
        $satir_sayisi = count($this->satirlar);

        $aciklama_xml = $this->aciklama ? "   <cbc:Note>{$this->aciklama}</cbc:Note>\n" : '';
        $saat_xml = $saat !== '' ? "   <cbc:IssueTime>{$saat}</cbc:IssueTime>\n" : '';

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

        $signature_xml = $this->build_signature();

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
         xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
         xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
         xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2"
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xmlns:n4="http://www.altova.com/samplexml/other-namespace"
         xsi:schemaLocation="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2 UBL-Invoice-2.1.xsd">
   <ext:UBLExtensions>
      <ext:UBLExtension>
         <ext:ExtensionContent>
            <n4:auto-generated_for_wildcard/>
         </ext:ExtensionContent>
      </ext:UBLExtension>
   </ext:UBLExtensions>
   <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
   <cbc:CustomizationID>TR1.2</cbc:CustomizationID>
   <cbc:ProfileID>{$this->profil}</cbc:ProfileID>
   <cbc:ID>{$no}</cbc:ID>
   <cbc:CopyIndicator>false</cbc:CopyIndicator>
   <cbc:UUID>{$this->uuid($no)}</cbc:UUID>
   <cbc:IssueDate>{$tarih}</cbc:IssueDate>
{$saat_xml}   <cbc:InvoiceTypeCode>{$this->fatura_turu}</cbc:InvoiceTypeCode>
{$aciklama_xml}   <cbc:DocumentCurrencyCode>{$this->parabirimi}</cbc:DocumentCurrencyCode>
   <cbc:LineCountNumeric>{$satir_sayisi}</cbc:LineCountNumeric>
{$signature_xml}   <cac:AccountingSupplierParty>
      <cac:Party>
         <cac:PartyIdentification>
            <cbc:ID schemeID="VKN">{$this->satici_vkn}</cbc:ID>
         </cac:PartyIdentification>
         <cac:PartyName>
            <cbc:Name>{$this->satici_unvan}</cbc:Name>
         </cac:PartyName>
{$satici_adres_xml}         <cac:PartyTaxScheme>
            <cac:TaxScheme>
               <cbc:Name>{$this->satici_vergi_dairesi}</cbc:Name>
            </cac:TaxScheme>
         </cac:PartyTaxScheme>
      </cac:Party>
   </cac:AccountingSupplierParty>
{$alici_xml}
{$odeme_xml}   <cac:TaxTotal>
      <cbc:TaxAmount currencyID="{$this->parabirimi}">{$kdv_toplam}</cbc:TaxAmount>
{$header_vergi_xml}   </cac:TaxTotal>
   <cac:LegalMonetaryTotal>
      <cbc:LineExtensionAmount currencyID="{$this->parabirimi}">{$mal_toplam}</cbc:LineExtensionAmount>
      <cbc:TaxExclusiveAmount currencyID="{$this->parabirimi}">{$mal_toplam}</cbc:TaxExclusiveAmount>
      <cbc:TaxInclusiveAmount currencyID="{$this->parabirimi}">{$genel_toplam}</cbc:TaxInclusiveAmount>
      <cbc:PayableAmount currencyID="{$this->parabirimi}">{$genel_toplam}</cbc:PayableAmount>
   </cac:LegalMonetaryTotal>
{$satir_xml}
</Invoice>
XML;
    }

    private function uuid(string $seed): string
    {
        $h = md5($seed);
        return strtoupper(
            substr($h, 0, 8) . '-' . substr($h, 8, 4) . '-4' . substr($h, 13, 3)
            . '-' . '8' . substr($h, 17, 3) . '-' . substr($h, 20, 12)
        );
    }

    private function build_adres(string $adres, string $ilce, string $il, string $ulke, int $indent): string
    {
        $pad = str_repeat(' ', $indent);
        return <<<XML
$pad<cac:PostalAddress>
$pad   <cbc:StreetName>{$adres}</cbc:StreetName>
$pad   <cbc:CitySubdivisionName>{$ilce}</cbc:CitySubdivisionName>
$pad   <cbc:CityName>{$il}</cbc:CityName>
$pad   <cac:Country>
$pad      <cbc:Name>{$ulke}</cbc:Name>
$pad   </cac:Country>
$pad</cac:PostalAddress>
XML;
    }

    private function build_signature(): string
    {
        $adres_xml = $this->build_adres(
            $this->satici_adres,
            $this->satici_ilce,
            $this->satici_il,
            $this->satici_ulke,
            9,
        );
        return <<<XML
   <cac:Signature>
      <cbc:ID schemeID="VKN_TCKN">{$this->satici_vkn}</cbc:ID>
      <cac:SignatoryParty>
         <cac:PartyIdentification>
            <cbc:ID schemeID="VKN">{$this->satici_vkn}</cbc:ID>
         </cac:PartyIdentification>
{$adres_xml}      </cac:SignatoryParty>
      <cac:DigitalSignatureAttachment>
         <cac:ExternalReference>
            <cbc:URI>#Signature</cbc:URI>
         </cac:ExternalReference>
      </cac:DigitalSignatureAttachment>
   </cac:Signature>
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

        $adres_xml = $this->build_adres(
            $this->alici_adres,
            $this->alici_ilce,
            $this->alici_il,
            $this->alici_ulke,
            3,
        );

        return <<<XML
   <cac:AccountingCustomerParty>
      <cac:Party>
         <cac:PartyIdentification>
            <cbc:ID schemeID="{$vkn_tckn}">{$this->alici_vkn}</cbc:ID>
         </cac:PartyIdentification>
         <cac:PartyName>
            <cbc:Name>{$this->alici_unvan}</cbc:Name>
         </cac:PartyName>
{$adres_xml}         <cac:PartyTaxScheme>
            <cac:TaxScheme>
               <cbc:Name>{$this->alici_vergi_dairesi}</cbc:Name>
            </cac:TaxScheme>
         </cac:PartyTaxScheme>
{$kisi}
      </cac:Party>
   </cac:AccountingCustomerParty>
XML;
    }
}
