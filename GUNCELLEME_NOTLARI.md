# ScriptMarkt Premium Fix Paketi

Bu paket canlı Coolify/Docker yayını için düzenlenmiştir.

## Öne çıkan düzeltmeler

- Tüm marka adı `ScriptMarkt` olarak güncellendi.
- Kök URL ve eksik sayfa route sorunları düzeltildi: `/scripts.php`, `/script-detay.php`, `/iletisim.php`, `/sepet.php` vb. artık çalışır.
- Logo ve favicon sistemi tek merkezden çalışacak şekilde düzenlendi. Değiştirilen logo public site, admin panel, sidebar ve footer tarafında aynı anda görünür.
- Logo yükleme alanları tıklanabilir hale getirildi; PNG, JPG, WEBP, GIF, SVG ve ICO desteği eklendi.
- Site Ayarları genişletildi: head kodları, body kodları, özel CSS, arka plan görseli, destek widget, footer ödeme yöntemleri, tema renkleri.
- Ürün ekleme/düzenleme sistemi geliştirildi: kapak görseli, galeri görselleri, demo linki, admin demo linki, dosya yükleme, rozet, destek dahil, aylık/yıllık/ömür boyu/ücretsiz lisans seçenekleri.
- Sepet ve ödeme tarafı seçilen lisans tipini siparişe aktarır.
- Sağ alt destek widget sistemi eklendi: site içi mesaj, WhatsApp, Telegram ve özel canlı destek linki admin panelinden kontrol edilir.
- Admin dashboard yenilendi: toplam, günlük, haftalık, aylık, yıllık satış metrikleri eklendi.
- Public ürün kartları ve admin form/kutu tasarımları daha glass/premium görünüme çekildi.
- Eski veritabanlarında eksik kolonlar otomatik tamamlanır. Temiz kurulum zorunlu değildir.

## Coolify kurulum notu

Coolify tarafında Environment Variables içinde şu değerler tanımlı olmalı:

```env
DB_HOST=...
DB_PORT=3306
DB_NAME=...
DB_USER=...
DB_PASS=...
APP_DEBUG=false
```

Veritabanı şifresi güvenlik için dosyaya gömülmemiştir; ENV üzerinden okunur.

## Deploy

1. Bu paketteki `scriptmaket` klasörünü GitHub reposundaki aynı klasörün üzerine yaz.
2. Commit / push yap.
3. Coolify içinde `Redeploy` veya `Force rebuild without cache` çalıştır.
4. İlk açılışta sistem eksik DB kolonlarını otomatik tamamlar.
5. Admin panelde `Site Ayarları > Tüm Ayarları Kaydet` yaparak yeni ayarları senkronlayabilirsin.
