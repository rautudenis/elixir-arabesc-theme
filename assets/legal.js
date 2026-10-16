/* Formularul de retragere (pagina Retur și dreptul de retragere). */
(function () {
  var form = document.getElementById('ea-wform');
  if (!form || !window.EA_LEGAL) return;
  var err = form.querySelector('[data-ea-wform-err]');
  function show(m) { err.textContent = m || ''; err.hidden = !m; }
  function post(data) {
    return fetch(EA_LEGAL.endpoint, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
      .then(function (r) { return r.json().then(function (j) { return { s: r.status, j: j }; }); });
  }
  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    show('');
    form.querySelectorAll('.is-err').forEach(function (n) { n.classList.remove('is-err'); });
    var data = {}; new FormData(form).forEach(function (v, k) { data[k] = v; });
    var bad = false;
    ['name', 'email', 'order', 'received', 'products', 'address'].forEach(function (k) {
      var el = form.elements[k];
      if (!el || !String(el.value).trim() || (k === 'email' && !/^\S+@\S+\.\S+$/.test(el.value))) { if (el) el.classList.add('is-err'); bad = true; }
    });
    if (bad) { show('Completează câmpurile marcate cu * (e-mail valid).'); return; }
    var btn = form.querySelector('button[type=submit]'); btn.disabled = true;
    post(data).then(function (r) {
      if (r.j && r.j.expired) {
        return fetch(EA_LEGAL.tokenUrl, { cache: 'no-store' }).then(function (t) { return t.json(); }).then(function (t) { data.nonce = t.token; return post(data); });
      }
      return r;
    }).then(function (r) {
      btn.disabled = false;
      if (r.j && r.j.ok) { form.hidden = true; var ok = document.getElementById('ea-wform-ok'); ok.hidden = false; ok.scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
      if (r.j && r.j.errors) Object.keys(r.j.errors).forEach(function (k) { if (form.elements[k]) form.elements[k].classList.add('is-err'); });
      show((r.j && r.j.message) || 'Nu am putut trimite formularul. Scrie-ne la contact@elixirarabesc.ro.');
    }).catch(function () {
      btn.disabled = false;
      show('Nu am putut trimite formularul. Scrie-ne la contact@elixirarabesc.ro.');
    });
  });
})();
