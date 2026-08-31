/**
 * ═══════════════════════════════════════════════════════════════
 * owner-auth.js — Hotel Tulsi Master Owner Authentication Guard
 * Protects Dashboard, Billing, Menu Control, Reports, QR Generator
 * ═══════════════════════════════════════════════════════════════
 */

'use strict';

const OWNER_AUTH_KEY = 'hotel_tulsi_owner_session';
const MASTER_OWNER_PASS = '01SEP2026@Hoteltulsi';

window.OwnerAuth = {
  MASTER_PASS: MASTER_OWNER_PASS,

  isLoggedIn: function() {
    try {
      const sess = sessionStorage.getItem(OWNER_AUTH_KEY) || localStorage.getItem(OWNER_AUTH_KEY);
      if (!sess) return false;
      const data = JSON.parse(sess);
      return !!(data && data.authenticated === true && data.token === 'HOTEL_TULSI_OWNER_MASTER_KEY_2026');
    } catch (_) {
      return false;
    }
  },

  login: function(password) {
    if (password === this.MASTER_PASS) {
      const payload = {
        authenticated: true,
        token: 'HOTEL_TULSI_OWNER_MASTER_KEY_2026',
        loginTime: new Date().toISOString()
      };
      sessionStorage.setItem(OWNER_AUTH_KEY, JSON.stringify(payload));
      localStorage.setItem(OWNER_AUTH_KEY, JSON.stringify(payload));
      return { success: true };
    }
    return { success: false, error: 'Incorrect password. Access denied.' };
  },

  logout: function() {
    try {
      sessionStorage.removeItem(OWNER_AUTH_KEY);
      localStorage.removeItem(OWNER_AUTH_KEY);
      sessionStorage.removeItem('ownerUID');
      sessionStorage.removeItem('ownerPhone');
    } catch (_) {}
    window.location.replace('owner-login.html');
  },

  protectPage: function() {
    const curPath = window.location.pathname.split('/').pop() || '';
    if (!curPath.includes('owner-login.html')) {
      if (!this.isLoggedIn()) {
        // Prevent flashing protected content
        document.documentElement.style.display = 'none';
        window.location.replace('owner-login.html?redirect=' + encodeURIComponent(curPath));
      } else {
        document.documentElement.style.display = '';
      }
    }
  }
};

// Immediate synchronous security check
if (typeof window !== 'undefined') {
  OwnerAuth.protectPage();
}
