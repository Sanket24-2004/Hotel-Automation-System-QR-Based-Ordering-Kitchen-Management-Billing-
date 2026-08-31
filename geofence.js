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

/* ── Table Security Token Module (Option A) ── */
window.TableSecurity = window.TableSecurity || (function() {
  const SECRET_SALT = 'HotelTulsi_Table_Token_Secret_2026_';

  function md5(string) {
    function rotateLeft(lValue, iShiftBits) { return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits)); }
    function addUnsigned(lX, lY) {
      var lX4, lY4, lX8, lY8, lResult;
      lX8 = (lX & 0x80000000); lY8 = (lY & 0x80000000);
      lX4 = (lX & 0x40000000); lY4 = (lY & 0x40000000);
      lResult = (lX & 0x3FFFFFFF) + (lY & 0x3FFFFFFF);
      if (lX4 & lY4) return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
      if (lX4 | lY4) {
        if (lResult & 0x40000000) return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
        else return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
      } else { return (lResult ^ lX8 ^ lY8); }
    }
    function F(x, y, z) { return (x & y) | ((~x) & z); }
    function G(x, y, z) { return (x & z) | (y & (~z)); }
    function H(x, y, z) { return (x ^ y ^ z); }
    function I(x, y, z) { return (y ^ (x | (~z))); }
    function FF(a, b, c, d, x, s, ac) { a = addUnsigned(a, addUnsigned(addUnsigned(F(b, c, d), x), ac)); return addUnsigned(rotateLeft(a, s), b); }
    function GG(a, b, c, d, x, s, ac) { a = addUnsigned(a, addUnsigned(addUnsigned(G(b, c, d), x), ac)); return addUnsigned(rotateLeft(a, s), b); }
    function HH(a, b, c, d, x, s, ac) { a = addUnsigned(a, addUnsigned(addUnsigned(H(b, c, d), x), ac)); return addUnsigned(rotateLeft(a, s), b); }
    function II(a, b, c, d, x, s, ac) { a = addUnsigned(a, addUnsigned(addUnsigned(I(b, c, d), x), ac)); return addUnsigned(rotateLeft(a, s), b); }
    function convertToWordArray(string) {
      var lWordCount;
      var lMessageLength = string.length;
      var lNumberOfWords_temp1 = lMessageLength + 8;
      var lNumberOfWords_temp2 = (lNumberOfWords_temp1 - (lNumberOfWords_temp1 % 64)) / 64;
      var lNumberOfWords = (lNumberOfWords_temp2 + 1) * 16;
      var lWordArray = Array(lNumberOfWords - 1);
      var lBytePosition = 0;
      var lByteCount = 0;
      while (lByteCount < lMessageLength) {
        lWordCount = (lByteCount - (lByteCount % 4)) / 4;
        lBytePosition = (lByteCount % 4) * 8;
        lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount) << lBytePosition));
        lByteCount++;
      }
      lWordCount = (lByteCount - (lByteCount % 4)) / 4;
      lBytePosition = (lByteCount % 4) * 8;
      lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition);
      lWordArray[lNumberOfWords - 2] = lMessageLength << 3;
      lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29;
      return lWordArray;
    }
    function wordToHex(lValue) {
      var WordToHexValue = "", WordToHexValue_temp = "", lByte, lCount;
      for (lCount = 0; lCount <= 3; lCount++) {
        lByte = (lValue >>> (lCount * 8)) & 255;
        WordToHexValue_temp = "0" + lByte.toString(16);
        WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length - 2, 2);
      }
      return WordToHexValue;
    }
    var x = convertToWordArray(string);
    var a = 0x67452301, b = 0xEFCDAB89, c = 0x98BADCFE, d = 0x10325476;
    var S11=7, S12=12, S13=17, S14=22, S21=5, S22=9, S23=14, S24=20, S31=4, S32=11, S33=16, S34=23, S41=6, S42=10, S43=15, S44=21;
    for (var k = 0; k < x.length; k += 16) {
      var AA = a, BB = b, CC = c, DD = d;
      a = FF(a, b, c, d, x[k + 0], S11, 0xD76AA478); d = FF(d, a, b, c, x[k + 1], S12, 0xE8C7B756); c = FF(c, d, a, b, x[k + 2], S13, 0x242070DB); b = FF(b, c, d, a, x[k + 3], S14, 0xC1BDCEEE);
      a = FF(a, b, c, d, x[k + 4], S11, 0xF57C0FAF); d = FF(d, a, b, c, x[k + 5], S12, 0x4787C62A); c = FF(c, d, a, b, x[k + 6], S13, 0xA8304613); b = FF(b, c, d, a, x[k + 7], S14, 0xFD469501);
      a = FF(a, b, c, d, x[k + 8], S11, 0x698098D8); d = FF(d, a, b, c, x[k + 9], S12, 0x8B44F7AF); c = FF(c, d, a, b, x[k + 10], S13, 0xFFFF5BB1); b = FF(b, c, d, a, x[k + 11], S14, 0x895CD7BE);
      a = FF(a, b, c, d, x[k + 12], S11, 0x6B901122); d = FF(d, a, b, c, x[k + 13], S12, 0xFD987193); c = FF(c, d, a, b, x[k + 14], S13, 0xA679438E); b = FF(b, c, d, a, x[k + 15], S14, 0x49B40821);
      a = GG(a, b, c, d, x[k + 1], S21, 0xF61E2562); d = GG(d, a, b, c, x[k + 6], S22, 0xC040B340); c = GG(c, d, a, b, x[k + 11], S23, 0x265E5A51); b = GG(b, c, d, a, x[k + 0], S24, 0xE9B6C7AA);
      a = GG(a, b, c, d, x[k + 5], S21, 0xD62F105D); d = GG(d, a, b, c, x[k + 10], S22, 0x2441453); c = GG(c, d, a, b, x[k + 15], S23, 0xD8A1E681); b = GG(b, c, d, a, x[k + 4], S24, 0xE7D3FBC8);
      a = GG(a, b, c, d, x[k + 9], S21, 0x21E1CDE6); d = GG(d, a, b, c, x[k + 14], S22, 0xC33707D6); c = GG(c, d, a, b, x[k + 3], S23, 0xF4D50D87); b = GG(b, c, d, a, x[k + 8], S24, 0x455A14ED);
      a = GG(a, b, c, d, x[k + 13], S21, 0xA9E3E905); d = GG(d, a, b, c, x[k + 2], S22, 0xFCEFA3F8); c = GG(c, d, a, b, x[k + 7], S23, 0x676F02D9); b = GG(b, c, d, a, x[k + 12], S24, 0x8D2A4C8A);
      a = HH(a, b, c, d, x[k + 5], S31, 0xFFFA3942); d = HH(d, a, b, c, x[k + 8], S32, 0x8771F681); c = HH(c, d, a, b, x[k + 11], S33, 0x6D9D6122); b = HH(b, c, d, a, x[k + 14], S34, 0xFDE5380C);
      a = HH(a, b, c, d, x[k + 1], S31, 0xA4BEEA44); d = HH(d, a, b, c, x[k + 4], S32, 0x4BDECFA9); c = HH(c, d, a, b, x[k + 7], S33, 0xF6BB4B60); b = HH(b, c, d, a, x[k + 10], S34, 0xBEBFBC70);
      a = HH(a, b, c, d, x[k + 13], S31, 0x289B7EC6); d = HH(d, a, b, c, x[k + 0], S32, 0xEAA127FA); c = HH(c, d, a, b, x[k + 3], S33, 0xD4EF3085); b = HH(b, c, d, a, x[k + 6], S34, 0x4881D05);
      a = HH(a, b, c, d, x[k + 9], S31, 0xD9D4D039); d = HH(d, a, b, c, x[k + 12], S32, 0xE6DB99E5); c = HH(c, d, a, b, x[k + 15], S33, 0x1FA27CF8); b = HH(b, c, d, a, x[k + 2], S34, 0xC4AC5665);
      a = II(a, b, c, d, x[k + 0], S41, 0xF4292244); d = II(d, a, b, c, x[k + 7], S42, 0x432AFF97); c = II(c, d, a, b, x[k + 14], S43, 0xAB9423A7); b = II(b, c, d, a, x[k + 5], S44, 0xFC93A039);
      a = II(a, b, c, d, x[k + 12], S41, 0x655B59C3); d = II(d, a, b, c, x[k + 3], S42, 0x8F0CCC92); c = II(c, d, a, b, x[k + 10], S43, 0xFFEFF47D); b = II(b, c, d, a, x[k + 1], S44, 0x85845DD1);
      a = II(a, b, c, d, x[k + 8], S41, 0x6FA87E4F); d = II(d, a, b, c, x[k + 15], S42, 0xFE2CE6E0); c = II(c, d, a, b, x[k + 6], S43, 0xA3014314); b = II(b, c, d, a, x[k + 13], S44, 0x4E0811A1);
      a = II(a, b, c, d, x[k + 4], S41, 0xF7537E82); d = II(d, a, b, c, x[k + 11], S42, 0xBD3AF235); c = II(c, d, a, b, x[k + 2], S43, 0x2AD7D2BB); b = II(b, c, d, a, x[k + 9], S44, 0xEB86D391);
      a = addUnsigned(a, AA); b = addUnsigned(b, BB); c = addUnsigned(c, CC); d = addUnsigned(d, DD);
    }
    return (wordToHex(a) + wordToHex(b) + wordToHex(c) + wordToHex(d)).toLowerCase();
  }

  function generateTableToken(tableNo) {
    if (!tableNo) return '';
    return md5(SECRET_SALT + String(tableNo).trim()).substring(0, 10);
  }

  function getActiveToken(tableNo) {
    if (!tableNo) return '';
    const params = new URLSearchParams(window.location.search);
    let token = params.get('token') || params.get('sec');
    if (!token) {
      token = localStorage.getItem('hotel_table_token_' + tableNo) || sessionStorage.getItem('hotel_table_token_' + tableNo);
    }
    if (!token) {
      token = generateTableToken(tableNo);
      storeTableToken(tableNo, token);
    }
    return token;
  }

  function storeTableToken(tableNo, token) {
    if (!tableNo) return;
    const tok = token || generateTableToken(tableNo);
    try {
      localStorage.setItem('hotel_table_token_' + tableNo, tok);
      sessionStorage.setItem('hotel_table_token_' + tableNo, tok);
      localStorage.setItem('hotel_active_table', String(tableNo));
    } catch (_) {}
  }

  return { generateTableToken, getActiveToken, storeTableToken };
})();

// Intercept fetch to automatically attach table_token to create-order.php
(function() {
  const _fetch = window.fetch;
  window.fetch = async function(resource, init) {
    if (typeof resource === 'string' && resource.includes('create-order.php') && init && init.body) {
      try {
        const payload = JSON.parse(init.body);
        if (payload && payload.table && !payload.table_token) {
          payload.table_token = window.TableSecurity ? window.TableSecurity.getActiveToken(payload.table) : '';
          init.body = JSON.stringify(payload);
        }
      } catch (_) {}
    }
    return _fetch.apply(this, arguments);
  };
})();

window.HotelSecurity = (function() {
  let cachedConfig = null;
  let bgLocationPromise = null;
  let bgStatus = { inFlight: false, verified: false, error: null };

  function calculateDistanceMeters(lat1, lon1, lat2, lon2) {
    const R = 6371000;
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
      console.warn('Could not fetch security config:', e);
    }
    return { geofence_enabled: false };
  }

  function injectSecurityModal() {
    if (document.getElementById('securityModalOverlay')) return;

    const modalHtml = `
      <div id="securityModalOverlay" style="
        display: none; position: fixed; inset: 0; z-index: 999999;
        background: rgba(0,0,0,0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
        align-items: center; justify-content: center; padding: 1.2rem;
      ">
        <div style="
          background: linear-gradient(155deg, rgba(30,22,6,0.98), rgba(14,9,2,0.99));
          border: 1.5px solid rgba(212,168,50,0.45);
          border-radius: 24px; width: 100%; max-width: 400px;
          padding: 2.2rem 1.8rem; color: #fff; text-align: center;
          box-shadow: 0 25px 65px rgba(0,0,0,0.95), 0 0 35px rgba(212,168,50,0.25);
          font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
        ">
          <div style="font-size: 2.8rem; margin-bottom: 0.5rem;" id="secModalIcon">📍</div>
          <h3 id="secModalTitle" style="color: #f5d48a; font-size: 1.25rem; font-weight: 700; margin-bottom: 0.4rem; letter-spacing: 0.5px;">
            स्थान / टेबल सत्यापन
          </h3>
          <p id="secModalMsg" style="font-size: 0.86rem; color: rgba(255,255,255,0.75); line-height: 1.45; margin-bottom: 1.2rem;">
            कृपया स्थान (Location) की अनुमति दें या 4-अंकों का टेबल पिन दर्ज करें।
          </p>

          <!-- Retry GPS Button -->
          <button type="button" id="secBtnRetryGPS" style="
            width: 100%; padding: 0.8rem; border-radius: 12px; border: 1px solid rgba(212,168,50,0.6);
            background: rgba(212,168,50,0.15); color: #f5d48a; font-weight: 600; cursor: pointer; font-size: 0.92rem;
            margin-bottom: 1.2rem; display: flex; align-items: center; justify-content: center; gap: 8px;
          ">
            <span>📍 Allow Location Access</span>
          </button>

          <!-- Divider -->
          <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 1.2rem; opacity: 0.6;">
            <div style="flex: 1; height: 1px; background: rgba(255,255,255,0.2);"></div>
            <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">या PIN कोड</span>
            <div style="flex: 1; height: 1px; background: rgba(255,255,255,0.2);"></div>
          </div>

          <!-- Passcode Input Form -->
          <div id="secPasscodeSection" style="margin-bottom: 1.2rem;">
            <input type="password" id="secPasscodeInput" maxlength="8" placeholder="••••" style="
              width: 160px; text-align: center; font-size: 1.6rem; letter-spacing: 0.3em;
              padding: 0.5rem 0.8rem; background: rgba(0,0,0,0.65);
              border: 1px solid rgba(212,168,50,0.5); border-radius: 12px;
              color: #fff; outline: none; margin: 0 auto; display: block;
            "/>
            <p id="secPasscodeError" style="color: #ef4444; font-size: 0.78rem; margin-top: 8px; display: none;"></p>
            <p style="font-size: 0.74rem; color: rgba(255,255,255,0.45); margin-top: 8px;">
              💡 वेटर से पूछें या अपने टेबल कार्ड पर 4-अंकों का कोड देखें।
            </p>
          </div>

          <div style="display: flex; gap: 10px; justify-content: center;">
            <button type="button" id="secBtnCancel" style="
              flex: 1; padding: 0.75rem 1rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.2);
              background: transparent; color: rgba(255,255,255,0.7); font-weight: 600; cursor: pointer; font-size: 0.9rem;
            ">Cancel</button>
            <button type="button" id="secBtnVerify" style="
              flex: 1; padding: 0.75rem 1rem; border-radius: 12px; border: none;
              background: linear-gradient(135deg, #d4a832, #b8901e); color: #080500; font-weight: 700; cursor: pointer; font-size: 0.9rem;
            ">Verify PIN →</button>
          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
  }

  /**
   * Directly requests location permission with up to 2 attempts.
   * Invoked on user click gesture (e.g. 'Proceed to Order →' or 'Confirm Order').
   */
  async function requestLocationPermission(tableNo, maxAttempts = 2) {
    const table = tableNo || (new URLSearchParams(window.location.search)).get('table') || sessionStorage.getItem('hotel_table');
    const tableToken = window.TableSecurity ? window.TableSecurity.getActiveToken(table) : '';

    const existingToken = sessionStorage.getItem('hotel_security_token') || localStorage.getItem('hotel_security_token');
    if (existingToken) {
      return { success: true, token: existingToken };
    }

    const config = await getSecurityConfig();
    if (!config.geofence_enabled) {
      sessionStorage.setItem('hotel_security_token', 'GEOFENCE_DISABLED');
      return { success: true, token: 'GEOFENCE_DISABLED' };
    }
    if (config.wifi_bypass_enabled && config.is_on_hotel_wifi) {
      sessionStorage.setItem('hotel_security_token', 'WIFI_VERIFIED');
      return { success: true, token: 'WIFI_VERIFIED' };
    }

    if (!navigator.geolocation) {
      return { success: false, reason: 'unsupported' };
    }

    for (let attempt = 1; attempt <= maxAttempts; attempt++) {
      try {
        const pos = await new Promise((resolve, reject) => {
          navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: true,
            timeout: 6000,
            maximumAge: 0
          });
        });

        if (pos && pos.coords) {
          const lat = pos.coords.latitude;
          const lng = pos.coords.longitude;
          const accuracy = pos.coords.accuracy || 25;

          const resp = await fetch('api/verify_location.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              lat: lat,
              lng: lng,
              accuracy: accuracy,
              table: table,
              table_token: tableToken
            })
          });
          const resData = await resp.json();
          if (resData && resData.success) {
            sessionStorage.setItem('hotel_security_token', resData.token);
            localStorage.setItem('hotel_security_token', resData.token);
            bgStatus = { inFlight: false, verified: true, error: null };
            return { success: true, token: resData.token };
          } else {
            bgStatus = { inFlight: false, verified: false, error: resData.error || 'Outside location boundary' };
            return { success: false, reason: resData.error || 'outside_boundary' };
          }
        }
      } catch (geoErr) {
        console.warn(`Location attempt ${attempt} failed:`, geoErr);
        if (attempt < maxAttempts) {
          // Short delay before second attempt
          await new Promise(r => setTimeout(r, 400));
        }
      }
    }

    return { success: false, reason: 'permission_denied_or_timeout' };
  }

  /**
   * Starts background GPS acquisition & location verification.
   */
  function startBackgroundLocationCheck(tableNo) {
    const table = tableNo || (new URLSearchParams(window.location.search)).get('table') || sessionStorage.getItem('hotel_table');

    // Read coordinates already captured on table-select.html (on user click gesture)
    const storedLat = parseFloat(sessionStorage.getItem('hotel_pending_lat'));
    const storedLng = parseFloat(sessionStorage.getItem('hotel_pending_lng'));
    const storedAcc = parseFloat(sessionStorage.getItem('hotel_pending_acc') || '25');

    if (storedLat && storedLng) {
      // We already have coordinates — verify silently with server, no GPS dialog
      bgStatus.inFlight = true;
      bgLocationPromise = (async () => {
        try {
          const tableToken = window.TableSecurity ? window.TableSecurity.getActiveToken(table) : '';
          const resp = await fetch('api/verify_location.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ lat: storedLat, lng: storedLng, accuracy: storedAcc, table, table_token: tableToken })
          });
          const d = await resp.json();
          if (d.success && d.token) {
            sessionStorage.setItem('hotel_security_token', d.token);
            localStorage.setItem('hotel_security_token', d.token);
            bgStatus.verified = true;
          } else {
            bgStatus.error = d.error || 'Outside hotel area';
          }
        } catch (e) {
          bgStatus.error = 'Server check failed';
        } finally {
          bgStatus.inFlight = false;
          // Clean up stored coords
          sessionStorage.removeItem('hotel_pending_lat');
          sessionStorage.removeItem('hotel_pending_lng');
          sessionStorage.removeItem('hotel_pending_acc');
        }
      })();
      return bgLocationPromise;
    }

    // No stored coords (user denied GPS on table-select) — don't ask again here
    // verifyOrderLocation() will show the PIN fallback when they try to order
    bgStatus.error = 'Location permission not granted';
    return Promise.resolve(null);
  }

  function promptPasscodeFallback(reasonMessage) {
    injectSecurityModal();
    const overlay = document.getElementById('securityModalOverlay');
    const msgEl   = document.getElementById('secModalMsg');
    const input   = document.getElementById('secPasscodeInput');
    const errEl   = document.getElementById('secPasscodeError');
    const btnVer  = document.getElementById('secBtnVerify');
    const btnCan  = document.getElementById('secBtnCancel');
    const btnGPS  = document.getElementById('secBtnRetryGPS');

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

      if (btnGPS) {
        btnGPS.onclick = async function() {
          btnGPS.textContent = '📍 Checking Location…';
          const pRes = await requestLocationPermission(null, 2);
          if (pRes && pRes.success) {
            overlay.style.display = 'none';
            resolve({ security_token: pRes.token, client_lat: 0, client_lng: 0 });
          } else {
            btnGPS.innerHTML = '<span>📍 Allow Location Access</span>';
            errEl.textContent = 'Location not detected. Please enter your Table PIN below.';
            errEl.style.display = 'block';
          }
        };
      }

      async function doVerify() {
        const code = input.value.trim();
        if (!code) {
          errEl.textContent = 'Please enter 4-digit PIN.';
          errEl.style.display = 'block';
          return;
        }

        btnVer.textContent = 'Verifying…';
        btnVer.disabled = true;
        try {
          const res = await fetch('api/verify_security_passcode.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ passcode: code })
          });
          const d = await res.json();
          btnVer.textContent = 'Verify PIN →';
          btnVer.disabled = false;

          if (d.success) {
            sessionStorage.setItem('hotel_security_token', d.token);
            localStorage.setItem('hotel_security_token', d.token);
            overlay.style.display = 'none';
            resolve({ security_token: d.token, client_lat: 0, client_lng: 0 });
          } else {
            errEl.textContent = d.error || 'Incorrect passcode.';
            errEl.style.display = 'block';
          }
        } catch (_) {
          btnVer.textContent = 'Verify PIN →';
          btnVer.disabled = false;
          errEl.textContent = 'Verification error. Try again.';
          errEl.style.display = 'block';
        }
      }

      btnVer.onclick = doVerify;
      input.onkeydown = function(e) { if (e.key === 'Enter') doVerify(); };
    });
  }

  /**
   * Called when customer clicks "Confirm Order".
   * If background GPS verification passed -> instantaneous 0ms pass!
   * If failed / unverified -> asks for PIN.
   */
  async function verifyOrderLocation(tableNo) {
    const table = tableNo || (new URLSearchParams(window.location.search)).get('table') || sessionStorage.getItem('hotel_table');
    const tableToken = window.TableSecurity ? window.TableSecurity.getActiveToken(table) : '';

    // 1. If security token already verified in session -> immediate seamless pass
    const existingToken = sessionStorage.getItem('hotel_security_token') || localStorage.getItem('hotel_security_token');
    if (existingToken) {
      return { security_token: existingToken, client_lat: 0, client_lng: 0, table_token: tableToken };
    }

    // 2. If background GPS check is still in-flight, wait up to 3 seconds for it
    if (bgStatus.inFlight && bgLocationPromise) {
      await Promise.race([
        bgLocationPromise,
        new Promise(r => setTimeout(r, 3000))
      ]);
    }

    // Re-check token after wait
    const tokenAfterWait = sessionStorage.getItem('hotel_security_token') || localStorage.getItem('hotel_security_token');
    if (tokenAfterWait) {
      return { security_token: tokenAfterWait, client_lat: 0, client_lng: 0, table_token: tableToken };
    }

    // 3. If geofence is disabled or on hotel Wi-Fi
    const config = await getSecurityConfig();
    if (!config.geofence_enabled) {
      return { security_token: '', client_lat: 0, client_lng: 0, table_token: tableToken };
    }
    if (config.wifi_bypass_enabled && config.is_on_hotel_wifi) {
      sessionStorage.setItem('hotel_security_token', 'WIFI_VERIFIED');
      return { security_token: 'WIFI_VERIFIED', client_lat: 0, client_lng: 0, table_token: tableToken };
    }

    // 4. Background GPS was not verified / denied -> prompt for 4-Digit Table PIN
    const passRes = await promptPasscodeFallback(
      bgStatus.error ? `⚠️ ${bgStatus.error}.<br/>Please enter your <strong>4-Digit Table PIN</strong> to confirm dining:` : 'Please enter your <strong>4-Digit Table PIN</strong> to confirm dining:'
    );
    if (passRes) passRes.table_token = tableToken;
    return passRes;
  }

  // Automatic session check: if table has no active occupation/bill in database,
  // clear confirmed orders and local cart to release customer device session.
  async function autoClearReleasedTableSession() {
    const params = new URLSearchParams(window.location.search);
    const table = params.get('table') || sessionStorage.getItem('hotel_table');
    if (!table || table === '—') return;
    const token = (window.TableSecurity ? window.TableSecurity.getActiveToken(table) : '') || params.get('token') || '';

    try {
      let url = 'api/get_active_bill.php?table=' + encodeURIComponent(table);
      if (token) url += '&token=' + encodeURIComponent(token);
      const res = await fetch(url);
      const d = await res.json();
      if (d.success && !d.exists && !d.occupied_at) {
        const orderKey = 'hotel_confirmed_orders_' + table;
        sessionStorage.removeItem(orderKey);
        localStorage.removeItem(orderKey);
        sessionStorage.removeItem('hotel_security_token');
        console.log('Released session for Table ' + table + ' because it was cleared in database.');
      }
    } catch (e) {
      console.warn('Failed to verify table session status:', e);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', autoClearReleasedTableSession);
  } else {
    autoClearReleasedTableSession();
  }

  // On menu.html: silently verify the location that was captured on table-select.html
  // This NEVER shows a GPS popup — it only reads the coords stored in sessionStorage
  if (typeof window !== 'undefined') {
    const _initBg = () => {
      const p   = new URLSearchParams(window.location.search);
      const tbl = p.get('table') || sessionStorage.getItem('hotel_table');
      if (tbl && tbl !== '—') {
        startBackgroundLocationCheck(tbl);
      }
    };
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', _initBg);
    } else {
      _initBg();
    }
  }

  return {
    requestLocationPermission,
    startBackgroundLocationCheck,
    verifyOrderLocation,
    promptPasscodeFallback,
    getSecurityConfig
  };
})();
