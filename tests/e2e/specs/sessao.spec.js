import { test as base } from './apoio.js';
import { expect, entrarNoDashboard, aceitarDialogos, toast, cpfValido, ADMIN, SENHA } from './apoio.js';

// Duas sessões reais: "page" é o administrador, e "vitima" já estava logada quando ele age.
const test = base.extend({
    novaSessao: async ({ browser }, use) => {
        const contextos = [];
        await use(async (email, senha = SENHA) => {
            const contexto = await browser.newContext({ baseURL: 'http://localhost:8088/sistema_ca_jesus/public/' });
            contextos.push(contexto);
            const pagina = await contexto.newPage();
            await entrarNoDashboard(pagina, email, senha);
            return pagina;
        });
        await Promise.all(contextos.map((c) => c.close()));
    },
});

async function criarUsuario(page, tipo = '2', nome = 'Pessoa Sessao') {
    const email = `sessao${Date.now().toString(36)}${Math.floor(Math.random() * 1000)}@teste.com`;
    await page.route('https://viacep.com.br/**', (rota) => rota.fulfill({
        json: { logradouro: 'Rua do CEP', bairro: 'Bairro do CEP', localidade: 'Mirassol', uf: 'SP' },
        headers: { 'access-control-allow-origin': '*' },
    }));
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
    return email;
}

const linhaDo = (page, email) => page.locator('#usuario-table-body tr.usuario-row', { hasText: email });
const abrirLista = (page, email) => page.goto(`usuarios?search=${encodeURIComponent(email)}`);

async function editarUsuario(page, email, altera) {
    await abrirLista(page, email);
    await linhaDo(page, email).locator('button[onclick^="editUsuario"]').click();
    await expect(page.locator('#usuario-modal-title')).toContainText('Editar');
    await expect(page.locator('#usuario-email')).toHaveValue(email);
    await altera();
    await page.click('#usuario-form button[type="submit"]');
    await expect(toast(page)).toContainText('Usuário atualizado com sucesso');
}

async function irParaUltimoPasso(page) {
    await page.locator('.next-step:visible').click();
    await page.locator('.next-step:visible').click();
}

async function esperarExpulsao(vitima) {
    await vitima.goto('produtos');
    await expect(vitima).toHaveURL(/\/login$/);
    await expect(vitima.locator('body')).toContainText('Faça login novamente');
}

test.describe('Revogação de acesso', () => {
    test.beforeEach(async ({ page }) => {
        aceitarDialogos(page);
        await entrarNoDashboard(page);
    });

    test('desativar encerra a sessão aberta, na tela e na API', async ({ page, novaSessao }) => {
        const email = await criarUsuario(page);
        const vitima = await novaSessao(email);
        const outraAba = await vitima.context().newPage();
        await outraAba.goto('produtos');

        await abrirLista(page, email);
        await linhaDo(page, email).locator('button[onclick^="toggleUsuarioStatus"]').click();
        await expect(toast(page)).toContainText('desativado com sucesso');

        const api = await vitima.request.get('api/estoque/atual', { headers: { Accept: 'application/json' } });
        expect(api.status()).toBe(401);
        await esperarExpulsao(vitima);
    });

    test('uma ação por fetch da sessão desativada leva ao login e não grava', async ({ page, novaSessao }) => {
        const email = await criarUsuario(page);
        const vitima = await novaSessao(email);
        aceitarDialogos(vitima);
        await vitima.goto('produtos');
        const marca = await vitima.locator('#produtos-table-body tr').first().locator('.font-semibold, .font-medium').first().innerText();

        await abrirLista(page, email);
        await linhaDo(page, email).locator('button[onclick^="toggleUsuarioStatus"]').click();
        await expect(toast(page)).toContainText('desativado com sucesso');

        await vitima.locator('#produtos-table-body tr').first().locator('button[onclick^="deleteProduto"]').click();
        await expect(vitima).toHaveURL(/\/login$/);

        await page.goto('produtos');
        await expect(page.locator('#produtos-table-body')).toContainText(marca);
    });

    test('excluir encerra a sessão aberta', async ({ page, novaSessao }) => {
        const email = await criarUsuario(page);
        const vitima = await novaSessao(email);

        await abrirLista(page, email);
        await linhaDo(page, email).locator('button[onclick^="deleteUsuario"]').click();
        await expect(toast(page)).toContainText('Usuário excluído com sucesso');

        await esperarExpulsao(vitima);
    });

    test('rebaixar tira o acesso de administrador na hora, e ele não se promove de volta', async ({ page, novaSessao }) => {
        const email = await criarUsuario(page, '1');
        const vitima = await novaSessao(email);
        await vitima.goto('usuarios');
        await expect(vitima).toHaveURL(/\/usuarios$/);
        const token = await vitima.locator('meta[name="csrf-token"]').getAttribute('content');
        const id = (await linhaDoId(page, email));

        await editarUsuario(page, email, async () => {
            await irParaUltimoPasso(page);
            await page.selectOption('#usuario-tipo', '2');
        });

        await vitima.goto('usuarios');
        await expect(vitima).toHaveURL(/\/dashboard$/);
        await expect(toast(vitima)).toContainText('Acesso restrito');

        const buscar = await vitima.request.get(`usuarios/buscar/${id}`, { headers: { Accept: 'application/json' } });
        expect(buscar.status()).toBe(403);

        const promover = await vitima.request.post('usuarios/salvar', {
            headers: { Accept: 'application/json', 'X-CSRF-Token': token },
            form: { id, tipo: '1', nome: 'Pessoa Sessao', email },
        });
        expect(promover.status()).toBe(403);
    });

    test('promover vale sem novo login', async ({ page, novaSessao }) => {
        const email = await criarUsuario(page, '2');
        const vitima = await novaSessao(email);
        await vitima.goto('usuarios');
        await expect(vitima).toHaveURL(/\/dashboard$/);

        await editarUsuario(page, email, async () => {
            await irParaUltimoPasso(page);
            await page.selectOption('#usuario-tipo', '1');
        });

        await vitima.goto('usuarios');
        await expect(vitima).toHaveURL(/\/usuarios$/);
    });

    test('o nome alterado pelo administrador aparece na sessão aberta', async ({ page, novaSessao }) => {
        const email = await criarUsuario(page);
        const vitima = await novaSessao(email);

        await editarUsuario(page, email, async () => {
            await page.fill('#usuario-nome', 'Nome Novo Sessao');
            await irParaUltimoPasso(page);
        });

        await vitima.goto('produtos');
        await expect(vitima.locator('#user-menu-btn')).toContainText('Nome Novo Sessao');
    });
});

test.describe('Troca de senha', () => {
    const ABA = `button[onclick="showTab('senha')"]`;

    async function trocarPeloPerfil(pagina, atual, nova) {
        await pagina.goto('usuarios/perfil');
        await pagina.click(ABA);
        await pagina.fill('#senha_atual', atual);
        await pagina.fill('#nova_senha', nova);
        await pagina.fill('#confirmar_senha', nova);
        await pagina.click('#tab-senha button[type="submit"]');
        await expect(toast(pagina)).toContainText('Senha alterada com sucesso');
    }

    test.beforeEach(async ({ page }) => entrarNoDashboard(page));

    test('trocar a própria senha encerra as outras sessões e mantém a atual', async ({ page, novaSessao }) => {
        const email = await criarUsuario(page);
        const quemTroca = await novaSessao(email);
        const outraSessao = await novaSessao(email);

        await trocarPeloPerfil(quemTroca, SENHA, 'NovaSenha@123');

        await quemTroca.goto('produtos');
        await expect(quemTroca).toHaveURL(/\/produtos$/);
        await esperarExpulsao(outraSessao);
    });

    test('depois de trocar, a sessão atual continua salvando (token e sessão válidos)', async ({ page, novaSessao }) => {
        const email = await criarUsuario(page);
        const quemTroca = await novaSessao(email);

        await trocarPeloPerfil(quemTroca, SENHA, 'NovaSenha@123');
        await trocarPeloPerfil(quemTroca, 'NovaSenha@123', 'OutraSenha@123');
    });

    test('o administrador redefinir a senha encerra a sessão da pessoa', async ({ page, novaSessao }) => {
        const email = await criarUsuario(page);
        const vitima = await novaSessao(email);

        await editarUsuario(page, email, async () => {
            await irParaUltimoPasso(page);
            await page.fill('#usuario-senha', 'RedefinidaPeloAdmin@1');
        });

        await esperarExpulsao(vitima);
        await entrarNoDashboard(vitima, email, 'RedefinidaPeloAdmin@1');
    });

    test('editar outro dado sem informar senha não derruba ninguém', async ({ page, novaSessao }) => {
        const email = await criarUsuario(page);
        const vitima = await novaSessao(email);

        await editarUsuario(page, email, async () => {
            await page.fill('#usuario-telefone', '17988887777');
            await irParaUltimoPasso(page);
        });

        await vitima.goto('produtos');
        await expect(vitima).toHaveURL(/\/produtos$/);
    });

    test('o administrador redefinir a própria senha mantém a sessão dele', async ({ page }) => {
        const email = await criarUsuario(page, '1');
        await page.context().clearCookies();
        await entrarNoDashboard(page, email);

        await editarUsuario(page, email, async () => {
            await irParaUltimoPasso(page);
            await page.fill('#usuario-senha', 'MinhaSenhaNova@1');
        });

        await page.goto('usuarios');
        await expect(page).toHaveURL(/\/usuarios$/);
    });
});

async function linhaDoId(page, email) {
    await abrirLista(page, email);
    const onclick = await linhaDo(page, email).locator('button[onclick^="viewUsuario"]').getAttribute('onclick');
    return onclick.match(/\d+/)[0];
}

test('o administrador continua logado depois de editar a si mesmo sem trocar a senha', async ({ page }) => {
    await entrarNoDashboard(page);
    await editarUsuario(page, ADMIN, async () => {
        await page.fill('#usuario-telefone', '17977776666');
        await irParaUltimoPasso(page);
    });
    await page.goto('usuarios');
    await expect(page).toHaveURL(/\/usuarios$/);
});
