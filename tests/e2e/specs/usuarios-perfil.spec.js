import { test, expect, entrar, entrarNoDashboard, unico, aceitarDialogos, toast, cpfValido, ADMIN, SENHA } from './apoio.js';

const linha = (page, texto) => page.locator('#usuario-table-body tr', { hasText: texto });

async function mockCep(page) {
    await page.route('https://viacep.com.br/**', (rota) => rota.fulfill({
        json: { logradouro: 'Rua do CEP', bairro: 'Bairro do CEP', localidade: 'Mirassol', uf: 'SP' },
        headers: { 'access-control-allow-origin': '*' },
    }));
}

async function criarUsuario(page, nome, email, tipo = '2') {
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
    await page.selectOption('#usuario-tipo', tipo);
    await page.click('#usuario-form button[type="submit"]');
    await expect(toast(page)).toContainText('Usuário criado com sucesso');
    // A lista é paginada e ordenada por nome: filtra para achar o usuário em qualquer página.
    await page.goto(`usuarios?search=${encodeURIComponent(email)}`);
}

test.describe('Usuários', () => {
    test.beforeEach(async ({ page }) => {
        await mockCep(page);
        await entrarNoDashboard(page);
    });

    test('o assistente não avança com campo obrigatório vazio', async ({ page }) => {
        await page.goto('usuarios');
        await page.click('#new-usuario-btn');
        await expect(page.locator('#progressText')).toHaveText('1 / 3');
        await page.locator('.next-step:visible').click();
        await expect(page.locator('#progressText')).toHaveText('1 / 3');
        await expect(page.locator('.error-message:visible').first()).toBeVisible();
    });

    test('criar pelo assistente, e o usuário novo consegue entrar', async ({ page, context }) => {
        const email = `${unico('novo').replace(' ', '-')}@teste.com`;
        await criarUsuario(page, 'Pessoa Nova', email);
        await expect(linha(page, email)).toBeVisible();

        await context.clearCookies();
        await entrarNoDashboard(page, email);
    });

    test('visualizar mostra os dados', async ({ page }) => {
        await page.goto('usuarios');
        await linha(page, 'e2e-func@teste.com').locator('button[onclick^="viewUsuario"]').click();
        await expect(page.locator('.fixed', { hasText: 'Funcionario E2E' }).first()).toBeVisible();
    });

    test('editar sem informar senha mantém a senha e o status', async ({ page, context }) => {
        const email = `${unico('editar').replace(' ', '-')}@teste.com`;
        await criarUsuario(page, 'Pessoa Editar', email);

        await linha(page, email).locator('button[onclick^="editUsuario"]').click();
        await expect(page.locator('#usuario-modal-title')).toContainText('Editar');
        await expect(page.locator('#usuario-nome')).toHaveValue('Pessoa Editar');
        await page.fill('#usuario-nome', 'Pessoa Editada');
        await page.locator('.next-step:visible').click();
        await page.locator('.next-step:visible').click();
        await page.click('#usuario-form button[type="submit"]');

        await expect(toast(page)).toContainText('Usuário atualizado com sucesso');
        await expect(linha(page, email)).toContainText('Pessoa Editada');
        await expect(linha(page, email)).toContainText('Ativo');

        await context.clearCookies();
        await entrarNoDashboard(page, email);
    });

    test('inativar bloqueia o login, e reativar libera', async ({ page, context }) => {
        const email = `${unico('status').replace(' ', '-')}@teste.com`;
        aceitarDialogos(page);
        await criarUsuario(page, 'Pessoa Status', email);

        await linha(page, email).locator('button[onclick^="toggleUsuarioStatus"]').click();
        await expect(toast(page)).toContainText('desativado com sucesso');
        await expect(linha(page, email)).toContainText('Inativo');

        await linha(page, email).locator('button[onclick^="toggleUsuarioStatus"]').click();
        await expect(toast(page)).toContainText('ativado com sucesso');
        await expect(linha(page, email)).toContainText('Ativo');

        await context.clearCookies();
        await entrarNoDashboard(page, email);
    });

    test('não dá para excluir nem inativar a si mesmo pela tela', async ({ page }) => {
        const dialogos = aceitarDialogos(page);
        await page.goto('usuarios');
        const propria = linha(page, ADMIN);
        await expect(propria).toBeVisible();

        for (const acao of ['toggleUsuarioStatus', 'deleteUsuario']) {
            const botao = propria.locator(`button[onclick^="${acao}"]`);
            if (await botao.count()) {
                await botao.click();
                await expect.poll(() => dialogos.join(' ')).toContain('próprio usuário');
            }
        }
        await page.goto('usuarios');
        await expect(linha(page, ADMIN)).toContainText('Ativo');
    });

    test('excluir tira da lista e impede o login', async ({ page, context }) => {
        const email = `${unico('excluir').replace(' ', '-')}@teste.com`;
        aceitarDialogos(page);
        await criarUsuario(page, 'Pessoa Excluir', email);

        await linha(page, email).locator('button[onclick^="deleteUsuario"]').click();
        await expect(toast(page)).toContainText('Usuário excluído com sucesso');
        await expect(linha(page, email)).toHaveCount(0);

        await context.clearCookies();
        await entrar(page, email, SENHA);
        await expect(page.locator('body')).toContainText('Email ou senha incorretos');
    });

    test('e-mail duplicado mostra o motivo', async ({ page }) => {
        await page.goto('usuarios');
        await page.click('#new-usuario-btn');
        await page.fill('#usuario-nome', 'Pessoa Duplicada');
        await page.fill('#usuario-email', ADMIN);
        await page.fill('#usuario-cpf', cpfValido());
        await page.fill('#usuario-rg', `RG${Date.now().toString(36)}d`);
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
        await expect(toast(page)).toContainText('Este email já está cadastrado');
    });
});

test.describe('Perfil', () => {
    const ABA = (nome) => `button[onclick="showTab('${nome}')"]`;

    test('mostra os dados do próprio usuário', async ({ page }) => {
        await entrarNoDashboard(page, 'e2e-perfil@teste.com');
        await page.goto('usuarios/perfil');
        await expect(page.locator('#nome')).toHaveValue('Perfil E2E');
        await expect(page.locator('#email-display')).toHaveValue('e2e-perfil@teste.com');
        await expect(page.locator('#cpf-display')).toHaveValue('862.883.667-57');
    });

    test('salvar os dados pessoais não apaga o endereço', async ({ page }) => {
        await entrarNoDashboard(page, 'e2e-perfil@teste.com');
        await page.goto('usuarios/perfil');
        await page.fill('#nome', 'Perfil Atualizado');
        await page.click('#tab-dados button[type="submit"]');
        await expect(toast(page)).toContainText('Perfil atualizado com sucesso');
        await expect(page.locator('#nome')).toHaveValue('Perfil Atualizado');

        await page.click(ABA('endereco'));
        await expect(page.locator('#endereco')).toHaveValue('Rua Original');
        await expect(page.locator('#bairro')).toHaveValue('Bairro Original');
        await expect(page.locator('#numero')).toHaveValue('44');
    });

    test('o CEP preenche o endereço, e salvar o endereço não apaga o nome', async ({ page }) => {
        await mockCep(page);
        await entrarNoDashboard(page, 'e2e-perfil@teste.com');
        await page.goto('usuarios/perfil');
        const nome = await page.locator('#nome').inputValue();

        await page.click(ABA('endereco'));
        await page.fill('#cep', '15130067');
        await page.locator('#cep').blur();
        await expect(page.locator('#endereco')).toHaveValue('Rua do CEP');
        await page.fill('#numero', '99');
        await page.click('#tab-endereco button[type="submit"]');

        await expect(toast(page)).toContainText('Perfil atualizado com sucesso');
        await expect(page.locator('#nome')).toHaveValue(nome);
        await page.click(ABA('endereco'));
        await expect(page.locator('#endereco')).toHaveValue('Rua do CEP');
        await expect(page.locator('#numero')).toHaveValue('99');
    });

    test('trocar a senha: recusa a atual errada e aceita a certa', async ({ page, context }) => {
        const nova = 'NovaSenha@123';
        await entrarNoDashboard(page, 'e2e-senha@teste.com');
        await page.goto('usuarios/perfil');

        await page.click(ABA('senha'));
        await page.fill('#senha_atual', 'errada12345');
        await page.fill('#nova_senha', nova);
        await page.fill('#confirmar_senha', nova);
        await page.click('#tab-senha button[type="submit"]');
        await expect(toast(page)).toContainText('Senha atual incorreta');

        await page.click(ABA('senha'));
        await page.fill('#senha_atual', SENHA);
        await page.fill('#nova_senha', nova);
        await page.fill('#confirmar_senha', nova);
        await page.click('#tab-senha button[type="submit"]');
        await expect(toast(page)).toContainText('Senha alterada com sucesso');

        await context.clearCookies();
        await entrar(page, 'e2e-senha@teste.com', SENHA);
        await expect(page.locator('body')).toContainText('Email ou senha incorretos');
        await entrarNoDashboard(page, 'e2e-senha@teste.com', nova);
    });
});
