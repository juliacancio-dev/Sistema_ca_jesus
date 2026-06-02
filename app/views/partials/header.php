<header class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex justify-between items-center px-6 py-4">
                <div class="flex items-center">
                    <button id="sidebar-toggle" class="lg:hidden mr-4 text-gray-500 hover:text-gray-700">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2 id="page-title" class="text-2xl font-semibold text-gray-800"><?= $pageTitle ?? 'Dashboard' ?></h2>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-white text-sm"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700">
                            <?= isset($usuarioLogado) && $usuarioLogado ? htmlspecialchars($usuarioLogado['nome']) : '' ?>
                        </span>
                        <span class="text-xs text-gray-500 px-2 py-1 bg-gray-100 rounded">
                            <?= isset($usuarioLogado) && $usuarioLogado ? ($usuarioLogado['tipo'] == 1 ? 'Administrador' : 'Funcionário') : '' ?>
                        </span>
                    </div>
                    
                    <a href="/sistema_ca_jesus/public/logout" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </header>