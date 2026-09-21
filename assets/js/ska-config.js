/**
 * SKA Hotels — runtime configuration
 * Safe to commit: uses publishable (anon) key only.
 */
window.SKA_CONFIG = {
  supabaseUrl: 'https://nllqkepymtwwbvbjnbyz.supabase.co',
  supabaseAnonKey: 'sb_publishable_LCuHabxBgF-bth8zDI2mgw_7QsdljHH',

  siteName: 'SKA The Boutique',
  siteEmail: 'info@skaboutiquebnb.com',

  branchEmails: {
    Naguru: 'naguru.booking@skaboutiquebnb.com',
    Munyonyo: 'munyonyo.booking@skaboutiquebnb.com'
  },

  notify: {
    webhookUrl: 'https://nllqkepymtwwbvbjnbyz.supabase.co/functions/v1/notify-email'
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
