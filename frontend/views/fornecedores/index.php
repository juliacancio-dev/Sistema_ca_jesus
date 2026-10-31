<?php
use App\Core\Session;
use App\Shared\Formatacao;
?>


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
                                                CNPJ: <?= Formatacao::cnpj($fornecedor["cnpj"] ?? '') ?>
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars(Formatacao::phone($fornecedor["telefone"] ?? '')) ?>
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

                    <form id="fornecedor-form" method="POST" action="<?= BASE_URL ?>/fornecedores/salvar" class="space-y-8" novalidate>
                        <input type="hidden" id="fornecedor-id" name="id">
                        <?= Session::campoCsrf() ?>

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

<script src="<?= BASE_URL ?>/assets/js/fornecedores.js" defer></script>
