/* Plataforma — likes.js
   AJAX like/unlike with optimistic UI.
   PlataformaData is injected by the plugin via wp_localize_script. */

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.like-btn').forEach((btn) => {
    btn.addEventListener('click', handleLikeClick);
  });
});

async function handleLikeClick(e) {
  const btn = e.currentTarget;

  // If data is not available (plugin inactive), do nothing silently.
  if (typeof PlataformaData === 'undefined') return;

  // Redirect to login if not authenticated.
  if (!PlataformaData.isLoggedIn) {
    window.location.href = PlataformaData.loginUrl;
    return;
  }

  // Prevent double-clicks while request is in flight.
  if (btn.dataset.pending === 'true') return;
  btn.dataset.pending = 'true';

  // Optimistic update.
  const wasLiked   = btn.classList.contains('is-liked');
  const countEl    = btn.querySelector('.like-count');
  const currentCount = parseInt(countEl.textContent, 10) || 0;

  btn.classList.toggle('is-liked', !wasLiked);
  btn.setAttribute('aria-pressed', String(!wasLiked));
  countEl.textContent = currentCount + (wasLiked ? -1 : 1);

  const body = new URLSearchParams({
    action:   'plataforma_toggle_like',
    post_id:  btn.dataset.postId,
    _wpnonce: PlataformaData.likeNonce,
  });

  try {
    const res  = await fetch(PlataformaData.ajaxUrl, { method: 'POST', body });
    const data = await res.json();

    if (!data.success) {
      // Server rejected — revert optimistic update.
      btn.classList.toggle('is-liked', wasLiked);
      btn.setAttribute('aria-pressed', String(wasLiked));
      countEl.textContent = data.data?.count ?? currentCount;

      // If unauthenticated, redirect to login.
      if (data.data?.loginUrl) {
        window.location.href = data.data.loginUrl;
      }
    } else {
      // Confirm server state (handles any race conditions).
      btn.classList.toggle('is-liked', data.data.liked);
      btn.setAttribute('aria-pressed', String(data.data.liked));
      countEl.textContent = data.data.count;
    }
  } catch {
    // Network error — revert.
    btn.classList.toggle('is-liked', wasLiked);
    btn.setAttribute('aria-pressed', String(wasLiked));
    countEl.textContent = currentCount;
  } finally {
    btn.dataset.pending = 'false';
  }
}
