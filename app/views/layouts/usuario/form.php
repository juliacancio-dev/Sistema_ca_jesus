<?php
$isEdit = isset($usuario) && is_array($usuario) && !empty($usuario['id']);
$actionUrl = BASE_URL . '/public/usuarios/salvar';
$titleForm = $isEdit ? 'Editar Usuário' : 'Novo Usuário';

function field($arr, $key, $default = '') {
    return htmlspecialchars($arr[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}
?>

<div class="max-w-5xl mx-auto">
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800"><?= $titleForm ?></h1>
            <a href="<?= BASE_URL ?>/public/usuarios" class="inline-flex items-center px-3 py-2 rounded-lg border text-sm text-gray-700 hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
        </div>
        <p class="text-sm text-gray-500 mt-1">Preencha os dados abaixo para <?= $isEdit ? 'atualizar' : 'cadastrar' ?> o usuário.</p>
    </div>

    <form action="<?= $actionUrl ?>" method="post" class="bg-white rounded-xl shadow p-6 space-y-6">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$usuario['id'] ?>">
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                <select name="tipo" class="w-full border-gray-300 rounded-lg" required>
                    <?php foreach (($tipos ?? []) as $k => $lbl): $sel = ($isEdit && (int)$usuario['tipo'] === (int)$k) ? 'selected' : ''; ?>
                        <option value="<?= (int)$k ?>" <?= $sel ?>><?= htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                <input type="text" name="nome" class="w-full border-gray-300 rounded-lg" value="<?= field($usuario ?? [], 'nome') ?>" minlength="3" maxlength="100" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" class="w-full border-gray-300 rounded-lg" value="<?= field($usuario ?? [], 'email') ?>" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Senha <?= $isEdit ? '<span class=\'text-gray-500\'>(deixe em branco para manter)</span>' : '' ?></label>
                <input type="password" name="senha" class="w-full border-gray-300 rounded-lg" <?= $isEdit ? '' : 'required' ?> minlength="8">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CPF</label>
                <input type="text" name="cpf" class="w-full border-gray-300 rounded-lg" value="<?= field($usuario ?? [], 'cpf') ?>" placeholder="Somente números" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">RG</label>
                <input type="text" name="rg" class="w-full border-gray-300 rounded-lg" value="<?= field($usuario ?? [], 'rg') ?>" placeholder="Opcional">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                <input type="text" name="telefone" class="w-full border-gray-300 rounded-lg" value="<?= field($usuario ?? [], 'telefone') ?>" placeholder="(xx) xxxxx-xxxx" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sexo</label>
                <select name="sexo" class="w-full border-gray-300 rounded-lg" required>
                    <?php $sxAtual = $isEdit ? ($usuario['sexo'] ?? '') : ''; ?>
                    <?php foreach (($sexos ?? []) as $sx): $sel = ($sxAtual === $sx) ? 'selected' : ''; ?>
                        <option value="<?= htmlspecialchars($sx, ENT_QUOTES, 'UTF-8') ?>" <?= $sel ?>><?= htmlspecialchars($sx, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                <input type="text" name="cep" class="w-full border-gray-300 rounded-lg" value="<?= field($usuario ?? [], 'cep') ?>" placeholder="Somente números" required>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Endereço</label>
                <input type="text" name="endereco" class="w-full border-gray-300 rounded-lg" value="<?= field($usuario ?? [], 'endereco') ?>" maxlength="200" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                <input type="text" name="cidade" class="w-full border-gray-300 rounded-lg" value="<?= field($usuario ?? [], 'cidade') ?>" maxlength="100" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estado (UF)</label>
                <input type="text" name="estado" class="w-full border-gray-300 rounded-lg uppercase" value="<?= field($usuario ?? [], 'estado') ?>" minlength="2" maxlength="2" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                <input type="text" name="numero" class="w-full border-gray-300 rounded-lg" value="<?= field($usuario ?? [], 'numero') ?>" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                <input type="text" name="complemento" class="w-full border-gray-300 rounded-lg" value="<?= field($usuario ?? [], 'complemento') ?>">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                <input type="text" name="bairro" class="w-full border-gray-300 rounded-lg" value="<?= field($usuario ?? [], 'bairro') ?>" maxlength="100" required>
            </div>
        </div>

        <div class="flex items-center justify-between pt-2">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <?php $ativo = $isEdit ? (int)($usuario['ativo'] ?? 1) : 1; ?>
                <input type="checkbox" name="ativo" value="1" <?= $ativo === 1 ? 'checked' : '' ?>>
                Usuário ativo
            </label>
            <div class="flex items-center gap-3">
                <a href="<?= BASE_URL ?>/public/usuarios" class="px-4 py-2 rounded-lg border text-gray-700 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Salvar</button>
            </div>
        </div>
    </form>
</div>
