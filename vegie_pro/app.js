/* ============================================================
   VEGIE PRO — Interactive Animations JS
============================================================ */

// 1. Number counter animation
function animateCounter(el) {
    const target = parseFloat(el.dataset.target || el.textContent.replace(/[^\d.-]/g, '')) || 0;
    const decimals = el.dataset.decimals ? parseInt(el.dataset.decimals) : 0;
    const prefix = el.dataset.prefix || '';
    const suffix = el.dataset.suffix || '';
    const duration = 1400;
    const start = performance.now();
    function step(now) {
        const p = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - p, 3);
        const val = target * eased;
        el.textContent = prefix + val.toLocaleString('en-US', {minimumFractionDigits: decimals, maximumFractionDigits: decimals}) + suffix;
        if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}
document.querySelectorAll('[data-counter]').forEach(animateCounter);

// 2. Live clock
function tick() {
    document.querySelectorAll('.live-clock').forEach(el => {
        const d = new Date();
        el.textContent = d.toLocaleTimeString('en-GB', {hour12: false});
    });
}
setInterval(tick, 1000); tick();

// 3. Live table search
function initSearch(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;
    input.addEventListener('input', () => {
        const q = input.value.toLowerCase();
        table.querySelectorAll('tbody tr').forEach(tr => {
            const match = tr.textContent.toLowerCase().includes(q);
            tr.style.display = match ? '' : 'none';
        });
    });
}
document.querySelectorAll('[data-search]').forEach(inp => initSearch(inp.id, inp.dataset.search));

// 4. Sortable table
document.querySelectorAll('table[data-sortable] thead th').forEach((th, i) => {
    th.style.cursor = 'pointer';
    th.title = 'Click to sort';
    th.addEventListener('click', () => {
        const table = th.closest('table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const asc = th.dataset.asc !== 'true';
        rows.sort((a, b) => {
            const av = a.children[i]?.textContent.trim() || '';
            const bv = b.children[i]?.textContent.trim() || '';
            const an = parseFloat(av.replace(/[^\d.-]/g, ''));
            const bn = parseFloat(bv.replace(/[^\d.-]/g, ''));
            if (!isNaN(an) && !isNaN(bn)) return asc ? an - bn : bn - an;
            return asc ? av.localeCompare(bv) : bv.localeCompare(av);
        });
        rows.forEach((r, idx) => { r.style.animation = 'none'; r.style.opacity = 1;
            setTimeout(() => { tbody.appendChild(r); }, 0); });
        th.dataset.asc = asc;
    });
});

// 5. Password toggle
document.querySelectorAll('.pwd-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = btn.previousElementSibling;
        if (!input) return;
        const isPwd = input.type === 'password';
        input.type = isPwd ? 'text' : 'password';
        btn.textContent = isPwd ? '🙈' : '👁';
    });
});

// 6. Toast notifications
function showToast(msg, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    const t = document.createElement('div');
    t.className = 'toast' + (type === 'error' ? ' error' : '');
    t.textContent = (type === 'error' ? '❌ ' : '✅ ') + msg;
    container.appendChild(t);
    setTimeout(() => { t.classList.add('hide'); setTimeout(() => t.remove(), 300); }, 3000);
}

// 7. Loading bar on form submit / link
document.querySelectorAll('form').forEach(f => {
    f.addEventListener('submit', () => {
        const bar = document.querySelector('.loading-bar');
        if (bar) bar.classList.add('show');
        const btn = f.querySelector('button[type=submit]');
        if (btn && !btn.dataset.noLoader) {
            setTimeout(() => {
                btn.innerHTML = '<span class="spinner"></span> Processing...';
                btn.disabled = true;
            }, 10);
        }
    });
});

// 8. Confirm delete modal (data-confirm)
document.querySelectorAll('form[data-confirm]').forEach(f => {
    f.addEventListener('submit', e => {
        if (f.dataset.confirmed === '1') return;
        e.preventDefault();
        if (confirm(f.dataset.confirm)) {
            f.dataset.confirmed = '1';
            f.submit();
        }
    });
});

// 9. Caps lock warning on login
const pwdField = document.querySelector('input[type=password]');
if (pwdField) {
    pwdField.addEventListener('keyup', e => {
        if (e.getModifierState && e.getModifierState('CapsLock')) {
            showToast('Caps Lock is ON', 'error');
        }
    });
}

// 10. Copy-to-clipboard buttons
document.querySelectorAll('[data-copy]').forEach(btn => {
    btn.addEventListener('click', () => {
        navigator.clipboard.writeText(btn.dataset.copy).then(() => showToast('Copied!'));
    });
});

// 11. Row click ripple feedback
document.querySelectorAll('tbody tr').forEach(tr => {
    tr.addEventListener('click', e => {
        if (e.target.closest('button,a,input,form')) return;
        tr.animate([{transform:'scale(1)'},{transform:'scale(1.01)'},{transform:'scale(1)'}], {duration:250});
    });
});

// 12. Fade in elements on scroll (IntersectionObserver)
if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(entries => {
        entries.forEach(en => {
            if (en.isIntersecting) {
                en.target.style.animation = 'slideUp .55s cubic-bezier(.34,1.56,.64,1) both';
                io.unobserve(en.target);
            }
        });
    }, {threshold: .1});
    document.querySelectorAll('.panel').forEach(el => io.observe(el));
}

// 13. Form validation shake
document.querySelectorAll('form').forEach(f => {
    f.addEventListener('submit', e => {
        const required = f.querySelectorAll('[required]');
        let bad = false;
        required.forEach(inp => {
            if (!inp.value.trim()) { bad = true; inp.classList.add('shake'); setTimeout(() => inp.classList.remove('shake'), 700); }
        });
        if (bad) { e.preventDefault(); showToast('Please fill all fields', 'error'); }
    });
});

// 14. Auto-dismiss alerts
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => {
        a.style.transition = 'opacity .4s, transform .4s';
        a.style.opacity = '0';
        a.style.transform = 'translateY(-10px)';
        setTimeout(() => a.remove(), 400);
    });
}, 4000);

// 15. Print invoice helper
document.querySelectorAll('[data-print]').forEach(btn => btn.addEventListener('click', () => window.print()));

// Expose global
window.showToast = showToast;