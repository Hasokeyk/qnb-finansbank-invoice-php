<?php

namespace QnbSolutions\QnbEsolutions\exception;

/**
 * Belge gönderim öncesi doğrulama hataları.
 *
 * `fatura_builder::build_xml()` / `send()` doğrulamayı geçemeyen belgede
 * bu exception'ı fırlatır; `$errors` okunur hata mesajları listesini tutar.
 */
class validation_exception extends \RuntimeException
{
    /** @var list<string> */
    public array $errors;

    /** @param list<string> $errors */
    public function __construct(array $errors)
    {
        $this->errors = array_values($errors);
        parent::__construct('Belge doğrulama hatası:' . "\n- " . implode("\n- ", $this->errors));
    }
}
