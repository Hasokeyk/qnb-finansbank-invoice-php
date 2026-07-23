# e-Defter

e-Defter, GİB'in belirlediği formatta XML veya sisteme uyumlu CSV dosyaları ile yükleme yapılabilir. CSV ile yüklemelerde XML dosyaları sistem tarafından oluşturulur.

## Dosya Formatları

- **XML**: GİB e-Defter formatında hazır XML
- **CSV**: Sisteme uyumlu CSV (örn. 20bin satır sınırı)

## Manuel Yükleme

CSV veya XML dosyasının yüklenmesi portal arayüzü üzerinden yapılır:

`https://portal.qnbesolutions.com.tr/yonetim/`

## Webservis Akışı

1. `e_defter_csv_file_yukle` ile ZIP dosyası yüklenir
2. `e_defter_csv_durum_sorgula` ile durum sorgulanır
3. Diğer ZIP dosyaları için 1-2 tekrarlanır
4. Tüm dosyalar yüklendiğinde `e_defter_csv_dosya_isle` çağrılır

> Defterin imzalanması ve GİB'e gönderimi portal arayüzü üzerinden tamamlanır.

---

## 1. Dosya Yükleme

**Metot:** `eDefterCsvFileYukle`

### Request

| Parametre | Tip | Açıklama | Z/O |
|-----------|-----|----------|-----|
| donem | string | Yıl/Ay (örn: `202501`) | Z |
| subeKodu | string | Şube kodu (yoksa `0000`) | Z |
| vknTckn | string | Firma VKN/TCKN | Z |
| parameterList (dosyaIsmi) | key/value | ZIP dosya adı (`VKN_Sube_Donem_Parca.zip`) | Z |
| parameterList (dosya) | key/value | Base64 encode ZIP | Z |

### Response

| Alan | Açıklama |
|------|----------|
| sonucKodu | Örn: 907 |
| sonucAciklama | Örn: "CSV dosyası başarıyla yüklendi, fileName:..." |

### Kullanım

```php
$zip_content = file_get_contents('VKN_0000_202501_1.zip');

$result = $client->ledger()->e_defter_csv_file_yukle(
    donem: '202501',
    vkn_tckn: '1234567890',
    sube_kodu: '0000',
    dosya_ismi: '1234567890_0000_202501_1.zip',
    dosya_base64: base64_encode($zip_content),
);

echo $result['sonuc_aciklama'];
```

---

## 2. Durum Sorgulama

**Metot:** `eDefterCsvDurumSorgula`

### Request

| Parametre | Tip | Açıklama | Z/O |
|-----------|-----|----------|-----|
| donem | string | Yıl/Ay | Z |
| vknTckn | string | VKN/TCKN | Z |
| yuklenenDefterTuru | string | `CSV`, `XML` | Z |

### Kullanım

```php
$result = $client->ledger()->e_defter_csv_durum_sorgula(
    donem: '202501',
    vkn_tckn: '1234567890',
    yuklenen_defter_turu: 'CSV',
);
```

---

## 3. Dosya İşleme

Tüm dosyaların gönderimi tamamlandığında çağrılır.

**Metot:** `eDefterCsvDosyaIsle`

### Request

| Parametre | Tip | Açıklama | Z/O |
|-----------|-----|----------|-----|
| donem | string | Yıl/Ay | Z |
| vknTckn | string | VKN/TCKN | Z |
| subeKodu | string | Şube kodu | Z |
| yuklenenDefterTuru | string | `CSV`, `XML` | Z |

### Kullanım

```php
$result = $client->ledger()->e_defter_csv_dosya_isle(
    donem: '202501',
    vkn_tckn: '1234567890',
    sube_kodu: '0000',
    yuklenen_defter_turu: 'CSV',
);
```
