// A regra fica no servidor: esta função só mostra o que ele responde.
async function registrarMovimentacao(event) {
    event.preventDefault();
    const form = event.target;
    const botao = form.querySelector('button[type="submit"]');
    const textoDoBotao = botao.innerHTML;
    botao.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Registrando...';
    botao.disabled = true;

    const dados = new FormData(form);
    const enviar = () => requisicaoJson(form.action, { method: 'POST', body: dados });

    let resposta = await enviar();
    if (resposta.requer_confirmacao && confirm(resposta.mensagem)) {
        dados.set('confirmar_custo_maior', '1');
        resposta = await enviar();
    }

    if (resposta.sucesso) {
        window.location.reload();
        return;
    }
    botao.innerHTML = textoDoBotao;
    botao.disabled = false;
    if (!resposta.requer_confirmacao) {
        showToast(resposta.mensagem, 'error');
    }
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
