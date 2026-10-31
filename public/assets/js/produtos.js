document.getElementById("new-produto-btn").addEventListener("click", () => {
    document.getElementById("produto-modal-title").textContent = "Cadastrar Produto";
    document.getElementById("produto-form").reset();
    document.getElementById("produto-form").action = APP_BASE_URL + "/produtos/salvar";
    setProdutoFormReadOnly(false);
    document.getElementById("produto-modal").classList.remove("hidden");
});

document.getElementById("close-produto-modal").addEventListener("click", () => {
    document.getElementById("produto-modal").classList.add("hidden");
});

function editProduto(id) {
    requisicaoJson(APP_BASE_URL + "/api/produtos/buscar?id=" + id)
        .then(resposta => {
            if (!resposta.sucesso) {
                alert(resposta.mensagem);
                return;
            }
            const produto = resposta.dados;
            document.getElementById("produto-modal-title").textContent = "Editar Produto";
            document.getElementById("produto-id").value = produto.id;
            document.getElementById("produto-marca").value = produto.marca;
            document.getElementById("produto-custo").value = produto.preco_custo;
            document.getElementById("produto-venda").value = produto.preco_venda;
            document.getElementById("produto-fornecedor").value = produto.fornecedor_id;
            document.getElementById("produto-form").action = APP_BASE_URL + "/produtos/atualizar/" + id;
            setProdutoFormReadOnly(false);
            document.getElementById("produto-modal").classList.remove("hidden");
        });
}

function viewProduto(id) {
    requisicaoJson(APP_BASE_URL + "/api/produtos/buscar?id=" + id)
        .then(resposta => {
            if (!resposta.sucesso) {
                alert(resposta.mensagem);
                return;
            }
            const produto = resposta.dados;
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
        requisicaoJson(APP_BASE_URL + "/produtos/excluir/" + id, {
                method: "DELETE"
            })
            .then(resposta => {
                if (resposta.sucesso) {
                    location.reload();
                } else {
                    alert(resposta.mensagem);
                }
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
