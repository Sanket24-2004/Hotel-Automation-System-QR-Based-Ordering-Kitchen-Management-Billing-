/**
 * kitchen.js — Hotel Tulsi 24" Hindi Kitchen Display System (KDS)
 * 100% Hands-Free Operation for Chefs and Kitchen Staff
 * v7.0 — Dual Partition (नया ऑर्डर / चल रहे ऑर्डर्स) with Live Audio Chimes
 */
'use strict';

/* ═══════════════════════════════════════════════════════════════
   CONFIG & CONSTANTS
   ═══════════════════════════════════════════════════════════════ */
const API_BASE = 'api/';
const POLL_INTERVAL = 2500; // Poll every 2.5 seconds
const NEW_ORDER_TIMEOUT_SEC = 180; // 3 minutes stay in "New" section before moving to "Ongoing" if no newer order arrives

const CATEGORY_ICONS = {
  starter: '🍽️', main: '🍛', bread: '🫓', rice: '🍚',
  beverage: '🥤', dessert: '🍨', salad: '🥗', side: '🥣', water: '💧'
};

const CATEGORY_HINDI = {
  starter: 'स्टार्टर', main: 'मेन कोर्स', bread: 'रोटी / ब्रेड', rice: 'चावल / बिरयानी',
  beverage: 'पेय / बेवरेज', dessert: 'मीठा / डेज़र्ट', salad: 'सलाद', side: 'अन्य', water: 'पानी'
};

/* ═══════════════════════════════════════════════════════════════
   STATE
   ═══════════════════════════════════════════════════════════════ */
let ordersCache = {}; // id -> order object
let previousOrderIds = new Set();
let soundEnabled = localStorage.getItem('kds_sound') !== 'false';
let isFirstLoad = true;
let isPolling = false;
let audioCtx = null;
let pollIntervalTimer = null;
let timerTickInterval = null;
let isHistoryOpen = false;

/* ═══════════════════════════════════════════════════════════════
   DOM REFERENCES
   ═══════════════════════════════════════════════════════════════ */
const $newOrdersContainer     = document.getElementById('newOrdersContainer');
const $ongoingOrdersContainer = document.getElementById('ongoingOrdersContainer');
const $newEmptyState          = document.getElementById('newEmptyState');
const $ongoingEmptyState      = document.getElementById('ongoingEmptyState');

const $badgeNewOrders         = document.getElementById('badgeNewOrders');
const $badgeOngoingOrders     = document.getElementById('badgeOngoingOrders');

const $statNewCount           = document.getElementById('statNewCount');
const $statOngoingCount       = document.getElementById('statOngoingCount');
const $statTotalActive        = document.getElementById('statTotalActive');

const $kdsClock               = document.getElementById('kdsClock');
const $kdsConnStatus          = document.getElementById('kdsConnStatus');
const $newOrderBanner         = document.getElementById('newOrderBanner');
const $newAlertText           = document.getElementById('newAlertText');
const $audioUnlockModal       = document.getElementById('audioUnlockModal');

const $soundToggleBtn         = document.getElementById('soundToggleBtn');
const $soundIcon              = document.getElementById('soundIcon');
const $soundText              = document.getElementById('soundText');

const $historyOverlay         = document.getElementById('historyOverlay');
const $historyContentList     = document.getElementById('historyContentList');

/* ═══════════════════════════════════════════════════════════════
   LIVE CLOCK (IST TIME)
   ═══════════════════════════════════════════════════════════════ */
function updateClock() {
  const now = new Date();
  const timeStr = now.toLocaleTimeString('en-IN', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: true
  });
  if ($kdsClock) {
    $kdsClock.textContent = timeStr;
  }
}
setInterval(updateClock, 1000);
updateClock();

/* ═══════════════════════════════════════════════════════════════
   WEB AUDIO API BELL CHIME (Hands-Free Melodic Bell)
   ═══════════════════════════════════════════════════════════════ */
function initAudioContext() {
  if (audioCtx && audioCtx.state !== 'closed') {
    if (audioCtx.state === 'suspended') {
      audioCtx.resume().catch(() => {});
    }
    return audioCtx;
  }
  try {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
    if (AudioContextClass) {
      audioCtx = new AudioContextClass();
    }
    return audioCtx;
  } catch (e) {
    console.warn('[KDS Audio] Failed to create AudioContext', e);
    return null;
  }
}

function checkAudioAutoplay() {
  const ac = initAudioContext();
  if (ac && ac.state === 'suspended') {
    if ($audioUnlockModal) {
      $audioUnlockModal.style.display = 'flex';
    }
  } else {
    if ($audioUnlockModal) {
      $audioUnlockModal.style.display = 'none';
    }
  }
}

function unlockAudio() {
  const ac = initAudioContext();
  if (ac) {
    ac.resume().then(() => {
      if ($audioUnlockModal) $audioUnlockModal.style.display = 'none';
      playKitchenChime(); // Test ring
    }).catch(() => {});
  }
}
document.addEventListener('click', () => {
  const ac = initAudioContext();
  if (ac && ac.state === 'suspended') {
    ac.resume().then(() => {
      if ($audioUnlockModal) $audioUnlockModal.style.display = 'none';
    }).catch(() => {});
  }
}, { once: true });

function playKitchenChime() {
  if (!soundEnabled) return;
  const ac = initAudioContext();
  if (!ac || ac.state === 'suspended') return;

  try {
    // Elegant 4-tone ascending kitchen chime: C6 -> E6 -> G6 -> C7
    const notes = [1046.50, 1318.51, 1567.98, 2093.00];
    const now = ac.currentTime;

    notes.forEach((freq, idx) => {
      const osc = ac.createOscillator();
      const gain = ac.createGain();

      osc.type = 'triangle';
      osc.frequency.setValueAtTime(freq, now + idx * 0.12);

      gain.gain.setValueAtTime(0.3, now + idx * 0.12);
      gain.gain.exponentialRampToValueAtTime(0.001, now + idx * 0.12 + 0.45);

      osc.connect(gain);
      gain.connect(ac.destination);

      osc.start(now + idx * 0.12);
      osc.stop(now + idx * 0.12 + 0.45);
    });
  } catch (e) {
    console.error('[KDS Audio] Error playing chime:', e);
  }
}

function toggleSound() {
  soundEnabled = !soundEnabled;
  localStorage.setItem('kds_sound', soundEnabled ? 'true' : 'false');
  updateSoundButtonUI();
  if (soundEnabled) {
    initAudioContext();
    playKitchenChime();
  }
}

function updateSoundButtonUI() {
  if (!$soundIcon || !$soundText) return;
  if (soundEnabled) {
    $soundIcon.textContent = '🔔';
    $soundText.textContent = 'घंटी चालू';
    $soundToggleBtn.classList.add('btn-active');
  } else {
    $soundIcon.textContent = '🔕';
    $soundText.textContent = 'घंटी बंद';
    $soundToggleBtn.classList.remove('btn-active');
  }
}
updateSoundButtonUI();

/* ═══════════════════════════════════════════════════════════════
   FULLSCREEN TOGGLE
   ═══════════════════════════════════════════════════════════════ */
function toggleFullScreen() {
  if (!document.fullscreenElement && !document.webkitFullscreenElement) {
    if (document.documentElement.requestFullscreen) {
      document.documentElement.requestFullscreen().catch(() => {});
    } else if (document.documentElement.webkitRequestFullscreen) {
      document.documentElement.webkitRequestFullscreen();
    }
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen().catch(() => {});
    } else if (document.webkitExitFullscreen) {
      document.webkitExitFullscreen();
    }
  }
}

/* ═══════════════════════════════════════════════════════════════
   POLLING & SYNC
   ═══════════════════════════════════════════════════════════════ */
async function fetchKitchenOrders() {
  const url = `${API_BASE}get_orders.php?status=all&since=0&t=${Date.now()}`;
  try {
    const resp = await fetch(url);
    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
    const data = await resp.json();

    if (!data.success) throw new Error(data.error || 'Server error');

    setConnectionStatus(true);
    processIncomingOrders(data.orders || []);
    isFirstLoad = false;
  } catch (err) {
    console.warn('[KDS Sync Error]', err);
    setConnectionStatus(false);
  }
}

function setConnectionStatus(connected) {
  if (!$kdsConnStatus) return;
  if (connected) {
    $kdsConnStatus.className = 'conn-badge connected';
    $kdsConnStatus.innerHTML = '<span class="conn-dot"></span><span class="conn-text">कनेक्टेड</span>';
  } else {
    $kdsConnStatus.className = 'conn-badge disconnected';
    $kdsConnStatus.innerHTML = '<span class="conn-dot"></span><span class="conn-text">पुनः प्रयास...</span>';
  }
}

function processIncomingOrders(orders) {
  let hasBrandNewOrder = false;
  let newOrderTable = '';

  const incomingActiveMap = {};

  orders.forEach(order => {
    // Only process unbilled orders
    if (order.status === 'served' || order.payment_method) {
      return;
    }

    const oid = order.id;
    incomingActiveMap[oid] = order;

    // Detect new order arrival
    if (!previousOrderIds.has(oid) && !isFirstLoad) {
      hasBrandNewOrder = true;
      newOrderTable = order.table_no;
    }

    // Detect additional batch items added to existing order
    const prevOrder = ordersCache[oid];
    if (prevOrder && !isFirstLoad) {
      const prevBatches = new Set((prevOrder.batches || []).map(b => b.batch_id));
      (order.batches || []).forEach(b => {
        if (!prevBatches.has(b.batch_id)) {
          hasBrandNewOrder = true;
          newOrderTable = order.table_no;
        }
      });
    }
  });

  // Update active cache
  ordersCache = incomingActiveMap;
  previousOrderIds = new Set(Object.keys(incomingActiveMap).map(Number));

  if (hasBrandNewOrder) {
    playKitchenChime();
    triggerNewOrderBanner(newOrderTable);
  }

  renderKdsPartitions();
}

function triggerNewOrderBanner(tableNo) {
  if (!$newOrderBanner) return;
  $newAlertText.textContent = tableNo ? `🔔 नया ऑर्डर! टेबल ${tableNo}` : `🔔 नया ऑर्डर प्राप्त हुआ!`;
  $newOrderBanner.classList.add('show');
  setTimeout(() => {
    $newOrderBanner.classList.remove('show');
  }, 4500);
}

/* ═══════════════════════════════════════════════════════════════
   DUAL-PARTITION RENDER LOGIC
   ═══════════════════════════════════════════════════════════════ */
function renderKdsPartitions() {
  const activeOrders = Object.values(ordersCache);

  // Sort by created_at DESC (newest first, then ID)
  activeOrders.sort((a, b) => new Date(b.created_at) - new Date(a.created_at) || b.id - a.id);

  const newOrders = [];
  const ongoingOrders = [];

  if (activeOrders.length > 0) {
    // Partition 1 (Left): ONLY the single latest / newest order!
    newOrders.push(activeOrders[0]);

    // Partition 2 (Right): All other previously received active orders
    for (let i = 1; i < activeOrders.length; i++) {
      ongoingOrders.push(activeOrders[i]);
    }
  }

  // Update header badges
  const totalCount = activeOrders.length;
  const newCount = newOrders.length;
  const ongoingCount = ongoingOrders.length;

  if ($statNewCount) $statNewCount.textContent = newCount;
  if ($statOngoingCount) $statOngoingCount.textContent = ongoingCount;
  if ($statTotalActive) $statTotalActive.textContent = totalCount;

  if ($badgeNewOrders) $badgeNewOrders.textContent = newCount;
  if ($badgeOngoingOrders) $badgeOngoingOrders.textContent = ongoingCount;

  const mAll = document.getElementById('mBadgeAll');
  const mNew = document.getElementById('mBadgeNew');
  const mOngoing = document.getElementById('mBadgeOngoing');
  if (mAll) mAll.textContent = totalCount;
  if (mNew) mNew.textContent = newCount;
  if (mOngoing) mOngoing.textContent = ongoingCount;

  // Render Partition 1: New Orders
  renderNewPartition(newOrders);

  // Render Partition 2: Ongoing Orders
  renderOngoingPartition(ongoingOrders);

  if (window.innerWidth <= 768) {
    switchMobileKdsTab(currentMobileTab);
  }
}

let currentMobileTab = 'all';

function switchMobileKdsTab(tab) {
  currentMobileTab = tab;
  document.querySelectorAll('.m-tab').forEach(btn => btn.classList.remove('active'));
  const activeBtn = document.getElementById(tab === 'all' ? 'mTabAll' : tab === 'new' ? 'mTabNew' : 'mTabOngoing');
  if (activeBtn) activeBtn.classList.add('active');

  const pNew = document.getElementById('partitionNew');
  const pOngoing = document.getElementById('partitionOngoing');
  if (!pNew || !pOngoing) return;

  if (tab === 'all') {
    pNew.style.display = '';
    pOngoing.style.display = '';
  } else if (tab === 'new') {
    pNew.style.display = 'flex';
    pOngoing.style.display = 'none';
  } else if (tab === 'ongoing') {
    pNew.style.display = 'none';
    pOngoing.style.display = 'flex';
  }
}

function renderNewPartition(orders) {
  if (!$newOrdersContainer || !$newEmptyState) return;

  if (orders.length === 0) {
    $newOrdersContainer.innerHTML = '';
    $newEmptyState.style.display = 'flex';
    return;
  }

  $newEmptyState.style.display = 'none';
  $newOrdersContainer.innerHTML = '';

  orders.forEach(order => {
    const card = createKdsCard(order, true);
    $newOrdersContainer.appendChild(card);
  });
}

function renderOngoingPartition(orders) {
  if (!$ongoingOrdersContainer || !$ongoingEmptyState) return;

  if (orders.length === 0) {
    $ongoingOrdersContainer.innerHTML = '';
    $ongoingEmptyState.style.display = 'flex';
    return;
  }

  $ongoingEmptyState.style.display = 'none';
  $ongoingOrdersContainer.innerHTML = '';

  orders.forEach(order => {
    const card = createKdsCard(order, false);
    $ongoingOrdersContainer.appendChild(card);
  });
}

/* ═══════════════════════════════════════════════════════════════
   CARD CREATION (HINDI KDS COMPONENT)
   ═══════════════════════════════════════════════════════════════ */
function createKdsCard(order, isNewPartition) {
  const card = document.createElement('div');
  const isDelayed = checkIsDelayed(order);

  const cardClass = isNewPartition 
    ? 'kds-card kds-card-new' 
    : `kds-card kds-card-ongoing${isDelayed ? ' delayed' : ''}`;
  
  card.className = cardClass;
  card.dataset.id = order.id;

  const elapsedText = getElapsedFormatted(order.created_at);
  const prepTime = getEstimatedPrepTime(order);

  // Status badge text
  let statusBadgeHtml = '';
  if (isNewPartition) {
    statusBadgeHtml = `<span class="status-tag tag-new">🔴 नया ऑर्डर</span>`;
  } else if (isDelayed) {
    statusBadgeHtml = `<span class="status-tag tag-delayed">⚠️ विलंब (${prepTime}+ मि.)</span>`;
  } else {
    statusBadgeHtml = `<span class="status-tag tag-ongoing">🟠 तैयारी चालू</span>`;
  }

  const personsCount = parseInt(order.persons, 10) || 1;

  // Customer Special Note
  let noteHtml = '';
  if (order.customer_note && order.customer_note.trim() !== '') {
    noteHtml = `
      <div class="card-customer-note">
        <span class="note-icon">📢</span>
        <div class="note-body">
          <span class="note-label">विशेष निर्देश / नोट:</span>
          ${escapeHtml(order.customer_note)}
        </div>
      </div>
    `;
  }

  // Items rows in Hindi
  let itemsHtml = '';
  (order.batches || []).forEach(batch => {
    (batch.items || []).forEach(item => {
      const icon = CATEGORY_ICONS[item.category] || '🍽️';
      const dishHindi = item.name_hi || item.name || 'व्यंजन';
      const dishEnglish = item.name_en && item.name_en !== dishHindi ? item.name_en : '';

      itemsHtml += `
        <div class="dish-row">
          <div class="dish-info">
            <span class="dish-icon">${icon}</span>
            <div class="dish-text">
              <span class="dish-name-hi">${dishHindi}</span>
              ${dishEnglish ? `<span class="dish-name-en">${escapeHtml(dishEnglish)}</span>` : ''}
            </div>
          </div>
          <span class="dish-qty-badge">× ${item.qty}</span>
        </div>
      `;
    });
  });

  card.innerHTML = `
    <div class="card-top">
      <div class="table-badge-wrap">
        <span class="table-hindi-label">टेबल</span>
        <span class="table-big-num">${order.table_no}</span>
      </div>
      <div class="card-status-pill">
        ${statusBadgeHtml}
        <div class="big-persons-badge" title="कुल ग्राहक">
          <span class="persons-icon">👥</span>
          <span class="persons-num">${personsCount}</span>
          <span class="persons-label">लोग</span>
        </div>
      </div>
    </div>

    <div class="card-meta-bar">
      <div class="meta-time-wrap">
        <span>⏱️ इंतज़ार:</span>
        <span class="meta-live-timer" data-created="${order.created_at}">${elapsedText}</span>
      </div>
      <div class="meta-prep-time">
        <span>⏳ ${prepTime} मि.</span>
      </div>
    </div>

    ${noteHtml}

    <div class="card-items-body">
      ${itemsHtml}
    </div>

    <div class="card-bottom-info">
      <span class="order-ref-num">${order.order_ref || ''}</span>
    </div>
  `;

  return card;
}

/* ═══════════════════════════════════════════════════════════════
   LIVE TIMERS (Tick every 1 second)
   ═══════════════════════════════════════════════════════════════ */
function updateLiveTimers() {
  document.querySelectorAll('.meta-live-timer[data-created]').forEach(el => {
    const createdStr = el.dataset.created;
    el.textContent = getElapsedFormatted(createdStr);

    const card = el.closest('.kds-card');
    if (card && !card.classList.contains('kds-card-new')) {
      const createdMs = new Date(createdStr).getTime();
      const elapsedMin = (Date.now() - createdMs) / 60000;
      if (elapsedMin > 15 && !card.classList.contains('delayed')) {
        card.classList.add('delayed');
      }
    }
  });
}

function getElapsedFormatted(dateStr) {
  if (!dateStr) return '00:00';
  const created = new Date(dateStr).getTime();
  const diffSec = Math.max(0, Math.floor((Date.now() - created) / 1000));
  const m = Math.floor(diffSec / 60);
  const s = diffSec % 60;
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
}

function getEstimatedPrepTime(order) {
  let maxPrep = 10;
  (order.batches || []).forEach(b => {
    (b.items || []).forEach(i => {
      const p = i.prep_time_min || 10;
      if (p > maxPrep) maxPrep = p;
    });
  });
  return maxPrep;
}

function checkIsDelayed(order) {
  const created = new Date(order.created_at).getTime();
  const elapsedMin = (Date.now() - created) / 60000;
  const estPrep = getEstimatedPrepTime(order);
  return elapsedMin > estPrep || elapsedMin > 15;
}

function escapeHtml(str) {
  if (!str) return '';
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}

/* ═══════════════════════════════════════════════════════════════
   HISTORY DRAWER VIEW (Billed Orders)
   ═══════════════════════════════════════════════════════════════ */
async function toggleHistoryView() {
  if (isHistoryOpen) {
    closeHistoryView();
  } else {
    openHistoryView();
  }
}

async function openHistoryView() {
  isHistoryOpen = true;
  if ($historyOverlay) $historyOverlay.classList.add('show');
  loadBilledHistory();
}

function closeHistoryView() {
  isHistoryOpen = false;
  if ($historyOverlay) $historyOverlay.classList.remove('show');
}

async function loadBilledHistory() {
  if (!$historyContentList) return;
  $historyContentList.innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:2rem;">इतिहास लोड हो रहा है...</p>';

  try {
    const resp = await fetch(`${API_BASE}get_history.php?days=1&t=${Date.now()}`);
    if (!resp.ok) throw new Error('HTTP error');
    const data = await resp.json();

    if (!data.success || !data.orders || data.orders.length === 0) {
      $historyContentList.innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:3rem;">आज कोई बिल नहीं बना है।</p>';
      return;
    }

    $historyContentList.innerHTML = '';
    data.orders.slice(0, 20).forEach(order => {
      const card = document.createElement('div');
      card.className = 'history-card-item';

      let itemsSummary = [];
      (order.batches || []).forEach(b => {
        (b.items || []).forEach(i => {
          const name = i.name_hi || i.name || 'व्यंजन';
          itemsSummary.push(`${name} (${i.qty})`);
        });
      });

      const billedTime = order.served_at ? new Date(order.served_at).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true }) : '--';

      card.innerHTML = `
        <div class="ht-header">
          <span class="ht-table">टेबल ${order.table_no}</span>
          <span class="ht-time">बिल समय: ${billedTime}</span>
        </div>
        <div class="ht-items">${itemsSummary.join(', ') || 'कोई आइटम नहीं'}</div>
        <div class="ht-billed">✓ भुगतान सम्पन्न (${order.payment_method || 'Cash'}) • ₹${order.total_amount}</div>
      `;
      $historyContentList.appendChild(card);
    });
  } catch (err) {
    $historyContentList.innerHTML = `<p style="color:var(--red-alert);text-align:center;">इतिहास लोड नहीं हो सका: ${err.message}</p>`;
  }
}

/* ═══════════════════════════════════════════════════════════════
   INITIALIZATION
   ═══════════════════════════════════════════════════════════════ */
function startKdsEngine() {
  if (isPolling) return;
  isPolling = true;

  checkAudioAutoplay();
  fetchKitchenOrders();

  pollIntervalTimer = setInterval(fetchKitchenOrders, POLL_INTERVAL);
  timerTickInterval = setInterval(updateLiveTimers, 1000);
}

startKdsEngine();
