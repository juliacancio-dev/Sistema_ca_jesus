<?php
require __DIR__ . '/../../../helpers/FormatHelper.php';
?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">

            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900">Gerenciar Fornecedores</h3>
                        <p class="text-sm text-gray-500">Adicione, edite e remova fornecedores do sistema.</p>
                    </div>
                    <button id="new-fornecedor-btn"
                        class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-600 to-blue-500 text-white px-5 py-2.5 rounded-xl hover:scale-105 hover:from-blue-700 hover:to-blue-600 shadow-md transition-all duration-200">
                        <i class="fas fa-plus"></i> Novo Fornecedor
                    </button>
                </div>
            </div>

            <div class="p-6 space-y-4">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="relative flex-1">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        <input type="text" id="search-fornecedor" placeholder="Buscar por nome, email, CNPJ..."
                            value="<?= htmlspecialchars($termoBusca ?? '') ?>"
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <button id="clear-search" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 hidden">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <select id="filter-status" class="px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos os status</option>
                        <option value="1">Ativos</option>
                        <option value="0">Inativos</option>
                    </select>
                </div>

                <div class="overflow-x-auto rounded-lg shadow-sm border border-gray-200">
                    <table class="w-full text-sm border-collapse">
                        <thead class="bg-gray-100 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-600 cursor-pointer hover:bg-gray-200" data-sort="nome">
                                    Nome <i class="fas fa-sort ml-1 opacity-50"></i>
                                </th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600 cursor-pointer hover:bg-gray-200" data-sort="email">
                                    Email <i class="fas fa-sort ml-1 opacity-50"></i>
                                </th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Telefone</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="fornecedor-table-body" class="divide-y divide-gray-200">
                            <?php if (empty($fornecedores)): ?>
                                <tr id="empty-state">
                                    <td colspan="5" class="px-4 py-12 text-center">
                                        <div class="flex flex-col items-center space-y-3">
                                            <i class="fas fa-truck text-4xl text-gray-300"></i>
                                            <p class="text-gray-500 font-medium">Nenhum fornecedor encontrado</p>
                                            <button onclick="document.getElementById('new-fornecedor-btn').click()"
                                                class="text-blue-600 hover:text-blue-800 underline">
                                                Cadastrar primeiro fornecedor
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($fornecedores as $fornecedor): ?>
                                    <tr class="hover:bg-gray-50 transition fornecedor-row"
                                        data-id="<?= $fornecedor["id"] ?>"
                                        data-nome="<?= htmlspecialchars(strtolower($fornecedor["nome"] ?? '')) ?>"
                                        data-nome-original="<?= htmlspecialchars($fornecedor["nome"] ?? '') ?>"
                                        data-email="<?= htmlspecialchars(strtolower($fornecedor["email"] ?? '')) ?>"
                                        data-email-original="<?= htmlspecialchars($fornecedor["email"] ?? '') ?>"
                                        data-status="<?= $fornecedor["ativo"] ?? 1 ?>"
                                        data-cnpj="<?= preg_replace('/[^0-9]/', '', $fornecedor["cnpj"] ?? '') ?>"
                                        data-telefone="<?= preg_replace('/[^0-9]/', '', $fornecedor["telefone"] ?? '') ?>"
                                        data-endereco="<?= htmlspecialchars($fornecedor["endereco"] ?? '') ?>"
                                        data-numero="<?= htmlspecialchars($fornecedor["numero"] ?? '') ?>"
                                        data-complemento="<?= htmlspecialchars($fornecedor["complemento"] ?? '') ?>"
                                        data-bairro="<?= htmlspecialchars($fornecedor["bairro"] ?? '') ?>"
                                        data-cidade="<?= htmlspecialchars($fornecedor["cidade"] ?? '') ?>"
                                        data-estado="<?= htmlspecialchars($fornecedor["estado"] ?? '') ?>"
                                        data-cep="<?= htmlspecialchars($fornecedor["cep"] ?? '') ?>"
                                        data-data-criacao="<?= htmlspecialchars($fornecedor["data_criacao"] ?? '') ?>">

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center space-x-3">
                                                <div class="flex-shrink-0">
                                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold">
                                                        <?= strtoupper(substr($fornecedor["nome"] ?? '', 0, 1)) ?>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($fornecedor["nome"] ?? '') ?>
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        ID: <?= $fornecedor["id"] ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?= htmlspecialchars($fornecedor["email"]) ?></div>
                                            <div class="text-xs text-gray-500">
                                                CNPJ: <?= FormatHelper::cnpj($fornecedor["cnpj"] ?? '') ?>
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars(FormatHelper::phone($fornecedor["telefone"] ?? '')) ?>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= ($fornecedor["ativo"] ?? 1) ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                                <i class="fas <?= ($fornecedor["ativo"] ?? 1) ? 'fa-check-circle' : 'fa-times-circle' ?> mr-1"></i>
                                                <?= ($fornecedor["ativo"] ?? 1) ? 'Ativo' : 'Inativo' ?>
                                            </span>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <div class="flex justify-center gap-2">
                                                <button onclick="viewFornecedor(<?= $fornecedor['id'] ?>)"
                                                    class="p-2 rounded-full bg-gray-50 text-gray-600 hover:bg-gray-100 hover:scale-110 transition"
                                                    title="Visualizar">
                                                    <i class="fas fa-eye text-xs"></i>
                                                </button>

                                                <button onclick="editFornecedor(<?= $fornecedor['id'] ?>)"
                                                    class="p-2 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-100 hover:scale-110 transition"
                                                    title="Editar">
                                                    <i class="fas fa-edit text-xs"></i>
                                                </button>

                                                <button onclick="toggleFornecedorStatus(<?= $fornecedor['id'] ?>, <?= ($fornecedor['ativo'] ?? 1) ? 0 : 1 ?>)"
                                                    class="p-2 rounded-full <?= ($fornecedor['ativo'] ?? 1) ? 'bg-yellow-50 text-yellow-600 hover:bg-yellow-100' : 'bg-green-50 text-green-600 hover:bg-green-100' ?> hover:scale-110 transition"
                                                    title="<?= ($fornecedor['ativo'] ?? 1) ? 'Desativar' : 'Ativar' ?>">
                                                    <i class="fas <?= ($fornecedor['ativo'] ?? 1) ? 'fa-pause' : 'fa-play' ?> text-xs"></i>
                                                </button>

                                                <button onclick="deleteFornecedor(<?= $fornecedor['id'] ?>, '<?= htmlspecialchars($fornecedor['nome'] ?? '') ?>')"
                                                    class="p-2 rounded-full bg-red-50 text-red-600 hover:bg-red-100 hover:scale-110 transition"
                                                    title="Excluir">
                                                    <i class="fas fa-trash text-xs"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (isset($paginacao) && $paginacao['totalPaginas'] > 1): ?>
                    <div class="flex justify-between items-center mt-6">
                        <div class="text-sm text-gray-700">
                            Mostrando <?= $paginacao['inicio'] ?> a <?= $paginacao['fim'] ?> de <?= $paginacao['total'] ?> fornecedores
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($paginacao['paginaAtual'] > 1): ?>
                                <a href="?pagina=<?= $paginacao['paginaAtual'] - 1 ?>&search=<?= urlencode($termoBusca ?? '') ?>"
                                    class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                    Anterior
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $paginacao['paginaAtual'] - 2); $i <= min($paginacao['totalPaginas'], $paginacao['paginaAtual'] + 2); $i++): ?>
                                <a href="?pagina=<?= $i ?>&search=<?= urlencode($termoBusca ?? '') ?>"
                                    class="px-3 py-2 border rounded-lg <?= $i == $paginacao['paginaAtual'] ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-300 hover:bg-gray-50' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($paginacao['paginaAtual'] < $paginacao['totalPaginas']): ?>
                                <a href="?pagina=<?= $paginacao['paginaAtual'] + 1 ?>&search=<?= urlencode($termoBusca ?? '') ?>"
                                    class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                    Próxima
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div id="fornecedor-modal" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50 hidden transition-all duration-300">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-hidden transform animate-fadeInUp relative">

                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white z-20">
                    <h3 id="fornecedor-modal-title" class="text-lg font-semibold text-gray-800">Cadastrar Fornecedor</h3>
                    <button id="close-fornecedor-modal" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">

                    <form id="fornecedor-form" method="POST" action="/sistema_ca_jesus/public/fornecedores/salvar" class="space-y-8" novalidate>
                        <input type="hidden" id="fornecedor-id" name="id">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                        <div>
                            <h4 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                                <i class="fas fa-building text-blue-600 mr-2"></i>
                                Dados do Fornecedor
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label for="fornecedor-nome" class="block text-sm font-medium text-gray-700 mb-2">
                                        Nome / Razão Social *
                                    </label>
                                    <input id="fornecedor-nome" type="text" name="nome"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        required maxlength="100">
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>

                                <div class="form-group">
                                    <label for="fornecedor-cnpj" class="block text-sm font-medium text-gray-700 mb-2">
                                        CNPJ *
                                    </label>
                                    <input id="fornecedor-cnpj" type="text" name="cnpj"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        required maxlength="18" placeholder="00.000.000/0000-00">
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>

                                <div class="form-group">
                                    <label for="fornecedor-email" class="block text-sm font-medium text-gray-700 mb-2">
                                        Email *
                                    </label>
                                    <input id="fornecedor-email" type="email" name="email"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        required>
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>

                                <div class="form-group">
                                    <label for="fornecedor-telefone" class="block text-sm font-medium text-gray-700 mb-2">
                                        Telefone *
                                    </label>
                                    <input id="fornecedor-telefone" type="tel" name="telefone"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        required placeholder="(11) 99999-9999">
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                                <i class="fas fa-map-marker-alt text-blue-600 mr-2"></i>
                                Endereço
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                <div class="form-group">
                                    <label for="fornecedor-cep" class="block text-sm font-medium text-gray-700 mb-2">
                                        CEP *
                                    </label>
                                    <input id="fornecedor-cep" type="text" name="cep"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        maxlength="9" placeholder="00000-000">
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>

                                <div class="form-group md:col-span-3">
                                    <label for="fornecedor-endereco" class="block text-sm font-medium text-gray-700 mb-2">
                                        Endereço *
                                    </label>
                                    <input id="fornecedor-endereco" type="text" name="endereco"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        maxlength="200">
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>

                                <div class="form-group">
                                    <label for="fornecedor-numero" class="block text-sm font-medium text-gray-700 mb-2">
                                        Número *
                                    </label>
                                    <input id="fornecedor-numero" type="text" name="numero"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        maxlength="10">
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>

                                <div class="form-group">
                                    <label for="fornecedor-complemento" class="block text-sm font-medium text-gray-700 mb-2">
                                        Complemento
                                    </label>
                                    <input id="fornecedor-complemento" type="text" name="complemento"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        maxlength="50">
                                </div>

                                <div class="form-group">
                                    <label for="fornecedor-bairro" class="block text-sm font-medium text-gray-700 mb-2">
                                        Bairro *
                                    </label>
                                    <input id="fornecedor-bairro" type="text" name="bairro"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        maxlength="100">
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>

                                <div class="form-group">
                                    <label for="fornecedor-cidade" class="block text-sm font-medium text-gray-700 mb-2">
                                        Cidade *
                                    </label>
                                    <input id="fornecedor-cidade" type="text" name="cidade"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        maxlength="100">
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>

                                <div class="form-group">
                                    <label for="fornecedor-estado" class="block text-sm font-medium text-gray-700 mb-2">
                                        Estado *
                                    </label>
                                    <select id="fornecedor-estado" name="estado"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                        <option value="">Selecione</option>
                                        <option value="AC">AC</option>
                                        <option value="AL">AL</option>
                                        <option value="AP">AP</option>
                                        <option value="AM">AM</option>
                                        <option value="BA">BA</option>
                                        <option value="CE">CE</option>
                                        <option value="DF">DF</option>
                                        <option value="ES">ES</option>
                                        <option value="GO">GO</option>
                                        <option value="MA">MA</option>
                                        <option value="MT">MT</option>
                                        <option value="MS">MS</option>
                                        <option value="MG">MG</option>
                                        <option value="PA">PA</option>
                                        <option value="PB">PB</option>
                                        <option value="PR">PR</option>
                                        <option value="PE">PE</option>
                                        <option value="PI">PI</option>
                                        <option value="RJ">RJ</option>
                                        <option value="RN">RN</option>
                                        <option value="RS">RS</option>
                                        <option value="RO">RO</option>
                                        <option value="RR">RR</option>
                                        <option value="SC">SC</option>
                                        <option value="SP">SP</option>
                                        <option value="SE">SE</option>
                                        <option value="TO">TO</option>
                                    </select>
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end mt-8">
                            <button type="submit" class="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>
                                <span id="submit-text">Criar Fornecedor</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const modal = document.getElementById('fornecedor-modal');
                const newBtn = document.getElementById('new-fornecedor-btn');
                const closeBtn = document.getElementById('close-fornecedor-modal');
                const form = document.getElementById('fornecedor-form');
                const searchInput = document.getElementById('search-fornecedor');
                const clearSearchBtn = document.getElementById('clear-search');
                const filterStatus = document.getElementById('filter-status');

                if (newBtn) {
                    newBtn.addEventListener('click', () => openModal());
                }

                if (closeBtn) {
                    closeBtn.addEventListener('click', () => closeModal());
                }

                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal();
                    }
                });

                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        const term = this.value.toLowerCase();
                        if (term) {
                            clearSearchBtn.classList.remove('hidden');
                        } else {
                            clearSearchBtn.classList.add('hidden');
                        }
                        filterTable();
                    });
                }

                if (clearSearchBtn) {
                    clearSearchBtn.addEventListener('click', function() {
                        searchInput.value = '';
                        this.classList.add('hidden');
                        filterTable();
                    });
                }

                if (filterStatus) {
                    filterStatus.addEventListener('change', filterTable);
                }

                const cepInput = document.getElementById('fornecedor-cep');
                if (cepInput) {
                    cepInput.addEventListener('blur', function() {
                        const cep = this.value.replace(/\D/g, '');
                        if (cep.length === 8) {
                            buscarCep(cep);
                        }
                    });
                }

                setupInputMasks();

                function openModal(fornecedor = null) {
                    resetForm();

                    if (fornecedor) {
                        document.getElementById('fornecedor-modal-title').textContent = 'Editar Fornecedor';
                        document.getElementById('submit-text').textContent = 'Atualizar Fornecedor';

                        fillForm(fornecedor);
                    } else {
                        document.getElementById('fornecedor-modal-title').textContent = 'Novo Fornecedor';
                        document.getElementById('submit-text').textContent = 'Criar Fornecedor';
                    }

                    modal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                }

                function closeModal() {
                    modal.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                    resetForm();
                }

                function resetForm() {
                    form.reset();
                    clearErrors();
                    document.getElementById('fornecedor-id').value = '';
                }

                function fillForm(fornecedor) {
                    document.getElementById('fornecedor-id').value = fornecedor.id;
                    document.getElementById('fornecedor-nome').value = fornecedor.nome;
                    document.getElementById('fornecedor-cnpj').value = formatCnpj(fornecedor.cnpj);
                    document.getElementById('fornecedor-email').value = fornecedor.email;
                    document.getElementById('fornecedor-telefone').value = formatPhone(fornecedor.telefone);
                    document.getElementById('fornecedor-cep').value = formatCep(fornecedor.cep);
                    document.getElementById('fornecedor-endereco').value = fornecedor.endereco;
                    document.getElementById('fornecedor-numero').value = fornecedor.numero;
                    document.getElementById('fornecedor-complemento').value = fornecedor.complemento || '';
                    document.getElementById('fornecedor-bairro').value = fornecedor.bairro;
                    document.getElementById('fornecedor-cidade').value = fornecedor.cidade;
                    document.getElementById('fornecedor-estado').value = fornecedor.estado;
                }

                function validateForm() {
                    const requiredFields = form.querySelectorAll('[required]');
                    let isValid = true;

                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            showFieldError(field, 'Este campo é obrigatório');
                            isValid = false;
                        } else {
                            hideFieldError(field);

                            if (field.name === 'email' && !validateEmail(field.value)) {
                                showFieldError(field, 'Email inválido');
                                isValid = false;
                            } else if (field.name === 'cnpj' && !validateCNPJ(field.value)) {
                                showFieldError(field, 'CNPJ inválido');
                                isValid = false;
                            } else if (field.name === 'cep' && !validateCEP(field.value)) {
                                showFieldError(field, 'CEP inválido');
                                isValid = false;
                            }
                        }
                    });

                    return isValid;
                }

                function showFieldError(field, message) {
                    const errorDiv = field.parentNode.querySelector('.error-message');
                    if (errorDiv) {
                        errorDiv.textContent = message;
                        errorDiv.classList.remove('hidden');
                    }
                    field.classList.add('border-red-500');
                }

                function hideFieldError(field) {
                    const errorDiv = field.parentNode.querySelector('.error-message');
                    if (errorDiv) {
                        errorDiv.classList.add('hidden');
                    }
                    field.classList.remove('border-red-500');
                }

                function clearErrors() {
                    document.querySelectorAll('.error-message').forEach(error => {
                        error.classList.add('hidden');
                    });
                    document.querySelectorAll('.border-red-500').forEach(field => {
                        field.classList.remove('border-red-500');
                    });
                }

                function setupInputMasks() {
                    const cnpjInput = document.getElementById('fornecedor-cnpj');
                    if (cnpjInput) {
                        cnpjInput.addEventListener('input', function(e) {
                            let value = e.target.value.replace(/\D/g, '');
                            value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                            value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                            value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                            value = value.replace(/(\d{4})(\d)/, '$1-$2');
                            e.target.value = value;
                        });
                    }
                    const telefoneInput = document.getElementById('fornecedor-telefone');
                    if (telefoneInput) {
                        telefoneInput.addEventListener('input', function(e) {
                            let value = e.target.value.replace(/\D/g, '');
                            if (value.length <= 10) {
                                value = value.replace(/(\d{2})(\d)/, '($1) $2');
                                value = value.replace(/(\d{4})(\d)/, '$1-$2');
                            } else {
                                value = value.replace(/(\d{2})(\d)/, '($1) $2');
                                value = value.replace(/(\d{5})(\d)/, '$1-$2');
                            }
                            e.target.value = value;
                        });
                    }

                    if (cepInput) {
                        cepInput.addEventListener('input', function(e) {
                            let value = e.target.value.replace(/\D/g, '');
                            value = value.replace(/(\d{5})(\d)/, '$1-$2');
                            e.target.value = value;
                        });
                    }
                }

                function buscarCep(cep) {
                    fetch(`https://viacep.com.br/ws/${cep}/json/`)
                        .then(response => response.json())
                        .then(data => {
                            if (!data.erro) {
                                document.getElementById('fornecedor-endereco').value = data.logradouro;
                                document.getElementById('fornecedor-bairro').value = data.bairro;
                                document.getElementById('fornecedor-cidade').value = data.localidade;
                                document.getElementById('fornecedor-estado').value = data.uf;
                            }
                        })
                        .catch(error => console.log('Erro ao buscar CEP:', error));
                }

                function filterTable() {
                    const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
                    const statusFilter = filterStatus ? filterStatus.value : '';

                    const rows = document.querySelectorAll('.fornecedor-row');
                    let visibleCount = 0;

                    rows.forEach(row => {
                        const nome = row.dataset.nome || '';
                        const email = row.dataset.email || '';
                        const cnpj = row.dataset.cnpj || '';
                        const status = row.dataset.status || '';

                        const matchesSearch = !searchTerm ||
                            nome.includes(searchTerm) ||
                            email.includes(searchTerm) ||
                            cnpj.includes(searchTerm);

                        const matchesStatus = !statusFilter || status === statusFilter;

                        if (matchesSearch && matchesStatus) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    const emptyState = document.getElementById('empty-state');
                    if (emptyState) {
                        emptyState.style.display = visibleCount === 0 ? '' : 'none';
                    }
                }

                function validateEmail(email) {
                    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    return re.test(email);
                }

                function validateCNPJ(cnpj) {
                    cnpj = cnpj.replace(/[^\d]+/g, '');
                    if (cnpj == '') return false;
                    if (cnpj.length != 14) return false;
                    if (/^(\d)\1+$/.test(cnpj)) return false;

                    let tamanho = cnpj.length - 2
                    let numeros = cnpj.substring(0, tamanho);
                    let digitos = cnpj.substring(tamanho);
                    let soma = 0;
                    let pos = tamanho - 7;
                    for (let i = tamanho; i >= 1; i--) {
                        soma += numeros.charAt(tamanho - i) * pos--;
                        if (pos < 2) pos = 9;
                    }
                    let resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
                    if (resultado != digitos.charAt(0)) return false;

                    tamanho = tamanho + 1;
                    numeros = cnpj.substring(0, tamanho);
                    soma = 0;
                    pos = tamanho - 7;
                    for (let i = tamanho; i >= 1; i--) {
                        soma += numeros.charAt(tamanho - i) * pos--;
                        if (pos < 2) pos = 9;
                    }
                    resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
                    if (resultado != digitos.charAt(1)) return false;

                    return true;
                }

                function validateCEP(cep) {
                    cep = cep.replace(/\D/g, '');
                    return cep.length === 8;
                }

                function formatCnpj(cnpj) {
                    cnpj = cnpj.replace(/\D/g, '');
                    return cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
                }

                function formatPhone(phone) {
                    phone = phone.replace(/\D/g, '');
                    if (phone.length === 10) {
                        return phone.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
                    } else if (phone.length === 11) {
                        return phone.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                    }
                    return phone;
                }

                function formatCep(cep) {
                    cep = cep.replace(/\D/g, '');
                    return cep.replace(/(\d{5})(\d{3})/, '$1-$2');
                }

                form.addEventListener('submit', function(e) {
                    if (!validateForm()) {
                        e.preventDefault();
                    }
                });

                window.openFornecedorModal = openModal;
            });

            function viewFornecedor(id) {
                const row = document.querySelector(`.fornecedor-row[data-id='${id}']`);
                if (!row) {
                    alert('Fornecedor não encontrado.');
                    return;
                }
                const fornecedor = {
                    id: id,
                    nome: row.dataset.nome || '',
                    cnpj: row.dataset.cnpj || '',
                    email: row.dataset.email || '',
                    telefone: row.dataset.telefone || '',
                    cep: row.dataset.cep || '',
                    endereco: row.dataset.endereco || '',
                    numero: row.dataset.numero || '',
                    complemento: row.dataset.complemento || '',
                    bairro: row.dataset.bairro || '',
                    cidade: row.dataset.cidade || '',
                    estado: row.dataset.estado || '',
                    ativo: row.dataset.status || 1,
                    data_criacao: row.dataset.dataCriacao || ''
                };
                showFornecedorDetails(fornecedor);
            }

            function editFornecedor(id) {
                const row = document.querySelector(`.fornecedor-row[data-id='${id}']`);
                if (!row) {
                    alert('Fornecedor não encontrado.');
                    return;
                }
                const fornecedor = {
                    id: id,
                    nome: row.dataset.nomeOriginal || row.dataset.nome || '',
                    cnpj: row.dataset.cnpj || '',
                    email: row.dataset.emailOriginal || row.dataset.email || '',
                    telefone: row.dataset.telefone || '',
                    cep: row.dataset.cep || '',
                    endereco: row.dataset.endereco || '',
                    numero: row.dataset.numero || '',
                    complemento: row.dataset.complemento || '',
                    bairro: row.dataset.bairro || '',
                    cidade: row.dataset.cidade || '',
                    estado: row.dataset.estado || '',
                    ativo: row.dataset.status || 1
                };
                window.openFornecedorModal(fornecedor);
            }

            function toggleFornecedorStatus(id, novoStatus) {
                const acao = novoStatus ? 'ativar' : 'desativar';
                if (confirm(`Deseja ${acao} este fornecedor?`)) {
                    fetch(`/sistema_ca_jesus/public/fornecedores/toggle-status/${id}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                status: novoStatus
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert('Erro ao alterar status: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Erro ao alterar status:', error);
                            alert('Erro ao alterar status do fornecedor');
                        });
                }
            }

            function deleteFornecedor(id, nome) {
                if (confirm(`Tem certeza que deseja excluir o fornecedor "${nome}"?`)) {
                    fetch(`/sistema_ca_jesus/public/fornecedores/excluir/${id}`, {
                            method: 'POST'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert('Erro: ' + data.message);
                            }
                        })
                        .catch(error => console.error('Erro:', error));
                }
            }

            function formatCnpj(cnpj) {
                cnpj = (cnpj || "").toString().replace(/\D/g, "");
                if (cnpj.length !== 14) return cnpj;
                return cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5");
            }

            function formatPhone(phone) {
                phone = (phone || "").toString().replace(/\D/g, "");
                if (phone.length === 10) {
                    return phone.replace(/(\d{2})(\d{4})(\d{4})/, "($1) $2-$3");
                } else if (phone.length === 11) {
                    return phone.replace(/(\d{2})(\d{5})(\d{4})/, "($1) $2-$3");
                }
                return phone;
            }

            function showFornecedorDetails(fornecedor) {
                const modal = document.createElement('div');
                modal.className = 'fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50';
                modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Detalhes do Fornecedor</h3>
                <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div><label class="block text-sm font-medium text-gray-600">Nome / Razão Social</label><p class="text-gray-900">${fornecedor.nome || '-'}</p></div>
                        <div><label class="block text-sm font-medium text-gray-600">CNPJ</label><p class="text-gray-900">${formatCnpj(fornecedor.cnpj || '')}</p></div>
                        <div><label class="block text-sm font-medium text-gray-600">Email</label><p class="text-gray-900">${fornecedor.email || '-'}</p></div>
                        <div><label class="block text-sm font-medium text-gray-600">Telefone</label><p class="text-gray-900">${formatPhone(fornecedor.telefone || '')}</p></div>
                    </div>
                    <div class="space-y-4">
                        <div><label class="block text-sm font-medium text-gray-600">Status</label><p class="text-gray-900">${(fornecedor.ativo ?? 1) ? 'Ativo' : 'Inativo'}</p></div>
                        <div><label class="block text-sm font-medium text-gray-600">Data de Cadastro</label><p class="text-gray-900">${fornecedor.data_criacao ? new Date(fornecedor.data_criacao).toLocaleString('pt-BR') : '-'}</p></div>
                    </div>
                </div>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h4 class="text-md font-semibold text-gray-800 mb-4">Endereço</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-600">CEP</label><p class="text-gray-900">${fornecedor.cep || '-'}</p></div>
                        <div><label class="block text-sm font-medium text-gray-600">Endereço</label><p class="text-gray-900">${fornecedor.endereco || '-'}</p></div>
                        <div><label class="block text-sm font-medium text-gray-600">Número</label><p class="text-gray-900">${fornecedor.numero || '-'}</p></div>
                        <div><label class="block text-sm font-medium text-gray-600">Bairro</label><p class="text-gray-900">${fornecedor.bairro || '-'}</p></div>
                        <div><label class="block text-sm font-medium text-gray-600">Cidade</label><p class="text-gray-900">${fornecedor.cidade || '-'}</p></div>
                        <div><label class="block text-sm font-medium text-gray-600">Estado</label><p class="text-gray-900">${fornecedor.estado || '-'}</p></div>
                    </div>
                </div>
            </div>
        </div>
    `;
                document.body.appendChild(modal);
            }
        </script>

        </body>

        </html>