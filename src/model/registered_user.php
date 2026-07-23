<?php

namespace QnbSolutions\QnbEsolutions\model;

class registered_user
{
    public function __construct(
        public readonly string $etiket,
        public readonly bool $kamu_kurulusu,
        public readonly string $kayit_zamani,
        public readonly string $unvan,
    ) {
    }
}
