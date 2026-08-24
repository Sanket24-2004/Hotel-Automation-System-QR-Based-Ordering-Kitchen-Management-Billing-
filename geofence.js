/**
 * ═══════════════════════════════════════════════════════════════
 * geofence.js — Golden Stone / Hotel Tulsi Order Security & Geofence
 * Multi-layer security against unauthorized / leaked QR orders:
 *   1. Hotel Local Wi-Fi Detection (Seamless Zero-Prompt)
 *   2. GPS Geofencing with indoor accuracy tolerance
 *   3. 4-Digit Table / Waiter Passcode Bypass (Indoor Fallback)
 * ═══════════════════════════════════════════════════════════════
 */

'use strict';

window.HotelSecurity = (function() {
  let cachedConfig = null;

  function calculateDistanceMeters(lat1, lon1, lat2, lon2) {
    const R = 6371000; // Earth radius in meters
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
  }

  async function getSecurityConfig() {
    if (cachedConfig) return cachedConfig;
    try {
      const res = await fetch('api/get_security_settings.php');
      const d = await res.json();
      if (d.success) {
        cachedConfig = d;
        return cachedConfig;
      }
    } catch (e) {
      console.warn('Could not fetch security config, allowing order:', e);
    }
    return { geofence_enabled: false };
  }

  // Ensure modal UI exists on page
  function injectSecurityModal() {
    if (document.getElementById('securityModalOverlay')) return;

    const modalHtml = `
      <div id="securityModalOverlay" style="
        display: none; position: fixed; inset: 0; z-index: 999999;
        background: rgba(0,0,0,0.85); backdrop-filter: blur(8px);
        align-items: center; justify-content: center; padding: 1.2rem;
      ">
        <div style="
          background: linear-gradient(145deg, #1f180e, #140f07);
          border: 1px solid rgba(212,168,50,0.4);
          border-radius: 16px; width: 100%; max-width: 400px;
          padding: 1.6rem; color: #fff; text-align: center;
          box-shadow: 0 20px 50px rgba(0,0,0,0.9);
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        ">
          <div style="font-size: 2.8rem; margin-bottom: 0.5rem;" id="secModalIcon">📍</div>
          <h3 id="secModalTitle" style="color: #f5d48a; font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">Location Verification</h3>
          <p id="secModalMsg" style="font-size: 0.88rem; color: rgba(255,255,255,0.75); line-height: 1.45; margin-bottom: 1.2rem;">
            Please verify you are dining at Hotel Tulsi to send your order to the kitchen.
          </p>

          <!-- Passcode Input Form -->
          <div id="secPasscodeSection" style="margin-bottom: 1.2rem;">
            <label style="display: block; font-size: 0.78rem; text-transform: uppercase; color: #d4a832; letter-spacing: 0.08em; margin-bottom: 6px; font-weight: 600;">
              Enter 4-Digit Table PIN
            </label>
            <input type="password" id="secPasscodeInput" maxlength="8" placeholder="••••" style="
              width: 160px; text-align: center; font-size: 1.5rem; letter-spacing: 0.3em;
              padding: 0.5rem 0.8rem; background: rgba(0,0,0,0.6);
              border: 1px solid rgba(212,168,50,0.5); border-radius: 10px;
              color: #fff; outline: none; margin: 0 auto; display: block;
            "/>
            <p id="secPasscodeError" style="color: #ef4444; font-size: 0.78rem; margin-top: 6px; display: none;"></p>
            <p style="font-size: 0.74rem; color: rgba(255,255,255,0.45); margin-top: 6px;">
              💡 Ask your waiter or check your table card for the code.
            </p>
          </div>

          <div style="display: flex; gap: 8px; justify-content: center;">
            <button id="secBtnCancel" style="
              flex: 1; padding: 0.75rem 1rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.2);
              background: transparent; color: rgba(255,255,255,0.7); font-weight: 600; cursor: pointer;
            ">Cancel</button>
            <button id="secBtnVerify" style="
              flex: 1; padding: 0.75rem 1rem; border-radius: 10px; border: none;
              background: linear-gradient(135deg, #d4a832, #b8901e); color: #000; font-weight: 700; cursor: pointer;
            ">Unlock &amp; Order</button>
          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
  }

  function promptPasscodeFallback(reasonMessage) {
    injectSecurityModal();
    const overlay = document.getElementById('securityModalOverlay');
    const msgEl   = document.getElementById('secModalMsg');
    const input   = document.getElementById('secPasscodeInput');
    const errEl   = document.getElementById('secPasscodeError');
    const btnVer  = document.getElementById('secBtnVerify');
    const btnCan  = document.getElementById('secBtnCancel');

    if (reasonMessage) msgEl.innerHTML = reasonMessage;
    errEl.style.display = 'none';
    input.value = '';
    overlay.style.display = 'flex';
    input.focus();

    return new Promise((resolve, reject) => {
      btnCan.onclick = function() {
        overlay.style.display = 'none';
        reject(new Error('Location verification cancelled.'));
      };

      async function doVerify() {
        const code = input.value.trim();
        if (!code) {
          errEl.textContent = 'Please enter the 4-digit table code.';
          errEl.style.display = 'block';
          return;
        }

        btnVer.textContent = 'Verifying...';
        btnVer.disabled = true;
        try {
          const res = await fetch('api/verify_security_passcode.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ passcode: code })
          });
          const d = await res.json();
          btnVer.textContent = 'Unlock & Order';
          btnVer.disabled = false;

          if (d.success) {
            sessionStorage.setItem('hotel_security_token', d.token);
            overlay.style.display = 'none';
            resolve({ security_token: d.token, client_lat: 0, client_lng: 0 });
          } else {
            errEl.textContent = d.error || 'Incorrect passcode.';
            errEl.style.display = 'block';
          }
        } catch (e) {
          btnVer.textContent = 'Unlock & Order';
          btnVer.disabled = false;
          errEl.textContent = 'Verification error. Try again.';
          errEl.style.display = 'block';
        }
      }

      btnVer.onclick = doVerify;
      input.onkeydown = function(e) {
        if (e.key === 'Enter') doVerify();
      };
    });
  }

  /**
   * Main verification entry point. Call before submitting an order.
   * Returns a promise resolving with payload security properties: { security_token, client_lat, client_lng }
   */
  async function verifyOrderLocation() {
    const config = await getSecurityConfig();

    // 1. If security is disabled, pass immediately
    if (!config.geofence_enabled) {
      return { security_token: '', client_lat: 0, client_lng: 0 };
    }

    // 2. If connected to Hotel Wi-Fi, pass immediately
    if (config.wifi_bypass_enabled && config.is_on_hotel_wifi) {
      return { security_token: 'WIFI_VERIFIED', client_lat: 0, client_lng: 0 };
    }

    // 3. If session already unlocked via valid token, pass immediately
    const existingToken = sessionStorage.getItem('hotel_security_token');
    if (existingToken) {
      return { security_token: existingToken, client_lat: 0, client_lng: 0 };
    }

    // 4. Check Browser GPS
    if (navigator.geolocation) {
      try {
        const position = await new Promise((resolve, reject) => {
          navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: true,
            timeout: 6000,
            maximumAge: 30000
          });
        });

        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const acc = position.coords.accuracy || 20;

        const dist = calculateDistanceMeters(lat, lng, config.hotel_lat, config.hotel_lng);
        // Effective distance factoring indoor satellite inaccuracy
        const effectiveDist = Math.max(0, dist - (acc / 2));

        if (effectiveDist <= config.radius_meters + 50) {
          return { security_token: '', client_lat: lat, client_lng: lng };
        } else {
          // Outside hotel range -> show passcode fallback
          return await promptPasscodeFallback(
            `⚠️ You appear to be <strong>${Math.round(dist)}m</strong> away from Hotel Tulsi.<br/>If you are dining inside, enter your <strong>Table PIN</strong> below to continue.`
          );
        }
      } catch (geoErr) {
        // Geolocation denied, timed out, or unavailable indoors
        return await promptPasscodeFallback(
          `📍 GPS signal is weak indoors.<br/>Please enter the <strong>4-digit Table PIN</strong> provided by your waiter to place your order.`
        );
      }
    } else {
      return await promptPasscodeFallback(
        `📍 Location is unsupported on this browser.<br/>Please enter your <strong>Table PIN</strong> to continue.`
      );
    }
  }

  // Automatic session check: if table has no active occupation/bill in database,
  // clear confirmed orders and local cart to release customer device session.
  async function autoClearReleasedTableSession() {
    const params = new URLSearchParams(window.location.search);
    const table = params.get('table') || sessionStorage.getItem('hotel_table');
    if (!table || table === '—') return;

    try {
      const res = await fetch('api/get_active_bill.php?table=' + encodeURIComponent(table));
      const d = await res.json();
      if (d.success && !d.exists && !d.occupied_at) {
        const orderKey = 'hotel_confirmed_orders_' + table;
        sessionStorage.removeItem(orderKey);
        sessionStorage.removeItem('hotel_security_token');
        console.log('Released session for Table ' + table + ' because it was cleared in database.');
      }
    } catch (e) {
      console.warn('Failed to verify table session status:', e);
    }
  }

  // Hook into DOM loaded to auto-run the session clean check
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', autoClearReleasedTableSession);
  } else {
    autoClearReleasedTableSession();
  }

  return {
    verifyOrderLocation,
    getSecurityConfig
  };
})();
