/**
 * SKA Hotels — property packages on booking forms and the packages page
 */
(function (global) {
  'use strict';

  function money(amount, currency) {
    var n = Number(amount || 0);
    return (currency || 'UGX') + ' ' + n.toLocaleString();
  }

  function parseOptions(pkg) {
    if (global.SkaApi && SkaApi.parsePackageOptions) return SkaApi.parsePackageOptions(pkg);
    return [{ label: pkg.title, price: pkg.price, detail: '', pricing: pkg.pricing_mode || 'fixed' }];
  }

  function setVal(id, value) {
    var el = document.getElementById(id);
    if (el) el.value = value;
  }

  function enhanceBookingForm(form) {
    if (!form || form.dataset.skaPkgReady === '1') return;
    form.dataset.skaPkgReady = '1';

    var branch = (form.querySelector('[name="branch"]') || {}).value || document.body.dataset.skaBranch || '';
    var row = form.querySelector('.row') || form;

    var wrap = document.createElement('div');
    wrap.className = 'col-12 ska-pkg-booking';
    wrap.innerHTML =
      '<label class="form-label-ska">What are you booking?</label>' +
      '<div class="ska-book-mode" role="tablist">' +
      '<button type="button" class="is-active" data-mode="room">Room stay</button>' +
      '<button type="button" data-mode="package">Event package</button>' +
      '</div>' +
      '<div class="row g-3 mt-1" id="skaPkgFields" style="display:none">' +
      '<div class="col-md-6"><label class="form-label-ska">Package</label>' +
      '<select id="skaPackageSelect" class="form-select ska-input"><option value="">Select a package</option></select></div>' +
      '<div class="col-md-6"><label class="form-label-ska">Package option</label>' +
      '<select name="package_option" id="skaPackageOption" class="form-select ska-input"></select></div>' +
      '<div class="col-md-4" id="skaGuestsWrap" style="display:none"><label class="form-label-ska">Guests / delegates</label>' +
      '<input type="number" min="1" name="guests" id="skaGuests" class="form-control ska-input" value="1"></div>' +
      '<div class="col-12"><p class="ska-pkg-inc-mini" id="skaPkgInc"></p></div>' +
      '</div>' +
      '<input type="hidden" name="package_id" id="skaPackageId" value="">' +
      '<input type="hidden" name="currency" id="skaCurrency" value="USD">';
    row.insertBefore(wrap, row.firstChild);

    var roomSelect = form.querySelector('#room_type');
    var packages = [];
    var mode = 'room';

    function currentPkg() {
      var id = document.getElementById('skaPackageSelect').value;
      return packages.find(function (p) { return String(p.id) === String(id); }) || null;
    }

    function currentOpt() {
      var pkg = currentPkg();
      if (!pkg) return null;
      var opts = parseOptions(pkg);
      var label = document.getElementById('skaPackageOption').value;
      return opts.find(function (o) { return o.label === label; }) || opts[0] || null;
    }

    function applyTotals() {
      var pkg = currentPkg();
      var opt = currentOpt();
      var guests = parseInt((document.getElementById('skaGuests') || {}).value || '1', 10) || 1;
      if (mode !== 'package' || !pkg || !opt) return;
      var unit = parseFloat(opt.price || pkg.price || 0);
      var pricing = opt.pricing || pkg.pricing_mode || 'fixed';
      var total = pricing === 'per_person' ? unit * guests : unit;
      var cur = pkg.currency || 'UGX';
      setVal('skaPackageId', pkg.id);
      setVal('skaCurrency', cur);
      setVal('formPrice', unit);
      var totalField = document.getElementById('formTotal');
      if (!totalField) {
        totalField = document.createElement('input');
        totalField.type = 'hidden';
        totalField.name = 'total';
        totalField.id = 'formTotal';
        form.appendChild(totalField);
      }
      totalField.value = total;
      if (roomSelect) {
        var val = pkg.title + ' — ' + (opt.label || '');
        var exists = false;
        Array.prototype.forEach.call(roomSelect.options, function (o) {
          if (o.value === val) exists = true;
        });
        if (!exists) {
          var extra = document.createElement('option');
          extra.value = val;
          extra.textContent = val;
          extra.dataset.skaPkg = '1';
          roomSelect.appendChild(extra);
        }
        roomSelect.value = val;
        roomSelect.removeAttribute('required');
      }
      var disp = document.getElementById('totalPrice');
      if (disp) disp.innerHTML = 'Total: <strong>' + money(total, cur) + '</strong>';
    }

    function fillOptions() {
      var pkg = currentPkg();
      var sel = document.getElementById('skaPackageOption');
      var inc = document.getElementById('skaPkgInc');
      sel.innerHTML = '';
      if (!pkg) {
        inc.textContent = '';
        document.getElementById('skaGuestsWrap').style.display = 'none';
        setVal('skaPackageId', '');
        return;
      }
      parseOptions(pkg).forEach(function (opt, i) {
        var o = document.createElement('option');
        o.value = opt.label;
        o.textContent = opt.label + ' — ' + money(opt.price, pkg.currency) + (opt.detail ? ' (' + opt.detail + ')' : '');
        if (i === 0) o.selected = true;
        sel.appendChild(o);
      });
      inc.textContent = (pkg.inclusions || '').split(/\n/).filter(Boolean).slice(0, 4).join(' · ');
      var opt = currentOpt();
      var per = opt && (opt.pricing || pkg.pricing_mode) === 'per_person';
      document.getElementById('skaGuestsWrap').style.display = per ? '' : 'none';
      applyTotals();
    }

    function setMode(next) {
      mode = next;
      wrap.querySelectorAll('[data-mode]').forEach(function (btn) {
        btn.classList.toggle('is-active', btn.dataset.mode === mode);
      });
      document.getElementById('skaPkgFields').style.display = mode === 'package' ? '' : 'none';
      if (mode === 'room') {
        setVal('skaPackageId', '');
        setVal('skaCurrency', 'USD');
        if (roomSelect) roomSelect.setAttribute('required', 'required');
        if (typeof global.calculateBooking === 'function') global.calculateBooking();
        else if (typeof global.calcFormTotal === 'function') global.calcFormTotal();
      } else {
        fillOptions();
        applyTotals();
      }
    }

    wrap.querySelectorAll('[data-mode]').forEach(function (btn) {
      btn.addEventListener('click', function () { setMode(btn.dataset.mode); });
    });
    document.getElementById('skaPackageSelect').addEventListener('change', fillOptions);
    document.getElementById('skaPackageOption').addEventListener('change', applyTotals);
    document.getElementById('skaGuests').addEventListener('input', applyTotals);
    ['checkin', 'checkout'].forEach(function (id) {
      document.getElementById(id)?.addEventListener('change', function () {
        if (mode === 'package') applyTotals();
      });
    });

    async function load() {
      try {
        if (global.SKA_PACKAGES && SKA_PACKAGES.length) {
          packages = SKA_PACKAGES;
        } else if (global.SkaApi && SkaApi.fetchPackages) {
          packages = await SkaApi.fetchPackages(branch);
        }
      } catch (e) {
        console.warn('[SKA] packages', e);
      }
      var sel = document.getElementById('skaPackageSelect');
      packages.forEach(function (p) {
        var o = document.createElement('option');
        o.value = p.id;
        o.textContent = p.title;
        sel.appendChild(o);
      });
      if (!packages.length) {
        wrap.style.display = 'none';
        return;
      }

      var params = new URLSearchParams(location.search);
      var want = params.get('package');
      if (want) {
        var match = packages.find(function (p) {
          return String(p.id) === String(want) ||
            (p.tag && p.tag.toLowerCase() === want.toLowerCase()) ||
            (p.title && p.title.toLowerCase().indexOf(want.toLowerCase()) >= 0);
        });
        if (match) {
          sel.value = match.id;
          setMode('package');
        }
      }
    }

    load();
  }

  async function hydratePackagesPage() {
    var grid = document.getElementById('packagesGrid');
    if (!grid || grid.dataset.skaHydrated === '1') return;
    if (!global.SKA_CONFIG || !SKA_CONFIG.isStaticHost()) return;
    try {
      if (!global.SkaApi) return;
      var pkgs = await SkaApi.fetchPackages();
      if (!pkgs.length) return;
      grid.innerHTML = pkgs.map(function (pkg) {
        var from = money(pkg.price, pkg.currency || 'UGX');
        var slug = (pkg.branch || 'Naguru').toLowerCase() === 'munyonyo' ? 'munyonyo.html' : 'naguru.html';
        var href = pkg.booking_url || (slug + '?package=' + pkg.id + '#book');
        if (href.indexOf('.php') >= 0) href = href.replace(/\.php/g, '.html');
        var inc = (pkg.inclusions || '').split(/\n/).filter(Boolean).slice(0, 5).map(function (l) {
          return '<li>' + l + '</li>';
        }).join('');
        return '<article class="ska-feature-card">' +
          '<div class="ska-feature-card__img" style="background-image:url(\'' + (pkg.image || 'assets/images/ska_naguru_home.jpeg') + '\')"></div>' +
          '<div class="ska-feature-card__body">' +
          (pkg.tag ? '<p class="ska-feature-card__tag">' + pkg.tag + ' · ' + (pkg.branch || '') + '</p>' : '') +
          '<h2 class="ska-feature-card__title">' + (pkg.title || '') + '</h2>' +
          '<p class="ska-feature-card__text">' + (pkg.description || '') + '</p>' +
          '<p class="ska-feature-card__text"><strong>From ' + from + '</strong></p>' +
          (inc ? '<ul class="ska-package-inc">' + inc + '</ul>' : '') +
          '<a href="' + href + '" class="ska-btn-gold">Book this package <i class="fa-solid fa-arrow-right"></i></a>' +
          '</div></article>';
      }).join('');
      grid.dataset.skaHydrated = '1';
    } catch (e) {
      console.warn('[SKA] packages page', e);
    }
  }

  function init() {
    document.querySelectorAll('form.booking-form, form#bookingForm, form[data-ska-form="booking"]').forEach(enhanceBookingForm);
    hydratePackagesPage();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();

  global.SkaPackages = { init: init };
})(window);
