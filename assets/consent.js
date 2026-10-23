/* Elixir Arabesc – preferințe cookie-uri (cookie ea_consent, 12 luni) + pixeli Meta/TikTok încărcați DOAR după „Accept toate”. */
(function () {
  var box = document.getElementById('ea-consent');
  var T = window.EA_TRACK || {};
  var loaded = false;

  function read() {
    var m = document.cookie.match(/(?:^|; )ea_consent=([^;]*)/);
    return m ? decodeURIComponent(m[1]) : '';
  }

  /* ---------- pixeli ---------- */
  function purchase(ev) {
    var d = (ev && ev.detail) || {};
    var ids = (d.items || []).map(function (i) { return String(i.id); });
    var val = Number(d.value) || 0, cur = d.currency || 'RON';
    if (window.fbq && T.meta) window.fbq('track', 'Purchase', { value: val, currency: cur, content_ids: ids, content_type: 'product', num_items: (d.items || []).length }, { eventID: 'order-' + d.order_id });
    if (window.ttq) window.ttq.track('PlaceAnOrder', { value: val, currency: cur, content_type: 'product', contents: (d.items || []).map(function (i) { return { content_id: String(i.id), content_name: i.name, quantity: i.qty, price: i.price }; }) });
  }
  function checkout() {
    if (window.fbq && T.meta) window.fbq('track', 'InitiateCheckout');
    if (window.ttq) window.ttq.track('InitiateCheckout');
  }
  function loadPixels() {
    if (loaded) return; loaded = true;
    if (T.meta) {
      !function (f, b, e, v, n, t, s) { if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); }; if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = []; t = b.createElement(e); t.async = !0; t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s); }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
      window.fbq('init', T.meta);
      window.fbq('track', 'PageView');
      window.fbq('track', 'ViewContent', { content_name: 'Mystery Box', content_type: 'product' });
    }
    if (T.tiktok) {
      !function (w, d, t) { w.TiktokAnalyticsObject = t; var ttq = w[t] = w[t] || []; ttq.methods = ['page', 'track', 'identify', 'instances', 'debug', 'on', 'off', 'once', 'ready', 'alias', 'group', 'enableCookie', 'disableCookie']; ttq.setAndDefer = function (t, e) { t[e] = function () { t.push([e].concat(Array.prototype.slice.call(arguments, 0))); }; }; for (var i = 0; i < ttq.methods.length; i++) ttq.setAndDefer(ttq, ttq.methods[i]); ttq.instance = function (t) { for (var e = ttq._i[t] || [], n = 0; n < ttq.methods.length; n++) ttq.setAndDefer(e, ttq.methods[n]); return e; }; ttq.load = function (e, n) { var i = 'https://analytics.tiktok.com/i18n/pixel/events.js'; ttq._i = ttq._i || {}; ttq._i[e] = []; ttq._i[e]._u = i; ttq._t = ttq._t || {}; ttq._t[e] = +new Date(); ttq._o = ttq._o || {}; ttq._o[e] = n || {}; var o = document.createElement('script'); o.type = 'text/javascript'; o.async = !0; o.src = i + '?sdkid=' + e + '&lib=' + t; var a = document.getElementsByTagName('script')[0]; a.parentNode.insertBefore(o, a); }; ttq.load(T.tiktok); ttq.page(); }(window, document, 'ttq');
      window.ttq.track('ViewContent', { content_name: 'Mystery Box', content_type: 'product' });
    }
    /* Pixel TikTok încărcat din alt loc (ex. plugin TikTok for Business): doar evenimentele temei. */
    else if (window.ttq) window.ttq.track('ViewContent', { content_name: 'Mystery Box', content_type: 'product' });
    window.addEventListener('ea:purchase', purchase);
    window.addEventListener('ea:checkout', checkout);
  }

  /* ---------- consimțământ ---------- */
  function announce(v) {
    window.eaConsent = { necessary: true, analytics: v === 'all', marketing: v === 'all' };
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ event: 'ea_consent_update', ea_consent: v });
    try { document.dispatchEvent(new CustomEvent('ea:consent', { detail: window.eaConsent })); } catch (e) {}
    if (v === 'all') loadPixels();
  }
  function save(v) {
    var prev = read();
    var secure = location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = 'ea_consent=' + v + '; Max-Age=31536000; Path=/; SameSite=Lax' + secure;
    if (box) box.hidden = true;
    if (prev === 'all' && v !== 'all') {
      // Retragerea consimțământului: ștergem cookie-urile de marketing și reîncărcăm pagina fără pixeli.
      ['_fbp', '_fbc', '_ttp', '_tt_enable_cookie', 'ttclid'].forEach(function (n) {
        document.cookie = n + '=; Max-Age=0; Path=/';
        document.cookie = n + '=; Max-Age=0; Path=/; Domain=.' + location.hostname.replace(/^www\./, '');
      });
      location.reload();
      return;
    }
    announce(v);
  }

  var cur = read();
  if (cur) announce(cur); else if (box) box.hidden = false;
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest && ev.target.closest('[data-ea-consent]');
    if (b) { save(b.getAttribute('data-ea-consent')); return; }
    var o = ev.target.closest && ev.target.closest('[data-ea-consent-open]');
    if (o && box) { ev.preventDefault(); box.hidden = false; }
  });
})();
