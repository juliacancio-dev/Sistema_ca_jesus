<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Produtos</h1>
                    <p class="text-sm text-gray-600 mt-1">Gerencie o catálogo de produtos do sistema</p>
                </div>
                <button id="new-produto-btn" class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-600 to-blue-500 text-white px-6 py-3 rounded-xl hover:scale-105 hover:from-blue-700 hover:to-blue-600 shadow-lg transition-all duration-200">
                    <i class="fas fa-plus"></i>
                    <span>Novo Produto</span>
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">

            <div class="p-6 border-b border-gray-200">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="relative flex-1">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        <input type="text" id="search-produtos" placeholder="Buscar produtos por marca, fornecedor..."
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    </div>
                    <div class="flex gap-2 items-center">
                        <select id="filter-estoque" class="px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="">Todos os estoques</option>
                            <option value="baixo">Estoque baixo (≤5)</option>
                            <option value="medio">Estoque médio (6-10)</option>
                            <option value="alto">Estoque alto (>10)</option>
                        </select>
                        <input id="filter-qtd-min" type="number" min="0" placeholder="Qtd min" class="w-28 px-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm" />
                        <input id="filter-qtd-max" type="number" min="0" placeholder="Qtd max" class="w-28 px-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm" />
                        <button id="btn-aplicar-filtro" class="px-4 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 text-sm">Filtrar</button>
                        <button id="btn-limpar-filtro" class="px-3 py-2.5 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 text-sm">Limpar</button>
                    </div>
                </div>
            </div>


            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Produto</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Preços</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estoque</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Margem</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="produtos-table-body" class="divide-y divide-gray-200">
                        <?php if (empty($produtos)): ?>
                            <tr id="empty-state">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center space-y-3">
                                        <i class="fas fa-boxes text-4xl text-gray-300"></i>
                                        <p class="text-gray-500 font-medium">Nenhum produto cadastrado</p>
                                        <button onclick="document.getElementById('new-produto-btn').click()"
                                            class="text-blue-600 hover:text-blue-800 underline">
                                            Cadastrar primeiro produto
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($produtos as $produto):
                                $margem = $produto["preco_venda"] > 0 ? (($produto["preco_venda"] - $produto["preco_custo"]) / $produto["preco_venda"]) * 100 : 0;
                            ?>
                                <tr class="hover:bg-gray-50 transition-colors produto-row"
                                    data-marca="<?= htmlspecialchars(strtolower($produto["marca"])) ?>"
                                    data-fornecedor="<?= htmlspecialchars(strtolower($produto["fornecedor"] ?? '')) ?>"
                                    data-estoque="<?= $produto["estoque"] ?>">

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center space-x-3">
                                            <div class="flex-shrink-0">
                                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold">
                                                    <?= strtoupper(substr($produto["marca"], 0, 1)) ?>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= htmlspecialchars($produto["marca"]) ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    Fornecedor: <?= htmlspecialchars($produto["fornecedor"] ?? "N/A") ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <div class="font-medium">Venda: R$ <?= number_format($produto["preco_venda"], 2, ",", ".") ?></div>
                                            <div class="text-xs text-gray-500">Custo: R$ <?= number_format($produto["preco_custo"], 2, ",", ".") ?></div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full <?= $produto["estoque"] > 10 ? "bg-green-100 text-green-800" : ($produto["estoque"] > 5 ? "bg-yellow-100 text-yellow-800" : ($produto["estoque"] > 0 ? "bg-red-100 text-red-800" : "bg-gray-100 text-gray-800")) ?>">
                                            <i class="fas fa-cube mr-1"></i>
                                            <?= $produto["estoque"] ?> un.
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm">
                                            <span class="font-medium <?= $margem > 30 ? 'text-green-600' : ($margem > 15 ? 'text-yellow-600' : 'text-red-600') ?>">
                                                <?= number_format($margem, 1) ?>%
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex justify-center gap-2">
                                            <button onclick="viewProduto(<?= $produto['id'] ?>)"
                                                class="p-2 rounded-full bg-gray-50 text-gray-600 hover:bg-gray-100 hover:scale-110 transition"
                                                title="Visualizar">
                                                <i class="fas fa-eye text-xs"></i>
                                            </button>

                                            <button onclick="editProduto(<?= $produto['id'] ?>)"
                                                class="p-2 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-100 hover:scale-110 transition"
                                                title="Editar">
                                                <i class="fas fa-edit text-xs"></i>
                                            </button>

                                            <button onclick="deleteProduto(<?= $produto['id'] ?>)"
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
        </div>
    </div>
</div>

<div id="produto-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 id="produto-modal-title" class="text-lg font-semibold text-gray-800">Cadastrar Produto</h3>
        </div>

        <form id="produto-form" action="<?= BASE_URL ?>/public/produtos/salvar" method="POST" class="p-6 space-y-4">
            <input type="hidden" id="produto-id" name="id">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Marca do Produto</label>
                <input type="text" id="produto-marca" name="marca" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Preço de Custo</label>
                <input type="number" id="produto-custo" name="preco_custo" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Preço de Venda</label>
                <input type="number" id="produto-venda" name="preco_venda" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Fornecedor</label>
                <select id="produto-fornecedor" name="fornecedor_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    <option value="">Selecione um fornecedor</option>
                    <?php foreach ($fornecedores as $fornecedor): ?>
                        <option value="<?= $fornecedor["id"] ?>"><?= htmlspecialchars($fornecedor["nome"]) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex space-x-4 pt-4">
                <button type="button" id="close-produto-modal" class="flex-1 bg-gray-500 text-white py-2 rounded-lg hover:bg-gray-600 transition duration-200">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition duration-200">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../partials/toast.php'; ?>

<script>

    document.getElementById("new-produto-btn").addEventListener("click", () => {
        document.getElementById("produto-modal-title").textContent = "Cadastrar Produto";
        document.getElementById("produto-form").reset();
        document.getElementById("produto-form").action = "<?= BASE_URL ?>/public/produtos/salvar";
        setProdutoFormReadOnly(false);
        document.getElementById("produto-modal").classList.remove("hidden");
    });

    document.getElementById("close-produto-modal").addEventListener("click", () => {
        document.getElementById("produto-modal").classList.add("hidden");
    });

    function editProduto(id) {
        fetch("<?= BASE_URL ?>/public/api/produtos/buscar?id=" + id)
            .then(response => response.json())
            .then(produto => {
                document.getElementById("produto-modal-title").textContent = "Editar Produto";
                document.getElementById("produto-id").value = produto.id;
                document.getElementById("produto-marca").value = produto.marca;
                document.getElementById("produto-custo").value = produto.preco_custo;
                document.getElementById("produto-venda").value = produto.preco_venda;
                document.getElementById("produto-fornecedor").value = produto.fornecedor_id;
                document.getElementById("produto-form").action = "<?= BASE_URL ?>/public/produtos/atualizar/" + id;
                setProdutoFormReadOnly(false);
                document.getElementById("produto-modal").classList.remove("hidden");
            });
    }

    function viewProduto(id) {
        fetch("<?= BASE_URL ?>/public/api/produtos/buscar?id=" + id)
            .then(response => response.json())
            .then(produto => {
                document.getElementById("produto-modal-title").textContent = "Visualizar Produto";
                document.getElementById("produto-id").value = produto.id;
                document.getElementById("produto-marca").value = produto.marca;
                document.getElementById("produto-custo").value = produto.preco_custo;
                document.getElementById("produto-venda").value = produto.preco_venda;
                document.getElementById("produto-fornecedor").value = produto.fornecedor_id;
                document.getElementById("produto-form").action = "#";
                setProdutoFormReadOnly(true);
                document.getElementById("produto-modal").classList.remove("hidden");
            });
    }

    function setProdutoFormReadOnly(readOnly) {
        const form = document.getElementById("produto-form");
        if (!form) return;
        form.querySelectorAll("input, select, textarea").forEach(el => {
            el.disabled = !!readOnly;
        });
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.style.display = readOnly ? 'none' : '';
        const cancelBtn = document.getElementById('close-produto-modal');
        if (cancelBtn) cancelBtn.textContent = readOnly ? 'Fechar' : 'Cancelar';
    }

    function deleteProduto(id) {
        if (confirm("Tem certeza que deseja excluir este produto?")) {
            fetch("<?= BASE_URL ?>/public/produtos/excluir/" + id, {
                    method: "DELETE"
                })
                .then(async (res) => {
                    const data = await res.json().catch(() => ({}));
                    if (res.ok) {
                        location.reload();
                    } else {
                        const msg = data && data.error ? data.error : "Falha ao excluir produto.";
                        alert(msg);
                    }
                })
                .catch((err) => {
                    alert("Erro de rede ao excluir: " + err.message);
                });
        }
    }

    const searchInput = document.getElementById("search-produtos");
    const filtroEstoque = document.getElementById("filter-estoque");
    const qtdMinInput = document.getElementById("filter-qtd-min");
    const qtdMaxInput = document.getElementById("filter-qtd-max");
    const btnAplicar = document.getElementById("btn-aplicar-filtro");
    const btnLimpar = document.getElementById("btn-limpar-filtro");

    function aplicarFiltros() {
        const termo = (searchInput.value || '').toLowerCase();
        const faixa = filtroEstoque.value;
        const min = parseInt(qtdMinInput.value || '');
        const max = parseInt(qtdMaxInput.value || '');

        const rows = document.querySelectorAll("#produtos-table-body tr");
        rows.forEach(row => {
            const texto = row.textContent.toLowerCase();
            const estoque = parseInt(row.getAttribute('data-estoque') || '0', 10);

            let visivel = texto.includes(termo);

            if (visivel && faixa) {
                if (faixa === 'baixo') visivel = estoque <= 5;
                else if (faixa === 'medio') visivel = estoque >= 6 && estoque <= 10;
                else if (faixa === 'alto') visivel = estoque > 10;
            }


            if (visivel && !isNaN(min)) visivel = estoque >= min;
            if (visivel && !isNaN(max)) visivel = estoque <= max;

            row.style.display = visivel ? '' : 'none';
        });
    }

    searchInput.addEventListener("input", aplicarFiltros);
    if (filtroEstoque) filtroEstoque.addEventListener("change", aplicarFiltros);
    if (btnAplicar) btnAplicar.addEventListener("click", aplicarFiltros);
    if (btnLimpar) btnLimpar.addEventListener("click", () => {
        qtdMinInput.value = '';
        qtdMaxInput.value = '';
        filtroEstoque.value = '';
        searchInput.value = '';
        aplicarFiltros();
    });

    const form = document.getElementById('produto-form');
    if (form) {
        form.addEventListener('submit', (e) => {
            const custo = parseFloat(document.getElementById('produto-custo').value || '0');
            const venda = parseFloat(document.getElementById('produto-venda').value || '0');
            if (venda < custo) {
                e.preventDefault();
                alert('Preço de venda não pode ser menor que o preço de custo.');
                return false;
            }
        });
    }

    document.addEventListener("click", (e) => {
        if (e.target.classList.contains("fixed") && e.target.classList.contains("inset-0")) {
            e.target.classList.add("hidden");
        }
    });
</script>