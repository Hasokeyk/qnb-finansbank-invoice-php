# e-Fatura

## Akış

1. Alıcının e-Fatura mükellefi olduğu `efatura_user_info` ile teyit edilir.
2. Fatura XML'i UBL formatında hazırlanır, base64 kodlanır, MD5 hash'i hesaplanır.
3. `belge_gonder_ext` ile fatura gönderilir → **belgeOid** döner.
4. `giden_belge_durum_sorgula_ext` ile durum periyodik olarak sorgulanır.
5. Durum **"İşlendi" (3)** olduğunda fatura indirilebilir.
6. Gelen faturalar için `gelen_belgeleri_listele_ext` → `gelen_belgeleri_indir_ext` sırası takip edilir.

---

## 1. Kayıtlı Kullanıcı Sorgulama

Alıcının e-Fatura mükellefi olup olmadığını sorgular.

**Metot:** `efaturaKullaniciBilgisi`

### Request

| Parametre | Tip | Açıklama | Z/O |
|-----------|-----|----------|-----|
| vergiTcKimlikNo | String | 10/11 hane VKN veya TCKN | Z |

### Response

| Parametre | Tip | Açıklama |
|-----------|-----|----------|
| etiket | String | Kayıtlı etiket adresi |
| kamuKurulusu | boolean | Kamu kuruluşu mu? |
| kayitZamani | String | e-Fatura sistemine kayıt zamanı |
| unvan | String | Unvan |

### Kullanım

```php
$user = $client->invoice()->efatura_user_info('1234567890');
echo $user->title;               // FIRMA ADI
echo $user->label;               // urn:mail:defaultpk@firma.com.tr
echo $user->registration_time;   // 2023-01-01 12:00:00
```

---

## 2. Kayıtlı Kullanıcı Sorgulama (Liste)

GİB tarafından yayımlanan tüm e-Fatura mükellef listesini base64 zip olarak döndürür. Liste 4 saatlik periyotlarda güncellenir.

**Metot:** `kayitliKullaniciListeleExtended`

### Request

| Parametre | Tip | Açıklama | Z/O |
|-----------|-----|----------|-----|
| gecmisEklensin | String | 1: etiket değişiklikleri dahil, 0: sadece aktif liste | Z |
| urun | String | `EFATURA` | Z |

### Response

Base64 encode .zip dosyası. Decode → .zip → unzip → .xml

### Kullanım

```php
$zip_base64 = $client->invoice()->kayitli_kullanici_listele_extended();
file_put_contents('mukellefler.zip', base64_decode($zip_base64));
// zip içindeki xml'i aç
```

---

## 3. e-Fatura Gönderme

Alıcısı e-Fatura mükellefi olan faturanın gönderimi. İşleme kuyruğa alınır, **belgeOid** döner.

**Metot:** `belgeGonderExt`

### Request

| Parametre | Tip | Açıklama | Z/O |
|-----------|-----|----------|-----|
| vergiTcKimlikNo | String | Gönderici VKN/TCKN | Z |
| belgeTuru | String | `FATURA_UBL`, `UYGULAMA_YANITI_UBL` | Z |
| belgeNo | String | Yerel belge numarası (tekil, alfanümerik) | Z |
| veri | base64Binary | Fatura XML verisi (base64) | Z |
| belgeHash | String | MD5 hash | Z |
| mimeType | String | Sabit: `application_xml` | Z |
| belgeVersiyon | String | `1.0` | Z |
| erpKodu | String | QNB eSolutions proje kodu (opsiyonel) | O |
| alanEtiket | String | Alıcı PK etiketi | O |
| gonderenEtiket | String | Gönderici GB etiketi | O |
| xsltAdi | String | Tasarım dosyası adı | O |
| xsltVeri | base64Binary | XSLT tasarım dosyası | O |
| subeKodu | String | Şubeli yapı için şube kodu | O |

### Response

| Parametre | Tip | Açıklama |
|-----------|-----|----------|
| belgeOid | String | Belge tekil tanımlayıcı (durum sorgulamada kullanılır) |

### Kullanım

```php
$xml = file_get_contents('fatura.xml');
$hash = strtoupper(md5($xml));

$oid = $client->invoice()->belge_gonder_ext(
    vergi_tc_kimlik_no: '3250566851',
    belge_no: 'INV202400001',
    veri_base64: base64_encode($xml),
    belge_hash_md5: $hash,
);
echo $oid; // kaydedilmesi gerekli
```

---

## 4. e-Fatura Durum Sorgulama

Gönderilen faturanın işlem durumunu sorgular. Şema/şematron kontrolü sonrası durum **"İşlendi" (3)** veya **"İşleme Hatası" (2)** olur.

**Metot:** `gidenBelgeDurumSorgulaExt`

### Request

| Parametre | Tip | Açıklama | Z/O |
|-----------|-----|----------|-----|
| vergiTcKimlikNo | String | VKN/TCKN | Z |
| belgeNo | String | belgeOid (OID) veya yerel belge no | Z |
| belgeNoTip | String | `OID` veya `YEREL` veya `ETTN` | Z |
| belgeTuru | String | `FATURA_UBL`, `UYGULAMA_YANITI_UBL` | Z |

> **Not:** `ETTN` ile sorgulama sadece **"İşlendi"** durumundaki belgeler için çalışır.

### Response

| Parametre | Tip | Açıklama |
|-----------|-----|----------|
| alimTarihi | String | Sisteme kayıt tarihi |
| belgeNo | String | Belge numarası |
| durum | String | 1: Alındı, 2: İşleme Hatası, 3: İşlendi |
| ettn | String | ETTN (UUID) |
| gonderimCevabiDetayi | String | GİB/alıcı cevap detayı |
| gonderimCevabiKodu | String | GİB/alıcı cevap kodu |
| gonderimDurumu | String | -2: İptal, -1: Kuyruk, 0: Gönderilemedi, 1: Gönderilecek, 2: Gönderildi, 3: GİB cevap, 4: Alıcı cevap |
| olusturulmaTarihi | String | Zarf oluşturulma tarihi |
| yanitDetayı | String | Alıcı uygulama yanıt açıklaması |
| yanitDurumu | String | -1: Gerekmiyor, 0: Bekleniyor, 1: Red, 2: Kabul |
| ulastiMi | boolean | Alıcıya ulaştı mı? (true ise tekrar sorgulamaya gerek yok) |
| yenidenGonderilebilirMi | boolean | 1100-1230 arası hata kodlarında true |
| yerelBelgeOid | String | Belge OID bilgisi |

### Kullanım

```php
$status = $client->invoice()->giden_belge_durum_sorgula_ext(
    vergi_tc_kimlik_no: '3250566851',
    belge_no: $oid, // belgeGonderExt'ten dönen OID
    belge_no_tip: 'OID',
);

echo $status->durum;             // 1, 2 veya 3
echo $status->ulasti_mi ? 'Ulaştı' : 'Bekliyor';
echo $status->gonderim_cevabi_detayi;
```

---

## 5. Giden e-Fatura İndirme

Giden belgelerin PDF, HTML veya UBL formatında indirilmesi. Base64 encode .zip döndürür.

**Metot:** `gidenBelgeleriIndirExt`

### Request

| Parametre | Tip | Açıklama | Z/O |
|-----------|-----|----------|-----|
| vergiTcKimlikNo | String | VKN/TCKN | Z |
| belgeOidListesi | String | Virgülle ayrılmış OID listesi | Z |
| belgeTuru | String | `FATURA`, `IRSALIYE`, `UYGULAMA_YANITI` | Z |
| belgeFormati | String | `HTML`, `PDF`, `UBL` | Z |

### Response

Base64 encode .zip dosyası.

### Kullanım

```php
$zip_base64 = $client->invoice()->giden_belgeleri_indir_ext(
    vergi_tc_kimlik_no: '3250566851',
    belge_oid_listesi: [$oid1, $oid2],
    belge_formati: 'PDF',
);
file_put_contents('faturalar.zip', base64_decode($zip_base64));
```

---

## 6. Gelen e-Fatura Akışı

1. Gelen her faturaya portal tarafında tekil **Belge Sıra No** atanır.
2. `gelen_belgeleri_listele_ext` ile son belge sıra no bazında liste alınır (azami 100 kayıt).
3. ETTN bilgisi ile `gelen_belgeleri_indir_ext` çağrılır.
4. Tüm kayıtlar işlendikten sonra son belge sıra no ile tekrar listeleme yapılır.

---

## 7. Gelen e-Fatura Listeleme

**Metot:** `gelenBelgeleriListeleExt`

### Request

| Parametre | Tip | Açıklama | Z/O |
|-----------|-----|----------|-----|
| vergiTcKimlikNo | String | VKN/TCKN | Z |
| sonAlinanBelgeSiraNumarasi | String | Son alınan belge sıra no (başlangıç: "0") | Z |
| belgeTuru | String | `FATURA`, `UYGULAMA_YANITI` | Z |
| alanEtiket | String | Alıcı PK etiketi | O |
| belgelerAlindiMi | Boolean | İşaretlenmiş belgeleri filtrele | O |
| donusTipiVersiyon | String | 1.0 - 6.0 | O |
| erpKodu | String | QNB eSolutions proje kodu | O |
| ettn | String (tekrarlanabilir) | ETTN filtresi | O |
| faturaTarihiBaslangic | String (yyyyMMdd) | Fatura tarihi başlangıç | O |
| faturaTarihiBitis | String (yyyyMMdd) | Fatura tarihi bitiş | O |
| gelisTarihiBaslangic | String (yyyyMMdd) | Geliş tarihi başlangıç | O |
| gelisTarihiBitis | String (yyyyMMdd) | Geliş tarihi bitiş | O |
| onayDurum | String | `ONAYBEKLEYEN`, `ONAYLANAN`, `HEPSI` | O |

### Response (incoming_document[])

| Parametre | Açıklama |
|-----------|----------|
| belgeNo | Fatura numarası |
| belgeSiraNo | Portal sıra no |
| belgeTarihi | Fatura tarihi |
| belgeTuru | FATURA, IRSALIYE, UYGULAMA_YANITI |
| ettn | ETTN (UUID) |
| gonderenEtiket | Gönderen etiket adresi |
| gonderenVknTckn | Gönderen VKN/TCKN |
| alanEtiket | Alan PK etiketi |
| aliciUnvan | Alıcı unvan |
| saticiUnvan | Satıcı unvan |
| zarfId | Zarf no |
| odenecekTutar | Ödenecek tutar |
| odenecekTutarDovizCinsi | Döviz cinsi |
| arsivlenmis | 0: Arşivlenmemiş, 1: Arşivlenmiş |
| belgeHash | Hash değeri |
| faturaGelisTarihi | Sisteme geliş tarihi |

### Kullanım

```php
$documents = $client->invoice()->gelen_belgeleri_listele_ext(
    vergi_tc_kimlik_no: '3250566851',
    son_alinan_belge_sira_numarasi: '0',
);

foreach ($documents as $doc) {
    echo $doc->ettn . ' - ' . $doc->satici_unvan;
}
```

---

## 8. Gelen e-Fatura İndirme

**Metot:** `gelenBelgeleriIndirExt`

### Request

| Parametre | Tip | Açıklama | Z/O |
|-----------|-----|----------|-----|
| vergiTcKimlikNo | String | VKN/TCKN | Z |
| belgeEttn | String | ETTN (UUID) | Z |
| belgeTuru | String | `FATURA`, `UYGULAMA_YANITI` | Z |
| belgeFormati | String | `HTML`, `PDF`, `UBL` | Z |

### Response

Base64 encode .zip dosyası.

---

## 9. e-Fatura Görüntüleme Linkleri

Servis metotları içerisinde faturaların doğrudan görüntülenebileceği bir link üretilmemektedir. Bu özellik ihtiyacı için `proje@destek.qnbesolutions.com.tr` adresine bildirim yapılması gerekmektedir.
