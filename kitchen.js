/**
 * kitchen.js — Golden Stone Hotel Kitchen Dashboard
 * Real-time order management via AJAX polling
 */
'use strict';

/* ═══════ CONFIG ═══════ */
const API_BASE = 'api/';
const POLL_INTERVAL = 3000;
const CATEGORY_ICONS = {
  starter: '🍽', main: '🍛', bread: '🫓', rice: '🍚',
  beverage: '🥤', dessert: '🍰', salad: '🥗', side: '🥣', water: '💧'
};
const PREP_TIMES = {
  starter: 5, main: 10, bread: 5, rice: 8,
  beverage: 2, dessert: 3, salad: 4, side: 4, water: 1
};
const STATUS_LABELS = { new: 'New Order', preparing: 'Preparing', ready: 'Ready', served: 'Served' };

/* ═══════ STATE ═══════ */
let ordersCache = {};
let lastPollTimestamp = 0;
let activeFilter = 'all';
let searchQuery = '';
let soundEnabled = true;
let pollTimer = null;
let timerInterval = null;
let isHistoryMode = false;

/* ═══════ DOM REFS ═══════ */
const $grid       = document.getElementById('ordersGrid');
const $readyPanel = document.getElementById('readyPanel');
const $readyGrid  = document.getElementById('readyGrid');
const $searchInput= document.getElementById('searchInput');
const $notifBanner= document.getElementById('notifBanner');
const $modal      = document.getElementById('timelineModal');
const $emptyState = document.getElementById('emptyState');

// History & Navigation
const $homeView         = document.getElementById('homeView');
const $historyView      = document.getElementById('historyView');
const $historyGrid      = document.getElementById('historyGrid');
const $historyEmptyState= document.getElementById('historyEmptyState');
const $btnHome          = document.getElementById('btnHome');
const $btnHistory       = document.getElementById('btnHistory');

/* ═══════ CLOCK ═══════ */
function updateClock() {
  const now = new Date();
  document.getElementById('clock').textContent = now.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
}
setInterval(updateClock, 1000);
updateClock();

/* ═══════ SOUND ═══════ */
function playNotificationSound() {
  if (!soundEnabled) return;
  try {
    const ac = new (window.AudioContext || window.webkitAudioContext)();
    [660, 880, 1100].forEach((freq, i) => {
      const osc = ac.createOscillator();
      const gain = ac.createGain();
      osc.type = 'sine';
      osc.frequency.value = freq;
      gain.gain.setValueAtTime(0.15, ac.currentTime + i * 0.15);
      gain.gain.exponentialRampToValueAtTime(0.001, ac.currentTime + i * 0.15 + 0.3);
      osc.connect(gain).connect(ac.destination);
      osc.start(ac.currentTime + i * 0.15);
      osc.stop(ac.currentTime + i * 0.15 + 0.3);
    });
  } catch (e) { /* ignore audio errors */ }
}

function toggleSound() {
  soundEnabled = !soundEnabled;
  const btn = document.getElementById('soundBtn');
  btn.textContent = soundEnabled ? '🔔 Sound ON' : '🔕 Sound OFF';
  btn.classList.toggle('active', soundEnabled);
}

/* ═══════ MONITOR MODE ═══════ */
function checkMonitorMode() {
  if (new URLSearchParams(location.search).get('mode') === 'monitor') {
    document.body.classList.add('monitor-mode');
  }
}
checkMonitorMode();

/* ═══════ POLLING ═══════ */
async function fetchOrders() {
  if (isHistoryMode) return; // Pause polling while in History tab

  try {
    const url = `${API_BASE}get_orders.php?since=${lastPollTimestamp}&status=all`;
    const resp = await fetch(url);
    if (!resp.ok) throw new Error('Network error');
    const data = await resp.json();
    if (!data.success) throw new Error(data.error || 'API error');

    lastPollTimestamp = data.timestamp || Math.floor(Date.now() / 1000);
    processOrders(data.orders || []);
  } catch (err) {
    console.warn('Poll error:', err.message);
  }
}

async function fetchStats() {
  try {
    const resp = await fetch(`${API_BASE}get_stats.php`);
    const data = await resp.json();
    if (data.success) {
      document.getElementById('statTotal').textContent = data.total_today;
      document.getElementById('statActive').textContent = data.active;
      document.getElementById('statNew').textContent = data.new;
      document.getElementById('statPreparing').textContent = data.preparing;
      document.getElementById('statReady').textContent = data.ready;
      document.getElementById('statServed').textContent = data.served;
    }
  } catch (e) { /* ignore */ }
}

function processOrders(orders) {
  let hasNewOrder = false;
  const previousIds = new Set(Object.keys(ordersCache).map(Number));

  orders.forEach(order => {
    const prevOrder = ordersCache[order.id];
    const isNew = !prevOrder && order.status === 'new';
    if (isNew && previousIds.size > 0) hasNewOrder = true;

    // Check for new items in existing orders
    if (prevOrder) {
      const prevBatchIds = new Set();
      (prevOrder.batches || []).forEach(b => prevBatchIds.add(b.batch_id));
      (order.batches || []).forEach(b => {
        if (!prevBatchIds.has(b.batch_id)) hasNewOrder = true;
      });
    }

    ordersCache[order.id] = order;
  });

  if (hasNewOrder) {
    playNotificationSound();
    showNotification();
  }

  renderAll();
}

/* ═══════ NOTIFICATION ═══════ */
function showNotification() {
  $notifBanner.classList.add('show');
  setTimeout(() => $notifBanner.classList.remove('show'), 4000);
}

/* ═══════ RENDER ═══════ */
function renderAll() {
  const allOrders = Object.values(ordersCache);
  const activeOrders = [];
  const readyOrders = [];

  allOrders.forEach(o => {
    if (o.status === 'served') return; // Served orders are handled in History view
    if (o.status === 'ready') { readyOrders.push(o); activeOrders.push(o); }
    else activeOrders.push(o);
  });

  renderReadyPanel(readyOrders);
  renderOrderCards(activeOrders);
  fetchStats();
}

function matchesFilter(order) {
  if (activeFilter !== 'all' && order.status !== activeFilter) return false;
  if (searchQuery) {
    const q = searchQuery.toLowerCase();
    const matchTable = ('table ' + order.table_no).toLowerCase().includes(q) || order.table_no.toString().toLowerCase().includes(q);
    const matchRef = (order.order_ref || '').toLowerCase().includes(q);
    if (!matchTable && !matchRef) return false;
  }
  return true;
}

function renderReadyPanel(readyOrders) {
  $readyPanel.classList.toggle('has-items', readyOrders.length > 0);
  $readyGrid.innerHTML = '';
  readyOrders.forEach(order => {
    const totalItems = countItems(order);
    const readyTime = order.ready_at ? formatTime(order.ready_at) : '--';
    const card = document.createElement('div');
    card.className = 'ready-card';
    card.innerHTML = `
      <div class="ready-table">T${order.table_no}</div>
      <div class="ready-time">Ready at ${readyTime}</div>
      <div class="ready-items">${totalItems} item${totalItems !== 1 ? 's' : ''}</div>
      <button class="btn-served" onclick="updateStatus(${order.id},'served')">✓ MARK SERVED</button>
    `;
    $readyGrid.appendChild(card);
  });
}

function renderOrderCards(orders) {
  $grid.innerHTML = '';
  const filtered = orders.filter(o => o.status !== 'served' && matchesFilter(o));

  // Sort: new first, then preparing, then ready
  filtered.sort((a, b) => {
    const order = { new: 0, preparing: 1, ready: 2 };
    const diff = (order[a.status] ?? 3) - (order[b.status] ?? 3);
    if (diff !== 0) return diff;
    return new Date(b.created_at) - new Date(a.created_at);
  });

  $emptyState.style.display = filtered.length === 0 ? 'block' : 'none';

  filtered.forEach(order => {
    const card = createOrderCard(order);
    $grid.appendChild(card);
  });
}

function createOrderCard(order) {
  const card = document.createElement('div');
  const isDelayed = checkDelayed(order);
  const statusClass = isDelayed ? 'status-delayed' : `status-${order.status}`;
  const isNew = !card.dataset.seen && order.status === 'new';
  card.className = `order-card ${statusClass}${isNew ? ' new-arrival' : ''}`;
  card.dataset.id = order.id;

  const badgeClass = isDelayed ? 'badge-delayed' : `badge-${order.status}`;
  const badgeText = isDelayed ? '⚠ DELAYED' : STATUS_LABELS[order.status];
  const elapsed = getElapsed(order);
  const timerClass = isDelayed ? 'timer overdue' : 'timer';
  const estPrep = getEstPrepTime(order);

  let itemsHtml = '';
  (order.batches || []).forEach(batch => {
    (batch.items || []).forEach(item => {
      const icon = CATEGORY_ICONS[item.category] || '🍽';
      const newBadge = item.is_new ? '<span class="new-item-badge">NEW</span>' : '';
      itemsHtml += `<div class="item-row">
        <span class="item-name"><span class="item-cat-icon">${icon}</span> ${item.name}${newBadge}</span>
        <span class="item-qty">× ${item.qty}</span>
      </div>`;
    });
  });

  const noteHtml = order.customer_note ? `<div class="customer-note">${escHtml(order.customer_note)}</div>` : '';

  let actionsHtml = '';
  if (order.status === 'new') {
    actionsHtml = `<button class="action-btn btn-prepare" onclick="updateStatus(${order.id},'preparing')">▶ Start Preparing</button>`;
  } else if (order.status === 'preparing') {
    actionsHtml = `<button class="action-btn btn-ready" onclick="updateStatus(${order.id},'ready')">✓ Mark Ready</button>`;
  } else if (order.status === 'ready') {
    actionsHtml = `<button class="action-btn btn-serve" onclick="updateStatus(${order.id},'served')">✓ Mark Served</button>`;
  }

  card.innerHTML = `
    <div class="card-header">
      <div>
        <div class="table-label">TABLE</div>
        <div class="table-number">${order.table_no}</div>
      </div>
      <span class="status-badge ${badgeClass}">${badgeText}</span>
    </div>
    <div class="card-meta">
      <span class="meta-item"><span class="meta-icon">👥</span> ${order.persons}</span>
      <span class="meta-item"><span class="meta-icon">⏱</span> <span class="${timerClass}" data-created="${order.created_at}">${elapsed}</span></span>
      <span class="meta-item" style="font-family:var(--ff-mono);font-size:.7rem;color:var(--text-muted)">${order.order_ref || ''}</span>
    </div>
    ${noteHtml}
    <div class="card-items">${itemsHtml}</div>
    <div class="card-footer">
      <div class="prep-time">Est. prep: ${estPrep} min</div>
      ${actionsHtml}
    </div>
    <div class="card-ref" onclick="showTimeline(${order.id})">📋 View Order Timeline</div>
  `;

  return card;
}

/* ═══════ HISTORY VIEW LOGIC ═══════ */
function showHome() {
  isHistoryMode = false;
  $homeView.style.display = 'block';
  $historyView.style.display = 'none';
  $btnHome.classList.add('active');
  $btnHistory.classList.remove('active');
  // Immediately fetch orders when returning to home
  fetchOrders();
}

function showHistory() {
  isHistoryMode = true;
  $homeView.style.display = 'none';
  $historyView.style.display = 'block';
  $btnHistory.classList.add('active');
  $btnHome.classList.remove('active');
  loadHistory();
}

async function loadHistory() {
  $historyGrid.innerHTML = '<p style="color:var(--text-muted); grid-column:1/-1; text-align:center;">Loading history...</p>';
  $historyEmptyState.style.display = 'none';

  try {
    const resp = await fetch(`${API_BASE}get_history.php`);
    if (!resp.ok) throw new Error('Network error');
    const data = await resp.json();
    if (!data.success) throw new Error(data.error || 'API error');

    renderHistoryCards(data.orders || []);
  } catch (err) {
    console.warn('History fetch error:', err.message);
    $historyGrid.innerHTML = `<p style="color:var(--red); grid-column:1/-1; text-align:center;">Failed to load history: ${err.message}</p>`;
  }
}

function renderHistoryCards(orders) {
  $historyGrid.innerHTML = '';
  
  if (orders.length === 0) {
    $historyEmptyState.style.display = 'block';
    return;
  }
  
  $historyEmptyState.style.display = 'none';
  
  orders.forEach(order => {
    // We re-use createOrderCard but force it to look like a served order
    // createOrderCard uses the actual order object
    const card = createOrderCard(order);
    $historyGrid.appendChild(card);
  });
}

/* ═══════ ACTIONS ═══════ */
async function updateStatus(orderId, newStatus) {
  try {
    const resp = await fetch(`${API_BASE}update_status.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ order_id: orderId, status: newStatus })
    });
    const data = await resp.json();
    if (data.success && ordersCache[orderId]) {
      ordersCache[orderId].status = newStatus;
      ordersCache[orderId].updated_at = data.updated_at;
      if (newStatus === 'preparing') ordersCache[orderId].prep_started_at = data.updated_at;
      if (newStatus === 'ready') ordersCache[orderId].ready_at = data.updated_at;
      if (newStatus === 'served') ordersCache[orderId].served_at = data.updated_at;
      renderAll();
    }
  } catch (e) { console.error('Status update failed:', e); }
}

async function acknowledgeItems(orderId, batchId) {
  try {
    await fetch(`${API_BASE}acknowledge_items.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ order_id: orderId, batch_id: batchId })
    });
  } catch (e) { /* ignore */ }
}

/* ═══════ TIMELINE MODAL ═══════ */
function showTimeline(orderId) {
  const order = ordersCache[orderId];
  if (!order) return;

  const $box = $modal.querySelector('.modal-box');
  document.getElementById('modalTitle').textContent = `Order Timeline — Table ${order.table_no}`;

  let html = '';
  (order.status_log || []).forEach(entry => {
    const time = formatTime(entry.changed_at);
    const label = STATUS_LABELS[entry.status] || entry.status;
    html += `<div class="timeline-entry t-${entry.status}">
      <div>
        <div class="timeline-status">${label}</div>
        <div class="timeline-time">${time}${entry.note ? ' — ' + escHtml(entry.note) : ''}</div>
      </div>
    </div>`;
  });
  document.getElementById('timelineContent').innerHTML = html || '<p style="color:var(--text-muted)">No timeline data</p>';
  $modal.classList.add('show');
}
function closeModal() { $modal.classList.remove('show'); }

/* ═══════ FILTERS ═══════ */
document.querySelectorAll('.filter-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelector('.filter-tab.active').classList.remove('active');
    tab.classList.add('active');
    activeFilter = tab.dataset.filter;
    renderAll();
  });
});

$searchInput.addEventListener('input', () => {
  searchQuery = $searchInput.value.trim();
  renderAll();
});

/* ═══════ SERVED SECTION TOGGLE ═══════ */
document.getElementById('servedTitle').addEventListener('click', () => {
  $servedList.classList.toggle('open');
});

/* ═══════ HELPERS ═══════ */
function countItems(order) {
  let total = 0;
  (order.batches || []).forEach(b => (b.items || []).forEach(i => total += i.qty));
  return total;
}

function getEstPrepTime(order) {
  let maxPrep = 0;
  (order.batches || []).forEach(b => (b.items || []).forEach(i => {
    maxPrep = Math.max(maxPrep, PREP_TIMES[i.category] || 5);
  }));
  return maxPrep;
}

function checkDelayed(order) {
  if (order.status === 'served' || order.status === 'ready') return false;
  const created = new Date(order.created_at).getTime();
  const elapsedMin = (Date.now() - created) / 60000;
  return elapsedMin > getEstPrepTime(order);
}

function getElapsed(order) {
  const created = new Date(order.created_at).getTime();
  const diff = Math.max(0, Math.floor((Date.now() - created) / 1000));
  const m = Math.floor(diff / 60);
  const s = diff % 60;
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
}

function formatTime(dateStr) {
  if (!dateStr) return '--';
  const d = new Date(dateStr);
  return d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true });
}

function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}

/* ═══════ LIVE TIMER UPDATES ═══════ */
function updateTimers() {
  document.querySelectorAll('.timer[data-created]').forEach(el => {
    const created = new Date(el.dataset.created).getTime();
    const diff = Math.max(0, Math.floor((Date.now() - created) / 1000));
    const m = Math.floor(diff / 60);
    const s = diff % 60;
    el.textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
  });
}

/* ═══════ START ═══════ */
function startPolling() {
  fetchOrders();
  pollTimer = setInterval(fetchOrders, POLL_INTERVAL);
  timerInterval = setInterval(updateTimers, 1000);
}

// Initial load
startPolling();
