/* Plataforma — likes.js
   Open to everyone. Logged-in: bypass anti-bot. Visitors: time-on-page check
   plus honeypot field sent in the request body. */

const PLATAFORMA_PAGE_LOAD = Date.now();

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.like-btn').forEach((btn) => {
    btn.addEventListener('click', handleLikeClick);
  });
});

async function handleLikeClick(e) {
  const btn = e.currentTarget;

  if (typeof PlataformaData === 'undefined') return;

  if (btn.dataset.pending === 'true') return;
  btn.dataset.pending = 'true';

  // Optimistic UI
  const wasLiked     = btn.classList.contains('is-liked');
  const countEl      = btn.querySelector('.like-count');
  const currentCount = parseInt(countEl.textContent, 10) || 0;

  btn.classList.toggle('is-liked', !wasLiked);
  btn.setAttribute('aria-pressed', String(!wasLiked));
  countEl.textContent = currentCount + (wasLiked ? -1 : 1);

  const body = new URLSearchParams({
    action:   'plataforma_toggle_like',
    post_id:  btn.dataset.postId,
    _wpnonce: PlataformaData.likeNonce,
    t:        String(Date.now() - PLATAFORMA_PAGE_LOAD), // anti-bot: time on page
    hp:       '',                                        // anti-bot: honeypot
  });

  try {
    const res  = await fetch(PlataformaData.ajaxUrl, { method: 'POST', body });
    const data = await res.json();

    if (!data.success) {
      // Revert
      btn.classList.toggle('is-liked', wasLiked);
      btn.setAttribute('aria-pressed', String(wasLiked));
      countEl.textContent = data.data?.count ?? currentCount;

      const msg = data.data?.message;
      if (msg) flashLikeError(btn, msg);
    } else {
      btn.classList.toggle('is-liked', data.data.liked);
      btn.setAttribute('aria-pressed', String(data.data.liked));
      countEl.textContent = data.data.count;
    }
  } catch {
    btn.classList.toggle('is-liked', wasLiked);
    btn.setAttribute('aria-pressed', String(wasLiked));
    countEl.textContent = currentCount;
  } finally {
    btn.dataset.pending = 'false';
  }
}

// Brief inline tooltip for like errors (rate limit, bot, etc.)
function flashLikeError(btn, message) {
  let tip = btn.querySelector('.like-error');
  if (!tip) {
    tip = document.createElement('span');
    tip.className = 'like-error';
    tip.style.cssText =
      'position:absolute;background:#9a2a0f;color:#fff;font-size:0.78rem;' +
      'padding:5px 9px;border-radius:8px;white-space:nowrap;z-index:10;' +
      'transform:translate(-50%, -120%);left:50%;top:0;';
    btn.style.position = 'relative';
    btn.appendChild(tip);
  }
  tip.textContent = message;
  setTimeout(() => { tip.remove(); }, 3000);
}
