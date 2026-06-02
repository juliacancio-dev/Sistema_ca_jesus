<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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

                <form action="/sistema_ca_jesus/public/movimentacoes/registrar" method="POST" class="space-y-4" onsubmit="return handleFormSubmit(event)">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Produto</label>
                        <select id="produto-select" name="produto"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required>
                            <option value="">Selecione um produto</option>
                            <?php if (!empty($produtos)): ?>
                                <?php foreach ($produtos as $p): ?>
                                    <option
                                        value="<?= htmlspecialchars($p['marca']) ?>"
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

    <script>

        function handleFormSubmit(event) {
            event.preventDefault();

            const form = event.target;

            const produto = form.querySelector('[name="produto"]').value.trim();
            const tipo = form.querySelector('[name="tipo"]').value.trim();
            const quantidade = parseInt(form.querySelector('[name="quantidade"]').value || '0', 10);
            const custo = parseFloat(form.querySelector('[name="preco_custo"]').value || '0');
            const venda = parseFloat(form.querySelector('[name="preco_venda"]').value || '0');

            if (!produto || !tipo || quantidade <= 0 || custo < 0 || venda < 0) {
                alert('Preencha todos os campos obrigatórios corretamente.');
                return false;
            }
            if (custo > venda) {
                alert('Preço de custo não pode ser maior que o preço de venda.');
                return false;
            }

            if (tipo === 'saida') {
                const select = document.getElementById('produto-select');
                const opt = select.options[select.selectedIndex];
                const estoque = parseInt(opt.getAttribute('data-estoque') || '0', 10);
                if (estoque <= 0) {
                    alert('Estoque do produto está zerado. Não é possível registrar saída.');
                    return false;
                }
                if (quantidade > estoque) {
                    alert('Quantidade de saída maior que o estoque disponível (' + estoque + ').');
                    return false;
                }
            }

            const formData = new FormData(form);

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Registrando...';
            submitBtn.disabled = true;

            fetch(form.action, {
                    method: 'POST',
                    body: formData
                })
                .then(() => window.location.reload())
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao registrar movimentação. Tente novamente.');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });

            return false;
        }

        document.getElementById("produto-select").addEventListener("change", function() {
            const selected = this.options[this.selectedIndex];
            const precoCusto = selected.getAttribute("data-custo");
            const precoVenda = selected.getAttribute("data-venda");
            const estoque = selected.getAttribute("data-estoque");

            document.getElementById("preco-custo").value = precoCusto || "";
            document.getElementById("preco-venda").value = precoVenda || "";


            const estoqueContainer = document.getElementById("estoque-info");
            const estoqueBadge = document.getElementById("estoque-badge");

            if (estoque !== null && estoque !== "") {
                let badgeClass = "";
                const estoqueNum = parseInt(estoque);

                if (estoqueNum === 0) {
                    badgeClass = "bg-gray-100 text-gray-800";
                } else if (estoqueNum > 10) {
                    badgeClass = "bg-green-100 text-green-800";
                } else if (estoqueNum > 5) {
                    badgeClass = "bg-yellow-100 text-yellow-800";
                } else {
                    badgeClass = "bg-red-100 text-red-800";
                }

                estoqueBadge.className = `px-3 py-1 text-sm font-semibold rounded-full ${badgeClass}`;
                estoqueBadge.textContent = `Estoque atual: ${estoque} unidade(s)`;
                estoqueContainer.classList.remove("hidden");
            } else {
                estoqueBadge.textContent = "";
                estoqueContainer.classList.add("hidden");
            }
        });
    </script>