# QNB eSolutions PHP SOAP Client

**QNB eSolutions Özel Entegratör SOAP API'leri için PHP istemci kütüphanesi** · _PHP client library for QNB eSolutions' Özel Entegratör SOAP APIs_

e-Fatura, e-İrsaliye, e-Arşiv ve e-Defter işlemlerini tek bir kütüphane üzerinden yürütmenizi sağlar. UBL 2.1 fatura XML'lerini fluent bir builder ile üretir, SOAP servislerine gönderir ve sonuçları tipik model nesneleri olarak döndürür.

---

## 🌐 Dil / Language

- **🇹🇷 [Türkçe — `README.tr.md`](README.tr.md)**
- **🇬🇧 [English — `README.en.md`](README.en.md)**

> GitHub, tarayıcınızın dili **Türkçe** ise `README.tr.md`'yi, **İngilizce** ise `README.en.md`'yi otomatik gösterir. Bu sayfa varsayılan geri dönüş `README.md`'dir.
>
> _GitHub auto-renders `README.tr.md` for Turkish and `README.en.md` for English based on your browser locale; this page is the default fallback._

---

## Hızlı Bakış / Quick Overview

| Modül / Module | Servis / Service |
|----------------|------------------|
| e-Fatura | `$c->invoice()` |
| e-İrsaliye | `$c->despatch()` |
| e-Arşiv | `$c->archive()` |
| e-Defter | `$c->ledger()` |

```bash
composer require hasokeyk/qnb-finansbank-invoice
```

**Kurulum, örnekler ve tüm detaylar için:** 📄 [`README.tr.md`](README.tr.md) · 📄 [`README.en.md`](README.en.md)
