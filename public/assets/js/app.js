(function () {
  const root = document.documentElement;
  const storedTheme = localStorage.getItem('fishmarket-theme');
  if (storedTheme) root.setAttribute('data-theme', storedTheme);

  document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const current = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      if (current === 'dark') root.setAttribute('data-theme', 'dark');
      else root.removeAttribute('data-theme');
      localStorage.setItem('fishmarket-theme', current === 'dark' ? 'dark' : 'light');
    });
  });

  const sidebar = document.getElementById('sidebar');
  const overlay = document.querySelector('.mobile-overlay');
  const openSidebar = () => {
    if (!sidebar) return;
    sidebar.classList.add('open');
    overlay && overlay.classList.add('show');
    document.body.classList.add('no-scroll');
  };
  const closeSidebar = () => {
    if (!sidebar) return;
    sidebar.classList.remove('open');
    overlay && overlay.classList.remove('show');
    document.body.classList.remove('no-scroll');
  };
  document.querySelectorAll('[data-open-sidebar]').forEach((el) => el.addEventListener('click', openSidebar));
  document.querySelectorAll('[data-close-sidebar]').forEach((el) => el.addEventListener('click', closeSidebar));

  document.querySelectorAll('[data-confirm]').forEach((link) => {
    link.addEventListener('click', (event) => {
      if (!confirm(link.dataset.confirm || 'Lanjutkan aksi ini?')) event.preventDefault();
    });
  });

  document.querySelectorAll('.flash-close').forEach((button) => {
    button.addEventListener('click', () => button.closest('.flash')?.remove());
  });
  window.setTimeout(() => {
    document.querySelectorAll('.flash').forEach((el) => {
      el.style.opacity = '0';
      el.style.transform = 'translateX(20px)';
      window.setTimeout(() => el.remove(), 250);
    });
  }, 5200);

  document.querySelectorAll('.btn, .nav-link, .role-card, .icon-button').forEach((el) => {
    el.addEventListener('click', function (event) {
      const rect = this.getBoundingClientRect();
      const ripple = document.createElement('span');
      const size = Math.max(rect.width, rect.height);
      ripple.className = 'ripple';
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = event.clientX - rect.left - size / 2 + 'px';
      ripple.style.top = event.clientY - rect.top - size / 2 + 'px';
      this.style.position = this.style.position || 'relative';
      this.style.overflow = 'hidden';
      this.appendChild(ripple);
      window.setTimeout(() => ripple.remove(), 700);
    });
  });



  document.querySelectorAll('img').forEach((img) => {
    img.addEventListener('error', () => {
      if (!img.dataset.fallbackApplied) {
        img.dataset.fallbackApplied = '1';
        img.src = 'assets/img/fish-empty.svg';
        img.classList.add('image-fallback');
      }
    });
  });

  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    const roleField = document.getElementById('roleField');
    const usernameField = document.getElementById('usernameField');
    const passwordField = document.getElementById('passwordField');
    document.querySelectorAll('.role-card').forEach((card) => {
      if (card.dataset.role === roleField.value) card.classList.add('active');
      card.addEventListener('click', () => {
        document.querySelectorAll('.role-card').forEach((item) => item.classList.remove('active'));
        card.classList.add('active');
        roleField.value = card.dataset.role || '';
        usernameField.value = card.dataset.username || '';
        passwordField.value = card.dataset.password || '';
      });
    });
  }

  document.querySelectorAll('.transaction-form').forEach((form) => {
    let prices = {};
    try { prices = JSON.parse(form.dataset.prices || '{}'); } catch (e) { prices = {}; }
    const body = form.querySelector('[data-item-body]');
    const template = form.querySelector('[data-row-template]');
    const totalWeight = form.querySelector('[data-total-weight]');
    const totalPrice = form.querySelector('[data-total-price]');

    const rupiah = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0);
    const refreshRow = (row) => {
      const fish = row.querySelector('select[name="id_ikan[]"]');
      const weight = row.querySelector('input[name="berat_kg[]"]');
      const price = row.querySelector('input[name$="_per_kg[]"]');
      const subtotal = row.querySelector('[data-row-subtotal]');
      if (fish && price && fish.value && (!price.value || Number(price.value) === 0)) {
        price.value = prices[fish.value] || 0;
      }
      const sub = Number(weight?.value || 0) * Number(price?.value || 0);
      if (subtotal) subtotal.textContent = rupiah(sub);
    };
    const refreshTotal = () => {
      let w = 0;
      let p = 0;
      body.querySelectorAll('tr').forEach((row) => {
        const weight = Number(row.querySelector('input[name="berat_kg[]"]')?.value || 0);
        const price = Number(row.querySelector('input[name$="_per_kg[]"]')?.value || 0);
        w += weight;
        p += weight * price;
      });
      totalWeight && (totalWeight.textContent = w.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' kg');
      totalPrice && (totalPrice.textContent = rupiah(p));
    };
    const bindRow = (row) => {
      row.querySelectorAll('input,select').forEach((input) => {
        input.addEventListener('input', () => { refreshRow(row); refreshTotal(); });
        input.addEventListener('change', () => {
          if (input.matches('select[name="id_ikan[]"]')) {
            const price = row.querySelector('input[name$="_per_kg[]"]');
            if (price) price.value = prices[input.value] || 0;
          }
          refreshRow(row); refreshTotal();
        });
      });
      row.querySelector('[data-remove-row]')?.addEventListener('click', () => {
        if (body.querySelectorAll('tr').length > 1) row.remove();
        refreshTotal();
      });
      refreshRow(row);
    };
    body.querySelectorAll('tr').forEach(bindRow);
    form.querySelector('[data-add-row]')?.addEventListener('click', () => {
      const wrapper = document.createElement('tbody');
      wrapper.innerHTML = template.innerHTML.trim();
      const row = wrapper.querySelector('tr');
      body.appendChild(row);
      bindRow(row);
      row.animate([{ opacity: 0, transform: 'translateY(-8px)' }, { opacity: 1, transform: 'translateY(0)' }], { duration: 220, easing: 'ease-out' });
      refreshTotal();
    });
    refreshTotal();
  });

  const revealObserver = 'IntersectionObserver' in window ? new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.animate([{ opacity: 0, transform: 'translateY(14px)' }, { opacity: 1, transform: 'translateY(0)' }], { duration: 360, easing: 'ease-out' });
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: .08 }) : null;
  if (revealObserver) document.querySelectorAll('.card,.stat-card,.fish-card').forEach((el) => revealObserver.observe(el));
})();
