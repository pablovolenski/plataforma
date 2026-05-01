/* Plataforma — main.js
   Filter chips, compose form AJAX submit, character counters.
   No jQuery required. PlataformaData is injected by the plugin via wp_localize_script. */

document.addEventListener('DOMContentLoaded', () => {
  initFilters();
  initCharCounters();
  initComposeForm();
});

// ---------------------------------------------------------------------------
// Filter chips (client-side, no page reload)
// ---------------------------------------------------------------------------

function initFilters() {
  const filterRoot = document.getElementById('filters');
  const articlesRoot = document.getElementById('articles');

  if (!filterRoot || !articlesRoot) return;

  filterRoot.addEventListener('click', (e) => {
    const chip = e.target.closest('.filter-chip');
    if (!chip) return;

    const filter = chip.dataset.filter;

    // Update active chip
    filterRoot.querySelectorAll('.filter-chip').forEach((btn) => {
      btn.classList.toggle('is-active', btn === chip);
    });

    // Show / hide cards
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
// Compose form: AJAX submit with fallback to standard POST
// ---------------------------------------------------------------------------

function initComposeForm() {
  const form   = document.getElementById('article-form');
  const notice = document.getElementById('compose-notice');

  if (!form) return;

  form.addEventListener('submit', async (e) => {
    // If PlataformaData is not available (plugin inactive), let the form POST normally.
    if (typeof PlataformaData === 'undefined') return;

    e.preventDefault();

    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Publicando…';

    const body = new URLSearchParams({
      action:         'plataforma_submit_post',
      _wpnonce:       PlataformaData.postNonce,
      post_title:     form.querySelector('[name="post_title"]').value.trim(),
      post_excerpt:   form.querySelector('[name="post_excerpt"]').value.trim(),
      post_content:   form.querySelector('[name="post_content"]').value.trim(),
      post_category:  form.querySelector('[name="post_category"]').value,
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
      submitBtn.textContent = 'Publicar en el muro';
    }
  });
}

function showNotice(el, message, type = 'success') {
  if (!el) return;
  el.textContent = message;
  el.className = `notice notice--${type}`;
  el.hidden = false;
  el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

  setTimeout(() => {
    el.hidden = true;
  }, 8000);
}
