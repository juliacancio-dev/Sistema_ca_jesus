<div id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform -translate-x-full lg:translate-x-0 sidebar-transition">
        <div class="flex items-center justify-center h-16 bg-blue-600 text-white">
            <h1 class="text-xl font-bold">C.A de Jesus</h1>
        </div>
        
        <nav class="mt-8">
                <div class="px-6 py-2">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Menu Principal</p>
                </div>
            
            <div class="mt-4">
                <a href="/sistema_ca_jesus/public/dashboard" class="nav-item flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition duration-200">
                    <i class="fas fa-chart-pie mr-3"></i>
                    Dashboard
                </a>
                <a href="/sistema_ca_jesus/public/produtos" class="nav-item flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition duration-200">
                    <i class="fas fa-boxes mr-3"></i>
                    Produtos
                </a>
                <a href="/sistema_ca_jesus/public/movimentacoes" class="nav-item flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition duration-200">
                    <i class="fas fa-exchange-alt mr-3"></i>
                    Movimentações
                </a>
                <a href="/sistema_ca_jesus/public/relatorios" class="nav-item flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition duration-200">
                    <i class="fas fa-chart-bar mr-3"></i>
                    Relatórios
                </a>
                <?php if (AuthHelper::isAdmin()): ?>
                <div id="admin-menu">
                    <div class="px-6 py-2 mt-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Administração</p>
                    </div>
                    <a href="/sistema_ca_jesus/public/usuarios" class="nav-item flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition duration-200">
                        <i class="fas fa-users mr-3"></i>
                        Usuários
                    </a>
                    <a href="/sistema_ca_jesus/public/fornecedores" class="nav-item flex items-center px-6 py-3 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition duration-200">
                        <i class="fas fa-truck mr-3"></i>
                        Fornecedores
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </nav>
    </div>