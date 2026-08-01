<?php
/**
 * e-İrsaliye örneği (düşük seviye servis).
 *
 * DİKKAT: e-İrsaliye için henüz UBL DespatchAdvice builder'ı YOKTUR.
 * İrsaliye XML'ini kendiniz üretip `despatch_service::belge_gonder_ext`
 * üzerinden göndermeniz gerekir.
 *
 * Kimlik bilgilerini kendi değerlerinizle değiştirin.
 */

require __DIR__.'/../vendor/autoload.php';

use QnbSolutions\QnbEsolutions\client;

$c = new client(
    username: 'KULLANICI_ADI',
    password: 'SIFRE',
    environment: client::ENV_TEST2,
);

$belge_no = 'NXT' . date('Y') . '000000001';

// Minimal DespatchAdvice TASLAĞI — gerçek gönderimde alanları doldurun.
// (Bu sınıf henüz irsaliye builder'ı üretmediği için ham XML verilir.)
$despatch_advice = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<DespatchAdvice xmlns="urn:oasis:names:specification:ubl:schema:xsd:DespatchAdvice-2"
                xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
                xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
   <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
   <cbc:CustomizationID>TR1.2</cbc:CustomizationID>
   <cbc:ProfileID>TEMELIRSALIYE</cbc:ProfileID>
   <cbc:ID>{$belge_no}</cbc:ID>
   <cbc:IssueDate>2026-08-01</cbc:IssueDate>
   <cac:DespatchSupplierParty>
      <!-- satıcı firma bilgileri -->
   </cac:DespatchSupplierParty>
   <cac:DeliveryCustomerParty>
      <!-- alıcı firma bilgileri -->
   </cac:DeliveryCustomerParty>
   <cac:DespatchLine>
      <!-- irsaliye satırları -->
   </cac:DespatchLine>
</DespatchAdvice>
XML;

$oid = $c->despatch()->belge_gonder_ext(
    vergi_tc_kimlik_no: 'SIRKET_VKN',
    belge_no: $belge_no,
    veri_base64: base64_encode($despatch_advice),
    belge_hash_md5: strtoupper(md5($despatch_advice)),
    belge_versiyon: '1.2',
    erp_kodu: 'ERP_KODUNUZ',
);

echo "İrsaliye gönderildi! belgeOid: {$oid}\n";

// Durum sorgula (belge türü IRSALIYE_UBL)
$durum = $c->despatch()->giden_belge_durum_sorgula_ext(
    vergi_tc_kimlik_no: 'SIRKET_VKN',
    belge_no: $oid,
    belge_no_tip: 'OID',
);
echo "Durum: {$durum->durum} (1: Alındı, 2: İşleme Hatası, 3: İşlendi)\n";
echo "Cevap kodu: {$durum->gonderim_cevabi_kodu}\n";
