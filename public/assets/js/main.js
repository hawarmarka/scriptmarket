/* =========================================================================
   ScriptMarkt — Main JS
   Matrix rain + particle network + UI interactions
   ========================================================================= */
(function() {
  'use strict';

  /* =====================================================================
     1) MATRIX RAIN BACKGROUND
     ===================================================================== */
  function initMatrix() {
    const canvas = document.querySelector('.bg-matrix');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    let columns, drops, fontSize;
    const chars = '0123456789ABCDEFアイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホ$#@&%*+=<>{}[]()/\\|';

    function resize() {
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
      fontSize = 14;
      columns = Math.floor(canvas.width / fontSize);
      drops = Array(columns).fill(0).map(() => Math.random() * -100);
    }
    resize();
    window.addEventListener('resize', resize);

    function draw() {
      // Fade trail
      ctx.fillStyle = 'rgba(2, 3, 10, 0.07)';
      ctx.fillRect(0, 0, canvas.width, canvas.height);

      ctx.font = fontSize + 'px JetBrains Mono, monospace';
      for (let i = 0; i < drops.length; i++) {
        const text = chars[Math.floor(Math.random() * chars.length)];
        const y = drops[i] * fontSize;

        // Random color: mostly cyan, sometimes purple/green for variety
        const r = Math.random();
        if (r < 0.85) ctx.fillStyle = 'rgba(34, 211, 238, 0.65)';
        else if (r < 0.92) ctx.fillStyle = 'rgba(168, 85, 247, 0.7)';
        else ctx.fillStyle = 'rgba(0, 255, 136, 0.7)';

        // Brightest at the head
        if (drops[i] * fontSize > canvas.height - 30) {
          ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
        }

        ctx.fillText(text, i * fontSize, y);

        if (y > canvas.height && Math.random() > 0.975) {
          drops[i] = 0;
        }
        drops[i]++;
      }
    }

    let lastTime = 0;
    function animate(currentTime) {
      if (currentTime - lastTime > 60) {  // ~16fps for performance
        draw();
        lastTime = currentTime;
      }
      requestAnimationFrame(animate);
    }
    animate(0);
  }
  initMatrix();

  /* =====================================================================
     2) MOBILE NAV TOGGLE
     ===================================================================== */
  const mobileToggle = document.querySelector('.mobile-toggle');
  const navbar = document.querySelector('.navbar');
  if (mobileToggle && navbar) {
    mobileToggle.addEventListener('click', () => {
      navbar.classList.toggle('nav-mobile-open');
    });
  }

  /* =====================================================================
     3) FAQ ACCORDION
     ===================================================================== */
  document.querySelectorAll('.faq-item').forEach(item => {
    const q = item.querySelector('.faq-q');
    if (q) q.addEventListener('click', () => item.classList.toggle('open'));
  });

  /* =====================================================================
     4) PAYMENT METHOD SELECTION
     ===================================================================== */
  const paymentMethods = document.querySelectorAll('.payment-method');
  paymentMethods.forEach(pm => {
    pm.addEventListener('click', (e) => {
      paymentMethods.forEach(p => p.classList.remove('selected'));
      pm.classList.add('selected');
      const radio = pm.querySelector('input[type="radio"]');
      if (radio) radio.checked = true;
      // Show only this one's details
      document.querySelectorAll('.payment-details').forEach(d => d.style.display = 'none');
      const next = pm.nextElementSibling;
      if (next && next.classList.contains('payment-details')) next.style.display = 'block';
    });
  });

  /* =====================================================================
     5) TABS
     ===================================================================== */
  document.querySelectorAll('.tabs-nav').forEach(nav => {
    const buttons = nav.querySelectorAll('.tab-btn');
    const container = nav.parentElement;
    buttons.forEach(btn => {
      btn.addEventListener('click', () => {
        buttons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        container.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        const tabName = btn.dataset.tab;
        const target = container.querySelector(`[data-tab-content="${tabName}"]`);
        if (target) target.classList.add('active');
      });
    });
  });

  /* =====================================================================
     6) LICENSE COPY
     ===================================================================== */
  document.querySelectorAll('.license-copy').forEach(btn => {
    btn.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(btn.dataset.key);
        const original = btn.textContent;
        btn.textContent = '✓ KOPYALANDI';
        setTimeout(() => btn.textContent = original, 1500);
      } catch (err) {
        console.error('Copy failed', err);
      }
    });
  });

  /* =====================================================================
     7) ANIMATED COUNTERS (IntersectionObserver)
     ===================================================================== */
  function animateCounter(el) {
    const target = parseFloat(el.dataset.count || el.textContent.replace(/[^\d.]/g, '')) || 0;
    const prefix = el.dataset.prefix || '';
    const suffix = el.dataset.suffix || '';
    const duration = 1500;
    const start = performance.now();
    const startVal = 0;
    function step(now) {
      const elapsed = now - start;
      const progress = Math.min(elapsed / duration, 1);
      const ease = 1 - Math.pow(1 - progress, 3);
      const value = startVal + (target - startVal) * ease;
      const formatted = target >= 1000
        ? Math.floor(value).toLocaleString('tr-TR')
        : value.toFixed(target % 1 ? 1 : 0);
      el.textContent = prefix + formatted + suffix;
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.3 });
    document.querySelectorAll('[data-count]').forEach(el => observer.observe(el));
  }


  /* =====================================================================
     SUPPORT WIDGET
     ===================================================================== */
  document.addEventListener('click', (e) => {
    const toggle = e.target.closest('[data-support-toggle]');
    const widget = document.getElementById('supportWidget');
    if (toggle && widget) {
      e.preventDefault();
      widget.classList.toggle('open');
      return;
    }
    if (widget && widget.classList.contains('open') && !widget.contains(e.target)) {
      widget.classList.remove('open');
    }
  });

  /* =====================================================================
     8) AJAX ADD TO CART
     ===================================================================== */
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-add-to-cart]');
    if (!btn) return;
    e.preventDefault();

    const scriptId = btn.dataset.addToCart;
    const url = btn.dataset.url;
    if (!scriptId || !url) return;

    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;"><circle cx="12" cy="12" r="10" stroke-dasharray="40 60"/></svg>';
    btn.disabled = true;

    try {
      const formData = new FormData();
      formData.append('script_id', scriptId);
      const selectedLicense = document.querySelector('input[name="license_option"]:checked');
      if (selectedLicense) formData.append('license_option', selectedLicense.value);
      const res = await fetch(url, { method: 'POST', body: formData });
      const data = await res.json();
      if (data.success) {
        // Update badge
        const badge = document.querySelector('.cart-badge');
        if (badge) {
          badge.textContent = data.count;
          badge.style.animation = 'bumpBadge .3s';
          setTimeout(() => badge.style.animation = '', 400);
        } else {
          // Insert new badge
          const cartIcon = document.querySelector('.cart-icon-btn');
          if (cartIcon) {
            const newBadge = document.createElement('span');
            newBadge.className = 'cart-badge';
            newBadge.textContent = data.count;
            cartIcon.appendChild(newBadge);
          }
        }
        showToast(data.message || 'Sepete eklendi', 'success');
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>';
        setTimeout(() => { btn.innerHTML = originalHTML; btn.disabled = false; }, 1200);
      } else {
        showToast(data.message || 'Hata oluştu', 'error');
        btn.innerHTML = originalHTML;
        btn.disabled = false;
      }
    } catch (err) {
      showToast('Bağlantı hatası', 'error');
      btn.innerHTML = originalHTML;
      btn.disabled = false;
    }
  });

  /* =====================================================================
     9) TOAST NOTIFICATIONS
     ===================================================================== */
  function showToast(message, type = 'success') {
    let stack = document.getElementById('toastStack');
    if (!stack) {
      stack = document.createElement('div');
      stack.id = 'toastStack';
      stack.style.cssText = 'position:fixed;top:80px;right:24px;z-index:200;display:flex;flex-direction:column;gap:10px;pointer-events:none;';
      document.body.appendChild(stack);
    }

    const colors = {
      success: { bg: 'rgba(16,185,129,.15)', border: '#10b981', text: '#6ee7b7' },
      error:   { bg: 'rgba(239,68,68,.15)',  border: '#ef4444', text: '#fca5a5' },
      info:    { bg: 'rgba(99,102,241,.15)', border: '#6366f1', text: '#a5b4fc' }
    };
    const c = colors[type] || colors.info;

    const toast = document.createElement('div');
    toast.style.cssText = `
      background: ${c.bg};
      backdrop-filter: blur(20px);
      border: 1px solid ${c.border};
      border-left-width: 3px;
      border-radius: 10px;
      padding: 12px 18px;
      color: ${c.text};
      font-size: 14px;
      box-shadow: 0 8px 24px rgba(0,0,0,.3);
      animation: toastIn .3s ease forwards;
      pointer-events: auto;
      max-width: 320px;
    `;
    toast.textContent = message;
    stack.appendChild(toast);

    setTimeout(() => {
      toast.style.animation = 'toastOut .3s ease forwards';
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }

  // Inject keyframes for toasts and spin
  if (!document.getElementById('mainJsKeyframes')) {
    const s = document.createElement('style');
    s.id = 'mainJsKeyframes';
    s.textContent = `
      @keyframes toastIn { from { opacity:0; transform: translateX(40px); } to { opacity:1; transform: translateX(0); } }
      @keyframes toastOut { to { opacity:0; transform: translateX(40px); } }
      @keyframes spin { to { transform: rotate(360deg); } }
      @keyframes bumpBadge { 0% { transform: scale(1); } 50% { transform: scale(1.4); } 100% { transform: scale(1); } }
    `;
    document.head.appendChild(s);
  }

  /* =====================================================================
     10) NAVBAR SCROLL EFFECT
     ===================================================================== */
  let lastScroll = 0;
  window.addEventListener('scroll', () => {
    const nav = document.querySelector('.navbar');
    if (!nav) return;
    if (window.scrollY > 30) {
      nav.style.background = 'rgba(2, 3, 10, .85)';
      nav.style.borderBottomColor = 'rgba(148, 163, 184, .2)';
    } else {
      nav.style.background = 'rgba(2, 3, 10, .6)';
      nav.style.borderBottomColor = 'rgba(148, 163, 184, .12)';
    }
  });

})();
