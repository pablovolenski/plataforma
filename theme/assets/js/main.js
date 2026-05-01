/* Plataforma — main.js
   Compose modal, filter chips, char counters, AJAX submit. Vanilla JS. */

document.addEventListener('DOMContentLoaded', () => {
  initFilters();
  initCharCounters();
  initComposerModal();
  initComposeForm();
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
