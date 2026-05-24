/* Plataforma — main.js
   Login modal, filter chips, compose editor, link preview, share. Vanilla JS. */

document.addEventListener('DOMContentLoaded', () => {
  initFilters();
  initCharCounters();
  initComposerPanel();
  initComposeForm();
  initMentions();
  initLoginModal();
  initLoginPage();
  initDashboardTabs();
  initNotificaciones();
  initAgendaCalendar();
  initPushNotifications();
  initProfileForm();
  initContactForm();
  initShare();
  initPersonasFilters();
  initAtcbButtons();
  initMisPublicaciones();
  initHamburger();
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
// Composer panel: inline expanding form (no overlay/backdrop)
// ---------------------------------------------------------------------------

function initComposerPanel() {
  const panel = document.getElementById('composer-panel');
  if (!panel) return;

  const titleEl = document.getElementById('composer-panel-title');

  function openPanel(presetCatId) {
    const catPicker   = panel.querySelector('.cat-picker');
    const eventFields = panel.querySelector('#event-fields') || document.getElementById('event-fields');

    if (presetCatId) {
      // Event mode: auto-check Eventos, hide category chips, reveal event fields
      panel.querySelectorAll('[name="post_categories[]"]').forEach((cb) => {
        cb.checked = (cb.value === String(presetCatId));
      });
      if (catPicker)   catPicker.hidden = true;
      if (eventFields) eventFields.hidden = false;
      if (titleEl) titleEl.textContent = 'Nuevo evento';
    } else {
      // Normal mode
      if (catPicker)   catPicker.hidden = false;
      if (titleEl) titleEl.textContent = 'Nueva publicación';
    }

    panel.classList.add('is-open');

    // Update aria-expanded on all trigger buttons
    document.querySelectorAll('[data-action="open-composer"]').forEach((t) => {
      t.setAttribute('aria-expanded', 'true');
    });

    // Focus the editor after the CSS transition completes
    setTimeout(() => {
      const editor = panel.querySelector('#compose-editor');
      if (editor) { editor.focus(); return; }
      const titleField = panel.querySelector('[name="post_title"]');
      if (titleField) titleField.focus();
    }, 290);

    // Scroll the panel into view smoothly
    setTimeout(() => panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 50);
  }

  function closePanel() {
    panel.classList.remove('is-open');
    document.querySelectorAll('[data-action="open-composer"]').forEach((t) => {
      t.setAttribute('aria-expanded', 'false');
    });
    // Reset to normal mode so reopening via Reflexión rápida is clean
    const catPicker   = panel.querySelector('.cat-picker');
    const eventFields = panel.querySelector('#event-fields') || document.getElementById('event-fields');
    panel.querySelectorAll('[name="post_categories[]"]').forEach((cb) => { cb.checked = false; });
    if (catPicker)   catPicker.hidden = false;
    if (eventFields) eventFields.hidden = true;
  }

  document.querySelectorAll('[data-action="open-composer"]').forEach((trigger) => {
    trigger.addEventListener('click', () => openPanel(trigger.dataset.category || ''));
  });

  panel.querySelectorAll('[data-action="close-composer"]').forEach((btn) => {
    btn.addEventListener('click', closePanel);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && panel.classList.contains('is-open')) closePanel();
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

  // Show/hide event fields based on category checkboxes
  const catCheckboxes = form.querySelectorAll('[name="post_categories[]"]');
  const eventFields   = form.querySelector('#event-fields') || document.getElementById('event-fields');
  if (catCheckboxes.length && eventFields) {
    const toggleEventFields = () => {
      const hasEventos = [...catCheckboxes].some((cb) => cb.checked && cb.dataset.slug === 'eventos');
      eventFields.hidden = !hasEventos;
    };
    catCheckboxes.forEach((cb) => cb.addEventListener('change', toggleEventFields));
    toggleEventFields();
  }

  // Location autocomplete
  initLocationAutocomplete();

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

    const checkedCats = [...form.querySelectorAll('[name="post_categories[]"]:checked')].map((cb) => cb.value);

    const body = new URLSearchParams();
    body.append('action',       'plataforma_submit_post');
    body.append('_wpnonce',     PlataformaData.postNonce);
    body.append('post_title',   form.querySelector('[name="post_title"]').value.trim());
    body.append('post_excerpt', form.querySelector('[name="post_excerpt"]')?.value.trim() ?? '');
    body.append('post_content', form.querySelector('[name="post_content"]').value.trim());
    checkedCats.forEach((id) => body.append('post_categories[]', id));

    // @mention: send mentioned user IDs
    const editorEl = form.querySelector('#compose-editor');
    if (editorEl?._mentionedUsers) {
      editorEl._mentionedUsers.forEach((uid) => body.append('mentioned_users[]', uid));
    }

    // Include event fields when Eventos category is selected
    if (eventFields && !eventFields.hidden) {
      const evDateVal = form.querySelector('[name="event_date_date"]')?.value;
      const evTimeVal = form.querySelector('[name="event_date_time"]')?.value;
      const evLoc     = form.querySelector('[name="event_location"]')?.value.trim();
      if (evDateVal) body.append('event_date_date', evDateVal);
      if (evTimeVal) body.append('event_date_time', evTimeVal);
      if (evLoc)     body.append('event_location',  evLoc);
    }

    // Include cover image attachment ID if uploaded
    const coverId = form.querySelector('[name="cover_image_id"]')?.value;
    if (coverId) body.append('cover_image_id', coverId);

    const preview = previewResult?.getPreview();
    if (preview) body.append('link_preview', JSON.stringify(preview));

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
  const root        = form.querySelector('#cover-upload') || form.querySelector('.cover-upload');
  const input       = form.querySelector('#cover-image-input');
  const idField     = form.querySelector('#cover-image-id');
  const preview     = form.querySelector('#cover-preview');
  const placeholder = form.querySelector('#cover-placeholder');
  const removeBtn   = form.querySelector('#cover-remove');
  const labelText   = form.querySelector('.cover-upload__label-text');
  if (!input || !idField) return;

  const showPreview = (url) => {
    if (preview) {
      preview.style.backgroundImage = `url('${url}')`;
      preview.hidden = false;
    }
    if (placeholder) placeholder.hidden = true;
    if (root) root.classList.add('cover-upload--has-image');
    if (labelText) labelText.textContent = 'Cambiar imagen';
    if (removeBtn) removeBtn.hidden = false;
  };

  const clearPreview = () => {
    idField.value = '';
    if (preview) {
      preview.style.backgroundImage = '';
      preview.hidden = true;
    }
    if (placeholder) placeholder.hidden = false;
    if (root) root.classList.remove('cover-upload--has-image');
    if (labelText) labelText.textContent = 'Elegir imagen';
    if (removeBtn) removeBtn.hidden = true;
  };

  async function uploadFile(file) {
    if (!file || typeof PlataformaData === 'undefined') return;
    if (!file.type.startsWith('image/')) {
      if (labelText) labelText.textContent = 'Solo imágenes';
      return;
    }
    if (labelText) labelText.textContent = 'Subiendo…';
    if (root) root.classList.add('cover-upload--uploading');

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
      if (root) root.classList.remove('cover-upload--uploading');
    }
  }

  input.addEventListener('change', () => uploadFile(input.files[0]));
  if (removeBtn) removeBtn.addEventListener('click', clearPreview);

  // Drag & drop on the entire upload area
  if (root) {
    ['dragenter', 'dragover'].forEach((evt) => {
      root.addEventListener(evt, (e) => {
        e.preventDefault();
        root.classList.add('cover-upload--dragover');
      });
    });
    ['dragleave', 'drop'].forEach((evt) => {
      root.addEventListener(evt, (e) => {
        e.preventDefault();
        if (evt === 'dragleave' && root.contains(e.relatedTarget)) return;
        root.classList.remove('cover-upload--dragover');
      });
    });
    root.addEventListener('drop', (e) => {
      const file = e.dataTransfer?.files?.[0];
      if (file) uploadFile(file);
    });
    // Click anywhere on the placeholder area triggers the file picker
    if (placeholder) {
      placeholder.addEventListener('click', () => input.click());
    }
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
// Add-to-Calendar (atcb) — uses add-to-calendar-button@2 from CDN
// ---------------------------------------------------------------------------

function initAtcbButtons() {
  document.querySelectorAll('.atcb-trigger[data-atcb]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      if (typeof window.atcb_action !== 'function') {
        console.warn('add-to-calendar-button library not loaded yet');
        return;
      }
      let config;
      try {
        config = JSON.parse(btn.dataset.atcb);
      } catch (err) {
        console.error('Invalid atcb config', err);
        return;
      }
      window.atcb_action(config, btn);
    });
  });
}

// ---------------------------------------------------------------------------
// Mis publicaciones — delete own posts via AJAX
// ---------------------------------------------------------------------------

function initMisPublicaciones() {
  const list   = document.querySelector('.mis-pubs__list');
  const notice = document.getElementById('mis-pubs-notice');
  if (!list) return;

  list.addEventListener('click', async (e) => {
    const btn = e.target.closest('.mis-pubs__delete');
    if (!btn) return;
    if (typeof PlataformaData === 'undefined') return;

    const postId = btn.dataset.postId;
    if (!postId) return;
    if (!confirm('¿Eliminar esta publicación? Esta acción no se puede deshacer.')) return;

    btn.disabled = true;
    const item = btn.closest('.mis-pubs__item');
    const body = new URLSearchParams({
      action:   'plataforma_delete_post',
      _wpnonce: PlataformaData.postNonce,
      post_id:  postId,
    });
    try {
      const res  = await fetch(PlataformaData.ajaxUrl, { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        if (item) item.remove();
        showNotice(notice, 'Publicación eliminada.', 'success');
        if (!list.querySelector('.mis-pubs__item')) list.remove();
      } else {
        showNotice(notice, data.data?.message || 'Error al eliminar.', 'error');
        btn.disabled = false;
      }
    } catch {
      showNotice(notice, 'Error de red.', 'error');
      btn.disabled = false;
    }
  });
}

// ---------------------------------------------------------------------------
// Location autocomplete (Google Places or Nominatim/OSM fallback)
// ---------------------------------------------------------------------------

// Called by Google Maps SDK as the async callback once the library loads.
// When Maps is ready we upgrade from Nominatim to Google Places if needed.
window.plataformaMapsReady = function () {
  const input = document.getElementById('event-location');
  if (!input) return;
  // If already upgraded to Google Places, do nothing
  if (input.dataset.acMode === 'google') return;
  // Mark for upgrade — reset the init flag so initLocationAutocomplete can run
  delete input.dataset.acInit;
  initLocationAutocomplete();
};

function initLocationAutocomplete() {
  const input       = document.getElementById('event-location');
  const suggestions = document.getElementById('event-location-suggestions');
  if (!input || !suggestions) return;
  if (input.dataset.acInit) return; // already initialized
  input.dataset.acInit = '1';

  // Google Places: available when Maps SDK has loaded with a valid key
  if (window.google?.maps?.places) {
    input.dataset.acMode = 'google';
    const autocomplete = new google.maps.places.Autocomplete(input, {
      fields: ['formatted_address', 'name', 'geometry'],
      types:  ['establishment', 'geocode'],
    });
    autocomplete.addListener('place_changed', () => {
      const place = autocomplete.getPlace();
      if (place.formatted_address) input.value = place.formatted_address;
    });
    // Google Places manages its own dropdown — hide ours
    suggestions.hidden = true;
    return;
  }

  // Open-source geocoding: Photon (Komoot) primary, Nominatim fallback
  let debounceTimer;
  let activeIndex = -1;
  let currentResults = [];

  function buildPhotonLabel(p) {
    const street = p.street && p.housenumber ? `${p.street} ${p.housenumber}` : (p.street || null);
    return [p.name, street, p.city, p.country].filter(Boolean).join(', ');
  }

  async function fetchGeoSuggestions(q) {
    // 1. Photon (Komoot) — no key, European-optimised
    try {
      const res  = await fetch(
        `https://photon.komoot.io/api/?q=${encodeURIComponent(q)}&limit=6&lang=de`,
        { signal: AbortSignal.timeout(4000) }
      );
      const data = await res.json();
      const items = (data.features || []).map((f) => ({ display_name: buildPhotonLabel(f.properties) })).filter((r) => r.display_name);
      if (items.length) return items;
    } catch { /* fall through */ }
    // 2. Nominatim (OpenStreetMap) — silent fallback
    try {
      const res  = await fetch(
        `https://nominatim.openstreetmap.org/search?format=json&limit=5&q=${encodeURIComponent(q)}`,
        { headers: { 'Accept-Language': 'es,de,en' } }
      );
      return await res.json();
    } catch { return []; }
  }

  function renderSuggestions(results) {
    currentResults = results;
    suggestions.innerHTML = '';
    if (!results.length) { suggestions.hidden = true; return; }
    results.forEach((r, i) => {
      const li = document.createElement('li');
      li.textContent = r.display_name;
      li.setAttribute('role', 'option');
      li.dataset.index = i;
      li.addEventListener('mousedown', (e) => {
        e.preventDefault();
        input.value = r.display_name;
        suggestions.hidden = true;
        input.setAttribute('aria-expanded', 'false');
      });
      suggestions.appendChild(li);
    });
    suggestions.hidden = false;
    input.setAttribute('aria-expanded', 'true');
    activeIndex = -1;
  }

  input.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    const q = input.value.trim();
    if (q.length < 3) { suggestions.hidden = true; return; }
    debounceTimer = setTimeout(async () => {
      renderSuggestions(await fetchGeoSuggestions(q));
    }, 350);
  });

  input.addEventListener('keydown', (e) => {
    const items = suggestions.querySelectorAll('li');
    if (!items.length || suggestions.hidden) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      activeIndex = Math.min(activeIndex + 1, items.length - 1);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      activeIndex = Math.max(activeIndex - 1, -1);
    } else if (e.key === 'Enter' && activeIndex >= 0) {
      e.preventDefault();
      input.value = currentResults[activeIndex].display_name;
      suggestions.hidden = true;
      input.setAttribute('aria-expanded', 'false');
      return;
    } else if (e.key === 'Escape') {
      suggestions.hidden = true;
      input.setAttribute('aria-expanded', 'false');
      return;
    }
    items.forEach((li, i) => li.setAttribute('aria-selected', String(i === activeIndex)));
  });

  document.addEventListener('click', (e) => {
    if (!input.contains(e.target) && !suggestions.contains(e.target)) {
      suggestions.hidden = true;
      input.setAttribute('aria-expanded', 'false');
    }
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
// @Mention autocomplete in the compose editor
// ---------------------------------------------------------------------------

function initMentions() {
  const editor = document.getElementById('compose-editor');
  if (!editor) return;

  const mentionedUsers = new Set();
  editor._mentionedUsers = mentionedUsers;

  let mentionList   = null;
  let activeQuery   = '';
  let debounceTimer = null;
  let mentionResults = [];
  let activeIndex   = -1;

  function getMentionMatch() {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return null;
    const range = sel.getRangeAt(0);
    if (!editor.contains(range.startContainer)) return null;
    const node = range.startContainer;
    if (node.nodeType !== Node.TEXT_NODE) return null;
    const text = node.textContent.slice(0, range.startOffset);
    const match = text.match(/@(\w{0,30})$/);
    return match ? { query: match[1], node, offset: range.startOffset } : null;
  }

  function getMentionRange(match) {
    const mentionRange = document.createRange();
    mentionRange.setStart(match.node, match.offset - match.query.length - 1); // include @
    mentionRange.setEnd(match.node, match.offset);
    return mentionRange;
  }

  function showList(results) {
    mentionResults = results;
    activeIndex   = 0;

    if (!mentionList) {
      mentionList = document.createElement('ul');
      mentionList.className = 'mention-list';
      mentionList.setAttribute('role', 'listbox');
      document.body.appendChild(mentionList);
    }

    mentionList.innerHTML = '';

    if (!results.length) { hideList(); return; }

    results.forEach((user, i) => {
      const li = document.createElement('li');
      li.setAttribute('role', 'option');
      li.innerHTML =
        `<img src="${escHtml(user.avatar)}" alt="" width="28" height="28" class="mention-list__avatar">` +
        `<span class="mention-list__name">${escHtml(user.name)}</span>`;
      li.addEventListener('mousedown', (e) => { e.preventDefault(); selectMention(user); });
      mentionList.appendChild(li);
    });

    const match = getMentionMatch();
    if (match) {
      const rect = getMentionRange(match).getBoundingClientRect();
      mentionList.style.top  = `${rect.bottom + window.scrollY + 4}px`;
      mentionList.style.left = `${Math.min(rect.left + window.scrollX, window.innerWidth - 220)}px`;
    }
    mentionList.hidden = false;
    highlightActive();
  }

  function highlightActive() {
    if (!mentionList) return;
    mentionList.querySelectorAll('li').forEach((li, i) => {
      li.classList.toggle('is-active', i === activeIndex);
    });
  }

  function hideList() {
    if (mentionList) mentionList.hidden = true;
    activeQuery   = '';
    mentionResults = [];
    activeIndex   = -1;
  }

  function selectMention(user) {
    const match = getMentionMatch();
    if (!match) { hideList(); return; }

    const range = getMentionRange(match);
    range.deleteContents();

    const span = document.createElement('span');
    span.className = 'mention';
    span.dataset.uid = user.id;
    span.contentEditable = 'false';
    span.textContent = `@${user.name}`;
    range.insertNode(span);

    const space = document.createTextNode(' ');
    span.after(space);
    const sel = window.getSelection();
    const newRange = document.createRange();
    newRange.setStartAfter(space);
    newRange.collapse(true);
    sel.removeAllRanges();
    sel.addRange(newRange);

    mentionedUsers.add(Number(user.id));
    hideList();

    const hidden = document.getElementById('compose-content-hidden');
    if (hidden) {
      const raw = editor.innerHTML;
      hidden.value = (raw === '<br>' || raw === '') ? '' : raw;
    }
  }

  editor.addEventListener('input', () => {
    const match = getMentionMatch();
    if (!match) { hideList(); return; }
    const { query } = match;
    if (query.length < 2) {
      if (query.length === 0) hideList();
      return;
    }
    if (query === activeQuery) return;
    activeQuery = query;
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(async () => {
      if (typeof PlataformaData === 'undefined') return;
      try {
        const url = `${PlataformaData.ajaxUrl}?action=plataforma_search_users&_wpnonce=${PlataformaData.postNonce}&q=${encodeURIComponent(query)}`;
        const res  = await fetch(url);
        const data = await res.json();
        if (data.success) showList(data.data);
      } catch { /* ignore */ }
    }, 300);
  });

  editor.addEventListener('keydown', (e) => {
    if (!mentionList || mentionList.hidden) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      activeIndex = Math.min(activeIndex + 1, mentionResults.length - 1);
      highlightActive();
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      activeIndex = Math.max(activeIndex - 1, 0);
      highlightActive();
    } else if ((e.key === 'Enter' || e.key === 'Tab') && mentionResults[activeIndex]) {
      e.preventDefault();
      selectMention(mentionResults[activeIndex]);
    } else if (e.key === 'Escape') {
      hideList();
    }
  });

  document.addEventListener('click', (e) => {
    if (mentionList && !mentionList.contains(e.target) && e.target !== editor) {
      hideList();
    }
  });
}

// ---------------------------------------------------------------------------
// Notifications — load, mark read, badge polling
// ---------------------------------------------------------------------------

function initNotificaciones() {
  if (typeof PlataformaData === 'undefined' || !PlataformaData.isLoggedIn) return;
  if (!document.querySelector('.tablero__tabs')) return; // only on /tablero/

  const badge      = document.getElementById('notif-badge');
  const pushBtn    = document.getElementById('enable-push-btn');
  const pushStatus = document.getElementById('push-status');

  // Determine push support once, update button visibility
  const pushSupported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
  if (pushBtn) {
    if (!pushSupported) {
      pushBtn.hidden = true; // hide on unsupported browsers instead of showing error on load
    } else if (!PlataformaData.vapidPublicKey) {
      pushBtn.hidden = true; // keys not generated yet — admin hasn't visited wp-admin
    } else {
      // Check if already subscribed
      navigator.serviceWorker.ready.then((reg) =>
        reg.pushManager.getSubscription()
      ).then((sub) => {
        if (sub) {
          pushBtn.disabled = true;
          pushBtn.textContent = 'Notificaciones activas';
        }
      }).catch(() => {});
    }
  }

  async function fetchAndUpdate() {
    try {
      const url = `${PlataformaData.ajaxUrl}?action=plataforma_fetch_notifications&_wpnonce=${PlataformaData.notifNonce}`;
      const res  = await fetch(url);
      const data = await res.json();
      if (data.success && badge) {
        const count = data.data.unread || 0;
        badge.textContent = count > 9 ? '9+' : String(count);
        badge.hidden = count === 0;
        // Live-update notification list if panel is visible
        renderNotifList(data.data.notifications || []);
      }
    } catch { /* ignore */ }
  }

  function renderNotifList(notifications) {
    const list = document.getElementById('notif-list');
    const empty = document.querySelector('.notif-panel__empty');
    if (!list && !empty) return;

    if (!notifications.length) {
      if (list) list.remove();
      if (!empty) {
        const p = document.createElement('p');
        p.className = 'notif-panel__empty';
        p.textContent = 'No tienes notificaciones aún.';
        document.querySelector('.notif-panel')?.appendChild(p);
      }
      return;
    }

    const container = list || (() => {
      const el = document.createElement('ul');
      el.className = 'notif-list';
      el.id = 'notif-list';
      document.querySelector('.notif-panel__empty')?.replaceWith(el);
      return el;
    })();

    container.innerHTML = notifications.map((n) => {
      const unread = !n.read;
      const time   = n.created_at ? relativeTime(n.created_at) : '';
      let message  = '';
      let link     = '';

      if (n.type === 'mention') {
        message = escHtml(n.author_name || 'Alguien') + ' te mencionó' + (n.post_title ? ' en: ' + escHtml(n.post_title) : '');
        link = n.post_id ? '#' : ''; // actual URL resolved server-side
      } else if (n.type === 'comment') {
        message = escHtml(n.author_name || 'Alguien') + ' comentó en: ' + escHtml(n.post_title || '');
        link = n.post_id ? '#comments' : '';
      } else {
        message = escHtml(n.message || 'Nueva notificación');
      }

      const inner = link
        ? `<a class="notif-card__message" href="${escHtml(link)}">${message}</a>`
        : `<span class="notif-card__message">${message}</span>`;

      return `<li class="notif-card${unread ? ' notif-card--unread' : ''}">
        <span class="notif-card__dot" aria-hidden="true"></span>
        <div class="notif-card__body">
          ${inner}
          ${time ? `<time class="notif-card__time">${time}</time>` : ''}
        </div>
      </li>`;
    }).join('');
  }

  function relativeTime(mysqlDate) {
    const diff = Math.floor((Date.now() - new Date(mysqlDate.replace(' ', 'T')).getTime()) / 1000);
    if (diff < 60)   return 'hace un momento';
    if (diff < 3600) return `hace ${Math.floor(diff / 60)} min`;
    if (diff < 86400)return `hace ${Math.floor(diff / 3600)} h`;
    return `hace ${Math.floor(diff / 86400)} d`;
  }

  // Mark notifications read when user opens the notificaciones tab
  document.querySelectorAll('.tablero__tab[data-tab="notificaciones"]').forEach((tab) => {
    tab.addEventListener('click', async () => {
      if (badge) { badge.textContent = ''; badge.hidden = true; }
      try {
        const body = new URLSearchParams({
          action:   'plataforma_mark_notifications_read',
          _wpnonce: PlataformaData.notifNonce,
        });
        await fetch(PlataformaData.ajaxUrl, { method: 'POST', body });
        // Clear unread styling on visible cards
        document.querySelectorAll('.notif-card--unread').forEach((c) => {
          c.classList.remove('notif-card--unread');
        });
      } catch { /* ignore */ }
    });
  });

  // Push notifications button
  if (pushBtn && pushSupported && PlataformaData.vapidPublicKey) {
    pushBtn.addEventListener('click', async () => {
      pushBtn.disabled = true;
      pushBtn.textContent = 'Activando…';
      if (pushStatus) pushStatus.hidden = true;

      if (!window.isSecureContext) {
        if (pushStatus) { pushStatus.textContent = 'Las notificaciones push requieren HTTPS. Usa la dirección segura del sitio.'; pushStatus.hidden = false; }
        pushBtn.disabled = false;
        pushBtn.textContent = 'Activar notificaciones en este dispositivo';
        return;
      }

      try {
        const reg  = await navigator.serviceWorker.register('/sw.js');
        await navigator.serviceWorker.ready; // wait until SW is active
        const perm = await Notification.requestPermission();
        if (perm !== 'granted') {
          if (pushStatus) { pushStatus.textContent = 'Permiso denegado. Actívalo en la configuración del navegador.'; pushStatus.hidden = false; }
          pushBtn.disabled = false;
          pushBtn.textContent = 'Activar notificaciones en este dispositivo';
          return;
        }
        const vapidKey = urlBase64ToUint8Array(PlataformaData.vapidPublicKey);
        const sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: vapidKey });
        const body = new URLSearchParams({
          action:       'plataforma_save_push_subscription',
          _wpnonce:     PlataformaData.notifNonce,
          subscription: JSON.stringify(sub.toJSON()),
        });
        await fetch(PlataformaData.ajaxUrl, { method: 'POST', body });
        if (pushStatus) { pushStatus.textContent = '¡Notificaciones push activadas!'; pushStatus.hidden = false; }
        pushBtn.textContent = 'Notificaciones activas';
      } catch (err) {
        const msg = err.message || String(err);
        if (pushStatus) { pushStatus.textContent = 'No se pudo activar: ' + msg; pushStatus.hidden = false; }
        pushBtn.disabled = false;
        pushBtn.textContent = 'Activar notificaciones en este dispositivo';
      }
    });
  }

  fetchAndUpdate();
  setInterval(fetchAndUpdate, 60000);
}

function urlBase64ToUint8Array(base64String) {
  const padding  = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64   = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const raw      = window.atob(base64);
  const out      = new Uint8Array(raw.length);
  for (let i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
  return out;
}

// ---------------------------------------------------------------------------
// Web Push — service worker registration (background, no UI)
// ---------------------------------------------------------------------------

async function initPushNotifications() {
  if (typeof PlataformaData === 'undefined' || !PlataformaData.vapidPublicKey) return;
  if (!('serviceWorker' in navigator)) return;
  try {
    const reg = await navigator.serviceWorker.register('/sw.js');
    const existing = await reg.pushManager.getSubscription();
    if (existing) {
      // Re-save subscription in case it changed (e.g. after browser update)
      const body = new URLSearchParams({
        action:       'plataforma_save_push_subscription',
        _wpnonce:     PlataformaData.notifNonce,
        subscription: JSON.stringify(existing.toJSON()),
      });
      fetch(PlataformaData.ajaxUrl, { method: 'POST', body });
    }
  } catch { /* ignore — not critical */ }
}

// ---------------------------------------------------------------------------
// Agenda calendar — month view with admin events
// ---------------------------------------------------------------------------

function initAgendaCalendar() {
  const container = document.getElementById('cal-month');
  if (!container || typeof PlataformaData === 'undefined') return;

  const grid     = document.getElementById('cal-grid');
  const heading  = document.getElementById('cal-heading');
  const listRoot = document.getElementById('cal-event-list');

  let year  = parseInt(container.dataset.year,  10);
  let month = parseInt(container.dataset.month, 10);

  const MONTHS_ES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                     'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

  async function fetchEvents(y, m) {
    try {
      const url = `${PlataformaData.ajaxUrl}?action=plataforma_fetch_calendar_events&_wpnonce=${PlataformaData.notifNonce}&year=${y}&month=${m}`;
      const res  = await fetch(url);
      const data = await res.json();
      return data.success ? data.data : [];
    } catch { return []; }
  }

  // "26 Mayo 2026, 19:30" or "26 Mayo 2026" if no time
  function formatBigDate(ev) {
    if (!ev.start_date) return '';
    const [y, m, d] = ev.start_date.split('-').map(Number);
    const monthName = MONTHS_ES[m - 1];
    let label = `${d} ${monthName} ${y}`;
    if (ev.start_time) label += `, ${ev.start_time.slice(0, 5)}`;
    if (ev.end_date && ev.end_date !== ev.start_date) {
      const [ey, em, ed] = ev.end_date.split('-').map(Number);
      label += ` — ${ed} ${MONTHS_ES[em - 1]} ${ey}`;
      if (ev.end_time) label += `, ${ev.end_time.slice(0, 5)}`;
    } else if (ev.end_time && ev.end_time !== ev.start_time) {
      label += ` — ${ev.end_time.slice(0, 5)}`;
    }
    return label;
  }

  function buildAtcbConfig(ev) {
    const startDate = ev.start_date;
    const startTime = ev.start_time ? ev.start_time.slice(0, 5) : '';
    const endDate   = ev.end_date || ev.start_date;
    const endTime   = ev.end_time ? ev.end_time.slice(0, 5)
                    : (startTime ? addOneHour(startTime) : '');

    const cfg = {
      name:        ev.title,
      description: ev.description || '',
      startDate,
      endDate,
      timeZone:    'Europe/Vienna',
      listStyle:   'dropdown',
      options:     ['Apple', 'Google', 'iCal', 'Outlook.com'],
      iCalFileName: (ev.title || 'evento').toLowerCase().replace(/[^a-z0-9]+/g, '-'),
    };
    if (startTime) { cfg.startTime = startTime; cfg.endTime = endTime; }
    if (ev.location) cfg.location = ev.location;
    return cfg;
  }

  function addOneHour(hhmm) {
    const [h, m] = hhmm.split(':').map(Number);
    const total = (h * 60 + m + 60) % (24 * 60);
    return String(Math.floor(total / 60)).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0');
  }

  function renderEventList(events) {
    if (!listRoot) return;
    if (!events.length) {
      listRoot.innerHTML = '<p class="cal-event-list__empty">No hay eventos este mes.</p>';
      return;
    }
    listRoot.innerHTML = events.map((ev) => {
      const atcbJson = JSON.stringify(buildAtcbConfig(ev)).replace(/"/g, '&quot;');
      const location = ev.location ? `<p class="cal-event-item__location">📍 ${escHtml(ev.location)}</p>` : '';
      const desc     = ev.description ? `<p class="cal-event-item__desc">${escHtml(ev.description)}</p>` : '';
      return `
        <article class="cal-event-item" style="--cal-event-color:${escHtml(ev.color || '#c0391c')}">
          <div class="cal-event-item__date">${escHtml(formatBigDate(ev))}</div>
          <h3 class="cal-event-item__title">${escHtml(ev.title)}</h3>
          ${location}
          ${desc}
          <div class="cal-event-item__footer">
            <button type="button" class="atcb-trigger" data-atcb="${atcbJson}" aria-label="Guardar en calendario" title="Guardar en calendario">
              <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="18" height="18">
                <rect x="2" y="4" width="16" height="14" rx="2.5" stroke="#ffffff" stroke-width="1.6"/>
                <path d="M2 9h16" stroke="#ffffff" stroke-width="1.6"/>
                <path d="M6.5 2v4M13.5 2v4" stroke="#ffffff" stroke-width="1.6" stroke-linecap="round"/>
                <circle cx="6"  cy="12.5" r="0.8" fill="#ffffff"/>
                <circle cx="9"  cy="12.5" r="0.8" fill="#ffffff"/>
                <circle cx="12" cy="12.5" r="0.8" fill="#ffffff"/>
                <circle cx="6"  cy="15.5" r="0.8" fill="#ffffff"/>
                <circle cx="9"  cy="15.5" r="0.8" fill="#ffffff"/>
                <circle cx="12" cy="15.5" r="0.8" fill="#ffffff"/>
                <circle cx="19" cy="19"   r="4"   stroke="#ffffff" stroke-width="1.6"/>
                <path d="M19 16.8v4.4M16.8 19h4.4" stroke="#ffffff" stroke-width="1.6" stroke-linecap="round"/>
              </svg>
            </button>
          </div>
        </article>
      `;
    }).join('');

    // Wire up atcb buttons in the rendered list
    listRoot.querySelectorAll('.atcb-trigger[data-atcb]').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (typeof window.atcb_action !== 'function') return;
        try {
          const cfg = JSON.parse(btn.dataset.atcb);
          window.atcb_action(cfg, btn);
        } catch { /* invalid config */ }
      });
    });
  }

  function scrollToEvent(eventId) {
    const el = listRoot?.querySelector(`[data-event-id="${eventId}"]`);
    if (!el) return;
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    el.classList.add('cal-event-item--flash');
    setTimeout(() => el.classList.remove('cal-event-item--flash'), 1200);
  }

  async function render() {
    if (!grid || !heading) return;
    heading.textContent = `${MONTHS_ES[month - 1]} ${year}`;
    grid.innerHTML = '';
    if (listRoot) listRoot.innerHTML = '<p class="cal-event-list__loading">Cargando…</p>';

    const events = await fetchEvents(year, month);

    // Build a map: date string → array of events
    const eventMap = {};
    events.forEach((ev) => {
      const key = ev.start_date;
      if (!eventMap[key]) eventMap[key] = [];
      eventMap[key].push(ev);
    });

    const firstDay = new Date(year, month - 1, 1);
    const totalDays = new Date(year, month, 0).getDate();
    // Monday-first: 0=Mon … 6=Sun
    const startDow = (firstDay.getDay() + 6) % 7;

    // Empty cells before first day
    for (let i = 0; i < startDow; i++) {
      const empty = document.createElement('div');
      empty.className = 'cal-day cal-day--empty';
      grid.appendChild(empty);
    }

    const today = new Date();
    for (let d = 1; d <= totalDays; d++) {
      const dateStr = `${year}-${String(month).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
      const dayEvents = eventMap[dateStr] || [];
      const isToday = (today.getFullYear() === year && today.getMonth() + 1 === month && today.getDate() === d);

      const cell = document.createElement('div');
      cell.className = 'cal-day' +
        (dayEvents.length ? ' cal-day--has-event' : '') +
        (isToday ? ' cal-day--today' : '');

      const num = document.createElement('span');
      num.className = 'cal-day__num';
      num.textContent = d;
      cell.appendChild(num);

      if (dayEvents.length) {
        const dot = document.createElement('span');
        dot.className = 'cal-day__dot';
        dot.style.background = dayEvents[0].color || '#c0391c';
        cell.appendChild(dot);
        cell.style.cursor = 'pointer';
        cell.addEventListener('click', () => scrollToEvent(dayEvents[0].id));
      }

      grid.appendChild(cell);
    }

    renderEventList(events);

    // After list is rendered, add data-event-id for the scrollToEvent lookup
    const items = listRoot ? listRoot.querySelectorAll('.cal-event-item') : [];
    events.forEach((ev, i) => {
      if (items[i]) items[i].dataset.eventId = ev.id;
    });
  }

  document.getElementById('cal-prev')?.addEventListener('click', () => {
    month--;
    if (month < 1) { month = 12; year--; }
    render();
  });

  document.getElementById('cal-next')?.addEventListener('click', () => {
    month++;
    if (month > 12) { month = 1; year++; }
    render();
  });

  // Load when the Calendario tab becomes visible
  const agendaTab = document.querySelector('.tablero__tab[data-tab="agenda"]');
  if (agendaTab) {
    agendaTab.addEventListener('click', () => { render(); }, { once: true });
  }
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

// ---------------------------------------------------------------------------
// Contact form — send message to a department via AJAX
// ---------------------------------------------------------------------------

function initContactForm() {
  const form   = document.getElementById('contact-form');
  const notice = document.getElementById('contact-notice');
  if (!form || typeof PlataformaData === 'undefined') return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const recipient = form.querySelector('[name="recipient"]').value;
    const subject   = form.querySelector('[name="subject"]').value.trim();
    const message   = form.querySelector('[name="message"]').value.trim();

    if (!recipient || !subject || !message) {
      showNotice(notice, 'Completa todos los campos antes de enviar.', 'error');
      return;
    }

    const btn  = form.querySelector('button[type="submit"]');
    const orig = btn.textContent;
    btn.disabled    = true;
    btn.textContent = 'Enviando…';

    const nonce = form.querySelector('[name="_wpnonce"]')?.value || PlataformaData.contactNonce;
    const body  = new URLSearchParams({
      action:    'plataforma_send_contact_message',
      _wpnonce:  nonce,
      recipient,
      subject,
      message,
    });

    try {
      const res  = await fetch(PlataformaData.ajaxUrl, { method: 'POST', body });
      const data = await res.json();
      if (data.success) {
        showNotice(notice, data.data?.message || 'Mensaje enviado.', 'success');
        form.reset();
      } else {
        showNotice(notice, data.data?.message || 'Error al enviar. Inténtalo de nuevo.', 'error');
      }
    } catch {
      showNotice(notice, 'Error de red. Verifica tu conexión.', 'error');
    } finally {
      btn.disabled    = false;
      btn.textContent = orig;
    }
  });
}

// ---------------------------------------------------------------------------
// Mobile hamburger — toggles .site-bar__pages.is-open
// ---------------------------------------------------------------------------

function initHamburger() {
  const btn = document.getElementById('nav-toggle');
  const nav = document.getElementById('site-bar-pages');
  if (!btn || !nav) return;

  btn.addEventListener('click', () => {
    const open = nav.classList.toggle('is-open');
    btn.setAttribute('aria-expanded', String(open));
    btn.setAttribute('aria-label', open ? 'Cerrar navegación' : 'Abrir navegación');
  });

  // Close when a nav link is tapped on mobile
  nav.addEventListener('click', (e) => {
    if (e.target.closest('a')) {
      nav.classList.remove('is-open');
      btn.setAttribute('aria-expanded', 'false');
      btn.setAttribute('aria-label', 'Abrir navegación');
    }
  });
}
