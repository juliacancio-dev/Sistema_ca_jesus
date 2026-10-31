import { test, expect, entrarNoDashboard, unico, aceitarDialogos, toast, cnpjValido, criarProduto, registrarMovimentacao, saldoNaTela } from './apoio.js';

const linha = (page, texto) => page.locator('#fornecedor-table-body tr', { hasText: texto });

async function mockCep(page) {
    await page.route('https://viacep.com.br/**', (rota) => rota.fulfill({
        json: { logradouro: 'Rua do CEP', bairro: 'Bairro do CEP', localidade: 'Mirassol', uf: 'SP' },
        headers: { 'access-control-allow-origin': '*' },
    }));
}

async function criarFornecedor(page, nome) {
    await page.goto('fornecedores');
    await page.click('#new-fornecedor-btn');
    await page.fill('#fornecedor-nome', nome);
    await page.fill('#fornecedor-cnpj', cnpjValido());
    await page.fill('#fornecedor-email', `${nome.replace(/\s/g, '-').toLowerCase()}@teste.com`);
    await page.fill('#fornecedor-telefone', '1733334444');
    await page.fill('#fornecedor-cep', '15130971');
    await page.locator('#fornecedor-cep').blur();
    await expect(page.locator('#fornecedor-endereco')).toHaveValue('Rua do CEP');
    await page.fill('#fornecedor-numero', '10');
    await page.click('#fornecedor-form button[type="submit"]');
    await expect(toast(page)).toContainText('Fornecedor criado com sucesso');
}

test.describe('Fornecedores', () => {
    test.beforeEach(async ({ page }) => {
        await mockCep(page);
        await entrarNoDashboard(page);
    });

    test('criar pelo modal', async ({ page }) => {
        const nome = unico('Fornecedor');
        await criarFornecedor(page, nome);
        await expect(linha(page, nome)).toBeVisible();
        await expect(linha(page, nome)).toContainText('Ativo');
    });

    test('formulário vazio não envia', async ({ page }) => {
        await page.goto('fornecedores');
        await page.click('#new-fornecedor-btn');
        await page.click('#fornecedor-form button[type="submit"]');
        await expect(page.locator('#fornecedor-modal')).toBeVisible();
        await expect(page.locator('.error-message:visible').first()).toBeVisible();
    });

    test('visualizar mostra os dados', async ({ page }) => {
        await page.goto('fornecedores');
        await linha(page, 'Talismã').locator('button[onclick^="viewFornecedor"]').click();
        await expect(page.locator('.fixed', { hasText: 'talisma@gmail.com' }).first()).toBeVisible();
    });

    test('editar mantém o fornecedor ativo', async ({ page }) => {
        const nome = unico('Editar');
        await criarFornecedor(page, nome);
        await linha(page, nome).locator('button[onclick^="editFornecedor"]').click();
        await expect(page.locator('#fornecedor-nome')).toHaveValue(nome);
        await page.fill('#fornecedor-nome', `${nome} novo`);
        await page.click('#fornecedor-form button[type="submit"]');
        await expect(toast(page)).toContainText('Fornecedor atualizado com sucesso');
        await expect(linha(page, `${nome} novo`)).toContainText('Ativo');
    });

    test('inativar e reativar sem produtos', async ({ page }) => {
        const nome = unico('Status');
        aceitarDialogos(page);
        await criarFornecedor(page, nome);
        await linha(page, nome).locator('button[onclick^="toggleFornecedorStatus"]').click();
        await expect(toast(page)).toContainText('desativado com sucesso');
        await expect(linha(page, nome)).toContainText('Inativo');
        await linha(page, nome).locator('button[onclick^="toggleFornecedorStatus"]').click();
        await expect(toast(page)).toContainText('ativado com sucesso');
    });

    test('inativar e excluir com produtos são recusados com o motivo', async ({ page }) => {
        const dialogos = aceitarDialogos(page);
        await page.goto('fornecedores');
        await linha(page, 'Talismã').locator('button[onclick^="toggleFornecedorStatus"]').click();
        await expect.poll(() => dialogos.join(' ')).toContain('produtos vinculados');

        dialogos.length = 0;
        await linha(page, 'Talismã').locator('button[onclick^="deleteFornecedor"]').click();
        await expect.poll(() => dialogos.join(' ')).toContain('produtos vinculados');
        await expect(linha(page, 'Talismã')).toBeVisible();
    });

    test('excluir sem produtos remove da lista', async ({ page }) => {
        const nome = unico('Excluir');
        aceitarDialogos(page);
        await criarFornecedor(page, nome);
        await linha(page, nome).locator('button[onclick^="deleteFornecedor"]').click();
        await expect(toast(page)).toContainText('Fornecedor excluído com sucesso');
        await expect(linha(page, nome)).toHaveCount(0);
    });
});

test.describe('Segurança e sessão', () => {
    test.beforeEach(async ({ page }) => entrarNoDashboard(page));

    test('duas abas abertas salvam em sequência', async ({ page, context }) => {
        const marcaA = unico('AbaA');
        const marcaB = unico('AbaB');
        const outra = await context.newPage();
        for (const aba of [page, outra]) {
            await aba.goto('produtos');
            await aba.click('#new-produto-btn');
        }
        for (const [aba, marca] of [[page, marcaA], [outra, marcaB]]) {
            await aba.fill('#produto-marca', marca);
            await aba.fill('#produto-custo', '1');
            await aba.fill('#produto-venda', '2');
            await aba.selectOption('#produto-fornecedor', '1');
            await aba.click('#produto-form button[type="submit"]');
            await expect(toast(aba)).toContainText('Produto cadastrado com sucesso');
        }
        await outra.close();
    });

    test('formulário com token adulterado volta com a mensagem e não grava', async ({ page }) => {
        const marca = unico('Token');
        await page.goto('produtos');
        await page.click('#new-produto-btn');
        await page.fill('#produto-marca', marca);
        await page.fill('#produto-custo', '1');
        await page.fill('#produto-venda', '2');
        await page.selectOption('#produto-fornecedor', '1');
        await page.evaluate(() => {
            document.querySelector('#produto-form input[name="csrf_token"]').value = 'adulterado';
        });
        await page.click('#produto-form button[type="submit"]');
        await expect(toast(page)).toContainText('Esta página expirou');
        await expect(page.locator('#produtos-table-body')).not.toContainText(marca);
    });

    test('fetch com token adulterado mostra a mensagem e não grava', async ({ page }) => {
        const marca = unico('TokenFetch');
        const dialogos = aceitarDialogos(page);
        await criarProduto(page, marca);
        await page.evaluate(() => {
            document.querySelector('meta[name="csrf-token"]').content = 'adulterado';
        });
        await page.locator('#produtos-table-body tr', { hasText: marca }).locator('button[onclick^="deleteProduto"]').click();
        await expect.poll(() => dialogos.join(' ')).toContain('Esta página expirou');
        await page.reload();
        await expect(page.locator('#produtos-table-body')).toContainText(marca);
    });

    test('sessão perdida numa ação por fetch leva ao login', async ({ page, context }) => {
        const marca = unico('Sessao');
        aceitarDialogos(page);
        await criarProduto(page, marca);
        await context.clearCookies();
        await page.locator('#produtos-table-body tr', { hasText: marca }).locator('button[onclick^="deleteProduto"]').click();
        await expect(page).toHaveURL(/\/login$/);
    });

    test('sessão perdida no formulário de movimentação leva ao login', async ({ page, context }) => {
        const marca = unico('SessaoMov');
        await criarProduto(page, marca);
        await registrarMovimentacao(page, marca, 'entrada', 2);
        await context.clearCookies();
        await page.selectOption('#produto-select', marca);
        await page.selectOption('select[name="tipo"]', 'saida');
        await page.fill('input[name="quantidade"]', '1');
        await page.click('form[onsubmit] button[type="submit"]');
        await expect(page).toHaveURL(/\/login$/);

        await entrarNoDashboard(page);
        await page.goto('movimentacoes');
        await expect(saldoNaTela(page, marca)).toContainText('2 unidade(s)');
    });

    test('arquivos internos não são servidos', async ({ request }) => {
        for (const caminho of [
            '../backend/Core/Auth.php', '../frontend/views/layout.php', '../database/schema.sql', '../database/seed.sql',
            '../config/config.php', '../vendor/autoload.php', '../tests/bootstrap.php', '../composer.json', '../README.md',
        ]) {
            const r = await request.get(caminho);
            expect([403, 404], caminho).toContain(r.status());
        }
    });
});
