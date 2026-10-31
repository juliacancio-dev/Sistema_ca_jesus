// Máscaras dos campos e formatação de valores vindos do servidor.

function soDigitos(valor) {
    return (valor || '').toString().replace(/\D/g, '');
}

function formatCpf(cpf) {
    return soDigitos(cpf).replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4');
}

function formatCnpj(cnpj) {
    return soDigitos(cnpj).replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
}

function formatTelefone(telefone) {
    const digitos = soDigitos(telefone);
    if (digitos.length === 10) {
        return digitos.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    }
    if (digitos.length === 11) {
        return digitos.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    }
    return digitos;
}

function formatCep(cep) {
    return soDigitos(cep).replace(/^(\d{5})(\d{3})$/, '$1-$2');
}

function aplicarMascara(campo, mascara) {
    if (campo) {
        campo.addEventListener('input', (e) => {
            e.target.value = mascara(soDigitos(e.target.value));
        });
    }
}

function mascaraCpf(campo) {
    aplicarMascara(campo, (valor) => valor
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d{1,2})$/, '$1-$2'));
}

function mascaraCnpj(campo) {
    aplicarMascara(campo, (valor) => valor
        .replace(/^(\d{2})(\d)/, '$1.$2')
        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1/$2')
        .replace(/(\d{4})(\d)/, '$1-$2'));
}

function mascaraTelefone(campo) {
    aplicarMascara(campo, (valor) => valor
        .replace(/(\d{2})(\d)/, '($1) $2')
        .replace(valor.length <= 10 ? /(\d{4})(\d)/ : /(\d{5})(\d)/, '$1-$2'));
}

function mascaraCep(campo) {
    aplicarMascara(campo, (valor) => valor.replace(/(\d{5})(\d)/, '$1-$2'));
}
