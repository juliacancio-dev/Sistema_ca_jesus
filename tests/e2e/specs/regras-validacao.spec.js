import { test, expect, entrarNoDashboard, criarProduto, toast, unico, ADMIN } from './apoio.js';

const JSON_ACEITO = { Accept: 'application/json' };

async function cabecalhos(page) {
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    return { ...JSON_ACEITO, 'X-CSRF-Token': token };
}

// A tabela de produtos só traz o id no onclick do botão de editar.
async function idDoProduto(page, marca) {
    const onclick = await page.locator('tr', { hasText: marca }).locator('[onclick*="editProduto"]').first().getAttribute('onclick');
    return onclick.match(/\d+/)[0];
}

async function saldoDoProduto(page, id) {
    const resposta = await page.request.get(`produtos/buscar?id=${id}`, { headers: JSON_ACEITO });
    return Number((await resposta.json()).dados.estoque);
}

test.describe('Regras de acesso e validação no servidor', () => {
    test.beforeEach(async ({ page }) => {
        await entrarNoDashboard(page);
    });

    test('o admin não muda o próprio tipo nem o próprio status', async ({ page }) => {
        await page.goto(`usuarios?search=${ADMIN}`);
        const onclick = await page.locator('tr', { hasText: ADMIN }).locator('[onclick*="editUsuario"]').first().getAttribute('onclick');
        const id = onclick.match(/\d+/)[0];
        const headers = await cabecalhos(page);
        const { dados } = await (await page.request.get(`usuarios/buscar/${id}`, { headers: JSON_ACEITO })).json();
        const preenchidos = Object.entries(dados).filter(([, valor]) => valor !== null).map(([campo, valor]) => [campo, String(valor)]);
        const form = { ...Object.fromEntries(preenchidos), senha: '', tipo: '2', ativo: '1' };

        await page.request.post('usuarios/salvar', { headers: { 'X-CSRF-Token': headers['X-CSRF-Token'] }, form, maxRedirects: 0 });
        delete form.ativo;
        form.tipo = '1';
        await page.request.post('usuarios/salvar', { headers: { 'X-CSRF-Token': headers['X-CSRF-Token'] }, form, maxRedirects: 0 });

        await page.goto('usuarios');
        await expect(page).toHaveURL(/\/usuarios$/);
        await expect(toast(page)).toContainText('próprio usuário');
        const depois = await (await page.request.get(`usuarios/buscar/${id}`, { headers: JSON_ACEITO })).json();
        expect(Number(depois.dados.tipo)).toBe(1);
        expect(Number(depois.dados.ativo)).toBe(1);
    });

    test('status de usuário e de fornecedor só aceita 0 ou 1', async ({ page }) => {
        const headers = await cabecalhos(page);
        for (const rota of ['usuarios/toggle-status/2', 'fornecedores/toggle-status/1']) {
            for (const data of ['{"status":2}', 'nao-e-json', '{}']) {
                const resposta = await page.request.post(rota, { headers: { ...headers, 'Content-Type': 'application/json' }, data });
                expect(resposta.status(), `${rota} ${data}`).toBe(422);
            }
        }
    });

    test('id inexistente responde 404, e não sucesso', async ({ page }) => {
        const headers = await cabecalhos(page);
        for (const rota of ['fornecedores/excluir/999999', 'usuarios/excluir/999999']) {
            expect((await page.request.post(rota, { headers })).status(), rota).toBe(404);
        }
        const status = { headers: { ...headers, 'Content-Type': 'application/json' }, data: '{"status":1}' };
        expect((await page.request.post('usuarios/toggle-status/999999', status)).status()).toBe(404);
        expect((await page.request.post('fornecedores/toggle-status/999999', status)).status()).toBe(404);
        expect((await page.request.delete('produtos/excluir/999999', { headers })).status()).toBe(404);
    });

    test('atualizar produto inexistente avisa que não encontrou', async ({ page }) => {
        const headers = await cabecalhos(page);
        await page.request.post('produtos/atualizar/999999', {
            headers: { 'X-CSRF-Token': headers['X-CSRF-Token'] },
            form: { marca: unico('Fantasma'), preco_custo: '1', preco_venda: '2', fornecedor_id: '1' },
        });
        await page.goto('produtos');
        await expect(toast(page)).toContainText('Produto não encontrado');
    });

    test('id com letras não chega ao banco', async ({ page }) => {
        const marca = unico('Id Letras');
        await criarProduto(page, marca);
        const id = await idDoProduto(page, marca);
        const headers = await cabecalhos(page);

        const resposta = await page.request.delete(`produtos/excluir/${id}abc`, { headers });

        expect(resposta.status()).toBe(404);
        await page.goto('produtos');
        await expect(page.locator('tr', { hasText: marca })).toHaveCount(1);
    });

    test('movimentação não converte quantidade nem preço em silêncio', async ({ page }) => {
        const marca = unico('Sem Coercao');
        await criarProduto(page, marca);
        const id = await idDoProduto(page, marca);
        const headers = await cabecalhos(page);
        const valido = { produto: id, tipo: 'entrada', quantidade: '5', preco_custo: '10', preco_venda: '20' };

        for (const troca of [{ quantidade: '2abc' }, { quantidade: '1e3' }, { quantidade: '1.9' }, { preco_custo: '12,50' }, { preco_venda: 'abc' }, { preco_custo: '' }]) {
            const resposta = await page.request.post('movimentacoes/registrar', { headers, form: { ...valido, ...troca } });
            expect(resposta.status(), JSON.stringify(troca)).toBe(422);
        }

        expect(await saldoDoProduto(page, id)).toBe(0);
        expect((await page.request.post('movimentacoes/registrar', { headers, form: valido })).status()).toBe(200);
        expect(await saldoDoProduto(page, id)).toBe(5);
    });

    test('marca em branco ou sem preço não cadastra produto', async ({ page }) => {
        const headers = await cabecalhos(page);
        const semPreco = unico('Sem Preco');
        for (const form of [
            { marca: '   ', preco_custo: '1', preco_venda: '2', fornecedor_id: '1' },
            { marca: semPreco, fornecedor_id: '1' },
            { marca: semPreco, preco_custo: '12,50', preco_venda: '20', fornecedor_id: '1' },
        ]) {
            await page.request.post('produtos/salvar', { headers: { 'X-CSRF-Token': headers['X-CSRF-Token'] }, form });
            await page.goto('produtos');
            await expect(toast(page)).toContainText('devem ser válidos');
        }
        await expect(page.locator('tr', { hasText: semPreco })).toHaveCount(0);
    });

    test('marca é gravada sem espaços nas pontas', async ({ page }) => {
        const marca = unico('Com Espaco');
        const headers = await cabecalhos(page);
        await page.request.post('produtos/salvar', {
            headers: { 'X-CSRF-Token': headers['X-CSRF-Token'] },
            form: { marca: `  ${marca}  `, preco_custo: '1.50', preco_venda: '2', fornecedor_id: '1' },
        });
        await page.goto('produtos');
        await expect(toast(page)).toContainText('cadastrado com sucesso');
        await expect(page.locator('tr', { hasText: marca })).toHaveCount(1);
    });

    test('perfil usa a mesma validação do cadastro', async ({ page }) => {
        const headers = await cabecalhos(page);
        const token = { 'X-CSRF-Token': headers['X-CSRF-Token'] };
        await page.goto('usuarios/perfil');
        const cep = await page.inputValue('#cep');

        await page.request.post('usuarios/atualizarEndereco', {
            headers: token,
            form: { endereco: '', cidade: '', estado: '', cep: '', numero: '', complemento: '', bairro: '' },
        });
        await page.goto('usuarios/perfil');
        await expect(toast(page)).toContainText('CEP inválido');
        await expect(page.locator('#cep')).toHaveValue(cep);

        await page.request.post('usuarios/atualizarDados', { headers: token, form: { nome: 'Pessoa Valida', telefone: '123' } });
        await page.goto('usuarios/perfil');
        await expect(toast(page)).toContainText('Telefone inválido');
    });

    test('campo enviado como lista é recusado, sem erro 500', async ({ page }) => {
        const headers = await cabecalhos(page);
        const token = { 'X-CSRF-Token': headers['X-CSRF-Token'], 'Content-Type': 'application/x-www-form-urlencoded' };
        for (const [rota, data] of [
            ['produtos/salvar', 'marca[]=x&preco_custo=1&preco_venda=2&fornecedor_id=1'],
            ['produtos/salvar', 'marca=x&preco_custo[]=1&preco_venda=2&fornecedor_id=1'],
            ['movimentacoes/registrar', 'produto=1&tipo=entrada&quantidade[]=50&preco_custo=1&preco_venda=2'],
            ['usuarios/salvar', 'nome[]=x'],
            ['usuarios/atualizarDados', 'nome[]=x&telefone[]=y'],
            ['usuarios/alterarSenha', 'senha_atual[]=x&nova_senha[]=y&confirmar_senha[]=y'],
            ['fornecedores/salvar', 'id[]=1&nome[]=x'],
        ]) {
            const resposta = await page.request.post(rota, { headers: token, data, maxRedirects: 0 });
            expect(resposta.status(), `${rota} ${data}`).toBeLessThan(500);
        }
    });
});
