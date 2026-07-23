<?php

namespace QnbSolutions\QnbEsolutions\enum;

enum status_code: int
{
    case ALINDI = 1;
    case ISLEME_HATASI = 2;
    case ISLENDI = 3;

    // Gönderim durum kodları
    case GONDERIM_IPTAL = -2;
    case GONDERIM_KUYRUK = -1;
    case GONDERIM_BEKLIYOR = 0;
    case GONDERILECEK = 1;
    case GONDERILDI = 2;
    case GIB_CEVAP_GELDI = 3;
    case ALICI_CEVAP_GELDI = 4;

    // Yanıt durum kodları
    case YANIT_GEREKMIYOR = -1;
    case YANIT_BEKLENIYOR = 0;
    case RED = 1;
    case KABUL = 2;

    public function label(): string
    {
        return match ($this) {
            self::ALINDI => 'Alındı',
            self::ISLEME_HATASI => 'İşleme Hatası',
            self::ISLENDI => 'İşlendi',
            self::GONDERIM_IPTAL => 'İptal edildi',
            self::GONDERIM_KUYRUK => 'Kuyruğa eklendi',
            self::GONDERIM_BEKLIYOR => 'Gönderilemedi',
            self::GONDERILECEK => 'Gönderilecek',
            self::GONDERILDI => 'Gönderildi',
            self::GIB_CEVAP_GELDI => 'GİB merkez yanıtı geldi',
            self::ALICI_CEVAP_GELDI => 'Alıcı yanıtı geldi',
            self::YANIT_GEREKMIYOR => 'Yanıt gerekmiyor',
            self::YANIT_BEKLENIYOR => 'Yanıt bekleniyor',
            self::RED => 'Red cevabı geldi',
            self::KABUL => 'Kabul cevabı geldi',
        };
    }
}
