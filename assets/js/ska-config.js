/**
 * SKA Hotels — runtime configuration
 * Safe to commit: uses publishable (anon) key only.
 */
window.SKA_CONFIG = {
  supabaseUrl: 'https://aoofgjyhwbxasdvhdwoe.supabase.co',
  supabaseAnonKey: 'sb_publishable_QiSDAOjXaeTxIMXFvJBqdA_fSUM8bY6',

  siteName: 'SKA The Boutique',
  siteEmail: 'info@skaboutiquebnb.com',

  /** GitHub Pages project site base path (repo: cirqcox-pixel/skahotels) */
  githubPagesBase: '/skahotels',

  /** Auto-detect GitHub Pages vs PHP server */
  isStaticHost: function () {
    return location.hostname.endsWith('github.io') ||
      location.protocol === 'file:' ||
      document.documentElement.dataset.skaStatic === 'true';
  },

  /** Resolve asset/page URLs for current host */
  asset: function (path) {
    path = (path || '').replace(/^\//, '');
    if (this.isStaticHost()) {
      return this.githubPagesBase + '/' + path;
    }
    return path;
  },

  page: function (name) {
    var ext = this.isStaticHost() ? '.html' : '.php';
    return this.asset(name + ext);
  }
};
