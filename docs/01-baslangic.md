# Başlangıç

QNB eSolutions Özel Entegratör hizmetlerinden web servis seviyesinde faydalanmak isteyen müşteriler ve çözüm ortakları için hazırlanmış PHP istemci kütüphanesidir.

## Kimlik Doğrulama

İki farklı yöntem kullanılabilir:

### 1. SOAP Header (WSSE UsernameToken)

Her SOAP isteğine WSSE güvenlik başlığı eklenir:

```xml
<wsse:Security
    xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
    <wsse:UsernameToken>
        <wsse:Username>kullaniciAdi</wsse:Username>
        <wsse:Password>sifre</wsse:Password>
    </wsse:UsernameToken>
</wsse:Security>
```

### 2. Cookie Container

Önce `wsLogin` metodu ile oturum açılır, dönen session cookie'si sonraki isteklerde kullanılır.

```xml
<soapenv:Envelope
    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
    xmlns:ser="http://service.csap.cs.com.tr/">
    <soapenv:Header/>
    <soapenv:Body>
        <ser:wsLogin>
            <userId>kullaniciAdi</userId>
            <password>sifre</password>
            <lang>tr</lang>
        </ser:wsLogin>
    </soapenv:Body>
</soapenv:Envelope>
```

## Servis URL'leri

### Test Ortamı

| Birim | Servis | URL |
|-------|--------|-----|
| Test1 | UserService | `https://erpefaturatest1.qnbesolutions.com.tr/efatura/ws/userService?wsdl` |
| Test1 | ConnectorService | `https://erpefaturatest1.qnbesolutions.com.tr/efatura/ws/connectorService?wsdl` |
| Test2 | UserService | `https://erpefaturatest2.qnbesolutions.com.tr/efatura/ws/userService?wsdl` |
| Test2 | ConnectorService | `https://erpefaturatest2.qnbesolutions.com.tr/efatura/ws/connectorService?wsdl` |
| e-Arşiv | UserService | `https://connectortest.qnbesolutions.com.tr/connector/ws/userService?wsdl` |
| e-Arşiv | EArsivService | `https://earsivtest.qnbesolutions.com.tr/earsiv/ws/EarsivWebService?wsdl` |

### Canlı Ortam

| Servis | URL |
|--------|-----|
| ConnectorService | `https://connector.qnbesolutions.com.tr/connector/ws/connectorService?wsdl` |
| e-Arşiv | `https://earsiv.qnbesolutions.com.tr/earsiv/ws/EarsivWebService?wsdl` |

## Kullanım

```php
<?php

require 'vendor/autoload.php';

use QnbSolutions\QnbEsolutions\client;

$client = new client(
    username: 'KULLANICI_ADI',
    password: 'SIFRE',
    environment: client::ENV_TEST,    // veya client::ENV_PROD
    auth_method: client::AUTH_WSSE,   // veya client::AUTH_COOKIE
);

// e-Fatura işlemleri
$invoice = $client->invoice();
$invoice->efatura_kullanici_bilgisi('1234567890');

// e-İrsaliye işlemleri
$despatch = $client->despatch();

// e-Arşiv işlemleri
$archive = $client->archive();

// e-Defter işlemleri
$ledger = $client->ledger();
```
