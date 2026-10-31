<?php

use App\Core\Auth;
use App\Core\Session;


if (!Auth::isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login');
    exit;
}

try {
    $usuarioLogado = Auth::getUser();
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
    <meta name="csrf-token" content="<?= Session::csrfToken() ?>">
    <title><?= $title ?? 'Sistema de Gestão de Estoque - C.A de Jesus' ?></title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.6.1"></script>
    <script>window.APP_BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
    <script src="<?= BASE_URL ?>/assets/js/app.js" defer></script>
    <script src="<?= BASE_URL ?>/assets/js/formatacao.js" defer></script>
    <script src="<?= BASE_URL ?>/assets/js/cep.js" defer></script>
    <script src="<?= BASE_URL ?>/assets/js/charts.js" defer></script>
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

    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
    <?php $flash = Session::pullFlash(); if ($flash): ?>
        <div id="flash" hidden data-tipo="<?= htmlspecialchars($flash['type']) ?>" data-mensagem="<?= htmlspecialchars($flash['message']) ?>"></div>
    <?php endif; ?>

    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-white shadow-xl transform -translate-x-full lg:translate-x-0 sidebar-transition">

        <a href="<?= BASE_URL ?>/dashboard" class="block">
            <div class="h-20 px-6 flex items-center text-white mainbar-blue">
                <img src="<?= BASE_URL ?>/assets/img/logoLogin.png" alt="Logo C.A de Jesus" class="brand-logo mr-4">
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
                <a href="<?= BASE_URL ?>/dashboard" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition duration-150 <?= isActive('/dashboard', $currentUri) ? 'nav-active font-semibold' : '' ?>">
                    <i class="fas fa-chart-pie mr-3 w-5 text-current"></i>
                    <span>Main Screen</span>
                </a>
                <a href="<?= BASE_URL ?>/produtos" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition duration-150 <?= isActive('/produtos', $currentUri) ? 'nav-active font-semibold' : '' ?>">
                    <i class="fas fa-boxes mr-3 w-5 text-current"></i>
                    <span>Produtos</span>
                </a>
                <a href="<?= BASE_URL ?>/movimentacoes" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition duration-150 <?= isActive('/movimentacoes', $currentUri) ? 'nav-active font-semibold' : '' ?>">
                    <i class="fas fa-exchange-alt mr-3 w-5 text-current"></i>
                    <span>Movimentações</span>
                </a>
                <a href="<?= BASE_URL ?>/relatorios" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition duration-150 <?= isActive('/relatorios', $currentUri) ? 'nav-active font-semibold' : '' ?>">
                    <i class="fas fa-chart-bar mr-3 w-5 text-current"></i>
                    <span>Relatórios</span>
                </a>

                <?php if (Auth::isAdmin()): ?>
                    <div class="px-6 py-2 mt-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Administração</p>
                    </div>
                    <a href="<?= BASE_URL ?>/usuarios" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition duration-150 <?= isActive('/usuarios', $currentUri) ? 'nav-active font-semibold' : '' ?>">
                        <i class="fas fa-users mr-3 w-5 text-current"></i>
                        <span>Usuários</span>
                    </a>
                    <a href="<?= BASE_URL ?>/fornecedores" class="flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition duration-150 <?= isActive('/fornecedores', $currentUri) ? 'nav-active font-semibold' : '' ?>">
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
                                <a href="<?= BASE_URL ?>/usuarios/perfil" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                    <i class="fas fa-id-badge mr-2"></i> Meu Perfil
                                </a>
                                <a href="<?= BASE_URL ?>/logout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
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

    <script src="<?= BASE_URL ?>/assets/js/layout.js" defer></script>
</body>

</html>