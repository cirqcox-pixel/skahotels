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
    client = global.supabase.createClient(cfg.supabaseUrl, cfg.supabaseAnonKey, {
      auth: {
        persistSession: true,
        autoRefreshToken: true,
        detectSessionInUrl: true
      }
    });
    return client;
  }

  function todayISO() {
    return new Date().toISOString().slice(0, 10);
  }

  function apiError(err) {
    console.error('[SKA API]', err);
    var msg = err && err.message ? err.message : 'Request failed';
    if (err && (err.code === 'PGRST301' || err.status === 401)) {
      return 'Session expired — please sign in again.';
    }
    return msg;
  }

  function isAuthError(err) {
    if (!err) return false;
    if (err.status === 401 || err.code === 'PGRST301') return true;
    var msg = (err.message || '').toLowerCase();
    return msg.indexOf('jwt') >= 0 || msg.indexOf('expired') >= 0 || msg.indexOf('invalid') >= 0;
  }

  async function adminRequest(queryFn) {
    var sb = getClient();
    var res = await queryFn(sb);
    if (res.error && isAuthError(res.error)) {
      var refreshed = await sb.auth.refreshSession();
      if (!refreshed.error && refreshed.data.session) {
        res = await queryFn(getClient());
      }
    }
    if (res.error) throw new Error(apiError(res.error));
    return res.data;
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

    fetchPackages: async function (branch) {
      var sb = getClient();
      var res = await sb.from('packages')
        .select('*')
        .eq('active', true)
        .order('sort_order')
        .order('id');
      if (res.error) throw new Error(apiError(res.error));
      var today = todayISO();
      return (res.data || []).filter(function (p) {
        if (p.valid_from && p.valid_from > today) return false;
        if (p.valid_to && p.valid_to < today) return false;
        if (branch && p.branch && p.branch !== branch && p.branch !== 'Both' && p.branch !== 'All') return false;
        return true;
      });
    },

    parsePackageOptions: function (pkg) {
      var raw = pkg && pkg.options;
      if (Array.isArray(raw)) return raw;
      if (!raw) {
        return [{
          label: (pkg && pkg.title) || 'Package',
          price: parseFloat((pkg && pkg.price) || 0),
          detail: '',
          pricing: (pkg && pkg.pricing_mode) || 'fixed'
        }];
      }
      try {
        var json = JSON.parse(raw);
        if (Array.isArray(json)) return json;
      } catch (e) {}
      return String(raw).split(/\r?\n/).filter(Boolean).map(function (line) {
        var p = line.split('|').map(function (s) { return s.trim(); });
        return {
          label: p[0],
          price: parseFloat(p[1] || (pkg && pkg.price) || 0),
          detail: p[2] || '',
          pricing: p[3] || (pkg && pkg.pricing_mode) || 'fixed'
        };
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
      try {
        var map = await SkaApi.fetchSettings();
        SkaApi.applyPublicSettings(map);
        if (map.site_email) data.site_email = map.site_email;
      } catch (e) { /* defaults in SKA_CONFIG */ }
      data.site_email = data.site_email || (cfg && cfg.siteEmail) || 'info@skaboutiquebnb.com';
      if (global.SkaNotify) {
        try { await SkaNotify.notify('inquiry', data); } catch (e) { /* already saved */ }
      }
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
      var total = data.total != null && data.total !== ''
        ? parseFloat(data.total)
        : price * nights;
      var payload = {
        name: data.name,
        email: data.email,
        phone: data.phone || null,
        whatsapp: data.whatsapp || null,
        room_type: data.room_type,
        price: price,
        checkin: data.checkin,
        checkout: data.checkout,
        total: total,
        message: data.message || null,
        season: data.season || 'low',
        branch: data.branch,
        status: 'pending',
        currency: data.currency || 'USD',
        guests: data.guests ? parseInt(data.guests, 10) : null,
        package_option: data.package_option || null
      };
      if (data.package_id) payload.package_id = parseInt(data.package_id, 10);
      var branchName = String(data.branch || payload.branch || '');
      var res = await sb.from('bookings').insert([payload]);
      if (res.error) throw new Error(apiError(res.error));
      if (global.SkaNotify) {
        try {
          try {
            var map = await SkaApi.fetchSettings();
            SkaApi.applyPublicSettings(map);
          } catch (e) { /* SKA_CONFIG defaults */ }
          await SkaNotify.notify('booking', Object.assign({}, data, {
            total: total,
            price: price,
            branch: branchName || data.branch || payload.branch
          }));
        } catch (e) {
          console.error('[SKA] booking notify:', e.message || e);
        }
      }
      return true;
    },

    /* ── Admin auth (GitHub Pages admin) ── */
    adminSignIn: async function (email, password) {
      var sb = getClient();
      var res = await sb.auth.signInWithPassword({
        email: String(email || '').trim(),
        password: password
      });
      if (res.error) throw new Error(apiError(res.error));
      try {
        await sb.rpc('claim_first_admin');
      } catch (e) { /* allowlist seeded in SQL; dashboard still enforces RLS */ }
      return res.data;
    },

    adminSignOut: async function () {
      var sb = getClient();
      await sb.auth.signOut();
    },

    adminSession: async function () {
      var sb = getClient();
      var sessRes = await sb.auth.getSession();
      var session = sessRes.data && sessRes.data.session;
      if (!session) return null;

      var exp = session.expires_at;
      if (exp && exp * 1000 < Date.now() + 120000) {
        var ref = await sb.auth.refreshSession();
        if (ref.data && ref.data.session) session = ref.data.session;
      }

      var userRes = await sb.auth.getUser();
      if (userRes.error || !userRes.data.user) {
        await sb.auth.signOut();
        return null;
      }
      return session;
    },

    adminFetchRooms: async function () {
      var data = await adminRequest(function (sb) {
        return sb.from('rooms')
          .select('*, room_images(id,image_path), room_amenities(id,icon_class,name)')
          .order('branch')
          .order('id');
      });
      return (data || []).map(function (room) {
        room.images = (room.room_images || []).map(function (r) { return r.image_path; });
        room.amenities = room.room_amenities || [];
        return room;
      });
    },

    adminFetchRoomImages: async function (roomId) {
      var data = await adminRequest(function (sb) {
        return sb.from('room_images').select('id,image_path').eq('room_id', roomId).order('id');
      });
      return data || [];
    },

    adminAddRoomImage: async function (roomId, imagePath) {
      return adminRequest(function (sb) {
        return sb.from('room_images').insert([{ room_id: roomId, image_path: imagePath }]).select().single();
      });
    },

    adminDeleteRoomImage: async function (imageId) {
      await adminRequest(function (sb) {
        return sb.from('room_images').delete().eq('id', imageId);
      });
      return true;
    },

    adminReplaceRoomAmenities: async function (roomId, names) {
      await adminRequest(function (sb) {
        return sb.from('room_amenities').delete().eq('room_id', roomId);
      });
      var rows = (names || []).map(function (name) {
        return { room_id: roomId, name: name, icon_class: 'fa-solid fa-check' };
      }).filter(function (r) { return r.name; });
      if (!rows.length) return [];
      return adminRequest(function (sb) {
        return sb.from('room_amenities').insert(rows).select();
      });
    },

    applyPublicSettings: function (map) {
      if (!map || !cfg) return map || {};
      if (map.site_email) cfg.siteEmail = map.site_email;
      cfg.branchEmails = cfg.branchEmails || {
        Naguru: 'naguru.booking@skaboutiquebnb.com',
        Munyonyo: 'munyonyo.booking@skaboutiquebnb.com'
      };
      if (map.naguru_notify_email && /@/.test(map.naguru_notify_email)) {
        cfg.branchEmails.Naguru = map.naguru_notify_email;
      }
      if (map.munyonyo_notify_email && /@/.test(map.munyonyo_notify_email)) {
        cfg.branchEmails.Munyonyo = map.munyonyo_notify_email;
      }
      cfg.formspree = cfg.formspree || {};
      if (map.naguru_formspree && String(map.naguru_formspree).trim()) {
        cfg.formspree.booking = String(map.naguru_formspree).trim();
      }
      if (map.munyonyo_formspree_project && String(map.munyonyo_formspree_project).trim()) {
        cfg.formspree.munyonyoProject = String(map.munyonyo_formspree_project).trim();
      }
      if (map.munyonyo_formspree && String(map.munyonyo_formspree).trim()) {
        cfg.formspree.bookingMunyonyo = String(map.munyonyo_formspree).trim();
      }
      if (cfg.notify && (map.naguru_notify_email || map.site_email)) {
        cfg.notify.to = map.naguru_notify_email || map.site_email;
      }
      return map;
    },

    adminSaveSettings: async function (rows) {
      if (!rows || !rows.length) return [];
      return adminRequest(function (sb) {
        return sb.from('site_settings').upsert(rows, { onConflict: 'setting_key' }).select();
      });
    },

    adminSaveRoom: async function (room) {
      var payload = {
        name: room.name,
        branch: room.branch,
        price: parseFloat(room.price || 0),
        price_low: room.price_low != null ? parseFloat(room.price_low) : null,
        price_shoulder: room.price_shoulder != null ? parseFloat(room.price_shoulder) : null,
        price_high: room.price_high != null ? parseFloat(room.price_high) : null,
        description: room.description || null
      };
      if (room.id) {
        return adminRequest(function (sb) {
          return sb.from('rooms').update(payload).eq('id', room.id).select().single();
        });
      }
      return adminRequest(function (sb) {
        return sb.from('rooms').insert([payload]).select().single();
      });
    },

    adminDeleteRoom: async function (id) {
      await adminRequest(function (sb) {
        return sb.from('rooms').delete().eq('id', id);
      });
      return true;
    },

    adminFetchBookings: async function () {
      var data = await adminRequest(function (sb) {
        return sb.from('bookings').select('*').order('created_at', { ascending: false });
      });
      return data || [];
    },

    adminUpdateBookingStatus: async function (id, status) {
      var row = await adminRequest(function (sb) {
        return sb.from('bookings').update({ status: status }).eq('id', id).select().single();
      });
      if (global.SkaNotify && row) {
        var type = status === 'confirmed' ? 'booking_confirmed' : 'booking_cancelled';
        try {
          try {
            var map = await SkaApi.fetchSettings();
            SkaApi.applyPublicSettings(map);
          } catch (e) { /* SKA_CONFIG defaults */ }
          await SkaNotify.notify(type, row);
        } catch (e) { /* status already saved */ }
      }
      return row;
    },

    adminFetchInquiries: async function () {
      var data = await adminRequest(function (sb) {
        return sb.from('inquiries').select('*').order('created_at', { ascending: false });
      });
      return data || [];
    },

    adminMarkInquiryRead: async function (id, isRead) {
      return adminRequest(function (sb) {
        return sb.from('inquiries').update({ is_read: !!isRead }).eq('id', id).select().single();
      });
    },

    adminReplyInquiry: async function (id, reply) {
      var text = String(reply || '').trim();
      if (!text) throw new Error('Write a reply first.');
      var payload = {
        is_read: true,
        reply_message: text,
        replied_at: new Date().toISOString()
      };
      var row;
      try {
        row = await adminRequest(function (sb) {
          return sb.from('inquiries').update(payload).eq('id', id).select().single();
        });
      } catch (e) {
        row = await adminRequest(function (sb) {
          return sb.from('inquiries').update({ is_read: true }).eq('id', id).select().single();
        });
        if (row) row.reply_message = text;
      }
      if (global.SkaNotify && row) {
        try {
          await SkaNotify.notify('inquiry_reply', Object.assign({}, row, { reply: text }));
        } catch (e) { /* saved even if mail fails */ }
      }
      return row;
    },

    adminFetchPromotions: async function () {
      var data = await adminRequest(function (sb) {
        return sb.from('promotions').select('*').order('sort_order').order('id');
      });
      return data || [];
    },

    adminSavePromotion: async function (promo) {
      var payload = {
        title: promo.title,
        description: promo.description || null,
        discount_type: promo.discount_type || 'percent',
        discount_value: parseFloat(promo.discount_value || 0),
        min_nights: parseInt(promo.min_nights || 1, 10),
        branch: promo.branch || 'Both',
        image: promo.image || null,
        active: promo.active === true || promo.active === 'true'
      };
      if (promo.id) {
        payload.updated_at = new Date().toISOString();
        return adminRequest(function (sb) {
          return sb.from('promotions').update(payload).eq('id', promo.id).select().single();
        });
      }
      return adminRequest(function (sb) {
        return sb.from('promotions').insert([payload]).select().single();
      });
    },

    adminDeletePromotion: async function (id) {
      await adminRequest(function (sb) {
        return sb.from('promotions').delete().eq('id', id);
      });
      return true;
    },

    adminFetchPackages: async function () {
      var data = await adminRequest(function (sb) {
        return sb.from('packages').select('*').order('sort_order').order('id');
      });
      return data || [];
    },

    adminSavePackage: async function (pkg) {
      var payload = {
        title: pkg.title,
        tag: pkg.tag || null,
        description: pkg.description || null,
        inclusions: pkg.inclusions || null,
        options: pkg.options || null,
        currency: pkg.currency || 'UGX',
        price: parseFloat(pkg.price || 0),
        pricing_mode: pkg.pricing_mode || 'fixed',
        branch: pkg.branch || 'Naguru',
        booking_url: pkg.booking_url || null,
        image: pkg.image || null,
        active: pkg.active === true || pkg.active === 'true' || pkg.active === '1'
      };
      if (pkg.sort_order != null) payload.sort_order = parseInt(pkg.sort_order, 10) || 0;
      if (pkg.id) {
        payload.updated_at = new Date().toISOString();
        return adminRequest(function (sb) {
          return sb.from('packages').update(payload).eq('id', pkg.id).select().single();
        });
      }
      return adminRequest(function (sb) {
        return sb.from('packages').insert([payload]).select().single();
      });
    },

    adminDeletePackage: async function (id) {
      await adminRequest(function (sb) {
        return sb.from('packages').delete().eq('id', id);
      });
      return true;
    },

    adminUploadPublicFile: async function (folder, file) {
      if (!file) throw new Error('Choose an image first.');
      var ext = String(file.name.split('.').pop() || 'jpg').toLowerCase().replace(/[^a-z0-9]/g, '');
      if (['jpg', 'jpeg', 'png', 'webp', 'gif'].indexOf(ext) < 0) {
        throw new Error('Use a JPG, PNG, or WebP image.');
      }
      if (file.size > 2.5 * 1024 * 1024) {
        throw new Error('Image must be under 2.5 MB.');
      }
      var path = (folder || 'misc') + '/' + Date.now() + '-' + Math.random().toString(36).slice(2, 8) + '.' + ext;
      var data = await adminRequest(function (sb) {
        return sb.storage.from('ska-uploads').upload(path, file, {
          upsert: true,
          contentType: file.type || 'image/jpeg'
        });
      });
      var sb = getClient();
      var pub = sb.storage.from('ska-uploads').getPublicUrl((data && data.path) || path);
      return (pub.data && pub.data.publicUrl) || path;
    },

    adminGetProfile: async function () {
      var data = await adminRequest(function (sb) {
        return sb.rpc('ska_admin_profile');
      });
      return data || { ok: false };
    },

    adminListStaff: async function () {
      var data = await adminRequest(function (sb) {
        return sb.rpc('ska_list_staff');
      });
      return data || [];
    },

    adminLoginUrl: function () {
      try {
        return new URL('login.html', location.href).toString();
      } catch (e) {
        return 'https://www.skaboutiquebnb.com/admin/login.html';
      }
    },

    adminInviteRedirect: function () {
      var url = SkaApi.adminLoginUrl();
      try {
        var u = new URL(url);
        u.searchParams.set('set_password', '1');
        return u.toString();
      } catch (e) {
        return url + (url.indexOf('?') >= 0 ? '&' : '?') + 'set_password=1';
      }
    },

    adminSendInviteEmail: async function (email) {
      email = String(email || '').trim().toLowerCase();
      if (!email) throw new Error('A valid email is required.');
      var redirectTo = SkaApi.adminInviteRedirect();
      var otpClient = global.supabase.createClient(cfg.supabaseUrl, cfg.supabaseAnonKey, {
        auth: {
          persistSession: false,
          autoRefreshToken: false,
          detectSessionInUrl: false
        }
      });
      var otp = await otpClient.auth.signInWithOtp({
        email: email,
        options: {
          emailRedirectTo: redirectTo,
          shouldCreateUser: true
        }
      });
      if (otp.error) {
        var msg = otp.error.message || 'Could not send invite email.';
        if (/rate|seconds/i.test(msg)) {
          msg = 'Supabase is rate-limiting invites. Wait about a minute, then resend.';
        } else if (/redirect|whitelist|allow/i.test(msg)) {
          msg = 'Add this URL under Supabase Authentication → URL Configuration → Redirect URLs: ' + redirectTo.split('?')[0];
        } else if (/signups? (not |are )?disabled|disabled/i.test(msg)) {
          msg = 'Email sign-ups are disabled in Supabase Auth. Enable them, or allow magic-link invites.';
        }
        throw new Error(msg);
      }
      return { ok: true, emailed: true, provider: 'magic_link' };
    },

    adminInviteStaff: async function (email, role, opts) {
      var bodyEmail = String(email || '').trim();
      var bodyRole = role || 'manager';
      var resend = !!(opts && opts.resend);

      if (!resend) {
        await SkaApi.adminAddStaffRpc(bodyEmail, bodyRole);
      } else {
        try {
          await SkaApi.adminUpdateStaff(bodyEmail, bodyRole);
        } catch (e) { /* invite row may not have an auth user yet */ }
      }

      var fnErr = null;
      try {
        var sb = getClient();
        var res = await sb.functions.invoke('invite-staff', {
          body: {
            email: bodyEmail,
            role: bodyRole,
            redirectTo: SkaApi.adminInviteRedirect(),
            resend: resend
          }
        });
        var data = res.data || {};
        if (!res.error && data && data.emailed) {
          return data;
        }
        if (res.error) fnErr = res.error.message;
        else if (data.error) fnErr = data.error;
      } catch (e) {
        fnErr = e.message || String(e);
      }

      try {
        return await SkaApi.adminSendInviteEmail(bodyEmail);
      } catch (otpErr) {
        throw new Error(otpErr.message || fnErr || 'Could not send invite email.');
      }
    },

    adminAddStaffRpc: async function (email, role) {
      return adminRequest(function (sb) {
        return sb.rpc('ska_add_staff', { p_email: email, p_role: role });
      });
    },

    adminAddStaff: async function (email, role) {
      return SkaApi.adminInviteStaff(email, role, { resend: false });
    },

    adminResendInvite: async function (email, role) {
      return SkaApi.adminInviteStaff(email, role, { resend: true });
    },

    adminUpdateStaff: async function (email, role) {
      return adminRequest(function (sb) {
        return sb.rpc('ska_update_staff', { p_email: email, p_role: role });
      });
    },

    adminRemoveStaff: async function (email) {
      return adminRequest(function (sb) {
        return sb.rpc('ska_remove_staff', { p_email: email });
      });
    }
  };

  global.SkaApi = SkaApi;
})(window);
