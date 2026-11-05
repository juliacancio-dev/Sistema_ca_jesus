<?php

namespace App\Features\Usuarios;

// As mesmas regras no cadastro feito pelo admin e no perfil editado pelo próprio usuário.
final class ValidacaoDeUsuario
{
    public static function dadosPessoais(string $nome, string $telefone): array
    {
        $erros = [];
        if (mb_strlen($nome) < 3 || mb_strlen($nome) > 100) {
            $erros[] = 'Nome é obrigatório e deve ter entre 3 e 100 caracteres.';
        }
        $digitos = preg_replace('/[^0-9]/', '', $telefone);
        if (strlen($digitos) < 10 || strlen($digitos) > 11 || mb_strlen($telefone) > 15) {
            $erros[] = 'Telefone inválido. Deve ter 10 ou 11 dígitos.';
        }
        return $erros;
    }

    public static function endereco(array $dados): array
    {
        $erros = [];
        if (strlen($dados['cep']) !== 8) {
            $erros[] = 'CEP inválido. Deve conter 8 dígitos.';
        }
        if ($dados['endereco'] === '' || mb_strlen($dados['endereco']) > 200) {
            $erros[] = 'Endereço é obrigatório e deve ter no máximo 200 caracteres.';
        }
        if ($dados['numero'] === '' || mb_strlen($dados['numero']) > 10) {
            $erros[] = 'Número é obrigatório e deve ter no máximo 10 caracteres.';
        }
        if (mb_strlen($dados['complemento']) > 255) {
            $erros[] = 'Complemento deve ter no máximo 255 caracteres.';
        }
        if ($dados['bairro'] === '' || mb_strlen($dados['bairro']) > 100) {
            $erros[] = 'Bairro é obrigatório e deve ter no máximo 100 caracteres.';
        }
        if ($dados['cidade'] === '' || mb_strlen($dados['cidade']) > 100) {
            $erros[] = 'Cidade é obrigatória e deve ter no máximo 100 caracteres.';
        }
        if (strlen($dados['estado']) !== 2) {
            $erros[] = 'Estado é obrigatório e deve ter 2 caracteres.';
        }
        return $erros;
    }
}
