# e-Arşiv

e-Arşiv Fatura, alıcısı e-Fatura mükellefi olmayan kurumlar veya nihai tüketicilere düzenlenen faturalar için kullanılır.

## Farklar

| Özellik | e-Fatura | e-Arşiv |
|---------|----------|---------|
| Çalışma şekli | Asenkron (kuyruk) | **Senkron** |
| Dönüş | belgeOid (sonradan sorgulama gerekir) | İşlem anında fatura görseli + URL |
| Alıcı | e-Fatura mükellefi | Herkes (nihai tüketici dahil) |
| Metot | `belgeGonderExt` | `faturaOlusturExt` |

---

## e-Arşiv Fatura Gönderme

**Metot:** `faturaOlusturExt`

### Request

| Parametre | Tip | Açıklama | Z/O |
|-----------|-----|----------|-----|
| donenBelgeFormati | int | 0: UBL, 2: HTML, 3: PDF, 9: YOK | Z |
| islemId | GUID | Her fatura için tekil UUID (ETTN ile aynı olması önerilir) | Z |
| vkn | string | Fatura düzenleyen VKN/TCKN | Z |
| sube | string | Şube kodu (örn: `DFLT`) | Z |
| kasa | string | Kasa kodu (örn: `DFLT`) | Z |
| erpKodu | string | QNB eSolutions proje kodu | Z |
| belgeFormati | String | Sabit: `UBL` | Z |
| belgeIcerigi | byte[] | Fatura XML base64 | Z |
| numaraVerilsinMi | int | 1: Sistem üretsin, 0: ERP göndersin | O |
| faturaSeri | string | Sistem üretecekse seri kodu | O |
| sablonAdi | string | Portal XSLT şablon adı | O |
| taslagaYonlendir | int | 1: Taslağa gönder, 0: Otomatik imzala | O |

### Response

| Özellik | Tip | Açıklama |
|---------|-----|----------|
| output | holder | Base64 belge görseli (donenBelgeFormati'na göre) |
| resultExtra.islemID | string | İşlem ID |
| resultExtra.faturaURL | string | Fatura görüntüleme linki |
| resultExtra.uuid | string | UUID (ETTN) |
| resultExtra.faturaNo | string | Fatura numarası |
| resultExtra.iptalTarihi | string | (Varsa) iptal tarihi |

### Kullanım

```php
use QnbSolutions\QnbEsolutions\enum\archive_output_format;

$xml = file_get_contents('earsiv_fatura.xml');

$result = $client->archive()->fatura_olustur_ext(
    belge_icerigi_base64: base64_encode($xml),
    donen_belge_formati: archive_output_format::PDF,
    islem_id: '4DB6DF16-E9AD-557B-A7C3-333C1A52FBZF',
    vkn: '1112223331',
    sube: 'DFLT',
    kasa: 'DFLT',
    erp_kodu: 'QES98765',
);

// Fatura görselini kaydet
if ($result->output_base64) {
    file_put_contents('fatura.pdf', base64_decode($result->output_base64));
}

// Fatura URL
echo $result->fatura_url;

// Fatura no
echo $result->fatura_no;

// UUID (ETTN)
echo $result->uuid;
```

Format seçenekleri:

```php
archive_output_format::UBL;  // 0 - XML görüntü
archive_output_format::HTML; // 2 - HTML görüntü
archive_output_format::PDF;  // 3 - PDF görüntü
archive_output_format::YOK;  // 9 - Görüntü istemiyorum
```
