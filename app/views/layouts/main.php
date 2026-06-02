<?php

if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__, 3));
}

require_once __DIR__ . '/../../helpers/AuthHelper.php';
require_once __DIR__ . '/../../helpers/ErrorHelper.php';
@date_default_timezone_set('America/Sao_Paulo');

if (!AuthHelper::isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login');
    exit;
}

try {
    $usuarioLogado = AuthHelper::getUser();
} catch (Throwable $e) {
    echo '<div style="background:#fee;color:#900;padding:16px;margin:16px 0;border:2px solid #900;">';
    echo '<strong>Erro ao obter usuário logado:</strong><br>';
    echo '<b>Mensagem:</b> ' . htmlspecialchars($e->getMessage()) . '<br>';
    echo '<b>Arquivo:</b> ' . htmlspecialchars($e->getFile()) . '<br>';
    echo '<b>Linha:</b> ' . $e->getLine() . '<br>';
    echo '<b>Trace:</b><pre style="white-space:pre-wrap;font-size:12px;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';
    $usuarioLogado = null;
}

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
function isActive($uri, $current)
{
    return strpos($current, $uri) !== false;
}

$displayTitle = isset($pageTitle) ? str_ireplace('Dashboard', 'Main Screen', $pageTitle) : 'Main Screen';
if (!isset($pageTitle) && isActive('/usuarios', $currentUri)) {
    $displayTitle = 'Usuário';
}
if (!isset($pageTitle) && isActive('/fornecedores', $currentUri)) {
    $displayTitle = 'Fornecedores';
}

$brandIcon = 'fa-cubes';
if (isActive('/dashboard', $currentUri)) {
    $brandIcon = 'fa-chart-pie';
} elseif (isActive('/produtos', $currentUri)) {
    $brandIcon = 'fa-boxes';
} elseif (isActive('/movimentacoes', $currentUri)) {
    $brandIcon = 'fa-exchange-alt';
} elseif (isActive('/relatorios', $currentUri)) {
    $brandIcon = 'fa-chart-bar';
} elseif (isActive('/usuarios', $currentUri)) {
    $brandIcon = 'fa-users';
} elseif (isActive('/fornecedores', $currentUri)) {
    $brandIcon = 'fa-truck';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Sistema de Gestão de Estoque - C.A de Jesus' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/app.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?= BASE_URL ?>/public/assets/js/app.js" defer></script>
    <script src="<?= BASE_URL ?>/public/assets/js/charts.js" defer></script>
    <style>
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }

        .fade-in {
            animation: fadeIn 0.4s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .chart-container {
            height: 400px;
        }

        .nav-active {
            background-color: #eff6ff;
            color: #2563eb;
        }

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .brand-gradient {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%) !important;
        }

        .mainbar-blue {
            background-color: #1d4ed8 !important;
        }

        .brand-logo {
            width: 75px;
            height: 100%;
            object-fit: contain;
            transition: all 0.3s ease;
        }

        .brand-logo:hover {
            transform: scale(1.05);
        }

        #sidebar-toggle * {
            pointer-events: none;
        }
        @media (max-width: 1023px) {
            #sidebar.translate-x-0 {
                transform: translateX(0) !important;
            }

            #sidebar.-translate-x-full {
                transform: translateX(-100%) !important;
            }
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .print-section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <div id="loading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white p-6 rounded-lg shadow-lg flex items-center space-x-3">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <span>Carregando...</span>
        </div>
    </div>

    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
    <?php ErrorHelper::displayToast(); ?>

    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-white shadow-xl transform -translate-x-full lg:translate-x-0 sidebar-transition">

        <a href="/sistema_ca_jesus/public/dashboard" class="block">
            <div class="h-20 px-6 flex items-center text-white mainbar-blue">
                <img src="<?= BASE_URL ?>/public/assets/img/logoLogin.png" alt="Logo C.A de Jesus" class="brand-logo mr-4">
                <div class="flex-1">
                    <h1 class="text-xl font-bold leading-tight">C.A de Jesus</h1>
                    <p class="text-sm text-blue-100 opacity-90 -mt-1">Comércio</p>
                </div>
            </div>
        </a>

        <nav class="mt-4 pb-6 overflow-y-auto max-h-[calc(100vh-4rem)] scrollbar-hide">
            <div class="px-6 py-2">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Menu Principal</p>
            </div>

            <div class="mt-2">
                <a href="/sistema_ca_jesus/public/dashboard" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition duration-150 <?= isActive('/dashboard', $currentUri) ? 'nav-active font-semibold' : '' ?>">
                    <i class="fas fa-chart-pie mr-3 w-5 text-current"></i>
                    <span>Main Screen</span>
                </a>
                <a href="/sistema_ca_jesus/public/produtos" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition duration-150 <?= isActive('/produtos', $currentUri) ? 'nav-active font-semibold' : '' ?>">
                    <i class="fas fa-boxes mr-3 w-5 text-current"></i>
                    <span>Produtos</span>
                </a>
                <a href="/sistema_ca_jesus/public/movimentacoes" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition duration-150 <?= isActive('/movimentacoes', $currentUri) ? 'nav-active font-semibold' : '' ?>">
                    <i class="fas fa-exchange-alt mr-3 w-5 text-current"></i>
                    <span>Movimentações</span>
                </a>
                <a href="/sistema_ca_jesus/public/relatorios" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition duration-150 <?= isActive('/relatorios', $currentUri) ? 'nav-active font-semibold' : '' ?>">
                    <i class="fas fa-chart-bar mr-3 w-5 text-current"></i>
                    <span>Relatórios</span>
                </a>

                <?php if (AuthHelper::isAdmin()): ?>
                    <div class="px-6 py-2 mt-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Administração</p>
                    </div>
                    <a href="/sistema_ca_jesus/public/usuarios" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition duration-150 <?= isActive('/usuarios', $currentUri) ? 'nav-active font-semibold' : '' ?>">
                        <i class="fas fa-users mr-3 w-5 text-current"></i>
                        <span>Usuários</span>
                    </a>
                    <a href="/sistema_ca_jesus/public/fornecedores" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition duration-150 <?= isActive('/fornecedores', $currentUri) ? 'nav-active font-semibold' : '' ?>">
                        <i class="fas fa-truck mr-3 w-5 text-current"></i>
                        <span>Fornecedores</span>
                    </a>
                <?php endif; ?>
            </div>
        </nav>
    </aside>

    <div class="lg:ml-64 min-h-screen flex flex-col">

        <header class="sticky top-0 z-30 shadow-sm">

            <div class="mainbar-blue">
                <div class="px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <button id="sidebar-toggle" type="button" aria-controls="sidebar" aria-expanded="false" class="lg:hidden text-white focus:outline-none focus:ring-2 focus:ring-white rounded">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h2 class="text-white text-lg sm:text-xl font-semibold leading-tight">
                            <?= htmlspecialchars($displayTitle) ?>
                        </h2>
                    </div>
                    <div class="flex items-center gap-3">

                        <?php if (isActive('/dashboard', $currentUri) || isActive('/movimentacoes', $currentUri) || isActive('/produtos', $currentUri)): ?>
                            <div class="relative">
                                <button id="btn-bell" class="relative text-white/90 hover:text-white focus:outline-none">
                                    <i class="fas fa-bell text-lg"></i>
                                    <span id="bell-badge" class="absolute -top-1 -right-1 min-w-[10px] h-2.5 bg-red-500 rounded-full ring-2 ring-white"></span>
                                </button>
                                <div id="bell-menu" class="hidden absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 overflow-hidden">
                                    <div class="px-4 py-2 text-sm font-medium text-gray-700 border-b">Alertas de Estoque</div>
                                    <div id="bell-list" class="max-h-64 overflow-auto"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="relative">
                            <button id="user-menu-btn" class="flex items-center gap-2 text-white focus:outline-none">
                                <span class="w-8 h-8 bg-white/20 rounded-full grid place-items-center">
                                    <i class="fas fa-user text-white text-sm"></i>
                                </span>
                                <span class="hidden sm:block text-sm font-medium truncate max-w-[120px]">
                                    <?= isset($usuarioLogado) && $usuarioLogado ? htmlspecialchars($usuarioLogado['nome']) : '' ?>
                                </span>
                                <i class="fas fa-chevron-down text-xs opacity-80"></i>
                            </button>
                            <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 overflow-hidden">
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-800 truncate">
                                        <?= isset($usuarioLogado) && $usuarioLogado ? htmlspecialchars($usuarioLogado['email']) : '' ?>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        <?= isset($usuarioLogado) && $usuarioLogado ? ($usuarioLogado['tipo'] == 1 ? 'Administrador' : 'Funcionário') : '' ?>
                                    </p>
                                </div>
                                <a href="/sistema_ca_jesus/public/usuarios/perfil" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                    <i class="fas fa-id-badge mr-2"></i> Meu Perfil
                                </a>
                                <a href="/sistema_ca_jesus/public/logout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Sair
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white border-b border-gray-200">
                <div class="px-4 sm:px-6 lg:px-8 py-2 flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2 text-gray-600">
                        <i class="fas fa-home text-blue-600"></i>
                        <span>Início</span>
                        <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                        <span class="text-gray-800 font-medium truncate max-w-[50vw]"><?= htmlspecialchars($displayTitle) ?></span>
                    </div>
                    <div class="text-gray-500">
                        <?= date('d/m/Y') ?>
                    </div>
                </div>
            </div>
        </header>

   
        <main class="p-4 sm:p-6 lg:p-8 fade-in">
            <?= $content ?? '' ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/js/all.min.js"></script>
    <script>

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

        const globalSearch = document.getElementById('global-search');
        const searchResults = document.getElementById('search-results');
        const searchLoading = document.getElementById('search-loading');
        const searchContent = document.getElementById('search-content');
        let searchTimeout;
        let currentSearchQuery = '';

        if (globalSearch && searchResults) {
            async function performSearch(query) {
                if (query.length < 2) {
                    searchResults.classList.add('hidden');
                    return;
                }

                currentSearchQuery = query;
                searchLoading.classList.remove('hidden');
                searchContent.innerHTML = '';
                searchResults.classList.remove('hidden');

                try {
                    const response = await fetch(`<?= BASE_URL ?>/public/api/search/simple.php?q=${encodeURIComponent(query)}`);

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }

                    const data = await response.json();

                    searchLoading.classList.add('hidden');

                    if (data.error) {
                        searchContent.innerHTML = `
                            <div class="p-4 text-center text-red-600">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <div class="font-medium">${data.error}</div>
                                ${data.message ? `<div class="text-sm mt-1">${data.message}</div>` : ''}
                                ${data.file && data.line ? `<div class="text-xs mt-1 text-gray-500">${data.file}:${data.line}</div>` : ''}
                            </div>
                        `;
                        return;
                    }

                    if (!data.results || data.results.length === 0) {
                        searchContent.innerHTML = `
                            <div class="p-4 text-center text-gray-500">
                                <i class="fas fa-search mr-2"></i>
                                Nenhum resultado encontrado para "${query}"
                            </div>
                        `;
                        return;
                    }

                    let html = '';
                    data.results.forEach(result => {
                        html += `
                            <div class="search-result-item p-3 hover:bg-gray-50 border-b border-gray-100 cursor-pointer" data-url="${result.url}">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0">
                                        <i class="fas ${result.icone} ${result.cor} text-lg"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-gray-900 truncate">${result.titulo}</div>
                                        <div class="text-sm text-gray-600 truncate">${result.subtitulo}</div>
                                        ${result.detalhes ? `<div class="text-xs text-gray-500 mt-1">${result.detalhes}</div>` : ''}
                                    </div>
                                    <div class="flex-shrink-0 text-xs text-gray-400">
                                        ${result.tipo.charAt(0).toUpperCase() + result.tipo.slice(1)}
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    if (data.total > data.results.length) {
                        html += `
                            <div class="p-3 text-center text-sm text-gray-500 border-t">
                                Mostrando ${data.results.length} de ${data.total} resultados
                            </div>
                        `;
                    }

                    searchContent.innerHTML = html;

                    document.querySelectorAll('.search-result-item').forEach(item => {
                        item.addEventListener('click', () => {
                            const url = item.getAttribute('data-url');
                            if (url) {
                                window.location.href = url;
                            }
                        });
                    });

                } catch (error) {
                    searchLoading.classList.add('hidden');
                    searchContent.innerHTML = `
                        <div class="p-4 text-center text-red-600">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Erro ao realizar busca. Tente novamente.
                        </div>
                    `;
                    console.error('Erro na busca:', error);
                }
            }

            globalSearch.addEventListener('input', (e) => {
                const query = e.target.value.trim();

                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }

                searchTimeout = setTimeout(() => {
                    performSearch(query);
                }, 300);
            });

            globalSearch.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const query = globalSearch.value.trim();
                    if (query.length >= 2) {
                       
                        const firstResult = document.querySelector('.search-result-item');
                        if (firstResult) {
                            const url = firstResult.getAttribute('data-url');
                            if (url) {
                                window.location.href = url;
                                return;
                            }
                        }
                      
                        window.location.href = '<?= BASE_URL ?>/public/produtos?search=' + encodeURIComponent(query);
                    }
                }

                
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    const items = document.querySelectorAll('.search-result-item');
                    if (items.length === 0) return;

                    let currentIndex = -1;
                    items.forEach((item, index) => {
                        if (item.classList.contains('bg-blue-50')) {
                            currentIndex = index;
                            item.classList.remove('bg-blue-50');
                        }
                    });

                    if (e.key === 'ArrowDown') {
                        currentIndex = (currentIndex + 1) % items.length;
                    } else {
                        currentIndex = currentIndex <= 0 ? items.length - 1 : currentIndex - 1;
                    }

                    items[currentIndex].classList.add('bg-blue-50');
                    items[currentIndex].scrollIntoView({
                        block: 'nearest'
                    });
                }

                if (e.key === 'Escape') {
                    searchResults.classList.add('hidden');
                    globalSearch.blur();
                }
            });

          
            globalSearch.addEventListener('focus', () => {
                if (currentSearchQuery && searchContent.innerHTML) {
                    searchResults.classList.remove('hidden');
                }
            });

            document.addEventListener('click', (e) => {
                if (!globalSearch.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.classList.add('hidden');
                }
            });
        }

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
                        const res = await fetch('<?= BASE_URL ?>/public/api/estoque/atual');
                        const data = await res.json();
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
                                row.innerHTML = `<div class=\"flex items-center\"><i class=\"fas fa-exclamation-triangle text-yellow-500 mr-2\"></i><span class=\"text-gray-800 truncate pr-2\">${nome}</span></div><span class=\"text-red-600 font-semibold\">${estq}</span>`;
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
                    const res = await fetch('<?= BASE_URL ?>/public/api/estoque/atual');
                    const data = await res.json();
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
        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `px-4 py-2 rounded shadow text-white ${type === 'error' ? 'bg-red-500' : 'bg-green-500'}`;
            toast.textContent = message;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }
    </script>
</body>

</html>