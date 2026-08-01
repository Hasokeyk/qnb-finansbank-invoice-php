<?php

namespace QnbSolutions\QnbEsolutions\builder;

/**
 * GİB e-Fatura `kdvIstisnaKodu` (TaxExemptionReasonCode) resmi kod listesi.
 *
 * Kaynak: QNB e-Fatura portalındaki kdvIstisnaKodu açılır listesi.
 * Harita: kod → resmi istisna/muafiyet açıklaması.
 */
class kdv_istisna_kodlari
{
    /** @var array<string, string> kod → resmi istisna/muafiyet açıklaması */
    public const KODLAR = [
        '001' => 'Diplomatik İstisna',
        '101' => 'İhracat İstisnası',
        '102' => 'Diplomatik İstisna',
        '103' => 'Askeri Amaçlı İstisna',
        '104' => 'Petrol Arama Faaliyetlerinde Bulunanlara Yapılan Teslimler',
        '105' => 'Uluslararası Anlaşmadan Doğan İstisna',
        '106' => 'Diğer İstisnalar',
        '107' => '7/a Maddesi Kapsamında Yapılan Teslimler',
        '108' => 'Geçici 5. Madde Kapsamında Yapılan Teslimler',
        '151' => 'ÖTV - İstisna Olmayan Diğer',
        '201' => '17/1 Kültür ve eğitim amacı taşıyan işlemler',
        '202' => '17/2-a Sağlık, çevre ve sosyal yardım amaçlı işlemler',
        '204' => '17/2-c Yabancı Diplomatik Organ Ve Hayır Kurumlarının Yapacakları Bağışlarla İlgili Mal Ve Hizmet Alışları',
        '205' => '17/2-d Taşınmaz kültür varlıklarına ilişkin teslimler ve mimarlık hizmetleri',
        '206' => '17/2-e Mesleki kuruluşların işlemleri',
        '207' => '17/3 Askeri Fabrika, Tersane ve Atölyelerin İşlemleri',
        '208' => '17/4-c Birleşme, devir, dönüşüm ve bölünme işlemleri',
        '209' => '17/4-e Banka ve sigorta muameleleri vergisi kapsamına giren işlemler',
        '211' => '17/4-h Zirai Amaçlı Su Teslimleri İle Köy Tüzel Kişiliklerince Yapılan İçme Suyu teslimleri',
        '212' => '17/4-ı Serbest bölgelerde verilen hizmetler',
        '213' => '17/4-j Boru hattı ile yapılan petrol ve gaz taşımacılığı',
        '214' => '17/4-k Organize Sanayi Bölgelerindeki Arsa ve İşyeri Teslimleri İle Konut Yapı Kooperatiflerinin Üyelerine Konut Teslimleri',
        '215' => '17/4-l Varlık yönetim şirketlerinin işlemleri',
        '216' => '17/4-m Tasarruf Mevduatı Sigorta Fonunun İşlemleri',
        '217' => '17/4-n Basın-Yayın ve Enformasyon Genel Müdürlüğüne Verilen Haber Hizmetleri',
        '218' => 'KDV 17/4-o md. Gümrük Antrepoları, Geçici Depolama Yerleri ile Gümrüklü Sahalarda Vergisiz Satış Yapılan İşyeri, Depo ve Ardiye Gibi Bağımsız Birimlerin Kiralanması',
        '219' => '17/4-p Hazine ve Arsa Ofisi Genel Müdürlüğünün işlemleri',
        '220' => '17/4-r İki Tam Yıl Süreyle Sahip Olunan Taşınmaz ve İştirak Hisseleri Satışları',
        '221' => 'Geçici 15 Konut yapı kooperatifleri, belediyeler ve sosyal güvenlik kuruluşlarına verilen inşaat taahhüt hizmeti',
        '223' => 'Geçici 20/1 Teknoloji geliştirme bölgelerinde yapılan işlemler',
        '225' => 'Geçici 23 Milli Eğitim Bakanlığına yapılan bilgisayar bağışları ile ilgili teslimler',
        '226' => '17/2-b Özel Okulları, Üniversite ve Yüksekokullar Tarafından Verilen Bedelsiz Eğitim Ve Öğretim Hizmetleri',
        '227' => '17/2-b Kanunların Gösterdiği Gerek Üzerine Bedelsiz Olarak Yapılan Teslim ve Hizmetler',
        '228' => '17/2-b Kanunun (17/1) Maddesinde Sayılan Kurum ve Kuruluşlara Bedelsiz Olarak Yapılan Teslimler',
        '229' => '17/2-b Gıda Bankacılığı Faaliyetinde Bulunan Dernek ve Vakıflara Bağışlanan Gıda, Temizlik, Giyecek ve Yakacak Maddeleri',
        '230' => '17/4-g Külçe Altın, Külçe Gümüş Ve Kiymetli Taşlarin Teslimi',
        '231' => '17/4-g Metal Plastik, Lastik, Kauçuk, Kağit, Cam Hurda Ve Atıkların Teslimi',
        '232' => '17/4-g Döviz, Para, Damga Pulu, Değerli Kağıtlar, Hisse Senedi ve Tahvil Teslimleri',
        '234' => '17/4-ş Konut Finansmanı Amacıyla Teminat Gösterilen ve İpotek Konulan Konutların Teslimi',
        '235' => '16/1-c Transit ve Gümrük Antrepo Rejimleri İle Geçici Depolama ve Serbest Bölge Hükümlerinin Uygulandığıı Malların Teslimi',
        '236' => '19/2 Usulüne Göre Yürürlüğe Girmiş Uluslararası Anlaşmalar Kapsamındaki İstisnalar (İade Hakkı Tanınmayan)',
        '237' => '17/4-t 5300 Sayılı Kanuna Göre Düzenlenen Ürün Senetlerinin İhtisas/Ticaret Borsaları Aracılığıyla İlk Teslimlerinden Sonraki Teslim',
        '238' => '17/4-u Varlıkların Varlık Kiralama Şirketlerine Devri İle Bu Varlıkların Varlık Kiralama Şirketlerince Kiralanması ve Devralınan Kuruma Devri',
        '239' => '17/4-y Taşınmazların Finansal Kiralama Şirketlerine Devri, Finansal Kiralama Şirketi Tarafından Devredene Kiralanması ve Devri',
        '240' => '17/4-z Patentli Veya Faydalı Model Belgeli Buluşa İlişkin Gayri Maddi Hakların Kiralanması, Devri ve Satışı',
        '241' => 'TürkAkım Gaz Boru Hattı Projesine İlişkin Anlaşmanın (9/b) Maddesinde Yer Alan Hizmetler',
        '242' => 'KDV 17/4-ö md. Gümrük Antrepoları, Geçici Depolama Yerleri ile Gümrüklü Sahalarda, İthalat ve İhracat İşlemlerine konu mallar ile transit rejim kapsamında işlem gören mallar için verilen ardiye, depolama ve terminal hizmetleri',
        '250' => 'Diğerleri',
        '301' => '11/1 - a Mal ihracatı',
        '302' => '11 /1 - a Hizmet ihracatı',
        '303' => '11/1 - a Roaming hizmetleri',
        '304' => '13/a Deniz Hava ve Demiryolu Taşıma Araçlarının Teslimi İle İnşa, Tadil, Bakım ve Onarımları',
        '305' => '13/b Deniz ve hava taşıma araçları için liman ve hava meydanlarında yapılan hizmetler',
        '306' => '13/c Petrol Aramaları ve Petrol Boru Hatlarının İnşa ve Modernizasyonuna İlişkin Yapılan Teslim ve Hizmetler',
        '307' => '13/c Maden Arama, Altın, Gümüş ve Platin Madenleri İçin İşletme, Zenginleştirme Ve Rafinaj Faaliyetlerine İlişkin Teslim Ve HizmetlerKDVGUT-(II/8-4)',
        '308' => '13/d Teşvikli yatırım mallarının teslimi',
        '309' => '13/e Liman ve hava meydanlarının inşası, yenilenmesi ve genişletilmesi',
        '310' => '13/f Ulusal güvenlik amaçlı teslim ve hizmetler',
        '311' => '14 Uluslararası taşımacılık',
        '312' => '15/a Diplomatik organ ve misyonlara yapılan teslim ve hizmetler',
        '313' => '15/b Uluslararası kuruluşlara yapılan teslim ve hizmetler',
        '314' => '19/2 Usulüne Göre Yürürlüğe Girmiş Uluslar Arası Anlaşmalar Kapsamındaki İstisnalar',
        '315' => '14/3 İhraç Konusu Eşyayı Taşıyan Kamyon, Çekici ve Yarı Romorklara Yapılan Motorin Teslimleri',
        '316' => '11/1-a Serbest Bölgelerdeki Müşteriler İçin Yapılan Fason Hizmetler',
        '317' => '17/4-s Engellilerin Eğitimleri, Meslekleri ve Günlük Yaşamlarına İlişkin Araç-Gereç ve Bilgisayar Programları',
        '318' => 'Geçici 29 3996 Yap-İşlet-Devret Modeli..., 3359 Kiralama Karşılığı Yaptırılan Sağlık Tesislerine İlişkin Projeler ve 652 Kiralama Karşılığı Yaptırılan Eğitim Öğretim Tesislerine İlişkin Projelere İlişkin Teslim ve Hizmetler',
        '319' => '13/g Başbakanlık Merkez Teşkilatına Yapılan Araç Teslimleri',
        '320' => 'Geçici 16 (6111 sayılı K.) İSMEP Kapsamında İstanbul İl Özel İdaresi\'ne Bağlı Olarak Faaliyet Gösteren "İstanbul Proje Koordinasyon Birimi"ne Yapılacak Teslim ve Hizmetler',
        '321' => 'Geçici 26 Birleşmiş Milletler (BM) ile Kuzey Atlantik Antlaşması Teşkilatı (NATO) Temsilcilikleri ve Kalkınma Teşkilatına (OECD) Resmi Kullanımları İçin Yapılacak Mal Teslimi ve Hizmet İfaları',
        '322' => '11/1-a Türkiye\'de İkamet Etmeyenlere Özel Fatura ile Yapılan Teslimler (Bavul Ticareti)',
        '323' => '13/ğ 5300 Sayılı Kanuna Göre Düzenlenen Ürün Senetlerinin İhtisas/Ticaret Borsaları Aracılığıyla İlk Teslimi',
        '324' => '13/h Türkiye Kızılay Derneğine Yapılan Teslim ve Hizmetler ile Türkiye Kızılay Derneğinin Teslim ve Hizmetleri',
        '325' => '13/ı Yem Teslimleri',
        '326' => '13/ı Gıda, Tarım ve Hayvancılık Bakanlığı Tarafından Tescil Edilmiş Gübrelerin Teslimi',
        '327' => '13/ı Gıda, Tarım ve Hayvancılık Bakanlığı Tarafından Tescil Edilmiş Gübrelerin İçeriğinde Bulunan Hammaddelerin Gübre Üreticilerine Teslimi',
        '328' => '13/i Konut veya İşyeri Teslimleri',
        '329' => 'Eğitimde Fırsatları Artırma ve Teknolojiyi İyileştirme Hareketi (FATİH)projesi Kapsamında Milli Eğitim Bakanlığına Yapılacak Mal Teslimi ve Hizmet İfası',
        '330' => 'KDV 13/j md. Organize Sanayi Bölgeleri ile Küçük Sanayi Sitelerinin İnşasına İlişkin Teslim ve Hizmetler',
        '331' => 'KDV 13/m  md.  Ar-Ge, Yenilik ve Tasarım Faaliyetlerinde Kullanılmak Üzere Yapılan Yeni Makina ve Teçhizat Teslimlerinde İstisna',
        '332' => 'KDV Geçici 39. Md.  İmalat Sanayiinde Kullanılmak Üzere Yapılan Yeni Makina ve Teçhizat Teslimlerinde İstisna',
        '333' => 'KDV 13/k md. Kapsamında Genel ve Özel Bütçeli Kamu İdarelerine, İl Özel İdarelerine, Belediyelere ve Köylere bağışlanan Tesislerin İnşasına İlişkin İstisna',
        '334' => 'KDV 13/l md. Kapsamında Yabancılara Verilen Sağlık Hizmetlerinde İstisna',
        '335' => 'Basılı Kitap ve Süreli Yayınların Teslimleri',
        '336' => 'UEFA Müsabakaları Kapsamında Yapılacak Teslim ve Hizmetler',
        '337' => 'Türk Akım Gaz Boru Hattı Projesine İlişkin Anlaşmanın (9/h) Maddesi Kapsamındaki Gaz Taşıma Hizmetleri',
        '338' => 'İmalatçıların Mal İhracatları',
        '339' => 'İmalat Sanayi ile Turizme Yönelik Yatırım Teşvik Belgesi Kapsamındaki',
        '342' => 'Genel Bütçeli Kamu İdarelerine Bağışlanacak Taşınmazların İnşasına İlişkin İstisna',
        '343' => 'Genel Bütçeli Kamu İdarelerine Bağışlanacak Konutların Yabancı Devlet Kurum ve Kuruluşlarına Teslimine İlişkin İstisna',
        '344' => '13/o Milli Savunma ve İç Güvenlik İhtiyaçlarında Kullanılmak Üzere Taşıt Teslimi',
        '350' => 'Diğerleri',
        '351' => 'KDV - İstisna Olmayan Diğer',
        '501' => 'Türkiye\'de İkamet Etmeyenlere KDV Hesaplanarak Yapılan Satışlar(Yolcu Beraberi Eşya)',
    ];

    public static function gecerli(string $kod): bool
    {
        return isset(self::KODLAR[$kod]);
    }

    public static function aciklama(string $kod): string
    {
        return self::KODLAR[$kod] ?? '';
    }
}
