(function() {
    const sidebar = document.getElementById('sidebar');
    const btn = document.getElementById('sidebar-toggle');
    if (!sidebar || !btn) return;

    const isLg = () => window.matchMedia('(min-width: 1024px)').matches;

    function addBackdrop() {
        if (document.getElementById('sidebar-backdrop') || isLg()) return;
        const backdrop = document.createElement('div');
        backdrop.id = 'sidebar-backdrop';
        backdrop.className = 'fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden';
        backdrop.addEventListener('click', closeSidebar);
        document.body.appendChild(backdrop);
        document.body.style.overflow = 'hidden';
    }

    function removeBackdrop() {
        const backdrop = document.getElementById('sidebar-backdrop');
        if (backdrop) backdrop.remove();
        document.body.style.overflow = '';
    }

    function openSidebar() {
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
        addBackdrop();
        btn.setAttribute('aria-expanded', 'true');
    }

    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        sidebar.classList.remove('translate-x-0');
        removeBackdrop();
        btn.setAttribute('aria-expanded', 'false');
    }

    function toggleSidebar(e) {
        if (e) e.stopPropagation();
        if (sidebar.classList.contains('-translate-x-full')) {
            openSidebar();
        } else {
            closeSidebar();
        }
    }

    btn.addEventListener('click', toggleSidebar);
    btn.addEventListener('touchstart', (e) => {
        e.preventDefault();
        toggleSidebar(e);
    }, {
        passive: false
    });

    btn.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggleSidebar(e);
        }
    });

    document.addEventListener('click', (e) => {
        const t = e.target.closest('#sidebar-toggle');
        if (t) {
            e.preventDefault();
            toggleSidebar(e);
        }
    });
    document.addEventListener('touchstart', (e) => {
        const t = e.target.closest('#sidebar-toggle');
        if (t) {
            e.preventDefault();
            toggleSidebar(e);
        }
    }, {
        passive: false
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !isLg()) {
            closeSidebar();
        }
    });

    document.addEventListener('click', (e) => {
        if (isLg()) return;
        if (!sidebar.classList.contains('-translate-x-full')) {
            if (!sidebar.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
                closeSidebar();
            }
        }
    });

    function ensureState() {
        if (isLg()) {
            removeBackdrop();
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
            document.body.style.overflow = '';
        } else {
            if (!sidebar.classList.contains('translate-x-0')) {
                sidebar.classList.add('-translate-x-full');
            }
        }
    }
    window.addEventListener('resize', ensureState);
    ensureState();
})();

const bellBtn = document.getElementById('btn-bell');
const bellMenu = document.getElementById('bell-menu');
const bellList = document.getElementById('bell-list');
const bellBadge = document.getElementById('bell-badge');
let bellLoaded = false;
if (bellBtn && bellMenu) {
    bellBtn.addEventListener('click', async (e) => {
        e.stopPropagation();
        bellMenu.classList.toggle('hidden');
        if (!bellLoaded) {
            try {
                                const data = (await requisicaoJson(APP_BASE_URL + '/api/estoque/atual')).dados;
                const low = (data || []).filter(p => (parseInt(p.estoque || p.estoque_produto || 0, 10)) < 5);
                bellList.innerHTML = '';
                if (low.length === 0) {
                    bellList.innerHTML = '<div class="px-4 py-3 text-sm text-gray-600">Tudo ok no estoque.</div>';
                    if (bellBadge) bellBadge.style.display = 'none';
                } else {
                    if (bellBadge) bellBadge.style.display = '';
                    low.slice(0, 10).forEach(p => {
                        const nome = p.marca || p.marca_produto || 'Produto';
                        const estq = p.estoque || p.estoque_produto || 0;
                        const row = document.createElement('div');
                        row.className = 'px-4 py-2 text-sm flex items-center justify-between hover:bg-gray-50';
                        row.innerHTML = `<div class="flex items-center"><i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i><span class="text-gray-800 truncate pr-2">${escapeHtml(nome)}</span></div><span class="text-red-600 font-semibold">${escapeHtml(estq)}</span>`;
                        bellList.appendChild(row);
                    });
                }
            } catch (err) {
                bellList.innerHTML = '<div class="px-4 py-3 text-sm text-red-600">Falha ao carregar alertas.</div>';
            }
            bellLoaded = true;
        }
    });
    document.addEventListener('click', () => bellMenu.classList.add('hidden'));

    (async () => {
        try {
                        const data = (await requisicaoJson(APP_BASE_URL + '/api/estoque/atual')).dados;
            const low = (data || []).filter(p => (parseInt(p.estoque || p.estoque_produto || 0, 10)) < 5);
            if (low.length > 0 && bellBadge) {
                bellBadge.style.display = '';
            } else if (bellBadge) {
                bellBadge.style.display = 'none';
            }
        } catch (err) {
            console.warn('Erro ao carregar notificações iniciais:', err);
        }
    })();
}

const userBtn = document.getElementById('user-menu-btn');
const userMenu = document.getElementById('user-menu');
if (userBtn && userMenu) {
    userBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        userMenu.classList.toggle('hidden');
    });
    document.addEventListener('click', () => userMenu.classList.add('hidden'));
}
