import { test, expect, entrar, entrarNoDashboard, FUNCIONARIO, SENHA, toast } from './apoio.js';

test.describe('Autenticação', () => {
    test('senha errada mostra mensagem e continua no login', async ({ page }) => {
        await entrar(page, 'e2e-admin@teste.com', 'errada12345');
        await expect(page).toHaveURL(/\/login$/);
        await expect(page.locator('body')).toContainText('Email ou senha incorretos');
    });

    test('e-mail inexistente mostra a mesma mensagem', async ({ page }) => {
        await entrar(page, 'ninguem@teste.com', SENHA);
        await expect(page.locator('body')).toContainText('Email ou senha incorretos');
    });

    test('usuário inativo com a senha certa recebe a mensagem específica', async ({ page }) => {
        await entrar(page, 'e2e-inativo@teste.com', SENHA);
        await expect(page).toHaveURL(/\/login$/);
        await expect(page.locator('body')).toContainText('inativo');
    });

    test('usuário inativo com a senha errada não descobre que a conta existe', async ({ page }) => {
        await entrar(page, 'e2e-inativo@teste.com', 'errada12345');
        await expect(page.locator('body')).toContainText('Email ou senha incorretos');
        await expect(page.locator('body')).not.toContainText('inativo');
    });

    test('login certo entra no dashboard, e logout volta ao login', async ({ page }) => {
        await entrarNoDashboard(page);
        await page.click('#user-menu-btn');
        await page.click('#user-menu a[href$="/logout"]');
        await expect(page).toHaveURL(/\/login$/);

        await page.goto('dashboard');
        await expect(page).toHaveURL(/\/login$/);
    });

    test('depois do login volta à página que foi pedida', async ({ page }) => {
        await page.goto('produtos');
        await expect(page).toHaveURL(/\/login$/);
        await page.fill('input[name="email"]', 'e2e-admin@teste.com');
        await page.fill('input[name="senha"]', SENHA);
        await page.click('button[type="submit"]');
        await expect(page).toHaveURL(/\/produtos$/);
    });

    test('quem já está logado e abre o login vai ao dashboard', async ({ page }) => {
        await entrarNoDashboard(page);
        await page.goto('login');
        await expect(page).toHaveURL(/\/dashboard$/);
    });

    test('funcionário não vê nem abre as áreas de administrador', async ({ page }) => {
        await entrarNoDashboard(page, FUNCIONARIO);
        await expect(page.locator('#sidebar a[href$="/usuarios"]')).toHaveCount(0);
        await expect(page.locator('#sidebar a[href$="/fornecedores"]')).toHaveCount(0);

        for (const rota of ['usuarios', 'fornecedores']) {
            await page.goto(rota);
            await expect(page).toHaveURL(/\/dashboard$/);
            await expect(toast(page)).toContainText('Acesso restrito');
        }
    });

    test('funcionário usa produtos, movimentações e relatórios', async ({ page }) => {
        await entrarNoDashboard(page, FUNCIONARIO);
        for (const rota of ['produtos', 'movimentacoes', 'relatorios', 'usuarios/perfil']) {
            await page.goto(rota);
            await expect(page).toHaveURL(new RegExp(`/${rota}$`));
        }
    });
});
