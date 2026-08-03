# e-İrsaliye

e-Fatura ile aynı servis metodları kullanılır, belge türü farklıdır.

## Farklar

| Alan | e-Fatura | e-İrsaliye |
|------|----------|------------|
| belgeTuru (gönderim) | `FATURA_UBL` | `IRSALIYE_UBL` |
| belgeTuru (yanıt) | `UYGULAMA_YANITI_UBL` | `IRSALIYE_YANITI_UBL` |
| belgeVersiyon | `1.0` | `1.2` |
| belgeTuru (indirme) | `FATURA` | `IRSALIYE` |
| ürün kodu (liste) | `EFATURA` | `EIRSALIYE` |

---

## 1. Kayıtlı Kullanıcı Sorgulama

**Metot:** `efaturaKullaniciBilgisi`

Aynı metot e-İrsaliye için de kullanılır. (GİB sistemi e-Fatura ve e-İrsaliye için aynı mükellefiyeti kullanır.)

```php
$user = $client->despatch()->efatura_user_info('1234567890');
```

---

## 2. Kayıtlı Kullanıcı Listeleme

**Metot:** `kayitliKullaniciListeleExtended`

```php
$zip_base64 = $client->despatch()->kayitli_kullanici_listele_extended(
    urun: 'EIRSALIYE'
);
```

---

## 3. e-İrsaliye Gönderme

**Metot:** `belgeGonderExt`

| Parametre | e-İrsaliye Değeri |
|-----------|-------------------|
| belgeTuru | `IRSALIYE_UBL` veya `IRSALIYE_YANITI_UBL` |
| mimeType | `application_xml` |
| belgeVersiyon | `1.2` |

```php
$xml = file_get_contents('irsaliye.xml');
$hash = strtoupper(md5($xml));

$oid = $client->despatch()->belge_gonder_ext(
    vergi_tc_kimlik_no: '3250566851',
    belge_no: 'DSP202400001',
    veri_base64: base64_encode($xml),
    belge_hash_md5: $hash,
);
```

---

## 4. Durum Sorgulama

**Metot:** `gidenBelgeDurumSorgulaExt`

```php
$status = $client->despatch()->giden_belge_durum_sorgula_ext(
    vergi_tc_kimlik_no: '3250566851',
    belge_no: $oid,
    belge_no_tip: 'OID',
    belge_turu: 'IRSALIYE_UBL',
);
```

---

## 5. Giden e-İrsaliye İndirme

**Metot:** `gidenBelgeleriIndirExt`

```php
$zip_base64 = $client->despatch()->giden_belgeleri_indir_ext(
    vergi_tc_kimlik_no: '3250566851',
    belge_oid_listesi: [$oid],
    belge_turu: 'IRSALIYE',
    belge_formati: 'PDF',
);
```

---

## 6. Gelen e-İrsaliye Akışı

e-Fatura ile aynı akış:

1. `gelen_belgeleri_listele_ext` (belgeTuru: `IRSALIYE`)
2. `gelen_belgeleri_indir_ext` (belgeTuru: `IRSALIYE`)

```php
// Listele
$list = $client->despatch()->gelen_belgeleri_listele_ext(
    vergi_tc_kimlik_no: '3250566851',
    belge_turu: 'IRSALIYE',
);

// İndir (her bir ETTN için)
foreach ($list as $doc) {
    $zip = $client->despatch()->gelen_belgeleri_indir_ext(
        vergi_tc_kimlik_no: '3250566851',
        belge_ettn: $doc->ettn,
        belge_turu: 'IRSALIYE',
        belge_formati: 'PDF',
    );
}
```

---

## 7. Tekrar Gönderme

**Metot:** `belgeleriTekrarGonder`

```php
$client->despatch()->belgeleri_tekrar_gonder([$oid1, $oid2]);
```
