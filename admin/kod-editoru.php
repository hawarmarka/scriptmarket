<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$adminPageTitle = 'Kod Editörü';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        flash('error', 'Güvenlik doğrulaması başarısız.');
        redirect(ADMIN_URL . '/kod-editoru.php');
    }

    $keys = ['custom_head_code', 'custom_css', 'custom_body_code'];
    foreach ($keys as $key) {
        $val = $_POST[$key] ?? '';
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)")
            ->execute([$key, $val]);
    }

    sm_setting_cache_flush();
    flash('success', '✓ Kodlar kaydedildi ve siteye uygulandı.');
    redirect(ADMIN_URL . '/kod-editoru.php');
}

require __DIR__ . '/_layout.php';
?>

<style>
.code-editor {
  font-family: 'JetBrains Mono', monospace;
  font-size: 13.5px;
  line-height: 1.7;
  min-height: 220px;
  background: rgba(2, 3, 10, .85);
  color: #e2e8f0;
  border: 1px solid var(--glass-border);
  border-radius: var(--radius);
  padding: 18px;
  resize: vertical;
  tab-size: 2;
  width: 100%;
  box-sizing: border-box;
}
.code-editor:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(99,102,241,.2);
}
.editor-label {
  font-family: 'JetBrains Mono', monospace;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: .15em;
  color: var(--accent);
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.editor-label::before {
  content: '';
  width: 8px; height: 8px;
  border-radius: 50%;
  background: var(--accent);
  box-shadow: 0 0 8px var(--accent);
}
.editor-hint {
  font-size: 12px;
  color: var(--text-dim);
  margin-top: 6px;
  line-height: 1.6;
  font-family: 'JetBrains Mono', monospace;
}
</style>

<form method="post">
  <?= csrf_field() ?>

  <!-- Bilgi kutusu -->
  <div class="admin-card" style="border-color: rgba(34,211,238,.3);background:rgba(34,211,238,.04);">
    <div style="display:grid;grid-template-columns:auto 1fr;gap:16px;align-items:flex-start;">
      <div style="font-size:36px;">🛠</div>
      <div>
        <h3 style="color:var(--accent);margin-bottom:6px;">Doğrudan Kod Enjeksiyonu</h3>
        <p style="font-size:13.5px;color:var(--text-soft);line-height:1.7;">
          Buraya yazdığın kodlar <strong style="color:var(--text);">tüm sayfalara otomatik</strong> eklenir — dosyaya dokunmana gerek yok.
          Google Analytics, Meta Pixel, özel CSS, chatbot scriptleri, font override'ları... hepsini buradan yönet.
        </p>
        <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
          <span style="padding:4px 10px;background:rgba(99,102,241,.15);border:1px solid rgba(99,102,241,.3);border-radius:100px;font-family:'JetBrains Mono',monospace;font-size:11px;color:#a5b4fc;">Google Analytics</span>
          <span style="padding:4px 10px;background:rgba(99,102,241,.15);border:1px solid rgba(99,102,241,.3);border-radius:100px;font-family:'JetBrains Mono',monospace;font-size:11px;color:#a5b4fc;">Meta Pixel</span>
          <span style="padding:4px 10px;background:rgba(99,102,241,.15);border:1px solid rgba(99,102,241,.3);border-radius:100px;font-family:'JetBrains Mono',monospace;font-size:11px;color:#a5b4fc;">Tawk.to Chat</span>
          <span style="padding:4px 10px;background:rgba(99,102,241,.15);border:1px solid rgba(99,102,241,.3);border-radius:100px;font-family:'JetBrains Mono',monospace;font-size:11px;color:#a5b4fc;">Hotjar</span>
          <span style="padding:4px 10px;background:rgba(99,102,241,.15);border:1px solid rgba(99,102,241,.3);border-radius:100px;font-family:'JetBrains Mono',monospace;font-size:11px;color:#a5b4fc;">Crisp Chat</span>
          <span style="padding:4px 10px;background:rgba(99,102,241,.15);border:1px solid rgba(99,102,241,.3);border-radius:100px;font-family:'JetBrains Mono',monospace;font-size:11px;color:#a5b4fc;">Özel Font</span>
        </div>
      </div>
    </div>
  </div>

  <!-- 1. HEAD Kodları -->
  <div class="admin-card">
    <div class="admin-card-head">
      <h3>&lt;head&gt; Kodu</h3>
      <div style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text-mute);">// &lt;/head&gt; etiketinden önce eklenir</div>
    </div>

    <div class="editor-label">HEAD_CODE / ANALYTICS / PIXEL</div>
    <textarea name="custom_head_code" class="code-editor" rows="8" placeholder="<!-- Örnek: Google Analytics -->
<script async src=&quot;https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX&quot;></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-XXXXXXXXXX');
</script>

<!-- Örnek: Meta Pixel -->
<script>
  !function(f,b,e,v,n,t,s){...}(window,...,'XXXXXXXXXXXXXXXXXX');
</script>"><?= htmlspecialchars(setting('custom_head_code', '')) ?></textarea>
    <div class="editor-hint">
      💡 Google Analytics, Meta Pixel, Hotjar, özel &lt;meta&gt; etiketleri, hreflang kodları buraya.
    </div>
  </div>

  <!-- 2. Custom CSS -->
  <div class="admin-card">
    <div class="admin-card-head">
      <h3>Özel CSS</h3>
      <div style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text-mute);">// &lt;style&gt; bloğu olarak &lt;head&gt;'e eklenir</div>
    </div>

    <div class="editor-label">CUSTOM_CSS / STYLE_OVERRIDES</div>
    <textarea name="custom_css" class="code-editor" rows="10" placeholder="/* Örnek: Buton rengini değiştir */
.btn-primary {
  background: linear-gradient(135deg, #ff6b6b, #feca57) !important;
}

/* Örnek: Font değiştir */
body {
  font-family: 'Roboto', sans-serif !important;
}

/* Örnek: Navbar arka planı */
.navbar {
  background: rgba(0, 0, 0, .95) !important;
}"><?= htmlspecialchars(setting('custom_css', '')) ?></textarea>
    <div class="editor-hint">
      💡 Sitenin herhangi bir CSS kuralını override etmek için kullan. CSS değişkenleri de desteklenir: <code>:root { --primary: #ff0000; }</code>
    </div>
  </div>

  <!-- 3. Body kodları -->
  <div class="admin-card">
    <div class="admin-card-head">
      <h3>&lt;/body&gt; Kodu</h3>
      <div style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text-mute);">// &lt;/body&gt; etiketinden önce eklenir</div>
    </div>

    <div class="editor-label">BODY_CODE / CHAT_WIDGET / SCRIPTS</div>
    <textarea name="custom_body_code" class="code-editor" rows="8" placeholder="<!-- Örnek: Tawk.to canlı destek -->
<script type=&quot;text/javascript&quot;>
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement(&quot;script&quot;),s0=document.getElementsByTagName(&quot;script&quot;)[0];
s1.async=true;
s1.src='https://embed.tawk.to/XXXXXX/default';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>

<!-- Örnek: Crisp Chat -->
<script>
window.$crisp=[];window.CRISP_WEBSITE_ID=&quot;XXXXXXXXX&quot;;
(function(){d=document;s=d.createElement(&quot;script&quot;);
s.src=&quot;https://client.crisp.chat/l.js&quot;;s.async=1;d.getElementsByTagName(&quot;head&quot;)[0].appendChild(s);})();
</script>"><?= htmlspecialchars(setting('custom_body_code', '')) ?></textarea>
    <div class="editor-hint">
      💡 Chat widget (Tawk.to, Crisp), tracking scriptleri, özel JS kodları buraya. Sayfa en sona yüklenir, performansı etkilemez.
    </div>
  </div>

  <!-- Hızlı şablonlar -->
  <div class="admin-card">
    <div class="admin-card-head">
      <h3>⚡ Hızlı Şablonlar</h3>
      <span style="font-size:13px;color:var(--text-mute);">Tıkla ve ilgili alana yapıştır</span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;">
      <?php
      $templates = [
        ['Google Analytics 4', 'head', "<!-- Google Analytics 4 -->\n<script async src=\"https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX\"></script>\n<script>\n  window.dataLayer = window.dataLayer || [];\n  function gtag(){dataLayer.push(arguments);}\n  gtag('js', new Date());\n  gtag('config', 'G-XXXXXXXXXX');\n</script>"],
        ['Meta (Facebook) Pixel', 'head', "<!-- Meta Pixel -->\n<script>\n!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?\nn.callMethod.apply(n,arguments):n.queue.push(arguments)};\nif(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';\nn.queue=[];t=b.createElement(e);t.async=!0;\nt.src=v;s=b.getElementsByTagName(e)[0];\ns.parentNode.insertBefore(t,s)}(window,document,'script',\n'https://connect.facebook.net/en_US/fbevents.js');\nfbq('init', 'XXXXXXXXXXXXXXXXXX');\nfbq('track', 'PageView');\n</script>"],
        ['Tawk.to Chat', 'body', "<!-- Tawk.to -->\n<script type=\"text/javascript\">\nvar Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();\n(function(){\nvar s1=document.createElement(\"script\"),s0=document.getElementsByTagName(\"script\")[0];\ns1.async=true;\ns1.src='https://embed.tawk.to/SİTE_ID/default';\ns1.charset='UTF-8';\ns1.setAttribute('crossorigin','*');\ns0.parentNode.insertBefore(s1,s0);\n})();\n</script>"],
        ['Crisp Chat', 'body', "<!-- Crisp Chat -->\n<script>\nwindow.\$crisp=[];\nwindow.CRISP_WEBSITE_ID=\"SİTE_ID\";\n(function(){\nd=document;\ns=d.createElement(\"script\");\ns.src=\"https://client.crisp.chat/l.js\";\ns.async=1;\nd.getElementsByTagName(\"head\")[0].appendChild(s);\n})();\n</script>"],
        ['Google Font (Inter)', 'head', "<link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">\n<link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>\n<link href=\"https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap\" rel=\"stylesheet\">"],
        ['Renk Override (CSS)', 'css', ":root {\n  --primary: #6366f1;\n  --secondary: #a855f7;\n  --accent: #22d3ee;\n}\n\n/* Buton rengi override */\n.btn-primary {\n  background: linear-gradient(135deg, var(--primary), var(--secondary)) !important;\n}"],
      ];
      foreach ($templates as $t): [$name, $target, $code] = $t;
      $targetLabel = $target === 'head' ? '→ &lt;head&gt;' : ($target === 'body' ? '→ &lt;/body&gt;' : '→ CSS');
      $targetColor = $target === 'head' ? 'var(--primary)' : ($target === 'body' ? 'var(--success)' : 'var(--secondary)');
      ?>
        <button type="button" class="template-btn"
          data-code="<?= htmlspecialchars($code) ?>"
          data-target="<?= $target ?>"
          style="text-align:left;padding:14px 16px;background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:var(--radius);cursor:pointer;transition:all .2s;display:flex;flex-direction:column;gap:6px;">
          <div style="font-weight:600;font-size:13.5px;"><?= $name ?></div>
          <div style="font-family:'JetBrains Mono',monospace;font-size:10.5px;color:<?= $targetColor ?>;"><?= $targetLabel ?></div>
        </button>
      <?php endforeach; ?>
    </div>
    <p style="font-size:12px;color:var(--text-dim);margin-top:12px;font-family:'JetBrains Mono',monospace;">// Butona tıkla → kod panoya kopyalanır → ilgili editöre yapıştır</p>
  </div>

  <div style="padding:18px;background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:12px;display:flex;gap:10px;justify-content:space-between;align-items:center;flex-wrap:wrap;">
    <div style="font-size:13px;color:var(--text-mute);">
      Değişiklikler kaydedildiğinde <strong style="color:var(--text);">anında tüm sayfalara</strong> yansır.
    </div>
    <button type="submit" class="btn btn-primary btn-lg">💾 Kodları Kaydet</button>
  </div>
</form>

<script>
// Tab key support in textarea
document.querySelectorAll('.code-editor').forEach(ta => {
  ta.addEventListener('keydown', e => {
    if (e.key === 'Tab') {
      e.preventDefault();
      const s = ta.selectionStart, end = ta.selectionEnd;
      ta.value = ta.value.substring(0, s) + '  ' + ta.value.substring(end);
      ta.selectionStart = ta.selectionEnd = s + 2;
    }
  });
});

// Template copy
document.querySelectorAll('.template-btn').forEach(btn => {
  btn.addEventListener('mouseenter', () => btn.style.borderColor = 'var(--primary)');
  btn.addEventListener('mouseleave', () => btn.style.borderColor = 'var(--glass-border)');
  btn.addEventListener('click', async () => {
    const code = btn.dataset.code;
    const target = btn.dataset.target;

    // Copy to clipboard
    try {
      await navigator.clipboard.writeText(code);
    } catch(e) {}

    // Also set to correct textarea
    const map = { head: 'custom_head_code', body: 'custom_body_code', css: 'custom_css' };
    const ta = document.querySelector(`textarea[name="${map[target]}"]`);
    if (ta) {
      const cur = ta.value;
      ta.value = (cur ? cur + '\n\n' : '') + code;
      ta.scrollTop = ta.scrollHeight;
      ta.focus();
    }

    const orig = btn.querySelector('div:first-child').textContent;
    btn.querySelector('div:first-child').textContent = '✓ Kopyalandı & Eklendi';
    btn.style.borderColor = 'var(--success)';
    setTimeout(() => {
      btn.querySelector('div:first-child').textContent = orig;
      btn.style.borderColor = 'var(--glass-border)';
    }, 1500);
  });
});
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>
