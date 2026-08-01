<?php

use QnbSolutions\QnbEsolutions\builder\qnb_invoice;
use QnbSolutions\QnbEsolutions\client;

/**
 * Hafif facade: e-Fatura.
 *
 *     $qnb = qnb_invoice($username, $password, $erp_kodu);
 *     $qnb->my_company()->set_company_name('Firma');
 *     $oid = $qnb->send();
 */
function qnb_invoice(
    string $username,
    string $password,
    string $erp_kodu = 'ERP1',
    string $environment = client::ENV_TEST2,
    ?\QnbSolutions\QnbEsolutions\client $client = null,
): qnb_invoice {
    return new qnb_invoice($username, $password, $erp_kodu, $environment, 'efatura', $client);
}

/**
 * Hafif facade: e-Arşiv (senkron).
 *
 *     $qnb = qnb_earsiv($username, $password, $erp_kodu);
 *     $sonuc = $qnb->kes();  // archive_result
 */
function qnb_earsiv(
    string $username,
    string $password,
    string $erp_kodu = 'ERP1',
    string $environment = client::ENV_TEST,
): qnb_invoice {
    return new qnb_invoice($username, $password, $erp_kodu, $environment, 'earsiv');
}
