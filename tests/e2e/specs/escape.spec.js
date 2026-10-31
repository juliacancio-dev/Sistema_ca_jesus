import { test, expect, entrarNoDashboard, unico, aceitarDialogos, toast, cpfValido, cnpjValido, SENHA } from './apoio.js';

// O dado é gravado como foi digitado e só é escapado na saída.
const COM_ACENTO = () => `D'Ávila & Filhos ${unico('x')}`;
const COM_HTML = () => `<b>Negrito</b> ${unico('x')}`;
const COM_SCRIPT = () => `<img src=x onerror="window.__xss=1"> ${unico('x')}`;
const QUEBRA_ONCLICK = () => `x');window.__xss=1;// ${unico('x')}`;

async function mockCep(page) {
    await page.route('https://viacep.com.br/**', (rota) => rota.fulfill({
        json: { logradouro: 'Rua do CEP', bairro: 'Bairro do CEP', localidade: 'Mirassol', uf: 'SP' },
        headers: { 'access-control-allow-origin': '*' },
    }));
}

async function criarFornecedor(page, nome) {
    const email = `${Date.now().toString(36)}${Math.floor(Math.random() * 1000)}@teste.com`;
    await page.goto('fornecedores');
    await page.click('#new-fornecedor-btn');
    await page.fill('#fornecedor-nome', nome);
    await page.fill('#fornecedor-cnpj', cnpjValido());
    await page.fill('#fornecedor-email', email);
    await page.fill('#fornecedor-telefone', '1733334444');
    await page.fill('#fornecedor-cep', '15130971');
    await page.locator('#fornecedor-cep').blur();
    await expect(page.locator('#fornecedor-endereco')).toHaveValue('Rua do CEP');
    await page.fill('#fornecedor-numero', '10');
    await page.click('#fornecedor-form button[type="submit"]');
    await expect(toast(page)).toContainText('Fornecedor criado com sucesso');
    await page.goto(`fornecedores?search=${encodeURIComponent(email)}`);
    return page.locator('#fornecedor-table-body tr.fornecedor-row', { hasText: email });
}

async function criarUsuario(page, nome) {
    const email = `${Date.now().toString(36)}${Math.floor(Math.random() * 1000)}@teste.com`;
    await page.goto('usuarios');
    await page.click('#new-usuario-btn');
    await page.fill('#usuario-nome', nome);
    await page.fill('#usuario-email', email);
    await page.fill('#usuario-cpf', cpfValido());
    await page.fill('#usuario-rg', `RG${Date.now().toString(36)}`);
    await page.fill('#usuario-telefone', '17999998888');
    await page.selectOption('#usuario-sexo', 'Outro');
    await page.locator('.next-step:visible').click();
    await page.fill('#usuario-cep', '15130067');
    await page.locator('#usuario-cep').blur();
    await expect(page.locator('#usuario-endereco')).toHaveValue('Rua do CEP');
    await page.fill('#usuario-numero', '10');
    await page.locator('.next-step:visible').click();
    await page.fill('#usuario-senha', SENHA);
    await page.selectOption('#usuario-tipo', '2');
    await page.click('#usuario-form button[type="submit"]');
    await expect(toast(page)).toContainText('Usuário criado com sucesso');
    await page.goto(`usuarios?search=${encodeURIComponent(email)}`);
    return { email, linha: page.locator('#usuario-table-body tr.usuario-row', { hasText: email }) };
}

const semXss = async (page) => expect(await page.evaluate(() => window.__xss)).toBeUndefined();
const detalhe = (page, texto) => page.locator('.fixed', { hasText: texto }).last();

test.describe('Escape: fornecedores', () => {
    test.beforeEach(async ({ page }) => {
        await mockCep(page);
        await entrarNoDashboard(page);
    });

    test('nome com acento, aspas e & aparece igual em todo lugar', async ({ page }) => {
        const nome = COM_ACENTO();
        const linha = await criarFornecedor(page, nome);
        await expect(linha).toContainText(nome);
        await expect(linha.locator('.rounded-full').first()).toHaveText('D');

        await linha.locator('button[onclick^="viewFornecedor"]').click();
        await expect(detalhe(page, nome)).toBeVisible();

        await page.reload();
        await linha.locator('button[onclick^="editFornecedor"]').click();
        await expect(page.locator('#fornecedor-nome')).toHaveValue(nome);
    });

    test('editar duas vezes não acumula escape', async ({ page }) => {
        const nome = COM_ACENTO();
        const linha = await criarFornecedor(page, nome);
        const url = page.url();
        for (let i = 0; i < 2; i++) {
            await linha.locator('button[onclick^="editFornecedor"]').click();
            await page.click('#fornecedor-form button[type="submit"]');
            await expect(toast(page)).toContainText('Fornecedor atualizado com sucesso');
            await page.goto(url);
        }
        await expect(linha).toContainText(nome);
    });

    test('a inicial com acento sai inteira', async ({ page }) => {
        const nome = `Água Limpa ${unico('x')}`;
        const linha = await criarFornecedor(page, nome);
        await expect(linha.locator('.rounded-full').first()).toHaveText('Á');
    });

    test('HTML no nome é texto na lista e no modal', async ({ page }) => {
        const nome = COM_HTML();
        const linha = await criarFornecedor(page, nome);
        await expect(linha).toContainText(nome);
        await linha.locator('button[onclick^="viewFornecedor"]').click();
        const modal = detalhe(page, nome);
        await expect(modal).toBeVisible();
        await expect(modal.locator('b')).toHaveCount(0);
    });

    test('script no nome não executa na lista nem no modal', async ({ page }) => {
        const linha = await criarFornecedor(page, COM_SCRIPT());
        await linha.locator('button[onclick^="viewFornecedor"]').click();
        await expect(page.locator('.fixed', { hasText: 'onerror' }).last()).toBeVisible();
        await page.waitForTimeout(300);
        await semXss(page);
    });

    test('o botão de excluir mostra o nome certo e não executa o nome', async ({ page }) => {
        for (const nome of [COM_ACENTO(), QUEBRA_ONCLICK()]) {
            const linha = await criarFornecedor(page, nome);
            const dialogos = [];
            const trata = async (d) => { dialogos.push(d.message()); await d.dismiss(); };
            page.on('dialog', trata);
            await linha.locator('button[onclick^="deleteFornecedor"]').click();
            await expect.poll(() => dialogos.length).toBe(1);
            page.off('dialog', trata);
            expect(dialogos[0]).toContain(nome);
            await semXss(page);
        }
    });

    test('o endereço também fica como foi digitado', async ({ page }) => {
        const nome = unico('Endereco');
        const linha = await criarFornecedor(page, nome);
        await linha.locator('button[onclick^="editFornecedor"]').click();
        await page.fill('#fornecedor-endereco', "Rua D'Ávila & <i>Cia</i>");
        await page.click('#fornecedor-form button[type="submit"]');
        await expect(toast(page)).toContainText('Fornecedor atualizado com sucesso');
        await page.reload();
        await linha.locator('button[onclick^="viewFornecedor"]').click();
        const modal = detalhe(page, "Rua D'Ávila & <i>Cia</i>");
        await expect(modal).toBeVisible();
        await expect(modal.locator('i', { hasText: 'Cia' })).toHaveCount(0);
    });
});

test.describe('Escape: usuários e perfil', () => {
    test.beforeEach(async ({ page }) => {
        await mockCep(page);
        await entrarNoDashboard(page);
    });

    test('nome com acento, aspas e & aparece igual em todo lugar', async ({ page }) => {
        const nome = COM_ACENTO();
        const { linha } = await criarUsuario(page, nome);
        await expect(linha).toContainText(nome);

        await linha.locator('button[onclick^="viewUsuario"]').click();
        await expect(detalhe(page, nome)).toBeVisible();

        await page.reload();
        await linha.locator('button[onclick^="editUsuario"]').click();
        await expect(page.locator('#usuario-nome')).toHaveValue(nome);
    });

    test('a busca da API devolve o dado como foi digitado', async ({ page }) => {
        const nome = COM_ACENTO();
        const { linha } = await criarUsuario(page, nome);
        const onclick = await linha.locator('button[onclick^="viewUsuario"]').getAttribute('onclick');
        const id = onclick.match(/\d+/)[0];
        const resposta = await page.request.get(`usuarios/buscar/${id}`, { headers: { Accept: 'application/json' } });
        expect((await resposta.json()).dados.nome).toBe(nome);
    });

    test('HTML e script no nome são texto no modal', async ({ page }) => {
        const nome = COM_HTML();
        const { linha } = await criarUsuario(page, nome);
        await linha.locator('button[onclick^="viewUsuario"]').click();
        const modal = detalhe(page, nome);
        await expect(modal).toBeVisible();
        await expect(modal.locator('b')).toHaveCount(0);

        const script = await criarUsuario(page, COM_SCRIPT());
        await script.linha.locator('button[onclick^="viewUsuario"]').click();
        await expect(page.locator('.fixed', { hasText: 'onerror' }).last()).toBeVisible();
        await page.waitForTimeout(300);
        await semXss(page);
    });

    test('o botão de excluir mostra o nome certo e não executa o nome', async ({ page }) => {
        for (const nome of [COM_ACENTO(), QUEBRA_ONCLICK()]) {
            const { linha } = await criarUsuario(page, nome);
            const dialogos = [];
            const trata = async (d) => { dialogos.push(d.message()); await d.dismiss(); };
            page.on('dialog', trata);
            await linha.locator('button[onclick^="deleteUsuario"]').click();
            await expect.poll(() => dialogos.length).toBe(1);
            page.off('dialog', trata);
            expect(dialogos[0]).toContain(nome);
            await semXss(page);
        }
    });

    test('nome salvo no perfil aparece igual, também para o administrador', async ({ page, context }) => {
        const nome = QUEBRA_ONCLICK();
        const { email, linha } = await criarUsuario(page, 'Pessoa Escape');
        const lista = page.url();

        await context.clearCookies();
        await entrarNoDashboard(page, email);
        await page.goto('usuarios/perfil');
        await page.fill('#nome', nome);
        await page.click('#tab-dados button[type="submit"]');
        await expect(toast(page)).toContainText('Perfil atualizado com sucesso');
        await expect(page.locator('#nome')).toHaveValue(nome);
        await expect(page.locator('#user-menu-btn')).toContainText(nome);
        await page.click('#tab-dados button[type="submit"]');
        await expect(toast(page)).toContainText('Perfil atualizado com sucesso');
        await expect(page.locator('#nome')).toHaveValue(nome);

        await context.clearCookies();
        await entrarNoDashboard(page);
        await page.goto(lista);
        await expect(linha).toContainText(nome);
        aceitarDialogos(page);
        await semXss(page);
    });
});
