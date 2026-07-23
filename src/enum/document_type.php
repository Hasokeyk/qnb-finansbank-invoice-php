<?php

namespace QnbSolutions\QnbEsolutions\enum;

enum document_type: string
{
    case FATURA_UBL = 'FATURA_UBL';
    case UYGULAMA_YANITI_UBL = 'UYGULAMA_YANITI_UBL';
    case IRSALIYE_UBL = 'IRSALIYE_UBL';
    case IRSALIYE_YANITI_UBL = 'IRSALIYE_YANITI_UBL';

    // Download / listeleme için kullanılan kısa kodlar
    case FATURA = 'FATURA';
    case IRSALIYE = 'IRSALIYE';
    case UYGULAMA_YANITI = 'UYGULAMA_YANITI';
    case IRSALIYE_YANITI = 'IRSALIYE_YANITI';
}
