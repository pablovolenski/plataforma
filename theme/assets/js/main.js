/* Plataforma — main.js
   Login modal, filter chips, compose editor, link preview, share. Vanilla JS. */

document.addEventListener('DOMContentLoaded', () => {
  initFilters();
  initCharCounters();
  initComposerModal();
  initComposeForm();
  initLoginModal();
  initLoginPage();
  initDashboardTabs();
  initProfileForm();
  initShare();
  initPersonasFilters();
  initCalendarDropdowns();
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
      const presetId = trigger.dataset.category;
      if (presetId && categorySelect) {
        categorySelect.value = presetId;
      }

      if (typeof modal.showModal === 'function') {
        modal.showModal();
      } else {
        modal.setAttribute('open', '');
      }

      setTimeout(() => {
        const editor = modal.querySelector('#compose-editor');
        if (editor) { editor.focus(); return; }
        const titleField = modal.querySelector('[name="post_title"]');
        if (titleField) titleField.focus();
      }, 50);
    });
  });

  modal.querySelectorAll('[data-action="close-composer"]').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (typeof modal.close === 'function') modal.close();
      else modal.removeAttribute('open');
    });
  });

  modal.addEventListener('click', (e) => {
    const rect = modal.getBoundingClientRect();
    const inDialog =
      e.clientX >= rect.left && e.clientX <= rect.right &&
      e.clientY >= rect.top  && e.clientY <= rect.bottom;
    if (!inDialog && typeof modal.close === 'function') modal.close();
  });
}

// ---------------------------------------------------------------------------
// Login modal
// ---------------------------------------------------------------------------

function initLoginModal() {
  const modal  = document.getElementById('login-modal');
  const form   = document.getElementById('login-form');
  const notice = document.getElementById('login-notice');
  if (!modal) return;

  function openLoginModal() {
    if (typeof modal.showModal === 'function') modal.showModal();
    else modal.setAttribute('open', '');
    setTimeout(() => modal.querySelector('[name="log"]')?.focus(), 50);
  }

  function closeLoginModal() {
    if (typeof modal.close === 'function') modal.close();
    else modal.removeAttribute('open');
  }

  document.querySelectorAll('[data-action="open-login"]').forEach((trigger) => {
    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      openLoginModal();
    });
  });

  modal.querySelectorAll('[data-action="close-login"]').forEach((btn) => {
    btn.addEventListener('click', closeLoginModal);
  });

  modal.addEventListener('click', (e) => {
    const rect = modal.getBoundingClientRect();
    const inDialog =
      e.clientX >= rect.left && e.clientX <= rect.right &&
      e.clientY >= rect.top  && e.clientY <= rect.bottom;
    if (!inDialog) closeLoginModal();
  });

  if (!form) return;

  form.addEventListener('submit', async (e) => {
    if (typeof PlataformaData === 'undefined') return; // fall through to native form action

    e.preventDefault();

    const submitBtn = form.querySelector('button[type="submit"]');
    const original  = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Ingresando…';

    const body = new URLSearchParams({
      action:     'plataforma_ajax_login',
      _wpnonce:   PlataformaData.loginNonce,
      log:        form.querySelector('[name="log"]').value.trim(),
      pwd:        form.querySelector('[name="pwd"]').value,
      rememberme: form.querySelector('[name="rememberme"]').checked ? 'forever' : '',
    });

    try {
      const res  = await fetch(PlataformaData.ajaxUrl, { method: 'POST', body });
      const data = await res.json();

      if (data.success) {
        window.location.reload();
        return;
      }

      showNotice(notice, data.data?.message || 'Error al ingresar. Intenta de nuevo.', 'error');
    } catch {
      showNotice(notice, 'Error de red. Verifica tu conexión.', 'error');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = original;
    }
  });
}

// ---------------------------------------------------------------------------
// Compose form: editor toolbar + image upload + link preview + AJAX submit
// ---------------------------------------------------------------------------

function initComposeForm() {
  const form   = document.getElementById('article-form');
  const notice = document.getElementById('compose-notice');
  if (!form) return;

  // Show/hide event fields based on category selection
  const categorySelect = form.querySelector('[name="post_category"]');
  const eventFields    = form.querySelector('#event-fields') || document.getElementById('event-fields');
  if (categorySelect && eventFields) {
    const toggleEventFields = () => {
      const selected = categorySelect.options[categorySelect.selectedIndex];
      eventFields.hidden = selected?.dataset.slug !== 'eventos';
    };
    categorySelect.addEventListener('change', toggleEventFields);
    toggleEventFields();
  }

  // Cover image upload (featured image)
  initCoverImageUpload(form);

  const editorResult  = initEditorToolbar();
  const previewResult = initLinkPreview(editorResult?.editor);

  form.addEventListener('submit', async (e) => {
    if (typeof PlataformaData === 'undefined') return;

    e.preventDefault();

    if (editorResult) editorResult.syncContent();

    const submitBtn = form.querySelector('button[type="submit"]');
    const original  = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Publicando…';

    const bodyData = {
      action:        'plataforma_submit_post',
      _wpnonce:      PlataformaData.postNonce,
      post_title:    form.querySelector('[name="post_title"]').value.trim(),
      post_excerpt:  form.querySelector('[name="post_excerpt"]')?.value.trim() ?? '',
      post_content:  form.querySelector('[name="post_content"]').value.trim(),
      post_category: form.querySelector('[name="post_category"]').value,
    };

    // Include event fields when Eventos category is selected
    if (eventFields && !eventFields.hidden) {
      const evDate = form.querySelector('[name="event_date"]')?.value;
      const evLoc  = form.querySelector('[name="event_location"]')?.value.trim();
      if (evDate) bodyData.event_date     = evDate;
      if (evLoc)  bodyData.event_location = evLoc;
    }

    // Include cover image attachment ID if uploaded
    const coverId = form.querySelector('[name="cover_image_id"]')?.value;
    if (coverId) bodyData.cover_image_id = coverId;

    const preview = previewResult?.getPreview();
    if (preview) bodyData.link_preview = JSON.stringify(preview);

    const body = new URLSearchParams(bodyData);

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

function initCoverImageUpload(form) {
  const input    = form.querySelector('#cover-image-input');
  const idField  = form.querySelector('#cover-image-id');
  const preview  = form.querySelector('#cover-preview');
  const removeBtn = form.querySelector('#cover-remove');
  const labelText = form.querySelector('.cover-upload__label-text');
  if (!input || !idField) return;

  const showPreview = (url) => {
    if (preview) {
      preview.style.backgroundImage = `url(${url})`;
      preview.hidden = false;
    }
    if (labelText) labelText.textContent = 'Cambiar imagen';
    if (removeBtn) removeBtn.hidden = false;
  };

  const clearPreview = () => {
    idField.value = '';
    if (preview) {
      preview.style.backgroundImage = '';
      preview.hidden = true;
    }
    if (labelText) labelText.textContent = 'Añadir imagen de portada';
    if (removeBtn) removeBtn.hidden = true;
  };

  input.addEventListener('change', async () => {
    const file = input.files[0];
    if (!file || typeof PlataformaData === 'undefined') return;

    if (labelText) labelText.textContent = 'Subiendo…';

    const fd = new FormData();
    fd.append('action',   'plataforma_upload_image');
    fd.append('_wpnonce', PlataformaData.postNonce);
    fd.append('file',     file);

    try {
      const res  = await fetch(PlataformaData.ajaxUrl, { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success && data.data?.url) {
        idField.value = data.data.id || '';
        showPreview(data.data.url);
      } else {
        if (labelText) labelText.textContent = 'Error — reintentar';
      }
    } catch {
      if (labelText) labelText.textContent = 'Error de red';
    } finally {
      input.value = '';
    }
  });

  if (removeBtn) {
    removeBtn.addEventListener('click', clearPreview);
  }
}

function initEditorToolbar() {
  const toolbar = document.getElementById('editor-toolbar');
  const editor  = document.getElementById('compose-editor');
  const hidden  = document.getElementById('compose-content-hidden');
  if (!toolbar || !editor || !hidden) return null;

  function syncContent() {
    const raw = editor.innerHTML;
    hidden.value = (raw === '<br>' || raw === '') ? '' : raw;
  }

  editor.addEventListener('input', syncContent);

  // Formatting buttons — mousedown prevents the editor losing focus
  toolbar.querySelectorAll('.editor-btn[data-cmd]').forEach((btn) => {
    btn.addEventListener('mousedown', (e) => {
      e.preventDefault();
      const cmd = btn.dataset.cmd;
      if (cmd === 'createLink') {
        const url = window.prompt('URL del enlace (ej: https://ejemplo.com):');
        if (url && url.trim()) document.execCommand('createLink', false, url.trim());
      } else {
        document.execCommand(cmd, false, null);
      }
      editor.focus();
      syncContent();
    });
  });

  // Image upload
  const imageInput = document.getElementById('compose-image-input');
  const fileLabel  = toolbar.querySelector('.editor-btn--file');

  if (imageInput && fileLabel) {
    imageInput.addEventListener('change', async () => {
      const file = imageInput.files[0];
      if (!file || typeof PlataformaData === 'undefined') return;

      fileLabel.classList.add('editor-btn--uploading');

      const formData = new FormData();
      formData.append('action',   'plataforma_upload_image');
      formData.append('_wpnonce', PlataformaData.postNonce);
      formData.append('file',     file);

      try {
        const res  = await fetch(PlataformaData.ajaxUrl, { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
          editor.focus();
          document.execCommand('insertHTML', false,
            `<img src="${data.data.url}" alt="" loading="lazy">`);
          syncContent();
        } else {
          alert(data.data?.message || 'Error al subir la imagen.');
        }
      } catch {
        alert('Error de red al subir la imagen.');
      } finally {
        fileLabel.classList.remove('editor-btn--uploading');
        imageInput.value = '';
      }
    });
  }

  return { editor, syncContent };
}

function initLinkPreview(editor) {
  const container    = document.getElementById('link-preview-container');
  const previewInput = document.getElementById('compose-link-preview-data');
  if (!container || !editor) return null;

  let storedPreview  = null;
  let debounceTimer  = null;
  let lastFetchedUrl = '';

  editor.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(detectAndFetch, 1000);
  });

  function detectAndFetch() {
    const text  = editor.innerText || '';
    const match = text.match(/https?:\/\/[^\s<>"]{8,}/i);
    if (match) {
      if (match[0] !== lastFetchedUrl) fetchPreview(match[0]);
    } else {
      clearPreview();
    }
  }

  async function fetchPreview(url) {
    if (typeof PlataformaData === 'undefined') return;
    lastFetchedUrl = url;

    const body = new URLSearchParams({
      action:   'plataforma_fetch_link_preview',
      _wpnonce: PlataformaData.postNonce,
      url,
    });

    try {
      const res  = await fetch(PlataformaData.ajaxUrl, { method: 'POST', body });
      const data = await res.json();

      if (data.success && data.data.title) {
        storedPreview = data.data;
        renderPreview(data.data);
        if (previewInput) previewInput.value = JSON.stringify(data.data);
      } else {
        clearPreview();
      }
    } catch {
      clearPreview();
    }
  }

  function renderPreview(p) {
    let domain = '';
    try { domain = new URL(p.url).hostname; } catch { /* ignore */ }

    container.hidden = false;
    container.innerHTML =
      `<div class="link-preview-card">` +
        (p.image ? `<img class="link-preview-card__img" src="${escHtml(p.image)}" alt="" loading="lazy">` : '') +
        `<div class="link-preview-card__body">` +
          `<div class="link-preview-card__title">${escHtml(p.title)}</div>` +
          (p.description ? `<div class="link-preview-card__desc">${escHtml(p.description)}</div>` : '') +
          (domain ? `<div class="link-preview-card__domain">${escHtml(domain)}</div>` : '') +
        `</div>` +
      `</div>`;
  }

  function clearPreview() {
    storedPreview  = null;
    lastFetchedUrl = '';
    container.hidden    = true;
    container.innerHTML = '';
    if (previewInput) previewInput.value = '';
  }

  return { getPreview: () => storedPreview };
}

// ---------------------------------------------------------------------------
// Login page (/ingresar/) — AJAX submission with redirect
// ---------------------------------------------------------------------------

function initLoginPage() {
  const form   = document.getElementById('login-form-page');
  const notice = document.getElementById('login-notice');
  if (!form || typeof PlataformaData === 'undefined') return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const btn      = form.querySelector('button[type="submit"]');
    const original = btn.textContent;
    btn.disabled    = true;
    btn.textContent = 'Ingresando…';

    const redirectTo = form.querySelector('[name="redirect_to"]')?.value
      || PlataformaData.tableroUrl;

    const body = new URLSearchParams({
      action:      'plataforma_ajax_login',
      _wpnonce:    PlataformaData.loginNonce,
      log:         form.querySelector('[name="log"]').value.trim(),
      pwd:         form.querySelector('[name="pwd"]').value,
      rememberme:  form.querySelector('[name="rememberme"]')?.checked ? 'forever' : '',
      redirect_to: redirectTo,
    });

    try {
      const res  = await fetch(PlataformaData.ajaxUrl, { method: 'POST', body });
      const data = await res.json();

      if (data.success) {
        window.location.href = data.data?.redirect || PlataformaData.tableroUrl;
        return;
      }
      showNotice(notice, data.data?.message || 'Error al ingresar. Intenta de nuevo.', 'error');
    } catch {
      showNotice(notice, 'Error de red. Verifica tu conexión.', 'error');
    } finally {
      btn.disabled    = false;
      btn.textContent = original;
    }
  });
}

// ---------------------------------------------------------------------------
// Dashboard tabs (/tablero/) — URL-hash based tab switching
// ---------------------------------------------------------------------------

function initDashboardTabs() {
  const tabsRoot = document.querySelector('.tablero__tabs');
  if (!tabsRoot) return;

  const tabs   = Array.from(tabsRoot.querySelectorAll('.tablero__tab'));
  const panels = Array.from(document.querySelectorAll('.tablero__panel'));

  function activateTab(name) {
    tabs.forEach((tab) => {
      const active = tab.dataset.tab === name;
      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', String(active));
    });
    panels.forEach((panel) => {
      panel.hidden = panel.id !== `tab-${name}`;
    });
    history.replaceState(null, '', `#${name}`);
  }

  const hash    = window.location.hash.replace('#', '');
  const initial = tabs.find((t) => t.dataset.tab === hash)
    ? hash
    : (tabs[0]?.dataset.tab || 'mensajes');
  activateTab(initial);

  tabs.forEach((tab) => {
    tab.addEventListener('click', () => activateTab(tab.dataset.tab));
  });
}

// ---------------------------------------------------------------------------
// Profile form (/tablero/#perfil) — update info & change password via AJAX
// ---------------------------------------------------------------------------

function initProfileForm() {
  const profileForm    = document.getElementById('profile-form');
  const passwordForm   = document.getElementById('password-form');
  const profileNotice  = document.getElementById('profile-notice');
  const passwordNotice = document.getElementById('password-notice');

  if (!profileForm || typeof PlataformaData === 'undefined') return;

  // Avatar upload
  const fileInput = document.getElementById('avatar-file');
  if (fileInput) {
    fileInput.addEventListener('change', async () => {
      const file = fileInput.files[0];
      if (!file) return;
      const status = document.getElementById('avatar-status');
      if (status) status.textContent = 'Subiendo…';
      const fd = new FormData();
      fd.append('action',   'plataforma_upload_avatar');
      fd.append('_wpnonce', PlataformaData.profileNonce);
      fd.append('avatar',   file);
      try {
        const res  = await fetch(PlataformaData.ajaxUrl, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          document.getElementById('avatar-preview').src = data.data.url;
          if (status) status.textContent = 'Foto actualizada';
        } else {
          if (status) status.textContent = data.data?.message || 'Error al subir la foto.';
        }
      } catch {
        if (status) status.textContent = 'Error de red.';
      } finally {
        fileInput.value = '';
      }
    });
  }

  profileForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const btn = profileForm.querySelector('button[type="submit"]');
    const orig = btn.textContent;
    btn.disabled    = true;
    btn.textContent = 'Guardando…';

    const body = new URLSearchParams({
      action:       'plataforma_update_profile',
      _wpnonce:     profileForm.querySelector('[name="_wpnonce"]').value,
      display_name: profileForm.querySelector('[name="display_name"]').value.trim(),
      user_email:   profileForm.querySelector('[name="user_email"]').value.trim(),
      description:  profileForm.querySelector('[name="description"]').value.trim(),
    });

    try {
      const res  = await fetch(PlataformaData.ajaxUrl, { method: 'POST', body });
      const data = await res.json();
      showNotice(profileNotice,
        data.data?.message || (data.success ? 'Guardado.' : 'Error al guardar.'),
        data.success ? 'success' : 'error');
    } catch {
      showNotice(profileNotice, 'Error de red.', 'error');
    } finally {
      btn.disabled    = false;
      btn.textContent = orig;
    }
  });

  if (!passwordForm) return;

  passwordForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const btn = passwordForm.querySelector('button[type="submit"]');
    const orig = btn.textContent;
    btn.disabled    = true;
    btn.textContent = 'Actualizando…';

    const body = new URLSearchParams({
      action:           'plataforma_change_password',
      _wpnonce:         passwordForm.querySelector('[name="_wpnonce"]').value,
      current_password: passwordForm.querySelector('[name="current_password"]').value,
      new_password:     passwordForm.querySelector('[name="new_password"]').value,
      confirm_password: passwordForm.querySelector('[name="confirm_password"]').value,
    });

    try {
      const res  = await fetch(PlataformaData.ajaxUrl, { method: 'POST', body });
      const data = await res.json();
      showNotice(passwordNotice,
        data.data?.message || (data.success ? 'Contraseña actualizada.' : 'Error.'),
        data.success ? 'success' : 'error');
      if (data.success) passwordForm.reset();
    } catch {
      showNotice(passwordNotice, 'Error de red.', 'error');
    } finally {
      btn.disabled    = false;
      btn.textContent = orig;
    }
  });
}

// ---------------------------------------------------------------------------
// Event calendar dropdowns
// ---------------------------------------------------------------------------

function initCalendarDropdowns() {
  document.querySelectorAll('.cal-dropdown__toggle').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const menu     = btn.nextElementSibling;
      const expanded = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', String(!expanded));
      if (menu) menu.hidden = expanded;
    });
  });

  document.addEventListener('click', () => {
    document.querySelectorAll('.cal-dropdown__toggle').forEach((btn) => {
      const menu = btn.nextElementSibling;
      btn.setAttribute('aria-expanded', 'false');
      if (menu) menu.hidden = true;
    });
  });
}

// ---------------------------------------------------------------------------
// /personas/ — user grid with category filter chips
// ---------------------------------------------------------------------------

function initPersonasFilters() {
  const chips = document.querySelectorAll('.personas-filters .filter-chip');
  const cards = document.querySelectorAll('.persona-card');
  if (!chips.length) return;

  chips.forEach((chip) => {
    chip.addEventListener('click', () => {
      chips.forEach((c) => c.classList.remove('is-active'));
      chip.classList.add('is-active');
      const group = chip.dataset.group;
      cards.forEach((card) => {
        const groups = card.dataset.groups ? card.dataset.groups.split(' ') : [];
        card.hidden = !!(group && !groups.includes(group));
      });
    });
  });
}

// ---------------------------------------------------------------------------
// Shared helpers
// ---------------------------------------------------------------------------

function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function showNotice(el, message, type = 'success') {
  if (!el) return;
  el.textContent = message;
  el.className   = `notice notice--${type}`;
  el.hidden      = false;
  el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  setTimeout(() => { el.hidden = true; }, 8000);
}

// ---------------------------------------------------------------------------
// Share: Web Share API on mobile, dropdown on desktop, clipboard for copy
// ---------------------------------------------------------------------------

function initShare() {
  document.querySelectorAll('.share-btn--toggle').forEach((btn) => {
    btn.addEventListener('click', async (e) => {
      e.stopPropagation();

      const dropdown = btn.closest('.share-dropdown');
      if (!dropdown) return;

      const url   = dropdown.dataset.shareUrl;
      const title = dropdown.dataset.shareTitle;

      if (navigator.share && /Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
        try {
          await navigator.share({ title, url });
          return;
        } catch { /* cancelled or unsupported */ }
      }

      const menu     = dropdown.querySelector('.share-dropdown__menu');
      const expanded = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', String(!expanded));
      if (menu) menu.hidden = expanded;
    });
  });

  document.addEventListener('click', (e) => {
    document.querySelectorAll('.share-dropdown').forEach((dropdown) => {
      if (!dropdown.contains(e.target)) {
        const btn  = dropdown.querySelector('.share-btn--toggle');
        const menu = dropdown.querySelector('.share-dropdown__menu');
        if (btn)  btn.setAttribute('aria-expanded', 'false');
        if (menu) menu.hidden = true;
      }
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('.share-dropdown').forEach((dropdown) => {
      const btn  = dropdown.querySelector('.share-btn--toggle');
      const menu = dropdown.querySelector('.share-dropdown__menu');
      if (btn)  btn.setAttribute('aria-expanded', 'false');
      if (menu) menu.hidden = true;
    });
  });

  document.querySelectorAll('.share-dropdown__item[href], .share-pill[href]').forEach((link) => {
    if (link.href.startsWith('mailto:')) return;
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const w = 600, h = 540;
      const left = (window.screen.width  - w) / 2;
      const top  = (window.screen.height - h) / 2;
      window.open(link.href, 'plataforma_share',
        `width=${w},height=${h},left=${left},top=${top},noopener,noreferrer`);
    });
  });

  document.querySelectorAll('[data-share-copy]').forEach((btn) => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      e.stopPropagation();

      const container = btn.closest('[data-share-url]');
      const url = container?.dataset.shareUrl || window.location.href;

      const confirm = () => {
        const label    = btn.querySelector('span');
        const original = label?.textContent;
        btn.classList.add('is-copied');
        if (label) label.textContent = '¡Copiado!';
        setTimeout(() => {
          btn.classList.remove('is-copied');
          if (label && original) label.textContent = original;
        }, 1800);
      };

      try {
        await navigator.clipboard.writeText(url);
        confirm();
      } catch {
        const ta = document.createElement('textarea');
        ta.value = url;
        ta.style.cssText = 'position:fixed;opacity:0;';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch { /* ignore */ }
        document.body.removeChild(ta);
        confirm();
      }
    });
  });
}
