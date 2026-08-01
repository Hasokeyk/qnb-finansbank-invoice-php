# Örnekler / Examples

Kütüphanenin farklı kullanım senaryolarını gösteren bağımsız PHP script'leri.

| Dosya | Senaryo |
|-------|---------|
| [`01_efatura.php`](01_efatura.php) | e-Fatura — facade ile oluştur → doğrula → gönder → durum sorgula |
| [`02_earsiv.php`](02_earsiv.php) | e-Arşiv — facade ile gönder (senkron) → `archive_result` |
| [`03_eirsaliye.php`](03_eirsaliye.php) | e-İrsaliye — `despatch_service` ile ham DespatchAdvice XML gönderimi |
| [`04_edefter.php`](04_edefter.php) | e-Defter — `ledger_service` ile CSV defter zip yükleme + işleme |
| [`05_kdv_muafiyet.php`](05_kdv_muafiyet.php) | KDV istisna/muafiyet — kodlu satır + otomatik açıklama |
| [`06_dogrulama.php`](06_dogrulama.php) | Gönderim öncesi doğrulama — `validate()` ve `validation_exception` |
| [`07_ham_servis.php`](07_ham_servis.php) | Düşük seviye servis — `invoice_builder` + `belge_gonder_ext` (hash/base64 tuzağı) |

**Not:** Tüm örneklerde kimlik bilgileri yer tutucudur — çalıştırmadan önce kendi
QNB eSolutions kullanıcı adı, şifre ve ERP kodunuzla değiştirin. Test ortamlarında
gönderim yalnızca karşı test ortamına kabul edilir (`ENV_TEST` → `ENV_TEST2` vb.).
