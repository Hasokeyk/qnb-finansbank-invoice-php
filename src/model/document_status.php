<?php

namespace QnbSolutions\QnbEsolutions\model;

class document_status
{
    public function __construct(
        public readonly string $alim_tarihi,
        public readonly string $belge_no,
        public readonly int $durum,
        public readonly string $ettn,
        public readonly string $gonderim_cevabi_detayi,
        public readonly string $gonderim_cevabi_kodu,
        public readonly int $gonderim_durumu,
        public readonly string $olusturulma_tarihi,
        public readonly string $yanit_detayi,
        public readonly string $yanit_durumu,
        public readonly bool $ulasti_mi,
        public readonly bool $yeniden_gonderilebilir_mi,
        public readonly string $yerel_belge_oid,
    ) {
    }
}
