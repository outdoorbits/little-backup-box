let slideshow_timeout = null;
let slideshow_running = false;

function slideshow_stop(ev) {
  // Prevent any default and bubbling (avoids click-through)
  if (ev) {
    ev.preventDefault();
    ev.stopPropagation();
    if (typeof ev.stopImmediatePropagation === 'function') ev.stopImmediatePropagation();
  }

  // Mark not running
  slideshow_running = false;

  // Clear our known timer
  if (slideshow_timeout) {
    clearTimeout(slideshow_timeout);
    slideshow_timeout = null;
  }

  // Nuclear option: clear *all* pending timeouts (in case another script set one)
  // NOTE: This will also clear other non-slideshow timers on the page.
  const maxId = setTimeout(() => {}, 0);
  for (let i = 0; i <= maxId; i++) clearTimeout(i);

  // Ensure the select shows "-" so a reload won't auto-start
  const sel = document.getElementById("slideshow_timer");
  if (sel) sel.value = '-';

  // Hide overlay + unlock scroll
  const overlay = document.getElementById("slideshowContent");
  if (overlay) overlay.style.display = "none";
  document.body.style.overflow = "";
}

function slideshow_init() {
  const sel = document.getElementById("slideshow_timer");
  const val = sel ? sel.value : '-';

  // Reset previous timer
  if (slideshow_timeout) {
    clearTimeout(slideshow_timeout);
    slideshow_timeout = null;
  }

  slideshow_running = (val !== '-');

  const overlay = document.getElementById("slideshowContent");
  if (!slideshow_running) {
    if (overlay) overlay.style.display = "none";
    document.body.style.overflow = "";
    return;
  }

  // Show overlay and arm redirect
  slideshow_display();
  slideshow_timeout = setTimeout(() => {
    // Only redirect if still running (prevents race on close)
    if (!slideshow_running) return;
    slideshow_redirect(val);
  }, Number(val) * 1000);
}

function slideshow_display() {
  const overlay = document.getElementById("slideshowContent");
  if (overlay) overlay.style.display = "block";
  document.body.style.overflow = "hidden";
}

function slideshow_redirect(slideshow_sec) {
  if (!slideshow_running) return; // extra guard
  const next = document.getElementById("slideshow_next_link");
  if (next && next.value) {
    window.location = next.value + '&slideshow_timer=' + slideshow_sec;
  }
}

// Close on ESC
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') slideshow_stop(e);
});

// Install safe backdrop/close handlers once DOM is ready
window.addEventListener('DOMContentLoaded', () => {
  const overlay = document.getElementById('slideshowContent');
  if (!overlay) return;

  const img = overlay.querySelector('img');
  const closeBtn = overlay.querySelector('.slideshowClose');

  // Close when pressing on the dark backdrop (use pointerdown to avoid click-through)
  const closeOnBackdrop = (e) => {
    if (e.target === overlay) slideshow_stop(e);
  };
  overlay.addEventListener('pointerdown', closeOnBackdrop);
  overlay.addEventListener('click', closeOnBackdrop, true); // capture as extra safety

  // Close on the X
  if (closeBtn) {
    const closeNow = (e) => slideshow_stop(e);
    closeBtn.addEventListener('pointerdown', closeNow);
    closeBtn.addEventListener('click', closeNow);
  }

  // Don't let clicks on the image bubble to the overlay
  if (img) {
    img.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
    });
  }
});
