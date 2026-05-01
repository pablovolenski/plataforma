/* Plataforma — main.js
   Compose modal, filter chips, char counters, AJAX submit. Vanilla JS. */

document.addEventListener('DOMContentLoaded', () => {
  initFilters();
  initCharCounters();
  initComposerModal();
  initComposeForm();
  initShare();
});

// ---------------------------------------------------------------------------
// Filter chips (client-side, no page reload)
// ---------------------------------------------------------------------------

function initFilters() {
  const filterRoot   = document.getElementById('filters');
  const articlesRoot = document.getElementById('articles');
  if (!filterRoot || !articlesRoot) return;

  filterRoot.addEventListener('click', (e) => {
    const chip = e.target.closest('.filter-chip');
    if (!chip) return;

    const filter = chip.dataset.filter;

    filterRoot.querySelectorAll('.filter-chip').forEach((btn) => {
      btn.classList.toggle('is-active', btn === chip);
    });

    articlesRoot.querySelectorAll('.article-card').forEach((card) => {
      const match = filter === 'all' || card.dataset.kind === filter;
      card.classList.toggle('is-hidden', !match);
    });
  });
}

// ---------------------------------------------------------------------------
// Character counters
// ---------------------------------------------------------------------------

function initCharCounters() {
  document.querySelectorAll('.char-counter[data-target]').forEach((counter) => {
    const target = counter.dataset.target;
    const max    = parseInt(counter.dataset.max, 10);
    const field  = document.querySelector(`[name="${target}"]`);
    if (!field || !max) return;

    function update() {
      const len = field.value.length;
      counter.textContent = `${len} / ${max}`;
      counter.classList.toggle('is-near-limit', len >= max * 0.85);
    }
    field.addEventListener('input', update);
    update();
  });
}

// ---------------------------------------------------------------------------
// Composer modal: open / close / category preset
// ---------------------------------------------------------------------------

function initComposerModal() {
  const modal = document.getElementById('composer-modal');
  if (!modal) return;

  const categorySelect = modal.querySelector('[name="post_category"]');

  document.querySelectorAll('[data-action="open-composer"]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
      // Pre-select category if specified
      const presetId = trigger.dataset.category;
      if (presetId && categorySelect) {
        categorySelect.value = presetId;
      }

      if (typeof modal.showModal === 'function') {
        modal.showModal();
      } else {
        modal.setAttribute('open', '');
      }

      // Focus first sensible field
      setTimeout(() => {
        const titleField = modal.querySelector('[name="post_title"]');
        if (titleField) titleField.focus();
      }, 50);
    });
  });

  // Close triggers
  modal.querySelectorAll('[data-action="close-composer"]').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (typeof modal.close === 'function') {
        modal.close();
      } else {
        modal.removeAttribute('open');
      }
    });
  });

  // Click backdrop to close
  modal.addEventListener('click', (e) => {
    const rect = modal.getBoundingClientRect();
    const inDialog =
      e.clientX >= rect.left && e.clientX <= rect.right &&
      e.clientY >= rect.top  && e.clientY <= rect.bottom;
    if (!inDialog && typeof modal.close === 'function') {
      modal.close();
    }
  });
}

// ---------------------------------------------------------------------------
// Compose form: AJAX submit
// ---------------------------------------------------------------------------

function initComposeForm() {
  const form   = document.getElementById('article-form');
  const notice = document.getElementById('compose-notice');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    if (typeof PlataformaData === 'undefined') return; // fall back to standard POST

    e.preventDefault();

    const submitBtn = form.querySelector('button[type="submit"]');
    const original  = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Publicando…';

    const body = new URLSearchParams({
      action:        'plataforma_submit_post',
      _wpnonce:      PlataformaData.postNonce,
      post_title:    form.querySelector('[name="post_title"]').value.trim(),
      post_excerpt:  form.querySelector('[name="post_excerpt"]').value.trim(),
      post_content: form.querySelector('[name="post_content"]').value.trim(),
      post_category: form.querySelector('[name="post_category"]').value,
    });

    try {
      const res  = await fetch(PlataformaData.ajaxUrl, { method: 'POST', body });
      const data = await res.json();

      if (data.success && data.data.redirect) {
        window.location.href = data.data.redirect;
        return;
      }

      showNotice(notice, data.data?.message || 'Error al publicar. Intenta de nuevo.', 'error');
    } catch {
      showNotice(notice, 'Error de red. Verifica tu conexión.', 'error');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = original;
    }
  });
}

function showNotice(el, message, type = 'success') {
  if (!el) return;
  el.textContent = message;
  el.className = `notice notice--${type}`;
  el.hidden = false;
  el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  setTimeout(() => { el.hidden = true; }, 8000);
}

// ---------------------------------------------------------------------------
// Share: Web Share API on mobile, dropdown on desktop, clipboard for copy
// ---------------------------------------------------------------------------

function initShare() {
  // Compact share button: native Web Share API on mobile, dropdown on desktop
  document.querySelectorAll('.share-btn--toggle').forEach((btn) => {
    btn.addEventListener('click', async (e) => {
      e.stopPropagation();

      const dropdown = btn.closest('.share-dropdown');
      if (!dropdown) return;

      const url   = dropdown.dataset.shareUrl;
      const title = dropdown.dataset.shareTitle;

      // Mobile: try native share sheet
      if (navigator.share && /Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
        try {
          await navigator.share({ title, url });
          return;
        } catch {
          // User cancelled or unsupported — fall through to dropdown
        }
      }

      // Desktop fallback: toggle dropdown menu
      const menu = dropdown.querySelector('.share-dropdown__menu');
      const expanded = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', String(!expanded));
      if (menu) menu.hidden = expanded;
    });
  });

  // Close dropdowns on outside click
  document.addEventListener('click', (e) => {
    document.querySelectorAll('.share-dropdown').forEach((dropdown) => {
      if (!dropdown.contains(e.target)) {
        const btn  = dropdown.querySelector('.share-btn--toggle');
        const menu = dropdown.querySelector('.share-dropdown__menu');
        if (btn) btn.setAttribute('aria-expanded', 'false');
        if (menu) menu.hidden = true;
      }
    });
  });

  // Close on Escape
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('.share-dropdown').forEach((dropdown) => {
      const btn  = dropdown.querySelector('.share-btn--toggle');
      const menu = dropdown.querySelector('.share-dropdown__menu');
      if (btn) btn.setAttribute('aria-expanded', 'false');
      if (menu) menu.hidden = true;
    });
  });

  // Open share links in popup window for desktop platforms
  document.querySelectorAll('.share-dropdown__item[href], .share-pill[href]').forEach((link) => {
    if (link.href.startsWith('mailto:')) return; // let email open the mail client
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const w = 600, h = 540;
      const left = (window.screen.width - w) / 2;
      const top  = (window.screen.height - h) / 2;
      window.open(
        link.href,
        'plataforma_share',
        `width=${w},height=${h},left=${left},top=${top},noopener,noreferrer`
      );
    });
  });

  // Copy link buttons
  document.querySelectorAll('[data-share-copy]').forEach((btn) => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      e.stopPropagation();

      const container = btn.closest('[data-share-url]');
      const url = container?.dataset.shareUrl || window.location.href;

      try {
        await navigator.clipboard.writeText(url);
        const original = btn.querySelector('span')?.textContent;
        btn.classList.add('is-copied');
        const label = btn.querySelector('span');
        if (label) label.textContent = '¡Copiado!';
        setTimeout(() => {
          btn.classList.remove('is-copied');
          if (label && original) label.textContent = original;
        }, 1800);
      } catch {
        // Fallback for browsers without clipboard API
        const ta = document.createElement('textarea');
        ta.value = url;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch {}
        document.body.removeChild(ta);
        btn.classList.add('is-copied');
        setTimeout(() => btn.classList.remove('is-copied'), 1800);
      }
    });
  });
}
