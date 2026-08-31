/**
 * kitchen.js — Hotel Tulsi 24" Hindi Kitchen Display System (KDS)
 * 100% Hands-Free & Interactive Operation for Chefs and Kitchen Staff
 * v9.0 — Single Consolidated Table Card, 3 Partitions (New, In-Progress, Fully Served), See All Served Items Toggle
 */
'use strict';

/* ═══════════════════════════════════════════════════════════════
   CONFIG & CONSTANTS
   ═══════════════════════════════════════════════════════════════ */
const API_BASE = 'api/';
const POLL_INTERVAL = 2500; // Poll every 2.5 seconds

const CATEGORY_ICONS = {
  welcome_drink: '🍹', breakfast: '🍳', starter: '🍽️', thali: '🍱', main: '🍛', special_dishes: '🍲', bread: '🫓', rice: '🍚',
  chinese: '🥢', side: '🥣', dessert: '🍨', water: '💧', salad: '🥗'
};

const CATEGORY_HINDI = {
  welcome_drink: 'वेलकम ड्रिंक', breakfast: 'नाश्ता', starter: 'स्टार्टर', thali: 'थाली', main: 'मेन कोर्स', special_dishes: 'स्पेशल हांडी', bread: 'रोटी / पराठा', rice: 'चावल / बिरयानी',
  chinese: 'चाइनीज / नूडल्स', side: 'सूप / रायता', dessert: 'आइसक्रीम / डेज़र्ट', water: 'पानी', salad: 'सलाद'
};

/* ═══════════════════════════════════════════════════════════════
   STATE
   ═══════════════════════════════════════════════════════════════ */
let ordersCache = {}; // table_no -> consolidated order object
let previousOrderIds = new Set();
let soundEnabled = localStorage.getItem('kds_sound') !== 'false';
let isFirstLoad = true;
let isPolling = false;
let audioCtx = null;
let pollIntervalTimer = null;
let timerTickInterval = null;
let isHistoryOpen = false;
let expandedServedTables = new Set(); // Track tables where user clicked "See All Items"

/* ═══════════════════════════════════════════════════════════════
   DOM REFERENCES
   ═══════════════════════════════════════════════════════════════ */
const $newOrdersContainer     = document.getElementById('newOrdersContainer');
const $ongoingOrdersContainer = document.getElementById('ongoingOrdersContainer');
const $servedOrdersContainer  = document.getElementById('servedOrdersContainer');

const $newEmptyState          = document.getElementById('newEmptyState');
const $ongoingEmptyState      = document.getElementById('ongoingEmptyState');
const $servedEmptyState       = document.getElementById('servedEmptyState');

const $badgeNewOrders         = document.getElementById('badgeNewOrders');
const $badgeOngoingOrders     = document.getElementById('badgeOngoingOrders');
const $badgeServedOrders      = document.getElementById('badgeServedOrders');

const $statNewCount           = document.getElementById('statNewCount');
const $statOngoingCount       = document.getElementById('statOngoingCount');
const $statServedCount        = document.getElementById('statServedCount');
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
   - Consolidates all unbilled items per table into ONE single card!
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

  const incomingTableMap = {};

  orders.forEach(order => {
    // Only process unbilled orders
    if (order.payment_method) {
      return;
    }

    const tNo = String(order.table_no);

    if (!incomingTableMap[tNo]) {
      incomingTableMap[tNo] = order;
    } else {
      // Consolidate duplicate table orders if any exist into one
      const existing = incomingTableMap[tNo];
      existing.batches = (existing.batches || []).concat(order.batches || []);
      if (new Date(order.updated_at || order.created_at) > new Date(existing.updated_at || existing.created_at)) {
        existing.updated_at = order.updated_at;
        existing.status = order.status;
      }
    }

    // Detect brand new order arrival
    const oid = order.id;
    if (!previousOrderIds.has(oid) && !isFirstLoad) {
      hasBrandNewOrder = true;
      newOrderTable = order.table_no;
    }

    // Detect additional batch items added to existing order
    const prevOrder = Object.values(ordersCache).find(o => String(o.table_no) === tNo);
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

  // Re-calculate all_served per consolidated table
  Object.values(incomingTableMap).forEach(order => {
    let totalItems = 0;
    let servedItems = 0;
    (order.batches || []).forEach(b => {
      (b.items || []).forEach(it => {
        totalItems++;
        if (it.is_served) servedItems++;
      });
    });
    order.total_items = totalItems;
    order.served_count = servedItems;
    order.all_served = (totalItems > 0 && servedItems >= totalItems) || order.status === 'served';
    order.has_unserved = (totalItems > servedItems) && order.status !== 'served';
  });

  // Update active cache
  ordersCache = incomingTableMap;
  previousOrderIds = new Set(orders.map(o => o.id));

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
   3-PARTITION RENDER LOGIC
   1. Partition 1 (Left / Red): NEWEST ACTIVE ORDER
   2. Partition 2 (Middle / Orange): IN-PROGRESS ACTIVE ORDERS
   3. Partition 3 (Right / Green): FULLY SERVED TABLES
   ═══════════════════════════════════════════════════════════════ */
function renderKdsPartitions() {
  const allTables = Object.values(ordersCache);

  // Separate unserved vs fully served tables
  const unservedTables = allTables.filter(o => !o.all_served);
  const fullyServedTables = allTables.filter(o => !!o.all_served);

  // Sort unserved tables: newest updated/created timestamp FIRST
  unservedTables.sort((a, b) => {
    const aTime = new Date(a.updated_at || a.created_at).getTime();
    const bTime = new Date(b.updated_at || b.created_at).getTime();
    return bTime - aTime || b.id - a.id;
  });

  // Sort fully served tables: most recently served first
  fullyServedTables.sort((a, b) => {
    const aTime = new Date(a.served_at || a.updated_at || a.created_at).getTime();
    const bTime = new Date(b.served_at || b.updated_at || b.created_at).getTime();
    return bTime - aTime || b.id - a.id;
  });

  const newOrders = [];
  const ongoingOrders = [];

  if (unservedTables.length > 0) {
    // Partition 1 (Left): ONLY the single latest / newest active order!
    newOrders.push(unservedTables[0]);

    // Partition 2 (Middle): All other unserved in-progress tables
    for (let i = 1; i < unservedTables.length; i++) {
      ongoingOrders.push(unservedTables[i]);
    }
  }

  // Update header badges
  const totalCount = allTables.length;
  const newCount = newOrders.length;
  const ongoingCount = ongoingOrders.length;
  const servedCount = fullyServedTables.length;

  if ($statNewCount) $statNewCount.textContent = newCount;
  if ($statOngoingCount) $statOngoingCount.textContent = ongoingCount;
  if ($statServedCount) $statServedCount.textContent = servedCount;
  if ($statTotalActive) $statTotalActive.textContent = totalCount;

  if ($badgeNewOrders) $badgeNewOrders.textContent = newCount;
  if ($badgeOngoingOrders) $badgeOngoingOrders.textContent = ongoingCount;
  if ($badgeServedOrders) $badgeServedOrders.textContent = servedCount;

  const mAll = document.getElementById('mBadgeAll');
  const mNew = document.getElementById('mBadgeNew');
  const mOngoing = document.getElementById('mBadgeOngoing');
  const mServed = document.getElementById('mBadgeServed');
  if (mAll) mAll.textContent = totalCount;
  if (mNew) mNew.textContent = newCount;
  if (mOngoing) mOngoing.textContent = ongoingCount;
  if (mServed) mServed.textContent = servedCount;

  // Render 3 Partitions
  renderNewPartition(newOrders);
  renderOngoingPartition(ongoingOrders);
  renderServedPartition(fullyServedTables);

  if (window.innerWidth <= 768) {
    switchMobileKdsTab(currentMobileTab);
  }
}

let currentMobileTab = 'all';

function switchMobileKdsTab(tab) {
  currentMobileTab = tab;
  document.querySelectorAll('.m-tab').forEach(btn => btn.classList.remove('active'));
  const activeBtn = document.getElementById(
    tab === 'all' ? 'mTabAll' : 
    tab === 'new' ? 'mTabNew' : 
    tab === 'ongoing' ? 'mTabOngoing' : 'mTabServed'
  );
  if (activeBtn) activeBtn.classList.add('active');

  const pNew = document.getElementById('partitionNew');
  const pOngoing = document.getElementById('partitionOngoing');
  const pServed = document.getElementById('partitionServed');
  if (!pNew || !pOngoing || !pServed) return;

  if (tab === 'all') {
    pNew.style.display = '';
    pOngoing.style.display = '';
    pServed.style.display = '';
  } else if (tab === 'new') {
    pNew.style.display = 'flex';
    pOngoing.style.display = 'none';
    pServed.style.display = 'none';
  } else if (tab === 'ongoing') {
    pNew.style.display = 'none';
    pOngoing.style.display = 'flex';
    pServed.style.display = 'none';
  } else if (tab === 'served') {
    pNew.style.display = 'none';
    pOngoing.style.display = 'none';
    pServed.style.display = 'flex';
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
    const card = createKdsCard(order, true, false);
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
    const card = createKdsCard(order, false, false);
    $ongoingOrdersContainer.appendChild(card);
  });
}

function renderServedPartition(orders) {
  if (!$servedOrdersContainer || !$servedEmptyState) return;

  if (orders.length === 0) {
    $servedOrdersContainer.innerHTML = '';
    $servedEmptyState.style.display = 'flex';
    return;
  }

  $servedEmptyState.style.display = 'none';
  $servedOrdersContainer.innerHTML = '';

  orders.forEach(order => {
    const card = createKdsCard(order, false, true);
    $servedOrdersContainer.appendChild(card);
  });
}

/* ═══════════════════════════════════════════════════════════════
   CARD CREATION (HINDI KDS COMPONENT)
   - Single Consolidated Table Card
   - See All Served Items Toggle for Fully Served Tables
   - Unserved items ALWAYS on top
   ═══════════════════════════════════════════════════════════════ */
function createKdsCard(order, isNewPartition, isServedPartition) {
  const card = document.createElement('div');
  const isDelayed = checkIsDelayed(order);
  const isAllServed = !!order.all_served;
  const tNo = String(order.table_no);

  let cardClass = isNewPartition 
    ? 'kds-card kds-card-new' 
    : isServedPartition
      ? 'kds-card kds-card-served all-served'
      : `kds-card kds-card-ongoing${isDelayed ? ' delayed' : ''}`;
  
  if (isAllServed && !isServedPartition) {
    cardClass += ' all-served';
  }
  
  card.className = cardClass;
  card.dataset.table = tNo;
  card.dataset.id = order.id;

  const elapsedText = getElapsedFormatted(order.created_at);
  const prepTime = getEstimatedPrepTime(order);

  // Status badge text
  let statusBadgeHtml = '';
  if (isAllServed) {
    statusBadgeHtml = `<span class="status-tag tag-served">✓ परोसा गया (Served)</span>`;
  } else if (isNewPartition) {
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

  // Flatten all items across batches and SORT:
  // 1. Unserved items (is_served === 0) ON TOP
  // 2. Served items (is_served === 1) BELOW with Green & Dimmed Transparency
  const allItems = [];
  (order.batches || []).forEach(batch => {
    (batch.items || []).forEach(item => {
      allItems.push({ ...item, batch_id: batch.batch_id });
    });
  });

  allItems.sort((a, b) => {
    const aServed = a.is_served ? 1 : 0;
    const bServed = b.is_served ? 1 : 0;
    if (aServed !== bServed) {
      return aServed - bServed; // 0 (unserved) first
    }
    return (b.item_id || 0) - (a.item_id || 0);
  });

  // Check if fully served items should be collapsed
  const isExpanded = expandedServedTables.has(tNo) || !isAllServed;

  let itemsHtml = '';
  allItems.forEach(item => {
    const icon = CATEGORY_ICONS[item.category] || '🍽️';
    const dishHindi = item.name_hi || item.name || 'व्यंजन';
    const dishEnglish = item.name_en && item.name_en !== dishHindi ? item.name_en : '';
    const isItemServed = !!item.is_served;

    itemsHtml += `
      <div class="dish-row ${isItemServed ? 'dish-served' : 'dish-active'}" data-item-id="${item.item_id || 0}">
        <div class="dish-info">
          <span class="dish-icon">${icon}</span>
          <div class="dish-text">
            <span class="dish-name-hi">${dishHindi}</span>
            ${dishEnglish ? `<span class="dish-name-en">${escapeHtml(dishEnglish)}</span>` : ''}
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:0.6rem;">
          <span class="dish-qty-badge">× ${item.qty}</span>
          <button class="dish-check-btn ${isItemServed ? 'checked' : ''}" 
                  onclick="toggleItemServed('${tNo}', ${order.id}, ${item.item_id || 0}, event)" 
                  title="${isItemServed ? 'परोसा जा चुका है (क्लिक करके बदलें)' : 'परोसा गया चिह्नित करें'}">
            ${isItemServed ? '✓ परोसा गया' : 'परोसें ✓'}
          </button>
        </div>
      </div>
    `;
  });

  // "See All" summary toggle when all items are served
  let servedSummaryHtml = '';
  if (isAllServed) {
    servedSummaryHtml = `
      <div class="all-served-summary">
        <div class="served-summary-text">
          <span>✓</span>
          <span>सभी ${allItems.length} व्यंजन परोसे जा चुके हैं</span>
        </div>
        <button class="toggle-items-btn" onclick="toggleTableItemsCollapse('${tNo}', event)">
          ${isExpanded ? '▲ विवरण छिपाएँ (Hide)' : `👁️ सभी व्यंजन देखें (${allItems.length} Items)`}
        </button>
      </div>
    `;
  }

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

    ${servedSummaryHtml}

    <div class="card-items-body" style="${!isExpanded && isAllServed ? 'display:none;' : ''}">
      ${itemsHtml}
    </div>

    <div class="card-actions-bar">
      <button class="kds-serve-btn ${isAllServed ? 'is-served' : ''}" 
              onclick="toggleOrderServed('${tNo}', ${order.id}, event)">
        ${isAllServed ? '✓ पूरा टेबल परोसा गया (वापस खोलें)' : '✓ परोसा गया (Mark as Served)'}
      </button>
    </div>

    <div class="card-bottom-info">
      <span class="order-ref-num">${order.order_ref || ''}</span>
      <span style="font-size:0.85rem;font-weight:700;color:${isAllServed ? '#4ade80' : 'var(--text-sub)'};">
        ${order.total_items ? `कुल: ${order.total_items} व्यंजन | परोसे: ${order.served_count || 0}` : ''}
      </span>
    </div>
  `;

  return card;
}

/* ═══════════════════════════════════════════════════════════════
   TOGGLE ITEMS COLLAPSE (SEE ALL ITEMS)
   ═══════════════════════════════════════════════════════════════ */
function toggleTableItemsCollapse(tableNo, evt) {
  if (evt) evt.stopPropagation();
  const tStr = String(tableNo);
  if (expandedServedTables.has(tStr)) {
    expandedServedTables.delete(tStr);
  } else {
    expandedServedTables.add(tStr);
  }
  renderKdsPartitions();
}

/* ═══════════════════════════════════════════════════════════════
   SERVE ACTIONS (WHOLE ORDER & INDIVIDUAL ITEMS)
   ═══════════════════════════════════════════════════════════════ */
async function toggleOrderServed(tableNo, orderId, evt) {
  if (evt) evt.stopPropagation();

  const order = ordersCache[String(tableNo)];
  if (!order) return;

  const willBeServed = !order.all_served;

  // Optimistic UI update
  order.all_served = willBeServed;
  order.status = willBeServed ? 'served' : 'preparing';
  (order.batches || []).forEach(b => {
    (b.items || []).forEach(i => {
      i.is_served = willBeServed ? 1 : 0;
    });
  });

  renderKdsPartitions();

  try {
    const resp = await fetch(`${API_BASE}update_status.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        order_id: orderId,
        action: 'toggle_served'
      })
    });
    const data = await resp.json();
    if (!data.success) {
      console.error('Failed to toggle order status:', data.error);
    }
  } catch (err) {
    console.error('Error toggling order status:', err);
  }
}

async function toggleItemServed(tableNo, orderId, itemId, evt) {
  if (evt) evt.stopPropagation();

  const order = ordersCache[String(tableNo)];
  if (!order) return;

  let targetItem = null;
  (order.batches || []).forEach(b => {
    (b.items || []).forEach(i => {
      if (i.item_id === itemId) {
        targetItem = i;
      }
    });
  });

  if (targetItem) {
    targetItem.is_served = targetItem.is_served ? 0 : 1;

    // Check if all items are served
    let total = 0;
    let served = 0;
    (order.batches || []).forEach(b => {
      (b.items || []).forEach(i => {
        total++;
        if (i.is_served) served++;
      });
    });

    order.all_served = (total > 0 && served >= total);
    order.status = order.all_served ? 'served' : 'preparing';
    order.served_count = served;
    order.total_items = total;

    renderKdsPartitions();
  }

  try {
    const resp = await fetch(`${API_BASE}update_status.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        order_id: orderId,
        item_id: itemId,
        action: 'toggle_item_served'
      })
    });
    const data = await resp.json();
    if (!data.success) {
      console.error('Failed to toggle item status:', data.error);
    }
  } catch (err) {
    console.error('Error toggling item status:', err);
  }
}

/* ═══════════════════════════════════════════════════════════════
   LIVE TIMERS (Tick every 1 second)
   ═══════════════════════════════════════════════════════════════ */
function updateLiveTimers() {
  document.querySelectorAll('.meta-live-timer[data-created]').forEach(el => {
    const createdStr = el.dataset.created;
    el.textContent = getElapsedFormatted(createdStr);

    const card = el.closest('.kds-card');
    if (card && !card.classList.contains('kds-card-new') && !card.classList.contains('all-served')) {
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
   KITCHEN SECURITY & AUTHENTICATION (Kitchen@414301)
   ═══════════════════════════════════════════════════════════════ */
const KITCHEN_MASTER_PASS = 'Kitchen@414301';
const KITCHEN_AUTH_KEY = 'hotel_tulsi_kitchen_session';

function isKitchenAuthenticated() {
  try {
    const s = sessionStorage.getItem(KITCHEN_AUTH_KEY) || localStorage.getItem(KITCHEN_AUTH_KEY);
    return s === 'HOTEL_TULSI_KITCHEN_UNLOCKED_414301';
  } catch (_) {
    return false;
  }
}

function handleKitchenUnlock(e) {
  if (e) e.preventDefault();
  const inp = document.getElementById('kitchenPassInput');
  const err = document.getElementById('kitchenLockError');
  const overlay = document.getElementById('kitchenLockOverlay');
  if (!inp) return;

  const val = inp.value.trim();
  if (val === KITCHEN_MASTER_PASS) {
    try {
      sessionStorage.setItem(KITCHEN_AUTH_KEY, 'HOTEL_TULSI_KITCHEN_UNLOCKED_414301');
      localStorage.setItem(KITCHEN_AUTH_KEY, 'HOTEL_TULSI_KITCHEN_UNLOCKED_414301');
    } catch (_) {}
    if (overlay) overlay.style.display = 'none';
    if (err) err.style.display = 'none';
    startKdsEngine();
  } else {
    if (err) {
      err.textContent = '❌ गलत पासवर्ड! कृपया सही पासवर्ड दर्ज करें।';
      err.style.display = 'block';
    }
    inp.focus();
    inp.select();
  }
}

function toggleKitchenPassVisibility() {
  const inp = document.getElementById('kitchenPassInput');
  if (!inp) return;
  inp.type = inp.type === 'password' ? 'text' : 'password';
}

function lockKitchen() {
  try {
    sessionStorage.removeItem(KITCHEN_AUTH_KEY);
    localStorage.removeItem(KITCHEN_AUTH_KEY);
  } catch (_) {}
  const overlay = document.getElementById('kitchenLockOverlay');
  const inp = document.getElementById('kitchenPassInput');
  const err = document.getElementById('kitchenLockError');
  if (err) err.style.display = 'none';
  if (inp) inp.value = '';
  if (overlay) overlay.style.display = 'flex';
  if (pollIntervalTimer) { clearInterval(pollIntervalTimer); pollIntervalTimer = null; }
  if (timerTickInterval) { clearInterval(timerTickInterval); timerTickInterval = null; }
  isPolling = false;
}

function checkKitchenAccess() {
  const overlay = document.getElementById('kitchenLockOverlay');
  if (isKitchenAuthenticated()) {
    if (overlay) overlay.style.display = 'none';
    startKdsEngine();
  } else {
    if (overlay) overlay.style.display = 'flex';
    const inp = document.getElementById('kitchenPassInput');
    if (inp) inp.focus();
  }
}

window.handleKitchenUnlock = handleKitchenUnlock;
window.toggleKitchenPassVisibility = toggleKitchenPassVisibility;
window.lockKitchen = lockKitchen;
window.checkKitchenAccess = checkKitchenAccess;

/* ═══════════════════════════════════════════════════════════════
   INITIALIZATION
   ═══════════════════════════════════════════════════════════════ */
function startKdsEngine() {
  if (isPolling) return;
  isPolling = true;

  checkAudioAutoplay();
  fetchKitchenOrders();

  if (!pollIntervalTimer) pollIntervalTimer = setInterval(fetchKitchenOrders, POLL_INTERVAL);
  if (!timerTickInterval) timerTickInterval = setInterval(updateLiveTimers, 1000);
}

// Check kitchen auth before starting engine
checkKitchenAccess();
