'use strict';
/* ─────────────────────────────────────────────────────────────────────────────
 * menu-page.js — shared DB-driven menu renderer
 *
 * Usage from each menu HTML page:
 *   initMenuPage({
 *     category:    'beverage',
 *     fallbackImg: 'Beverages_Mango_lassi.png',
 *     ui,                       // page's translations
 *     getName:     fn(item),    // returns localized name
 *     onCartChange: fn(),       // called when cart updates (page provides)
 *   });
 *
 * Page MUST expose `MENU` as a let on window (will be repopulated), and
 * `cart` (object) — used by changeQty/updateCart that stay on the page.
 * ──────────────────────────────────────────────────────────────────────────── */

async function initMenuPage(cfg) {
  const grid = document.getElementById('itemsGrid');
  if (!grid) return;

  // Clear any existing static section labels — DB drives everything.
  grid.parentElement.querySelectorAll('.section-label').forEach(el => el.remove());

  // noResultCard
  const noResultCard = document.createElement('div');
  noResultCard.className = 'no-results-card';
  noResultCard.id = 'noResultCard';
  noResultCard.style.display = 'none';
  noResultCard.textContent = (cfg.ui && cfg.ui.noResults) || 'No items found';

  try {
    const res = await fetch('api/get_menu.php?category=' + encodeURIComponent(cfg.category));
    const d = await res.json();
    if (!d.success || !Array.isArray(d.items) || d.items.length === 0) {
      grid.innerHTML = '<div class="no-results-card">Menu unavailable. Please try again.</div>';
      return;
    }

    window.MENU = d.items.map(i => ({
      id: i.id,
      en: i.name_en,
      hi: i.name_hi || i.name_en,
      mr: i.name_mr || i.name_en,
      price: parseFloat(i.price),
      img: i.image_path || '',
      section: i.section || '',
    }));

    const getName = cfg.getName || (it => it.en);
    const fb = cfg.fallbackImg || '';

    let currentSection = null;
    let idx = 0;
    window.MENU.forEach(item => {
      if (item.section && item.section !== currentSection) {
        currentSection = item.section;
        const lbl = document.createElement('div');
        lbl.className = 'section-label';
        lbl.setAttribute('data-section', currentSection);
        lbl.style.cssText = 'grid-column:1/-1;margin-top:.5rem;';
        const key = currentSection.toLowerCase().replace(/[^a-z]/g, '');
        lbl.textContent = '— ' + ((cfg.ui && cfg.ui[key]) || currentSection) + ' —';
        grid.appendChild(lbl);
      }

      const card = document.createElement('div');
      card.className = 'item-card visible';
      card.id = 'card-' + item.id;
      card.setAttribute('data-section', item.section || '');
      const loadAttr = idx < 4 ? 'eager' : 'lazy';
      card.innerHTML =
        '<div class="item-img-wrap">' +
          '<img src="' + (item.img || '') + '" alt="' + escapeHtml(item.en) + '" loading="' + loadAttr + '"' +
            (fb ? ' onerror="this.onerror=null;this.src=\'' + fb + '\'"' : '') + '/>' +
        '</div>' +
        '<div class="item-info">' +
          '<div class="item-name">' + escapeHtml(getName(item)) + '</div>' +
          '<div class="item-price-row">' +
            '<div class="item-price">&#8377;' + item.price + '</div>' +
            '<div class="order-counter" id="counter-' + item.id + '">' +
              '<button class="counter-btn minus" onclick="changeQty(' + item.id + ',-1)" aria-label="Remove one">&#8722;</button>' +
              '<span class="counter-display" id="qty-' + item.id + '">0</span>' +
              '<button class="counter-btn plus" onclick="changeQty(' + item.id + ',1)" aria-label="Add">&#43;</button>' +
            '</div>' +
          '</div>' +
        '</div>';
      grid.appendChild(card);
      idx++;
    });

    grid.appendChild(noResultCard);

    // Entrance animation
    const io = new IntersectionObserver(entries => {
      entries.forEach(en => {
        if (en.isIntersecting) {
          en.target.classList.add('visible');
          io.unobserve(en.target);
        }
      });
    }, { threshold: 0.05 });
    document.querySelectorAll('.item-card').forEach((card, i) => {
      card.style.transition = 'opacity .45s ' + (0.06 + i * 0.04) + 's ease, transform .45s ' + (0.06 + i * 0.04) + 's ease';
      io.observe(card);
    });

    // Search wiring (section-aware)
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
      searchInput.addEventListener('input', () => {
        const q = searchInput.value.trim().toLowerCase();
        const sectionCounts = {};
        let totalVisible = 0;

        window.MENU.forEach(item => {
          const card = document.getElementById('card-' + item.id);
          if (!card) return;
          const s = card.getAttribute('data-section') || '';
          const match = !q
            || item.en.toLowerCase().includes(q)
            || getName(item).toLowerCase().includes(q);
          card.style.display = match ? '' : 'none';
          if (!sectionCounts[s]) sectionCounts[s] = 0;
          if (match) { sectionCounts[s]++; totalVisible++; }
        });

        document.querySelectorAll('.section-label').forEach(lbl => {
          const s = lbl.getAttribute('data-section') || '';
          lbl.style.display = (sectionCounts[s] || 0) > 0 ? '' : 'none';
        });

        noResultCard.style.display = (q && totalVisible === 0) ? '' : 'none';
        if (q && totalVisible === 0) noResultCard.textContent = (cfg.ui && cfg.ui.noResults) || 'No items found';
      });
    }
  } catch (e) {
    console.error('Menu load failed', e);
    grid.innerHTML = '<div class="no-results-card">Menu unavailable. Please try again.</div>';
  }
}

function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}
