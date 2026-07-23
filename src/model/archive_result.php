<?php

namespace QnbSolutions\QnbEsolutions\model;

class archive_result
{
    public function __construct(
        public readonly ?string $output_base64,
        public readonly string $islem_id,
        public readonly string $fatura_url,
        public readonly string $uuid,
        public readonly string $fatura_no,
        public readonly ?string $iptal_tarihi,
    ) {
    }
}
