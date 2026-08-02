<?php
/**
 * Test: create_efatura / create_earsiv fluent zinciri (mock SOAP ile).
 * Gerçek SOAP çağrısı yapılmaz.
 */
require_once __DIR__.'/../vendor/autoload.php';

use QnbSolutions\QnbEsolutions\builder\create_efatura;
use QnbSolutions\QnbEsolutions\builder\create_earsiv;
use QnbSolutions\QnbEsolutions\builder\customer_company;
use QnbSolutions\QnbEsolutions\builder\my_company;
use QnbSolutions\QnbEsolutions\builder\products;
use QnbSolutions\QnbEsolutions\client;
use QnbSolutions\QnbEsolutions\qnb_esolutions;
use QnbSolutions\QnbEsolutions\service\archive_service;
use QnbSolutions\QnbEsolutions\service\invoice_service;

$ok = 0;
$fail = 0;

function test(string $label, mixed $deger, mixed $beklenen): void
{
    global $ok, $fail;
    if ($deger === $beklenen) {
        echo "  ✓ {$label}\n";
        $ok++;
    } else {
        echo "  ✗ {$label}: beklenen=" . json_encode($beklenen) . ", alinan=" . json_encode($deger) . "\n";
        $fail++;
    }
}

// ─── Mock SoapClient (hem efatura hem earsiv) ───────────────────────────
$mock = new class extends \SoapClient {
    public array $calls = [];
    public function __construct()
    {
    }
    public function __call(string $name, array $args): mixed
    {
        return $this->__soapCall($name, $args);
    }
    public function __soapCall(string $name, array $args, ?array $options = null, $inputHeaders = null, &$outputHeaders = null): mixed
    {
        $this->calls[] = ['method' => $name, 'args' => $args];

        if ($name === 'belgeGonderExt') {
            $r = new \stdClass;
            $r->belgeOid = 'OID-EFATURA';
            $ret = new \stdClass;
            $ret->return = $r;
            return $ret;
        }

        if ($name === 'faturaOlusturExt') {
            $out = new \stdClass;
            $out->belgeIcerigi = 'PDF-ICERIK-HAM';
            $r = new \stdClass;
            $r->resultExtra = new \stdClass;
            $r->resultExtra->entry = [
                (object) ['key' => 'islemID', 'value' => 'ISLEM-1'],
                (object) ['key' => 'faturaURL', 'value' => 'https://arsiv.test/fatura/1'],
                (object) ['key' => 'uuid', 'value' => 'UUID-1'],
                (object) ['key' => 'faturaNo', 'value' => 'NXT2026000000001'],
                (object) ['key' => 'iptalTarihi', 'value' => null],
            ];
            $wrapper = new \stdClass;
            $wrapper->output = $out;
            $wrapper->return = $r;
            return $wrapper;
        }

        if ($name === 'efaturaKullaniciBilgisi') {
            $r = new \stdClass;
            $r->unvan = 'ALICI LTD.';
            $r->etiket = 'TEST';
            $ret = new \stdClass;
            $ret->return = $r;
            return $ret;
        }

        $r = new \stdClass;
        $r->return = 'OK';
        return $r;
    }
};

// Mock SoapClient kullanan özel client (gerçek WSDL yok, network yok)
$test_client = new class('6312064091', 'test', client::ENV_TEST2) extends client {
    public \SoapClient $soap;
    public function invoice(): invoice_service
    {
        return new invoice_service($this->soap);
    }
    public function archive(): archive_service
    {
        return new archive_service($this->soap);
    }
};
$test_client->soap = $mock;

// ─── Entity'ler ──────────────────────────────────────────────────────────
$my_company = (new my_company)
    ->set_company_name('TEST2 FİRMASI')
    ->set_tax_number('6312064091')
    ->set_address('İnönü Mah. Çetin Emeç Bulvarı No:8', 'Çankaya', 'Ankara');

$customer_company = (new customer_company)
    ->set_company_name('ALICI LTD. ŞTİ.')
    ->set_tax_number('6312064090')
    ->set_address('Alıcı Adresi No:1', 'Kadıköy', 'İstanbul');

$products = (new products)
    ->set_product_name('Eğitim Hizmeti')
    ->set_quantity(1)
    ->set_unit_price(1000.00)
    ->set_vat_rate(20)
    ->add_product()
    ->set_product_name('Danışmanlık')
    ->set_quantity(2)
    ->set_unit_price(250.00)
    ->set_vat_rate(20);

echo "=== Fluent Builder Test (mock) ===\n";

// ─── create_efatura ──────────────────────────────────────────────────────
$efatura = new create_efatura($my_company, $customer_company, $products, $test_client);
$xml = $efatura->build_xml();

test('efatura xml UBL iceriyor', str_contains($xml, '<Invoice'), true);
test('efatura profil TICARIFATURA', str_contains($xml, 'TICARIFATURA'), true);
test('efatura satici unvan', str_contains($xml, 'TEST2 FİRMASI'), true);
test('efatura 2 satir', substr_count($xml, '<cac:InvoiceLine>'), 2);
test('efatura genel toplam', str_contains($xml, '<cbc:PayableAmount currencyID="TRY">1800</cbc:PayableAmount>'), true);

// XML'deki belge no'yu çıkar
preg_match('#<cbc:ID>([^<]+)</cbc:ID>#', $xml, $m);
$xml_belge_no = $m[1] ?? '';

$oid = $efatura->send();
test('efatura OID', $oid, 'OID-EFATURA');

$call = $mock->calls[0];
test('efatura metodu belgeGonderExt', $call['method'], 'belgeGonderExt');
$params = $call['args'][0]['parametreler'] ?? [];
test('efatura veri decode edilmis XML', str_contains($params['veri'] ?? '', '<Invoice'), true);
test('efatura belgeNo otomatik uretildi', str_starts_with($params['belgeNo'] ?? '', 'NXT'), true);
test('efatura belgeNo XML ile ayni', $params['belgeNo'] ?? '', $xml_belge_no);
test('efatura hash buyuk harf md5', $params['belgeHash'] ?? '', strtoupper(md5($xml)));
test('efatura erpKodu', $params['erpKodu'] ?? '', 'ERP1');

// ─── create_earsiv ───────────────────────────────────────────────────────
$mock->calls = [];
$earsiv = new create_earsiv($my_company, $customer_company, $products, $test_client);
$sonuc = $earsiv->send();

test('earsiv fatura no', $sonuc->fatura_no, 'NXT2026000000001');
test('earsiv uuid', $sonuc->uuid, 'UUID-1');
test('earsiv url', $sonuc->fatura_url, 'https://arsiv.test/fatura/1');
test('earsiv output icerik', $sonuc->output_base64, base64_encode('PDF-ICERIK-HAM'));

$call = $mock->calls[0];
test('earsiv metodu faturaOlusturExt', $call['method'], 'faturaOlusturExt');
$params = $call['args'][0] ?? [];
$input = json_decode($params['input'] ?? '{}', true);
$fatura = $params['fatura'] ?? [];
test('earsiv input JSON islemId', isset($input['islemId']) && $input['islemId'] !== '', true);
test('earsiv input JSON vkn', $input['vkn'] ?? '', '6312064091');
test('earsiv input JSON erpKodu', $input['erpKodu'] ?? '', 'ERP1');
test('earsiv input JSON donenBelgeFormati', $input['donenBelgeFormati'] ?? '', '3');
test('earsiv fatura belgeFormati UBL', $fatura['belgeFormati'] ?? '', 'UBL');
test('earsiv fatura belgeIcerigi decode edilmis XML', str_contains($fatura['belgeIcerigi'] ?? '', '<Invoice'), true);

// ─── qnb_esolutions facade ───────────────────────────────────────────────
$mock->calls = [];
$qnb = new qnb_esolutions('kullanici', 'sifre', 'ERP1', client::ENV_TEST2, $test_client);

$qnb->my_company()
    ->set_company_name('TEST2 FİRMASI')
    ->set_tax_number('6312064091')
    ->set_address('İnönü Mah. Çetin Emeç Bulvarı No:8', 'Çankaya', 'Ankara');
$qnb->customer_company()
    ->set_company_name('ALICI LTD.')
    ->set_tax_number('6312064090')
    ->set_address('Alıcı Adresi No:1', 'Kadıköy', 'İstanbul');
$qnb->product()
    ->set_product_name('Hizmet')
    ->set_quantity(1)
    ->set_unit_price(100.00)
    ->set_vat_rate(20);

$oid = $qnb->create_invoice('efatura')->send();
test('facade OID', $oid, 'OID-EFATURA');

$gonder = null;
foreach ($mock->calls as $c) {
    if ($c['method'] === 'belgeGonderExt') {
        $gonder = $c;
        break;
    }
}
test('facade metodu belgeGonderExt', $gonder['method'] ?? null, 'belgeGonderExt');
$params = $gonder['args'][0]['parametreler'] ?? [];
test('facade veri XML', str_contains($params['veri'] ?? '', '<Invoice'), true);
test('facade erpKodu', $params['erpKodu'] ?? '', 'ERP1');
test('facade satici VKN', $params['vergiTcKimlikNo'] ?? '', '6312064091');

echo "\n";
echo "Sonuc: {$ok} gecti, {$fail} kaldi\n";
exit($fail > 0 ? 1 : 0);
