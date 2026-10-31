<?php
use App\Core\Session;
use App\Shared\Formatacao;
?>

    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Meu Perfil</h1>
                    <p class="text-gray-600 mt-2">Gerencie suas informações pessoais e configurações de conta</p>
                </div>
                <a href="<?= BASE_URL ?>/dashboard"
                    class="inline-flex items-center gap-2 bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                    Voltar ao Dashboard
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="text-center mb-6">
                        <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 text-white text-2xl font-bold mb-4">
                            <?= htmlspecialchars(mb_strtoupper(mb_substr($usuario['nome'], 0, 1))) ?>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900"><?= htmlspecialchars($usuario['nome']) ?></h3>
                        <p class="text-gray-600"><?= htmlspecialchars($usuario['email']) ?></p>
                        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full mt-2 <?= $usuario['tipo'] == 1 ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' ?>">
                            <?= $usuario['tipo'] == 1 ? 'Administrador' : 'Funcionário' ?>
                        </span>
                    </div>

                    <nav class="space-y-2">
                        <button onclick="showTab('dados')" class="tab-btn w-full text-left px-4 py-3 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-3 bg-blue-50 text-blue-700">
                            <i class="fas fa-user"></i>
                            Dados Pessoais
                        </button>
                        <button onclick="showTab('endereco')" class="tab-btn w-full text-left px-4 py-3 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-3 text-gray-700">
                            <i class="fas fa-map-marker-alt"></i>
                            Endereço
                        </button>
                        <button onclick="showTab('senha')" class="tab-btn w-full text-left px-4 py-3 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-3 text-gray-700">
                            <i class="fas fa-key"></i>
                            Alterar Senha
                        </button>
                    </nav>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div id="tab-dados" class="tab-content bg-white rounded-2xl shadow-lg p-8">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-user text-blue-600 mr-3"></i>
                        Dados Pessoais
                    </h2>

                    <form method="POST" action="<?= BASE_URL ?>/usuarios/atualizarDados">
                        <?= Session::campoCsrf() ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="nome" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nome Completo *
                                </label>
                                <input type="text" id="nome" name="nome"
                                    value="<?= htmlspecialchars($usuario['nome']) ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    required maxlength="100">
                            </div>

                            <div class="form-group">
                                <label for="email-display" class="block text-sm font-medium text-gray-700 mb-2">
                                    Email
                                </label>
                                <input type="email" id="email-display"
                                    value="<?= htmlspecialchars($usuario['email']) ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-500"
                                    disabled>
                                <p class="text-xs text-gray-500 mt-1">O email não pode ser alterado</p>
                            </div>

                            <div class="form-group">
                                <label for="cpf-display" class="block text-sm font-medium text-gray-700 mb-2">
                                    CPF
                                </label>
                                <input type="text" id="cpf-display"
                                    value="<?= Formatacao::cpf($usuario['cpf']) ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-gray-500"
                                    disabled>
                                <p class="text-xs text-gray-500 mt-1">O CPF não pode ser alterado</p>
                            </div>

                            <div class="form-group">
                                <label for="telefone" class="block text-sm font-medium text-gray-700 mb-2">
                                    Telefone *
                                </label>
                                <input type="tel" id="telefone" name="telefone"
                                    value="<?= htmlspecialchars($usuario['telefone']) ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    required placeholder="(11) 99999-9999">
                            </div>
                        </div>

                        <div class="flex justify-end mt-8">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>
                                Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>

                <div id="tab-endereco" class="tab-content bg-white rounded-2xl shadow-lg p-8 hidden">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-map-marker-alt text-blue-600 mr-3"></i>
                        Endereço
                    </h2>

                    <form method="POST" action="<?= BASE_URL ?>/usuarios/atualizarEndereco">
                        <?= Session::campoCsrf() ?>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div class="form-group">
                                <label for="cep" class="block text-sm font-medium text-gray-700 mb-2">
                                    CEP *
                                </label>
                                <input type="text" id="cep" name="cep"
                                    value="<?= htmlspecialchars($usuario['cep']) ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    required maxlength="9" placeholder="00000-000">
                            </div>

                            <div class="form-group md:col-span-3">
                                <label for="endereco" class="block text-sm font-medium text-gray-700 mb-2">
                                    Endereço *
                                </label>
                                <input type="text" id="endereco" name="endereco"
                                    value="<?= htmlspecialchars($usuario['endereco']) ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    required maxlength="200">
                            </div>

                            <div class="form-group">
                                <label for="numero" class="block text-sm font-medium text-gray-700 mb-2">
                                    Número *
                                </label>
                                <input type="text" id="numero" name="numero"
                                    value="<?= htmlspecialchars($usuario['numero']) ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    required maxlength="10">
                            </div>

                            <div class="form-group">
                                <label for="complemento" class="block text-sm font-medium text-gray-700 mb-2">
                                    Complemento
                                </label>
                                <input type="text" id="complemento" name="complemento"
                                    value="<?= htmlspecialchars($usuario['complemento'] ?? '') ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    maxlength="50">
                            </div>

                            <div class="form-group">
                                <label for="bairro" class="block text-sm font-medium text-gray-700 mb-2">
                                    Bairro *
                                </label>
                                <input type="text" id="bairro" name="bairro"
                                    value="<?= htmlspecialchars($usuario['bairro']) ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    required maxlength="100">
                            </div>

                            <div class="form-group">
                                <label for="cidade" class="block text-sm font-medium text-gray-700 mb-2">
                                    Cidade *
                                </label>
                                <input type="text" id="cidade" name="cidade"
                                    value="<?= htmlspecialchars($usuario['cidade']) ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    required maxlength="100">
                            </div>

                            <div class="form-group">
                                <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">
                                    Estado *
                                </label>
                                <select id="estado" name="estado"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    required>
                                    <option value="">Selecione</option>
                                    <?php
                                    $estados = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
                                    foreach ($estados as $estado):
                                    ?>
                                        <option value="<?= $estado ?>" <?= ($usuario['estado'] == $estado) ? 'selected' : '' ?>>
                                            <?= $estado ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end mt-8">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i>
                                Salvar Endereço
                            </button>
                        </div>
                    </form>
                </div>

                <div id="tab-senha" class="tab-content bg-white rounded-2xl shadow-lg p-8 hidden">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-key text-blue-600 mr-3"></i>
                        Alterar Senha
                    </h2>

                    <form method="POST" action="<?= BASE_URL ?>/usuarios/alterarSenha">
                        <?= Session::campoCsrf() ?>

                        <div class="space-y-6">
                            <div class="form-group">
                                <label for="senha_atual" class="block text-sm font-medium text-gray-700 mb-2">
                                    Senha Atual *
                                </label>
                                <div class="relative">
                                    <input type="password" id="senha_atual" name="senha_atual"
                                        class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        required>
                                    <button type="button" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600" onclick="togglePassword('senha_atual')">
                                        <i class="fas fa-eye" id="senha_atual-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="nova_senha" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nova Senha *
                                    <span class="text-gray-500 text-xs ml-1">(Mínimo 8 caracteres)</span>
                                </label>
                                <div class="relative">
                                    <input type="password" id="nova_senha" name="nova_senha"
                                        class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        required minlength="8">
                                    <button type="button" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600" onclick="togglePassword('nova_senha')">
                                        <i class="fas fa-eye" id="nova_senha-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="confirmar_senha" class="block text-sm font-medium text-gray-700 mb-2">
                                    Confirmar Nova Senha *
                                </label>
                                <div class="relative">
                                    <input type="password" id="confirmar_senha" name="confirmar_senha"
                                        class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        required minlength="8">
                                    <button type="button" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600" onclick="togglePassword('confirmar_senha')">
                                        <i class="fas fa-eye" id="confirmar_senha-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="text-sm text-gray-700 mb-2">Força da senha:</div>
                                <div class="flex space-x-1">
                                    <div class="h-2 w-1/4 bg-gray-200 rounded" id="strength-1"></div>
                                    <div class="h-2 w-1/4 bg-gray-200 rounded" id="strength-2"></div>
                                    <div class="h-2 w-1/4 bg-gray-200 rounded" id="strength-3"></div>
                                    <div class="h-2 w-1/4 bg-gray-200 rounded" id="strength-4"></div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1" id="strength-text">Digite uma senha</div>
                            </div>
                        </div>

                        <div class="flex justify-end mt-8">
                            <button type="submit" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition-colors">
                                <i class="fas fa-key mr-2"></i>
                                Alterar Senha
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<script src="<?= BASE_URL ?>/assets/js/perfil.js" defer></script>
