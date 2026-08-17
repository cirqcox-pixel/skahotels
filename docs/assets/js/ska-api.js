/**
 * SKA Hotels — Supabase client & data API
 */
(function (global) {
  'use strict';

  var cfg = global.SKA_CONFIG;
  var client = null;

  function getClient() {
    if (client) return client;
    if (!global.supabase || !cfg) {
      throw new Error('Supabase SDK or SKA_CONFIG not loaded');
    }
    client = global.supabase.createClient(cfg.supabaseUrl, cfg.supabaseAnonKey);
    return client;
  }

  function todayISO() {
    return new Date().toISOString().slice(0, 10);
  }

  function apiError(err) {
    console.error('[SKA API]', err);
    return err && err.message ? err.message : 'Request failed';
  }

  var SkaApi = {
    client: getClient,

    isAvailable: function () {
      return !!(global.supabase && cfg && cfg.supabaseUrl && cfg.supabaseAnonKey);
    },

    /* ── Rooms ── */
    fetchRooms: async function (branch) {
      var sb = getClient();
      var res = await sb.from('rooms').select('*').eq('branch', branch).order('id');
      if (res.error) throw new Error(apiError(res.error));
      var rooms = res.data || [];

      for (var i = 0; i < rooms.length; i++) {
        var room = rooms[i];
        var imgs = await sb.from('room_images').select('image_path').eq('room_id', room.id).order('id');
        var ams = await sb.from('room_amenities').select('icon_class,name').eq('room_id', room.id);
        room.images = (imgs.data || []).map(function (r) { return r.image_path; });
        room.amenities = ams.data || [];
        room.price_now = SkaApi.seasonPrice(room);
      }
      return rooms;
    },

    seasonPrice: function (room) {
      var month = new Date().getMonth() + 1;
      var season = 'low';
      if ([6, 7, 8, 12, 1].indexOf(month) >= 0) season = 'high';
      else if ([3, 4, 5, 9, 10, 11].indexOf(month) >= 0) season = 'shoulder';
      var col = { low: 'price_low', shoulder: 'price_shoulder', high: 'price_high' }[season];
      var val = room[col];
      return val != null ? parseFloat(val) : parseFloat(room.price || 0);
    },

    /* ── Promotions ── */
    fetchPromotions: async function () {
      var sb = getClient();
      var res = await sb.from('promotions')
        .select('*')
        .eq('active', true)
        .order('sort_order')
        .order('id');
      if (res.error) throw new Error(apiError(res.error));
      var today = todayISO();
      return (res.data || []).filter(function (p) {
        if (p.valid_from && p.valid_from > today) return false;
        if (p.valid_to && p.valid_to < today) return false;
        return true;
      });
    },

    /* ── CMS ── */
    fetchSetting: async function (key, fallback) {
      var sb = getClient();
      var res = await sb.from('site_settings').select('setting_value').eq('setting_key', key).maybeSingle();
      if (res.error || !res.data || !res.data.setting_value) return fallback || '';
      return res.data.setting_value;
    },

    fetchSettings: async function () {
      var sb = getClient();
      var res = await sb.from('site_settings').select('*').order('setting_group').order('setting_key');
      if (res.error) throw new Error(apiError(res.error));
      var map = {};
      (res.data || []).forEach(function (r) { map[r.setting_key] = r.setting_value; });
      return map;
    },

    fetchPage: async function (slug) {
      var sb = getClient();
      var res = await sb.from('cms_pages').select('*').eq('slug', slug).eq('active', true).maybeSingle();
      if (res.error) throw new Error(apiError(res.error));
      return res.data;
    },

    fetchBlocks: async function (pageSlug) {
      var sb = getClient();
      var res = await sb.from('cms_blocks')
        .select('*')
        .eq('page_slug', pageSlug)
        .eq('active', true)
        .order('sort_order').order('id');
      if (res.error) throw new Error(apiError(res.error));
      return res.data || [];
    },

    fetchBlock: async function (pageSlug, blockKey) {
      var sb = getClient();
      var res = await sb.from('cms_blocks')
        .select('*')
        .eq('page_slug', pageSlug)
        .eq('block_key', blockKey)
        .eq('active', true)
        .maybeSingle();
      if (res.error) throw new Error(apiError(res.error));
      return res.data;
    },

    fetchGallery: async function (branch) {
      var sb = getClient();
      var images = [];
      var gal = await sb.from('property_gallery')
        .select('image_path,caption')
        .eq('branch', branch)
        .eq('active', true)
        .order('sort_order').order('id');
      if (gal.data) {
        gal.data.forEach(function (g) {
          images.push({ path: g.image_path, caption: g.caption || '' });
        });
      }
      var rooms = await SkaApi.fetchRooms(branch);
      rooms.forEach(function (room) {
        (room.images || []).slice(0, 4).forEach(function (p) {
          images.push({ path: p, caption: room.name });
        });
      });
      return images;
    },

    /* ── Submissions ── */
    submitInquiry: async function (data) {
      var sb = getClient();
      var res = await sb.from('inquiries').insert([{
        name: data.name,
        email: data.email,
        phone: data.phone || null,
        subject: data.subject || 'General Inquiry',
        message: data.message,
        is_read: false
      }]);
      if (res.error) throw new Error(apiError(res.error));
      return true;
    },

    submitBooking: async function (data) {
      var sb = getClient();
      var nights = 1;
      try {
        var ci = new Date(data.checkin);
        var co = new Date(data.checkout);
        if (co > ci) nights = Math.round((co - ci) / 86400000);
      } catch (e) {}
      var price = parseFloat(data.price || 0);
      var res = await sb.from('bookings').insert([{
        name: data.name,
        email: data.email,
        phone: data.phone || null,
        whatsapp: data.whatsapp || null,
        room_type: data.room_type,
        price: price,
        checkin: data.checkin,
        checkout: data.checkout,
        total: price * nights,
        message: data.message || null,
        season: data.season || 'low',
        branch: data.branch,
        status: 'pending'
      }]);
      if (res.error) throw new Error(apiError(res.error));
      return true;
    },

    /* ── Admin auth (GitHub Pages admin) ── */
    adminSignIn: async function (email, password) {
      var sb = getClient();
      var res = await sb.auth.signInWithPassword({ email: email, password: password });
      if (res.error) throw new Error(apiError(res.error));
      return res.data;
    },

    adminSignOut: async function () {
      var sb = getClient();
      await sb.auth.signOut();
    },

    adminSession: async function () {
      var sb = getClient();
      var res = await sb.auth.getSession();
      return res.data && res.data.session;
    }
  };

  global.SkaApi = SkaApi;
})(window);
