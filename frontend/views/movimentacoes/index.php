<?php
use App\Core\Session;
?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Movimentações</h1>
                    <p class="text-sm text-gray-600 mt-1">Gerencie entradas e saídas de estoque</p>
                </div>
                <div class="flex items-center gap-3 text-sm text-gray-500">
                    <i class="fas fa-calendar text-blue-600"></i>
                    <span><?= date('d/m/Y') ?></span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Nova Movimentação</h3>

                <form action="<?= BASE_URL ?>/movimentacoes/registrar" method="POST" class="space-y-4" onsubmit="registrarMovimentacao(event)">
                    <?= Session::campoCsrf() ?>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Produto</label>
                        <select id="produto-select" name="produto"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required>
                            <option value="">Selecione um produto</option>
                            <?php if (!empty($produtos)): ?>
                                <?php foreach ($produtos as $p): ?>
                                    <option
                                        value="<?= (int)$p['id'] ?>"
                                        data-custo="<?= htmlspecialchars($p['preco_custo']) ?>"
                                        data-venda="<?= htmlspecialchars($p['preco_venda']) ?>"
                                        data-estoque="<?= (int)$p['estoque'] ?>">
                                        <?= htmlspecialchars($p['marca']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>

                        <div id="estoque-info" class="mt-3 hidden">
                            <span id="estoque-badge"
                                class="px-3 py-1 text-sm font-semibold rounded-full">
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                            <select name="tipo"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required>
                                <option value="">Selecione</option>
                                <option value="entrada">Entrada</option>
                                <option value="saida">Saída</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quantidade</label>
                            <input type="number" name="quantidade" min="1"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Preço Custo</label>
                            <input type="number" id="preco-custo" name="preco_custo" step="0.01"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Preço Venda</label>
                            <input type="number" id="preco-venda" name="preco_venda" step="0.01"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required>
                        </div>
                    </div>


                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                        <textarea name="observacao" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    </div>


                    <button type="submit"
                        class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition duration-200">
                        <i class="fas fa-save mr-2"></i> Registrar Movimentação
                    </button>
                </form>
            </div>


            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Estoque Atual</h3>
                <div id="estoque-atual" class="space-y-3 max-h-96 overflow-y-auto">
                    <?php if (!empty($produtos)): ?>
                        <?php foreach ($produtos as $produto): ?>
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                <span class="font-medium"><?= htmlspecialchars($produto['marca']) ?></span>
                                <span class="<?= (int)$produto['estoque'] === 0 ? 'text-gray-500' : ((int)$produto['estoque'] > 10 ? 'text-green-600' : ((int)$produto['estoque'] > 5 ? 'text-yellow-600' : 'text-red-600')) ?>">
                                    <?= (int)$produto['estoque'] ?> unidade(s)
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-4">Nenhum produto cadastrado</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Histórico de Movimentações</h3>
            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produto</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantidade</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preço Custo</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preço Venda</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuário</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Observação</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($movimentacoes)): ?>
                            <?php foreach ($movimentacoes as $mov): ?>
                                <tr>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                        <?= date('d/m/Y H:i', strtotime($mov['data'])) ?>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($mov['produto']) ?>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm">
                                        <span class="px-2 py-1 text-xs rounded-full <?= $mov['acao'] === 'entrada' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= ucfirst($mov['acao']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                        <?= $mov['quantidade'] ?>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                        R$ <?= number_format($mov['preco_custo'], 2, ',', '.') ?>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                        R$ <?= number_format($mov['preco_venda'], 2, ',', '.') ?>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($mov['usuario'] ?? '-') ?>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($mov['observacao'] ?? '-') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                    Nenhuma movimentação registrada
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<script src="<?= BASE_URL ?>/assets/js/movimentacoes.js" defer></script>
