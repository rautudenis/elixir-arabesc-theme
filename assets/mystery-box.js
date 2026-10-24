/* Elixir Arabesc – Mystery Box landing. Vanilla JS, fără dependențe. */
(function () {
  'use strict';

  var C = window.EA_MB || {};
  var root = document.getElementById('ea-page');
  if (!root) return;

  var $ = function (s, c) { return (c || root).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || root).querySelectorAll(s)); };

  var LABELS = { m: '4 Bărbați', f: '4 Femei', x: '2+2 Mix' };
  var mountedAt = Date.now();
  var st = {
    choice: 'x', bundle: 1, ship: 'courier', pay: 'cod',
    open: false, openedAt: 0, now: Date.now(), submitted: false, sending: false
  };

  var modal = $('#ea-order');
    var form = $('#ea-form');
  var toast = $('[data-ea-toast]');

  /* ---------- helpers ---------- */
  function fmt(n) {
    n = Number(n);
    return (Math.floor(n) === n ? String(n) : n.toFixed(2).replace('.', ',')) + ' lei';
  }
  function pad(n) { return String(Math.max(0, n)).padStart(2, '0'); }
  function store(k, v) { try { if (v === undefined) return window.sessionStorage.getItem(k); window.sessionStorage.setItem(k, v); } catch (e) {} return null; }

  function deliveryDate(now) {
    var d = new Date(now), n = 0;
    while (n < 2) { d.setDate(d.getDate() + 1); if (d.getDay() !== 0 && d.getDay() !== 6) n++; }
    return d.toLocaleDateString('ro-RO', { weekday: 'long', day: 'numeric', month: 'long' });
  }

  function freeShip() { return st.bundle >= 2; }

  /* ---------- render ---------- */
  function render() {
    var now = st.now;
    var c = C.courier, e = C.easybox;
    var shipCost = st.ship === 'easybox' ? e : c;
    var sub = C.prices[st.bundle];
    var free = freeShip();
    var ship = free ? 0 : shipCost;
    var pay = C.cardEnabled ? st.pay : 'cod';
    var dpct = pay === 'card' ? (C.cardDiscount || 0) : 0;
    var disc = dpct > 0 ? Math.round(sub * dpct) / 100 : 0;
    var total = sub - disc + ship;

    var midnight = new Date(now); midnight.setHours(24, 0, 0, 0);
    var left = Math.floor((midnight - now) / 1000);
    var resLeft = Math.max(0, Math.floor((st.openedAt + 600000 - now) / 1000));
    var el = Math.floor((now - mountedAt) / 1000);

    var phone = form ? form.elements.phone.value : '';
    var name = form ? form.elements.name.value.trim() : '';

    var vals = {
      hh: pad(Math.floor(left / 3600)), mm: pad(Math.floor(left % 3600 / 60)), ss: pad(left % 60),
      resMM: pad(Math.floor(resLeft / 60)), resSS: pad(resLeft % 60),
      viewers: 19 + ((Math.floor(el / 6) * 7919) % 14),
      delivery: deliveryDate(now),
      choiceLabel: LABELS[st.choice],
      bundleTotal: fmt(sub),
      bundleLine: st.bundle + (st.bundle === 1 ? ' cutie' : ' cutii') + ' · ' + st.bundle * 4 + ' parfumuri × 100 ml',
      subtotal: fmt(sub),
      discount: disc > 0 ? '−' + fmt(disc) : '',
      discLabel: 'Reducere card (−' + String(dpct).replace('.', ',') + '%)',
      shipping: ship > 0 ? fmt(ship) : 'Gratuită',
      total: fmt(total),
      courierPrice: free ? 'Gratuit' : fmt(c),
      easyboxPrice: free ? 'Gratuit' : fmt(e),
      addressLabel: st.ship === 'easybox' ? 'Locker easybox preferat' : 'Adresă',
      submitLabel: (pay === 'cod' ? 'Comand și plătesc la livrare · ' : 'Comand și plătesc cu cardul · ') + fmt(total),
      submitNote: pay === 'cod' ? 'Nu plătești nimic acum. Te sunăm pentru confirmare.' : 'Vei fi redirecționat către plata securizată.',
      payLabel: pay === 'cod' ? 'plata la curier' : 'plătit cu cardul',
      firstName: name.split(/\s+/)[0] || '',
      phoneVal: phone
    };
    $$('[data-ea]').forEach(function (n) {
      var k = n.getAttribute('data-ea');
      if (k in vals && n.textContent !== String(vals[k])) n.textContent = vals[k];
    });

    $$('[data-ea-choice]').forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-ea-choice') === st.choice); });
    $$('[data-ea-bundle]').forEach(function (b) { b.classList.toggle('is-on', +b.getAttribute('data-ea-bundle') === st.bundle); });
    $$('[data-ea-ship]').forEach(function (b) {
      b.classList.toggle('is-on', b.getAttribute('data-ea-ship') === st.ship);
      b.classList.toggle('is-free', free);
    });
    $$('[data-ea-pay]').forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-ea-pay') === pay); });
    var dr = $('[data-ea-discrow]'); if (dr) dr.hidden = disc <= 0;
    var hint = $('[data-ea-freehint]'); if (hint) hint.hidden = st.bundle !== 1;
    var shipEl = $('[data-ea-shipping]'); if (shipEl) shipEl.classList.toggle('is-free', ship === 0);
    var ph = $('[data-ea-placeholder]');
    if (ph) ph.placeholder = st.ship === 'easybox' ? 'Ex. easybox Mega Image, Str. Florilor 12' : 'Strada, număr, bloc, apartament';

    if (form) {
      var set = function (k, v) { var i = form.querySelector('[data-ea-input="' + k + '"]'); if (i) i.value = v; };
      set('choice', st.choice); set('bundle', st.bundle); set('ship', st.ship);
      set('pay', pay);
    }

    // Toast comenzi recente: ciclu 16s, vizibil 4s–11s, prima după ~5s.
    var people = C.toasts || [];
    if (toast && people.length) {
      var phase = el % 16, ti = Math.floor(el / 16), tp = people[ti % people.length];
      var on = !st.open && el > 5;
      toast.hidden = !on;
      if (on) {
        var t = { toastName: tp.name, toastCity: tp.city, toastChoice: tp.choice, toastAgo: 2 + (ti * 5) % 17 };
        $$('[data-ea]', toast).forEach(function (n) { var k = n.getAttribute('data-ea'); if (k in t) n.textContent = t[k]; });
        var vial = $('[data-ea="toastColor"]', toast); if (vial) vial.style.background = tp.color;
        toast.classList.toggle('is-visible', phase >= 4 && phase < 11);
      }
    }
  }

  /* ---------- modal ---------- */
  function openModal() {
    if (!modal) return;
    if (!st.open) { st.openedAt = Date.now(); try { window.dispatchEvent(new CustomEvent('ea:checkout', { detail: { value: C.prices && C.prices[st.bundle] || C.basePrice, currency: 'RON', items: [{ id: C.productId || 'mystery-box', name: 'Mystery Box', qty: 1, price: C.basePrice }] } })); } catch (e) {} }
    st.open = true; st.submitted = false;
    setView('form');
    modal.hidden = false;
    root.classList.add('ea-modal-open');
    document.documentElement.style.overflow = 'hidden';
    render();
  }
  function closeModal() {
    st.open = false;
    if (modal) modal.hidden = true;
    root.classList.remove('ea-modal-open');
    document.documentElement.style.overflow = '';
    if (location.hash === '#ea-order') history.replaceState(null, '', location.pathname + location.search);
    render();
  }
  function setView(v) {
    $$('[data-ea-view]').forEach(function (n) { n.hidden = n.getAttribute('data-ea-view') !== v; });
  }

  /* ---------- validare + trimitere ---------- */
  function validate() {
    var f = form.elements, e = {};
    if (f.name.value.trim().split(/\s+/).length < 2) e.name = 1;
    if (!/^(\+?40|0)7\d{8}$/.test(f.phone.value.replace(/[\s.-]/g, ''))) e.phone = 1;
    if (!f.county.value.trim()) e.county = 1;
    if (!f.city.value.trim()) e.city = 1;
    if (f.address.value.trim().length < 5) e.address = 1;
    if (f.email && f.email.value.trim() && !/^\S+@\S+\.\S+$/.test(f.email.value.trim())) e.email = 1;
    if (f.terms && !f.terms.checked) e.terms = 1;
    return e;
  }
  function showErrors(e) {
    ['name', 'phone', 'email', 'county', 'city', 'address'].forEach(function (k) {
      var inp = form.elements[k]; if (inp) inp.classList.toggle('is-err', !!e[k]);
    });
    var tl = $('[data-ea-terms]'); if (tl) tl.classList.toggle('is-err', !!e.terms);
    var map = { name: e.name, phone: e.phone, email: e.email, address: e.address || e.city || e.county, terms: e.terms };
    $$('[data-ea-err]').forEach(function (n) { n.hidden = !map[n.getAttribute('data-ea-err')]; });
  }
  function formError(msg) {
    var n = $('[data-ea-formerror]'); if (!n) return;
    n.textContent = msg || ''; n.hidden = !msg;
  }

  function submit(ev) {
    ev.preventDefault();
    if (st.sending) return;
    formError('');
    var errs = validate();
    showErrors(errs);
    if (Object.keys(errs).length) {
      var first = form.querySelector('input.is-err') || (errs.terms && form.elements.terms); if (first) first.focus();
      return;
    }
    var data = {};
    new FormData(form).forEach(function (v, k) { data[k] = v; });
    st.sending = true;
    var btn = $('.ea-submit'); if (btn) btn.disabled = true;

    var send = function () {
      return fetch(C.endpoint, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      }).then(function (r) { return r.json().then(function (j) { return { s: r.status, j: j }; }); });
    };
    // Token expirat (pagină veche / cache): îl reînnoim în fundal și reîncercăm o dată, fără ca clientul să vadă ceva.
    var refresh = function () {
      return fetch(C.tokenUrl, { credentials: 'same-origin', cache: 'no-store' })
        .then(function (r) { return r.json(); })
        .then(function (t) {
          data.nonce = t.token;
          var h = form.elements.nonce; if (h) h.value = t.token;
          C.nonce = t.token;
        });
    };

    send().then(function (res) {
      if (res.j && res.j.expired) { return refresh().then(send); }
      return res;
    }).then(function (res) {
        st.sending = false; if (btn) btn.disabled = false;
        var j = res.j || {};
        if (j.ok) {
          if (j.status === 'card' && j.netopia && j.netopia.action) {
            // Direct la pagina de plată Netopia, fără pagini intermediare.
            var nf = document.createElement('form');
            nf.method = 'post';
            nf.action = j.netopia.action;
            Object.keys(j.netopia.fields || {}).forEach(function (k) {
              var i = document.createElement('input');
              i.type = 'hidden'; i.name = k; i.value = j.netopia.fields[k];
              nf.appendChild(i);
            });
            document.body.appendChild(nf);
            nf.submit();
            return;
          }
          if (j.status === 'card' && j.redirect) { window.location.href = j.redirect; return; }
          onSuccess(j);
        } else if (j.errors) {
          showErrors(j.errors);
        } else {
          formError(j.message || 'Nu am putut trimite comanda. Încearcă din nou.');
        }
      })
      .catch(function () {
        // Fallback: trimitere clasică (admin-post).
        st.sending = false;
        form.removeEventListener('submit', submit);
        form.submit();
      });
  }

  function onSuccess(j) {
    st.submitted = true;
    setView('done');
    render();
    var detail = { order_id: j.order_id, order_number: j.order_number, value: j.total, currency: j.currency || 'RON', items: j.items || [], payment: j.payment || 'cod' };
    try {
      window.dispatchEvent(new CustomEvent('ea:purchase', { detail: detail }));
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({
        event: 'purchase',
        ecommerce: {
          transaction_id: String(j.order_id), value: j.total, currency: detail.currency,
          items: detail.items.map(function (i) { return { item_id: String(i.id), item_name: i.name, price: i.price, quantity: i.qty }; })
        }
      });
    } catch (e) {}
  }

  /* ---------- evenimente ---------- */
  root.addEventListener('click', function (ev) {
    var t = ev.target;
    var hit = function (sel) { return t.closest ? t.closest(sel) : null; };
    var b;

    if ((b = hit('[data-ea-open]'))) { ev.preventDefault(); openModal(); return; }
    if ((b = hit('[data-ea-reviews]'))) {
      var r = $('#ea-reviews'); if (r) window.scrollTo({ top: r.getBoundingClientRect().top + window.scrollY, behavior: 'smooth' });
      return;
    }
    if (hit('[data-ea-close]')) { closeModal(); return; }
    if (t === modal) { closeModal(); return; }

    if ((b = hit('[data-ea-choice]'))) { st.choice = b.getAttribute('data-ea-choice'); render(); return; }
    if ((b = hit('[data-ea-bundle]'))) { st.bundle = +b.getAttribute('data-ea-bundle'); render(); return; }
    if ((b = hit('[data-ea-ship]'))) { st.ship = b.getAttribute('data-ea-ship'); render(); return; }
    if ((b = hit('[data-ea-pay]'))) { st.pay = b.getAttribute('data-ea-pay'); render(); return; }
  });

  document.addEventListener('keydown', function (ev) {
    if (ev.key !== 'Escape') return;
    if (st.open) closeModal();
  });

  if (form) {
    form.addEventListener('submit', submit);
    form.addEventListener('input', function (ev) {
      var k = ev.target.name;
      if (k === 'terms') {
        var tl = $('[data-ea-terms]'); if (tl) tl.classList.remove('is-err');
        var te = $('[data-ea-err="terms"]'); if (te) te.hidden = true;
        return;
      }
      if (!k || !(k in { name: 1, phone: 1, email: 1, county: 1, city: 1, address: 1 })) return;
      ev.target.classList.remove('is-err');
      var map = { name: 'name', phone: 'phone', email: 'email', county: 'address', city: 'address', address: 'address' };
      var err = $('[data-ea-err="' + map[k] + '"]'); if (err) err.hidden = true;
    });
  }

  /* ---------- tick ---------- */
  setInterval(function () {
    st.now = Date.now();
    render();
  }, 1000);

  if (C.done) {
    // Întors de la plata cu cardul: mesajul de mulțumire în modal, ca la ramburs.
    var dn = C.done;
    if (LABELS[dn.choice]) st.choice = dn.choice;
    if (dn.bundle === 1 || dn.bundle === 2) st.bundle = dn.bundle;
    st.pay = 'card';
    if (form) { form.elements.name.value = dn.name; form.elements.phone.value = dn.phone; }
    openModal();
    var seen = 'ea_done_' + dn.order_id;
    if (store(seen)) { st.submitted = true; setView('done'); render(); }
    else { store(seen, '1'); dn.payment = 'card'; onSuccess(dn); }
    history.replaceState(null, '', location.pathname);
  } else if (C.fail) {
    // Plata cu cardul nu a reușit: formularul reumplut + mesaj, ca să poată încerca din nou.
    var fl = C.fail, ff = fl.form || {};
    if (LABELS[fl.choice]) st.choice = fl.choice;
    if (fl.bundle === 1 || fl.bundle === 2) st.bundle = fl.bundle;
    if (fl.ship === 'easybox') st.ship = 'easybox';
    if (form) {
      ['name', 'phone', 'email', 'county', 'city', 'address'].forEach(function (k) {
        if (form.elements[k] && ff[k]) form.elements[k].value = ff[k];
      });
      if (form.elements.terms) form.elements.terms.checked = true;
    }
    openModal();
    formError('Plata cu cardul nu a fost finalizată (tranzacție refuzată sau anulată). Nu s-a înregistrat comanda. Poți încerca din nou sau alege plata la livrare.');
    history.replaceState(null, '', location.pathname);
  } else if (location.hash === '#ea-order') openModal(); else render();
})();

/* UGC video: pornește la vedere (mut), pauză la ieșire; butonul comută sunetul. */
(function () {
  var vids = document.querySelectorAll('[data-ea-ugc-video]');
  if (!vids.length) return;
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  var io = 'IntersectionObserver' in window && !reduce ? new IntersectionObserver(function (es) {
    es.forEach(function (e) {
      if (e.isIntersecting) { var p = e.target.play(); if (p && p.catch) p.catch(function () {}); }
      else e.target.pause();
    });
  }, { threshold: 0.5 }) : null;
  Array.prototype.forEach.call(vids, function (v) {
    if (io) io.observe(v);
    var btn = v.parentNode.querySelector('[data-ea-ugc-sound]');
    if (!btn) return;
    btn.addEventListener('click', function () {
      v.muted = !v.muted;
      btn.setAttribute('aria-pressed', v.muted ? 'false' : 'true');
      btn.setAttribute('aria-label', v.muted ? 'Pornește sunetul' : 'Oprește sunetul');
      if (!v.muted && v.paused) v.play();
    });
  });
})();
