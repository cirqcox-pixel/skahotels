/**
 * SKA Hotels — runtime configuration
 * Safe to commit: uses publishable (anon) key only.
 */
window.SKA_CONFIG = {
  supabaseUrl: 'https://aoofgjyhwbxasdvhdwoe.supabase.co',
  supabaseAnonKey: 'sb_publishable_QiSDAOjXaeTxIMXFvJBqdA_fSUM8bY6',

  siteName: 'SKA The Boutique',
  siteEmail: 'info@skaboutiquebnb.com',

  /**
   * Formspree — email alerts for GitHub Pages forms
   * 1. Sign up at https://formspree.io
   * 2. Create two forms (or one) → copy the form ID (e.g. xyzeabcd)
   * 3. Paste IDs below. Same ID for both is fine if you prefer one inbox.
   */
  formspree: {
    booking: '',   // e.g. 'xyzeabcd'
    inquiry: ''    // e.g. 'wqazplmn'
  },

  /**
   * Optional Resend via Supabase Edge Function (see supabase/functions/notify-email)
   * Leave empty if using Formspree only.
   */
  notify: {
    webhookUrl: '',  // e.g. 'https://aoofgjyhwbxasdvhdwoe.supabase.co/functions/v1/notify-email'
    to: 'info@skaboutiquebnb.com'
  },

  /** GitHub Pages project site base path (repo: cirqcox-pixel/skahotels) */
  githubPagesBase: '/skahotels',

  /** Auto-detect GitHub Pages vs PHP server */
  isStaticHost: function () {
    return location.hostname.endsWith('github.io') ||
      location.protocol === 'file:' ||
      document.documentElement.dataset.skaStatic === 'true';
  },

  /** Resolve asset/page URLs — relative paths work on GitHub Pages */
  asset: function (path) {
    return (path || '').replace(/^\//, '');
  },

  page: function (name) {
    var ext = this.isStaticHost() ? '.html' : '.php';
    return name + ext;
  }
};
