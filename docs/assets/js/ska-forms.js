/**
 * SKA Hotels — form handlers for static/GitHub Pages hosting
 */
(function (global) {
  'use strict';

  var cfg = global.SKA_CONFIG;

  function showAlert(form, type, message) {
    var existing = form.querySelector('.ska-form-alert');
    if (existing) existing.remove();
    var div = document.createElement('div');
    div.className = 'alert alert-' + type + ' ska-form-alert mt-3';
    div.setAttribute('role', 'alert');
    div.textContent = message;
    form.prepend(div);
    div.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function setLoading(form, loading) {
    var btn = form.querySelector('[type="submit"]');
    if (!btn) return;
    btn.disabled = loading;
    if (loading) {
      btn.dataset.origText = btn.innerHTML;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending…';
    } else if (btn.dataset.origText) {
      btn.innerHTML = btn.dataset.origText;
    }
  }

  function formData(form) {
    var fd = new FormData(form);
    var obj = {};
    fd.forEach(function (v, k) { obj[k] = v; });
    return obj;
  }

  function shouldUseSupabase(form) {
    if (!global.SkaApi || !SkaApi.isAvailable()) return false;
    if (form.dataset.skaForcePhp === 'true') return false;
    return cfg && cfg.isStaticHost();
  }

  async function handleInquiry(form) {
    var d = formData(form);
    if (!d.name || !d.email || !d.message) {
      showAlert(form, 'danger', 'Please fill in name, email, and message.');
      return;
    }
    setLoading(form, true);
    try {
      await SkaApi.submitInquiry(d);
      form.reset();
      showAlert(form, 'success', 'Thank you — your message has been sent. We\'ll be in touch shortly.');
    } catch (err) {
      showAlert(form, 'danger', err.message || 'Could not send message. Please call us directly.');
    } finally {
      setLoading(form, false);
    }
  }

  async function handleBooking(form) {
    var d = formData(form);
    if (!d.name || !d.email || !d.phone || !d.room_type || !d.checkin || !d.checkout) {
      showAlert(form, 'danger', 'Please complete all required fields.');
      return;
    }
    setLoading(form, true);
    try {
      await SkaApi.submitBooking(d);
      form.reset();
      showAlert(form, 'success', 'Thank you! Your booking request has been sent successfully. We\'ll be in touch shortly.');
    } catch (err) {
      showAlert(form, 'danger', err.message || 'Could not submit booking. Please call us directly.');
    } finally {
      setLoading(form, false);
    }
  }

  function bindForms() {
    document.querySelectorAll('form[data-ska-form="inquiry"]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        if (!shouldUseSupabase(form)) return;
        e.preventDefault();
        handleInquiry(form);
      });
    });

    document.querySelectorAll('form[data-ska-form="booking"]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        if (!shouldUseSupabase(form)) return;
        e.preventDefault();
        handleBooking(form);
      });
    });

    document.querySelectorAll('form.booking-form, form#bookingForm').forEach(function (form) {
      if (form.dataset.skaForm) return;
      form.dataset.skaForm = 'booking';
      form.addEventListener('submit', function (e) {
        if (!shouldUseSupabase(form)) return;
        e.preventDefault();
        handleBooking(form);
      });
    });

    document.querySelectorAll('form[action*="process_inquiry"]').forEach(function (form) {
      if (!form.dataset.skaForm) form.dataset.skaForm = 'inquiry';
      form.addEventListener('submit', function (e) {
        if (!shouldUseSupabase(form)) return;
        e.preventDefault();
        handleInquiry(form);
      });
    });
  }

  /** Rewrite .php links → .html on GitHub Pages */
  function fixLinks() {
    if (!cfg || !cfg.isStaticHost()) return;
    document.querySelectorAll('a[href]').forEach(function (a) {
      var href = a.getAttribute('href');
      if (!href || href.indexOf('http') === 0 || href.indexOf('#') === 0 || href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0) return;
      a.setAttribute('href', href.replace(/\.php/g, '.html'));
    });
    document.querySelectorAll('form[action]').forEach(function (f) {
      if (f.dataset.skaForm) return;
      var action = f.getAttribute('action');
      if (action && action.indexOf('process_') >= 0) {
        f.dataset.skaForm = action.indexOf('inquiry') >= 0 ? 'inquiry' : 'booking';
      }
    });
  }

  function init() {
    fixLinks();
    bindForms();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  global.SkaForms = { init: init, fixLinks: fixLinks };
})(window);
