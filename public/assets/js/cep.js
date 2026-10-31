// Preenche o endereço pelo ViaCEP. "prefixo" é o início dos ids dos campos
// (ex.: "usuario-" para usuario-endereco, usuario-bairro...).
function buscarCep(cep, prefixo = '') {
    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then((resposta) => resposta.json())
        .then((dados) => {
            if (!dados.erro) {
                document.getElementById(`${prefixo}endereco`).value = dados.logradouro;
                document.getElementById(`${prefixo}bairro`).value = dados.bairro;
                document.getElementById(`${prefixo}cidade`).value = dados.localidade;
                document.getElementById(`${prefixo}estado`).value = dados.uf;
            }
        })
        .catch((erro) => console.log('Erro ao buscar CEP:', erro));
}
