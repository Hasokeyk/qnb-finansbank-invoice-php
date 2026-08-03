<?php

namespace QnbSolutions\QnbEsolutions\model;

class registered_user
{
    public function __construct(
        public readonly string $label,
        public readonly bool $is_public_institution,
        public readonly string $registration_time,
        public readonly string $title,
    ) {
    }
}
