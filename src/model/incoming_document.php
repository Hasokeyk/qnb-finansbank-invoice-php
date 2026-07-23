<?php

namespace QnbSolutions\QnbEsolutions\model;

class incoming_document
{
    public function __construct(
        public readonly string $belge_no,
        public readonly string $belge_sira_no,
        public readonly string $belge_tarihi,
        public readonly string $belge_turu,
        public readonly string $ettn,
        public readonly string $gonderen_etiket,
        public readonly string $gonderen_vkn_tckn,
        public readonly string $alan_etiket,
        public readonly string $alici_unvan,
        public readonly string $satici_unvan,
        public readonly string $zarf_id,
        public readonly string $odenecek_tutar,
        public readonly string $odenecek_tutar_doviz_cinsi,
        public readonly string $arsivlenmis,
        public readonly string $belge_hash,
        public readonly string $fatura_gelis_tarihi,
    ) {
    }
}
