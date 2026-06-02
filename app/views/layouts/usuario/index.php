<?php
require __DIR__ . '/../../../helpers/FormatHelper.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Usuários</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50 min-h-screen">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white rounded-2xl shadow-lg mb-6 overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900">Gerenciar Usuários</h3>
                        <p class="text-sm text-gray-500">Adicione, edite e remova usuários do sistema.</p>
                    </div>
                    <?php if ($_SESSION['user']['tipo'] == 1): 
                    ?>
                        <button id="new-usuario-btn"
                            class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-600 to-blue-500 text-white px-5 py-2.5 rounded-xl hover:scale-105 hover:from-blue-700 hover:to-blue-600 shadow-md transition-all duration-200">
                            <i class="fas fa-plus"></i> Novo Usuário
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="p-6 space-y-4">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="relative flex-1">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        <input type="text" id="search-usuario" placeholder="Buscar por nome, email, CPF..."
                            value="<?= htmlspecialchars($termoBusca ?? '') ?>"
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <button id="clear-search" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 hidden">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <select id="filter-tipo" class="px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos os tipos</option>
                        <option value="1">Administrador</option>
                        <option value="2">Funcionário</option>
                    </select>
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
                                <th class="px-4 py-3 text-left font-medium text-gray-600 cursor-pointer hover:bg-gray-200" data-sort="tipo">
                                    Tipo <i class="fas fa-sort ml-1 opacity-50"></i>
                                </th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Telefone</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600">Último Login</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="usuario-table-body" class="divide-y divide-gray-200">
                            <?php if (empty($usuarios)): ?>
                                <tr id="empty-state">
                                    <td colspan="7" class="px-4 py-12 text-center">
                                        <div class="flex flex-col items-center space-y-3">
                                            <i class="fas fa-users text-4xl text-gray-300"></i>
                                            <p class="text-gray-500 font-medium">Nenhum usuário encontrado</p>
                                            <?php if ($_SESSION['user']['tipo'] == 1): ?>
                                                <button onclick="document.getElementById('new-usuario-btn').click()"
                                                    class="text-blue-600 hover:text-blue-800 underline">
                                                    Cadastrar primeiro usuário
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr class="hover:bg-gray-50 transition usuario-row"
                                        data-nome="<?= htmlspecialchars(strtolower($usuario["nome"])) ?>"
                                        data-email="<?= htmlspecialchars(strtolower($usuario["email"])) ?>"
                                        data-tipo="<?= $usuario["tipo"] ?>"
                                        data-status="<?= $usuario["ativo"] ?? 1 ?>"
                                        data-cpf="<?= preg_replace('/[^0-9]/', '', $usuario["cpf"] ?? '') ?>">

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="flex items-center space-x-3">
                                                <div class="flex-shrink-0">
                                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold">
                                                        <?= strtoupper(substr($usuario["nome"], 0, 1)) ?>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($usuario["nome"]) ?>
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        ID: <?= $usuario["id"] ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?= htmlspecialchars($usuario["email"]) ?></div>
                                            <div class="text-xs text-gray-500">
                                                CPF: <?= FormatHelper::cpf($usuario["cpf"] ?? '') ?>
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $usuario["tipo"] == 1 ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' ?>">
                                                <?= $usuario["tipo"] == 1 ? 'Administrador' : 'Funcionário' ?>
                                            </span>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars(FormatHelper::phone($usuario["telefone"] ?? '')) ?>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= ($usuario["ativo"] ?? 1) ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                                <i class="fas <?= ($usuario["ativo"] ?? 1) ? 'fa-check-circle' : 'fa-times-circle' ?> mr-1"></i>
                                                <?= ($usuario["ativo"] ?? 1) ? 'Ativo' : 'Inativo' ?>
                                            </span>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            <?= isset($usuario["ultimo_login"]) ? date('d/m/Y H:i', strtotime($usuario["ultimo_login"])) : 'Nunca' ?>
                                        </td>

                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <div class="flex justify-center gap-2">
                                                <button onclick="viewUsuario(<?= $usuario['id'] ?>)"
                                                    class="p-2 rounded-full bg-gray-50 text-gray-600 hover:bg-gray-100 hover:scale-110 transition"
                                                    title="Visualizar">
                                                    <i class="fas fa-eye text-xs"></i>
                                                </button>

                                                <?php if ($_SESSION['user']['tipo'] == 1): ?>
                                                    <button onclick="editUsuario(<?= $usuario['id'] ?>)"
                                                        class="p-2 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-100 hover:scale-110 transition"
                                                        title="Editar">
                                                        <i class="fas fa-edit text-xs"></i>
                                                    </button>

                                                    <button onclick="toggleUsuarioStatus(<?= $usuario['id'] ?>, <?= ($usuario['ativo'] ?? 1) ? 0 : 1 ?>)"
                                                        class="p-2 rounded-full <?= ($usuario['ativo'] ?? 1) ? 'bg-yellow-50 text-yellow-600 hover:bg-yellow-100' : 'bg-green-50 text-green-600 hover:bg-green-100' ?> hover:scale-110 transition"
                                                        title="<?= ($usuario['ativo'] ?? 1) ? 'Desativar' : 'Ativar' ?>">
                                                        <i class="fas <?= ($usuario['ativo'] ?? 1) ? 'fa-pause' : 'fa-play' ?> text-xs"></i>
                                                    </button>

                                                    <?php if ($_SESSION['user']['id'] != $usuario['id']): ?>
                                                        <button onclick="deleteUsuario(<?= $usuario['id'] ?>, '<?= htmlspecialchars($usuario['nome']) ?>')"
                                                            class="p-2 rounded-full bg-red-50 text-red-600 hover:bg-red-100 hover:scale-110 transition"
                                                            title="Excluir">
                                                            <i class="fas fa-trash text-xs"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
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
                            Mostrando <?= $paginacao['inicio'] ?> a <?= $paginacao['fim'] ?> de <?= $paginacao['total'] ?> usuários
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

        <div id="usuario-modal" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50 hidden transition-all duration-300">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-hidden transform animate-fadeInUp relative">
    
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white z-20">
                    <h3 id="usuario-modal-title" class="text-lg font-semibold text-gray-800">Cadastrar Usuário</h3>
                    <button id="close-usuario-modal" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
       
                    <div class="flex items-center mb-8">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div id="progressBar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 33%"></div>
                        </div>
                        <span id="progressText" class="ml-4 text-sm font-medium text-gray-700">1 / 3</span>
                    </div>

                    <form id="usuario-form" method="POST" action="/sistema_ca_jesus/public/usuarios/salvar" class="space-y-8" novalidate>
                        <input type="hidden" id="usuario-id" name="id">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                        <div class="step">
                            <h4 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                                <i class="fas fa-user text-blue-600 mr-2"></i>
                                Dados Pessoais
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label for="usuario-nome" class="block text-sm font-medium text-gray-700 mb-2">
                                        Nome Completo *
                                    </label>
                                    <input id="usuario-nome" type="text" name="nome"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        required maxlength="100" autocomplete="name">
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>

                                <div class="form-group">
                                    <label for="usuario-email" class="block text-sm font-medium text-gray-700 mb-2">
                                        Email *
                                    </label>
                                    <input id="usuario-email" type="email" name="email"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        required autocomplete="email">
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>

                                <div class="form-group">
                                    <label for="usuario-cpf" class="block text-sm font-medium text-gray-700 mb-2">
                                        CPF *
                                    </label>
                                    <input id="usuario-cpf" type="text" name="cpf"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        required maxlength="14" placeholder="000.000.000-00">
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>

                                <div class="form-group">
                                    <label for="usuario-rg" class="block text-sm font-medium text-gray-700 mb-2">
                                        RG *
                                    </label>
                                    <input id="usuario-rg" type="text" name="rg"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        required maxlength="20">
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>

                                <div class="form-group">
                                    <label for="usuario-telefone" class="block text-sm font-medium text-gray-700 mb-2">
                                        Telefone *
                                    </label>
                                    <input id="usuario-telefone" type="tel" name="telefone"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        required placeholder="(11) 99999-9999">
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>

                                <div class="form-group">
                                    <label for="usuario-sexo" class="block text-sm font-medium text-gray-700 mb-2">
                                        Sexo *
                                    </label>
                                    <select id="usuario-sexo" name="sexo"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        required>
                                        <option value="">Selecione</option>
                                        <option value="Masculino">Masculino</option>
                                        <option value="Feminino">Feminino</option>
                                        <option value="Outro">Outro</option>
                                    </select>
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>
                            </div>

                            <div class="flex justify-end mt-8">
                                <button type="button" class="next-step bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-arrow-right mr-2"></i> Próximo
                                </button>
                            </div>
                        </div>

                        <div class="step hidden">
                            <h4 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                                <i class="fas fa-map-marker-alt text-blue-600 mr-2"></i>
                                Endereço
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                <div class="form-group">
                                    <label for="usuario-cep" class="block text-sm font-medium text-gray-700 mb-2">
                                        CEP *
                                    </label>
                                    <input id="usuario-cep" type="text" name="cep"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        required maxlength="9" placeholder="00000-000">
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>

                                <div class="form-group md:col-span-3">
                                    <label for="usuario-endereco" class="block text-sm font-medium text-gray-700 mb-2">
                                        Endereço *
                                    </label>
                                    <input id="usuario-endereco" type="text" name="endereco"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        required maxlength="200">
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>

                                <div class="form-group">
                                    <label for="usuario-numero" class="block text-sm font-medium text-gray-700 mb-2">
                                        Número *
                                    </label>
                                    <input id="usuario-numero" type="text" name="numero"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        required maxlength="10">
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>

                                <div class="form-group">
                                    <label for="usuario-complemento" class="block text-sm font-medium text-gray-700 mb-2">
                                        Complemento
                                    </label>
                                    <input id="usuario-complemento" type="text" name="complemento"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        maxlength="50">
                                </div>

                                <div class="form-group">
                                    <label for="usuario-bairro" class="block text-sm font-medium text-gray-700 mb-2">
                                        Bairro *
                                    </label>
                                    <input id="usuario-bairro" type="text" name="bairro"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        required maxlength="100">
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>

                                <div class="form-group">
                                    <label for="usuario-cidade" class="block text-sm font-medium text-gray-700 mb-2">
                                        Cidade *
                                    </label>
                                    <input id="usuario-cidade" type="text" name="cidade"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        required maxlength="100">
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>

                                <div class="form-group">
                                    <label for="usuario-estado" class="block text-sm font-medium text-gray-700 mb-2">
                                        Estado *
                                    </label>
                                    <select id="usuario-estado" name="estado"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        required>
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

                            <div class="flex justify-between mt-8">
                                <button type="button" id="prev-step-2" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors">
                                    <i class="fas fa-arrow-left mr-2"></i> Anterior
                                </button>
                                <button type="button" class="next-step bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-arrow-right mr-2"></i> Próximo
                                </button>
                            </div>
                        </div>

                        <div class="step hidden">
                            <h4 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                                <i class="fas fa-key text-blue-600 mr-2"></i>
                                Acesso e Configurações
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label for="usuario-senha" class="block text-sm font-medium text-gray-700 mb-2">
                                        <span id="senha-label">Senha *</span>
                                        <span class="text-gray-500 text-xs ml-1">(Mínimo 8 caracteres)</span>
                                    </label>
                                    <div class="relative">
                                        <input id="usuario-senha" type="password" name="senha"
                                            class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                            minlength="8">
                                        <button type="button" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600" onclick="togglePassword('usuario-senha')">
                                            <i class="fas fa-eye" id="usuario-senha-eye"></i>
                                        </button>
                                    </div>
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                    <div id="senha-help" class="text-xs text-gray-500 mt-1 hidden">Deixe em branco para manter a senha atual</div>
                                </div>

                                <div class="form-group">
                                    <label for="usuario-tipo" class="block text-sm font-medium text-gray-700 mb-2">
                                        Tipo de Usuário *
                                    </label>
                                    <select id="usuario-tipo" name="tipo"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        required>
                                        <option value="">Selecione</option>
                                        <option value="1">Administrador</option>
                                        <option value="2">Funcionário</option>
                                    </select>
                                    <div class="error-message text-red-500 text-xs mt-1 hidden"></div>
                                </div>

                                <div class="form-group md:col-span-2">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="usuario-ativo" name="ativo" value="1" checked
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label for="usuario-ativo" class="ml-2 block text-sm text-gray-700">
                                            Usuário ativo
                                        </label>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Usuários inativos não podem fazer login no sistema</p>
                                </div>
                            </div>
                        </div>

                        <div id="final-nav" class="flex justify-between mt-8 hidden">
                            <button type="button" id="prev-step-3" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i> Anterior
                            </button>
                            <button type="submit" class="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>
                                <span id="submit-text">Criar Usuário</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Elementos
                const modal = document.getElementById('usuario-modal');
                const newBtn = document.getElementById('new-usuario-btn');
                const closeBtn = document.getElementById('close-usuario-modal');
                const form = document.getElementById('usuario-form');
                const searchInput = document.getElementById('search-usuario');
                const clearSearchBtn = document.getElementById('clear-search');
                const filterTipo = document.getElementById('filter-tipo');
                const filterStatus = document.getElementById('filter-status');

                let currentStep = 1;
                const totalSteps = 3;
                const steps = document.querySelectorAll('.step');
                const progressBar = document.getElementById('progressBar');
                const progressText = document.getElementById('progressText');

                updateStepDisplay();

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

                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('next-step') || e.target.closest('.next-step')) {
                        e.preventDefault();
                        if (validateCurrentStep()) {
                            nextStep();
                        }
                    }

                    if (e.target.id === 'prev-step-2' || e.target.closest('#prev-step-2')) {
                        e.preventDefault();
                        prevStep();
                    }

                    if (e.target.id === 'prev-step-3' || e.target.closest('#prev-step-3')) {
                        e.preventDefault();
                        prevStep();
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

                if (filterTipo) {
                    filterTipo.addEventListener('change', filterTable);
                }

                if (filterStatus) {
                    filterStatus.addEventListener('change', filterTable);
                }

                const cepInput = document.getElementById('usuario-cep');
                if (cepInput) {
                    cepInput.addEventListener('blur', function() {
                        const cep = this.value.replace(/\D/g, '');
                        if (cep.length === 8) {
                            buscarCep(cep);
                        }
                    });
                }

                setupInputMasks();

                function openModal(usuario = null) {
                    resetForm();
                    currentStep = 1;
                    updateStepDisplay();

                    if (usuario) {
                        document.getElementById('usuario-modal-title').textContent = 'Editar Usuário';
                        document.getElementById('submit-text').textContent = 'Atualizar Usuário';
                        document.getElementById('senha-help').classList.remove('hidden');
                        document.getElementById('senha-label').innerHTML = 'Nova Senha <span class="text-gray-500 text-xs ml-1">(Deixe em branco para manter atual)</span>';

                        fillForm(usuario);
                    } else {
                        document.getElementById('usuario-modal-title').textContent = 'Novo Usuário';
                        document.getElementById('submit-text').textContent = 'Criar Usuário';
                        document.getElementById('senha-help').classList.add('hidden');
                        document.getElementById('senha-label').innerHTML = 'Senha * <span class="text-gray-500 text-xs ml-1">(Mínimo 8 caracteres)</span>';
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
                    currentStep = 1;
                    updateStepDisplay();
                    clearErrors();
                    document.getElementById('usuario-id').value = '';
                }

                function fillForm(usuario) {
                    document.getElementById('usuario-id').value = usuario.id;
                    document.getElementById('usuario-nome').value = usuario.nome;
                    document.getElementById('usuario-email').value = usuario.email;
                    document.getElementById('usuario-cpf').value = formatCpf(usuario.cpf);
                    document.getElementById('usuario-rg').value = usuario.rg;
                    document.getElementById('usuario-telefone').value = formatPhone(usuario.telefone);
                    document.getElementById('usuario-sexo').value = usuario.sexo;
                    document.getElementById('usuario-cep').value = formatCep(usuario.cep);
                    document.getElementById('usuario-endereco').value = usuario.endereco;
                    document.getElementById('usuario-numero').value = usuario.numero;
                    document.getElementById('usuario-complemento').value = usuario.complemento || '';
                    document.getElementById('usuario-bairro').value = usuario.bairro;
                    document.getElementById('usuario-cidade').value = usuario.cidade;
                    document.getElementById('usuario-estado').value = usuario.estado;
                    document.getElementById('usuario-tipo').value = usuario.tipo;
                    document.getElementById('usuario-ativo').checked = usuario.ativo == 1;
                }

                function nextStep() {
                    if (currentStep < totalSteps) {
                        steps[currentStep - 1].classList.add('hidden');
                        currentStep++;
                        steps[currentStep - 1].classList.remove('hidden');
                        updateStepDisplay();
                    }
                }

                function prevStep() {
                    if (currentStep > 1) {
                        steps[currentStep - 1].classList.add('hidden');
                        currentStep--;
                        steps[currentStep - 1].classList.remove('hidden');
                        updateStepDisplay();
                    }
                }

                function updateStepDisplay() {
                    const progress = (currentStep / totalSteps) * 100;
                    progressBar.style.width = progress + '%';
                    progressText.textContent = `${currentStep} / ${totalSteps}`;

                    steps.forEach((step, index) => {
                        if (index === currentStep - 1) {
                            step.classList.remove('hidden');
                        } else {
                            step.classList.add('hidden');
                        }
                    });
                    const finalNav = document.getElementById('final-nav');
                    if (finalNav) {
                        if (currentStep === totalSteps) {
                            finalNav.classList.remove('hidden');
                        } else {
                            finalNav.classList.add('hidden');
                        }
                    }
                }

                function validateCurrentStep() {
                    const currentStepElement = steps[currentStep - 1];
                    const requiredFields = currentStepElement.querySelectorAll('[required]');
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
                            } else if (field.name === 'cpf' && !validateCPF(field.value)) {
                                showFieldError(field, 'CPF inválido');
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
                    const cpfInput = document.getElementById('usuario-cpf');
                    if (cpfInput) {
                        cpfInput.addEventListener('input', function(e) {
                            let value = e.target.value.replace(/\D/g, '');
                            value = value.replace(/(\d{3})(\d)/, '$1.$2');
                            value = value.replace(/(\d{3})(\d)/, '$1.$2');
                            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                            e.target.value = value;
                        });
                    }

                    const telefoneInput = document.getElementById('usuario-telefone');
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
                                document.getElementById('usuario-endereco').value = data.logradouro;
                                document.getElementById('usuario-bairro').value = data.bairro;
                                document.getElementById('usuario-cidade').value = data.localidade;
                                document.getElementById('usuario-estado').value = data.uf;
                            }
                        })
                        .catch(error => console.log('Erro ao buscar CEP:', error));
                }

                function filterTable() {
                    const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
                    const tipoFilter = filterTipo ? filterTipo.value : '';
                    const statusFilter = filterStatus ? filterStatus.value : '';

                    const rows = document.querySelectorAll('.usuario-row');
                    let visibleCount = 0;

                    rows.forEach(row => {
                        const nome = row.dataset.nome || '';
                        const email = row.dataset.email || '';
                        const cpf = row.dataset.cpf || '';
                        const tipo = row.dataset.tipo || '';
                        const status = row.dataset.status || '';

                        const matchesSearch = !searchTerm ||
                            nome.includes(searchTerm) ||
                            email.includes(searchTerm) ||
                            cpf.includes(searchTerm);

                        const matchesTipo = !tipoFilter || tipo === tipoFilter;
                        const matchesStatus = !statusFilter || status === statusFilter;

                        if (matchesSearch && matchesTipo && matchesStatus) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    const emptyState = document.getElementById('empty-state');
                    if (emptyState) {
                        if (visibleCount === 0) {
                            emptyState.style.display = '';
                        } else {
                            emptyState.style.display = 'none';
                        }
                    }

                    updateStats();
                }

                function updateStats() {
                    const visibleRows = document.querySelectorAll('.usuario-row:not([style*="display: none"])');
                    const total = visibleRows.length;
                    const ativos = Array.from(visibleRows).filter(row => row.dataset.status === '1').length;
                    const admins = Array.from(visibleRows).filter(row => row.dataset.tipo === '1').length;
                    const funcionarios = Array.from(visibleRows).filter(row => row.dataset.tipo === '2').length;

                    document.getElementById('stats-total').textContent = total;
                    document.getElementById('stats-ativos').textContent = ativos;
                    document.getElementById('stats-admins').textContent = admins;
                    document.getElementById('stats-funcionarios').textContent = funcionarios;
                }

                function validateEmail(email) {
                    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    return re.test(email);
                }

                function validateCPF(cpf) {
                    cpf = cpf.replace(/\D/g, '');
                    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;

                    let sum = 0;
                    for (let i = 0; i < 9; i++) {
                        sum += parseInt(cpf.charAt(i)) * (10 - i);
                    }
                    let remainder = 11 - (sum % 11);
                    if (remainder === 10 || remainder === 11) remainder = 0;
                    if (remainder !== parseInt(cpf.charAt(9))) return false;

                    sum = 0;
                    for (let i = 0; i < 10; i++) {
                        sum += parseInt(cpf.charAt(i)) * (11 - i);
                    }
                    remainder = 11 - (sum % 11);
                    if (remainder === 10 || remainder === 11) remainder = 0;
                    return remainder === parseInt(cpf.charAt(10));
                }

                function validateCEP(cep) {
                    cep = cep.replace(/\D/g, '');
                    return cep.length === 8;
                }

                function formatCpf(cpf) {
                    cpf = cpf.replace(/\D/g, '');
                    return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                }

                function formatTelefone(telefone) {
                    telefone = telefone.replace(/\D/g, '');
                    if (telefone.length === 10) {
                        return telefone.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
                    } else if (telefone.length === 11) {
                        return telefone.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                    }
                    return telefone;
                }

                function formatCep(cep) {
                    cep = cep.replace(/\D/g, '');
                    return cep.replace(/(\d{5})(\d{3})/, '$1-$2');
                }

                form.addEventListener('submit', function(e) {
                    if (!validateCurrentStep()) {
                        e.preventDefault();
                    }
                });

                window.openUsuarioModal = openModal;
            });

            function formatCPF(cpf) {
                cpf = cpf.replace(/\D/g, '');
                if (cpf.length === 11) {
                    return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                }
                return cpf;
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

            function viewUsuario(id) {
                fetch(`/sistema_ca_jesus/public/usuarios/buscar/${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showUsuarioDetails(data.usuario);
                        } else {
                            alert('Erro ao carregar dados do usuário: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao carregar usuário:', error);
                        alert('Erro ao carregar dados do usuário');
                    });
            }

            function editUsuario(id) {
                fetch(`/sistema_ca_jesus/public/usuarios/buscar/${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.openUsuarioModal(data.usuario);
                        } else {
                            alert('Erro ao carregar dados do usuário: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao carregar usuário:', error);
                        alert('Erro ao carregar dados do usuário');
                    });
            }

            function toggleUsuarioStatus(id, novoStatus) {
                const acao = novoStatus ? 'ativar' : 'desativar';
                if (confirm(`Deseja ${acao} este usuário?`)) {
                    fetch(`/sistema_ca_jesus/public/usuarios/toggle-status/${id}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                status: novoStatus
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Recarregar a 
                                window.location.reload();
                            } else {
                                alert('Erro ao alterar status: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Erro ao alterar status:', error);
                            alert('Erro ao alterar status do usuário');
                        });
                }
            }

            function deleteUsuario(id, nome) {
                if (confirm(`Tem certeza que deseja excluir o usuário "${nome}"?\n\nEsta ação não pode ser desfeita.`)) {
                    fetch(`/sistema_ca_jesus/public/usuarios/excluir/${id}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Recarregar a 
                                window.location.reload();
                            } else {
                                alert('Erro ao excluir usuário: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Erro ao excluir usuário:', error);
                            alert('Erro ao excluir usuário');
                        });
                }
            }

            function showUsuarioDetails(usuario) {
                // Criar modal de visualização
                const modal = document.createElement('div');
                modal.className = 'fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50';
                modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Detalhes do Usuário</h3>
                <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Nome</label>
                            <p class="text-gray-900">${usuario.nome || '-'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Email</label>
                            <p class="text-gray-900">${usuario.email || '-'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">CPF</label>
                            <p class="text-gray-900">${formatCPF(usuario.cpf || '')}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Telefone</label>
                            <p class="text-gray-900">${formatPhone(usuario.telefone || '')}</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Tipo</label>
                            <p class="text-gray-900">${usuario.tipo == 1 ? 'Administrador' : 'Funcionário'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Status</label>
                            <p class="text-gray-900">${(usuario.ativo ?? 1) ? 'Ativo' : 'Inativo'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Último Login</label>
                            <p class="text-gray-900">${usuario.ultimo_login ? new Date(usuario.ultimo_login).toLocaleString('pt-BR') : 'Nunca'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Data de Cadastro</label>
                            <p class="text-gray-900">${usuario.data_criacao ? new Date(usuario.data_criacao).toLocaleString('pt-BR') : '-'}</p>
                        </div>
                    </div>
                </div>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h4 class="text-md font-semibold text-gray-800 mb-4">Endereço</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600">CEP</label>
                            <p class="text-gray-900">${usuario.cep || '-'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Endereço</label>
                            <p class="text-gray-900">${usuario.endereco || '-'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Número</label>
                            <p class="text-gray-900">${usuario.numero || '-'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Bairro</label>
                            <p class="text-gray-900">${usuario.bairro || '-'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Cidade</label>
                            <p class="text-gray-900">${usuario.cidade || '-'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Estado</label>
                            <p class="text-gray-900">${usuario.estado || '-'}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
                document.body.appendChild(modal);
            }

            function togglePassword(fieldId) {
                const field = document.getElementById(fieldId);
                const eye = document.getElementById(fieldId + '-eye');

                if (field.type === 'password') {
                    field.type = 'text';
                    eye.classList.remove('fa-eye');
                    eye.classList.add('fa-eye-slash');
                } else {
                    field.type = 'password';
                    eye.classList.remove('fa-eye-slash');
                    eye.classList.add('fa-eye');
                }
            }
        </script>

</body>

</html>