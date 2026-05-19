<?php
/**
 * İletişim sayfası
 */
require_once __DIR__ . '/../config/config.php';
require_once INCLUDES_PATH . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        flash('error', 'Güvenlik doğrulaması başarısız.');
    } else {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        $errors = [];
        if (mb_strlen($name) < 3)  $errors[] = 'İsim en az 3 karakter olmalı.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli bir e-posta girin.';
        if (mb_strlen($message) < 10) $errors[] = 'Mesaj en az 10 karakter olmalı.';

        if (empty($errors)) {
            $ins = $pdo->prepare(
                "INSERT INTO messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)"
            );
            $ins->execute([$name, $email, $phone ?: null, $subject ?: null, $message]);
            flash('success', 'Mesajınız alındı. En kısa sürede dönüş yapılacak.');
            redirect(PUBLIC_URL . '/iletisim.php');
        }
        foreach ($errors as $err) flash('error', $err);
    }
}

$pageTitle = 'İletişim — ' . setting('site_name');
require INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
  <div class="breadcrumb"><a href="<?= PUBLIC_URL ?>/index.php">Ana Sayfa</a> / İletişim</div>
  <h1>Bize <em>ulaş</em></h1>
  <p class="text-mute">Sorularını cevaplamak için buradayız. En kısa sürede dönüş yapıyoruz.</p>
</div>

<div class="container" style="max-width: 1000px;">
  <div style="display:grid;grid-template-columns:1fr 1.4fr;gap:30px;" class="contact-layout">

    <div class="detail-card">
      <h3 style="margin-bottom:16px;">İletişim Kanalları</h3>

      <div style="display:flex;flex-direction:column;gap:18px;">
        <?php if (setting('contact_email')): ?>
          <div style="display:flex;gap:14px;align-items:flex-start;">
            <div style="width:42px;height:42px;border-radius:10px;background:var(--grad-primary);display:grid;place-items:center;flex-shrink:0;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <div>
              <div style="font-size:12px;color:var(--text-mute);text-transform:uppercase;letter-spacing:.1em;">E-posta</div>
              <a href="mailto:<?= e(setting('contact_email')) ?>" style="font-size:15px;color:var(--text);"><?= e(setting('contact_email')) ?></a>
            </div>
          </div>
        <?php endif; ?>

        <?php if (setting('whatsapp_number')): ?>
          <div style="display:flex;gap:14px;align-items:flex-start;">
            <div style="width:42px;height:42px;border-radius:10px;background:#25D366;display:grid;place-items:center;flex-shrink:0;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M17.5 14.4c-.3-.1-1.8-.9-2.1-1-.3-.1-.5-.1-.7.2-.2.3-.7 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.5-1.6-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.4.1-.6.1-.1.3-.3.4-.5.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5 0-.1-.7-1.7-1-2.3-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.5.3-.7.3-1.4.2-1.5-.1-.1-.3-.2-.6-.3z"/></svg>
            </div>
            <div>
              <div style="font-size:12px;color:var(--text-mute);text-transform:uppercase;letter-spacing:.1em;">WhatsApp</div>
              <a href="https://wa.me/<?= e(preg_replace('/\D/', '', setting('whatsapp_number'))) ?>" target="_blank" style="font-size:15px;color:var(--text);"><?= e(setting('whatsapp_number')) ?></a>
            </div>
          </div>
        <?php endif; ?>

        <?php if (setting('telegram_link')): ?>
          <div style="display:flex;gap:14px;align-items:flex-start;">
            <div style="width:42px;height:42px;border-radius:10px;background:#26A5E4;display:grid;place-items:center;flex-shrink:0;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71l-4.14-3.05-1.99 1.93c-.23.23-.42.42-.83.42z"/></svg>
            </div>
            <div>
              <div style="font-size:12px;color:var(--text-mute);text-transform:uppercase;letter-spacing:.1em;">Telegram</div>
              <a href="<?= e(setting('telegram_link')) ?>" target="_blank" style="font-size:15px;color:var(--text);">Telegram Kanalı</a>
            </div>
          </div>
        <?php endif; ?>

        <?php if (setting('contact_phone')): ?>
          <div style="display:flex;gap:14px;align-items:flex-start;">
            <div style="width:42px;height:42px;border-radius:10px;background:var(--surface-2);border:1px solid var(--border);display:grid;place-items:center;flex-shrink:0;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <div>
              <div style="font-size:12px;color:var(--text-mute);text-transform:uppercase;letter-spacing:.1em;">Telefon</div>
              <span style="font-size:15px;color:var(--text);"><?= e(setting('contact_phone')) ?></span>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="detail-card">
      <h3 style="margin-bottom:16px;">Bize Yaz</h3>
      <form method="post">
        <?= csrf_field() ?>
        <div class="form-group">
          <label class="form-label">Adınız</label>
          <input type="text" name="name" class="form-input" required>
        </div>
        <div class="form-group">
          <label class="form-label">E-posta</label>
          <input type="email" name="email" class="form-input" required>
        </div>
        <div class="form-group">
          <label class="form-label">Telefon (opsiyonel)</label>
          <input type="tel" name="phone" class="form-input">
        </div>
        <div class="form-group">
          <label class="form-label">Konu</label>
          <input type="text" name="subject" class="form-input">
        </div>
        <div class="form-group">
          <label class="form-label">Mesajınız</label>
          <textarea name="message" class="form-textarea" required minlength="10" rows="6"></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-lg">Mesajı Gönder</button>
      </form>
    </div>
  </div>
</div>

<style>
@media (max-width: 768px) {
  .contact-layout { grid-template-columns: 1fr !important; }
}
</style>

<?php require INCLUDES_PATH . '/footer.php'; ?>
