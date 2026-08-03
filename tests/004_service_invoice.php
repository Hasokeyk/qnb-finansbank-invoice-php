<?php
/**
 * Test: invoice_service — mocked SOAP client.
 * Gerçek SOAP çağrısı yapılmaz.
 */
require_once __DIR__.'/../vendor/autoload.php';

use QnbSolutions\QnbEsolutions\service\invoice_service;

// Mock SOAP client
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
            $r->belgeOid = 'OID-12345';
            $ret = new \stdClass;
            $ret->return = $r;
            return $ret;
        }

        if ($name === 'efaturaKullaniciBilgisi') {
            $r = new \stdClass;
            $r->etiket = 'TEST';
            $r->kamuKurulusu = false;
            $r->kayitZamani = '2025-01-01 12:00:00';
            $r->unvan = 'TEST FİRMASI LTD. ŞTİ.';
            $ret = new \stdClass;
            $ret->return = $r;
            return $ret;
        }

        if ($name === 'kayitliKullaniciListeleExtended') {
            $ret = new \stdClass;
            $ret->return = base64_encode('test zip data');
            return $ret;
        }

        $r = new \stdClass;
        $r->return = 'OK';
        return $r;
    }
};

$svc = new invoice_service($mock);
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

echo "=== invoice_service Test (mock) ===\n";

// efaturaKullaniciBilgisi
$u = $svc->efatura_user_info('9876543210');
test('user title', $u->title, 'TEST FİRMASI LTD. ŞTİ.');
test('user label', $u->label, 'TEST');
test('user registration time', $u->registration_time, '2025-01-01 12:00:00');
test('user public institution', $u->is_public_institution, false);

// kayitliKullaniciListeleExtended
$data = $svc->kayitli_kullanici_listele_extended();
test('kayitli liste', $data, base64_encode('test zip data'));

// belgeGonderExt
$oid = $svc->belge_gonder_ext(
    vergi_tc_kimlik_no: '9876543210',
    belge_no: 'TEST2025001',
    veri_base64: base64_encode('<xml/>'),
    belge_hash_md5: md5('<xml/>'),
    belge_versiyon: '2.1',
    erp_kodu: 'TESTERP',
);
test('belge OID', $oid, 'OID-12345');

echo "\n";
echo "Sonuc: {$ok} gecti, {$fail} kaldi\n";
exit($fail > 0 ? 1 : 0);
