</main>
  </div>
</div>

<!-- ===== TOAST (notifikasi mengambang) ===== -->
<div id="toast-wrap" class="fixed top-4 right-4 z-[70] flex flex-col gap-2.5 w-full max-w-xs pointer-events-none" aria-live="polite" aria-atomic="true"></div>

<!-- ===== MODAL KONFIRMASI ===== -->
<div id="confirm-modal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/40 p-4">
  <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden">
    <div class="p-6">
      <div class="flex items-start gap-4">
        <span id="confirm-icon" class="grid place-items-center w-11 h-11 rounded-xl shrink-0 bg-rose-50 text-rose-600">
          <i data-lucide="alert-triangle" class="w-5 h-5"></i>
        </span>
        <div class="min-w-0">
          <h3 id="confirm-title" class="font-semibold text-slate-900" style="letter-spacing:-0.02em">Konfirmasi</h3>
          <p id="confirm-msg" class="text-sm text-slate-500 mt-1"></p>
        </div>
      </div>
    </div>
    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-2">
      <button id="confirm-cancel" type="button" class="px-5 py-2.5 rounded-xl text-slate-700 font-semibold hover:bg-slate-100 transition">Batal</button>
      <button id="confirm-ok" type="button" class="px-5 py-2.5 rounded-xl bg-rose-600 text-white font-semibold hover:bg-rose-700 transition shadow-lg shadow-rose-600/25">Ya, lanjut</button>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/lucide.min.js"></script>
<script>
  lucide.createIcons();
  function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('-translate-x-full');
    document.getElementById('overlay').classList.toggle('hidden');
  }

  /* ===== Toast — notifikasi mengambang, palet semantik DESIGN.md ===== */
  (function(){
    const TYPES = {
      success: { cls:'bg-emerald-50 border-emerald-200 text-emerald-700', icon:'check-circle' },
      error:   { cls:'bg-rose-50 border-rose-200 text-rose-700',          icon:'alert-circle' },
      warning: { cls:'bg-amber-50 border-amber-200 text-amber-700',       icon:'alert-triangle' },
      info:    { cls:'bg-blue-50 border-blue-200 text-blue-700',          icon:'info' },
    };
    window.toast = function(message, type, duration){
      const t = TYPES[type] || TYPES.info;
      const wrap = document.getElementById('toast-wrap');
      const el = document.createElement('div');
      el.className = 'toast-item pointer-events-auto flex items-start gap-2.5 rounded-xl border px-4 py-3 text-sm shadow-xl ' + t.cls;
      el.setAttribute('role', 'alert');
      const ico = document.createElement('i');
      ico.setAttribute('data-lucide', t.icon);
      ico.className = 'w-4 h-4 shrink-0 mt-0.5';
      const span = document.createElement('span');
      span.className = 'flex-1 leading-snug';
      span.textContent = message;
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'shrink-0 opacity-50 hover:opacity-100 transition';
      btn.innerHTML = '<i data-lucide="x" class="w-3.5 h-3.5"></i>';
      el.append(ico, span, btn);
      wrap.appendChild(el);
      lucide.createIcons();

      let timer;
      const dismiss = () => {
        clearTimeout(timer);
        el.classList.add('toast-out');
        el.addEventListener('animationend', () => el.remove(), { once:true });
      };
      btn.addEventListener('click', dismiss);
      timer = setTimeout(dismiss, duration || 4000);
      return el;
    };
  })();

  /* ===== Modal konfirmasi — pengganti confirm() native ===== */
  (function(){
    const modal  = document.getElementById('confirm-modal');
    const titleEl= document.getElementById('confirm-title');
    const msgEl  = document.getElementById('confirm-msg');
    const iconEl = document.getElementById('confirm-icon');
    const okBtn  = document.getElementById('confirm-ok');
    const noBtn  = document.getElementById('confirm-cancel');

    // variant → palet semantik (danger = rose, primary = blue, warning = amber)
    const VARIANTS = {
      danger:  { surface:'bg-rose-50 text-rose-600',     icon:'alert-triangle', ok:'bg-rose-600 hover:bg-rose-700 shadow-rose-600/25' },
      primary: { surface:'bg-blue-50 text-blue-600',     icon:'help-circle',    ok:'bg-blue-600 hover:bg-blue-700 shadow-blue-600/25' },
      warning: { surface:'bg-amber-50 text-amber-600',   icon:'alert-triangle', ok:'bg-amber-600 hover:bg-amber-700 shadow-amber-600/25' },
    };
    let resolver = null;

    function close(result){
      modal.classList.add('hidden');
      modal.classList.remove('flex');
      const r = resolver; resolver = null;
      if (r) r(result);
    }

    window.confirmDialog = function(opts){
      opts = opts || {};
      const v = VARIANTS[opts.variant] || VARIANTS.danger;
      titleEl.textContent = opts.title || 'Konfirmasi';
      msgEl.textContent    = opts.message || '';
      msgEl.classList.toggle('hidden', !opts.message);
      iconEl.className = 'grid place-items-center w-11 h-11 rounded-xl shrink-0 ' + v.surface;
      iconEl.innerHTML = '<i data-lucide="' + v.icon + '" class="w-5 h-5"></i>';
      okBtn.textContent = opts.okText || 'Ya, lanjut';
      okBtn.className = 'px-5 py-2.5 rounded-xl text-white font-semibold transition shadow-lg ' + v.ok;
      noBtn.textContent = opts.cancelText || 'Batal';
      lucide.createIcons();
      modal.classList.remove('hidden');
      modal.classList.add('flex');
      return new Promise(res => { resolver = res; });
    };

    okBtn.addEventListener('click', () => close(true));
    noBtn.addEventListener('click', () => close(false));
    modal.addEventListener('click', e => { if (e.target === modal) close(false); });
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && !modal.classList.contains('hidden')) close(false);
    });

    // Intersepsi form ber-atribut data-confirm → tampilkan modal sebelum submit
    document.addEventListener('submit', function(e){
      const form = e.target;
      if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || form.dataset.confirmed) return;
      e.preventDefault();
      confirmDialog({
        title:   form.dataset.confirmTitle,
        message: form.dataset.confirm,
        variant: form.dataset.confirmVariant || 'danger',
        okText:  form.dataset.confirmOk,
      }).then(ok => {
        if (ok){ form.dataset.confirmed = '1'; form.submit(); }
      });
    }, true);
  })();
</script>
<?= $extra_js ?? '' ?>
</body>
</html>
