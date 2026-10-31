import { test, expect, entrar, entrarNoDashboard } from './apoio.js';

// Contas do seed, usadas só aqui, para o bloqueio não atingir os outros testes.
const SENHA_SEED = '123456789';
const BLOQUEADA = 'jonathan@gmail.com';
const OUTRA = 'ana@gmail.com';
const ZERADA = 'maria@gmail.com';

async function errar(page, email, vezes) {
    for (let i = 0; i < vezes; i++) {
        await entrar(page, email, 'senha-errada-123');
        await expect(page.locator('body')).toContainText('Email ou senha incorretos');
    }
}

test.describe('Limite de tentativas de login', () => {
    test('depois de 5 erros, nem a senha certa entra; outra conta não é afetada', async ({ page, context }) => {
        await errar(page, BLOQUEADA, 5);

        await entrar(page, BLOQUEADA, SENHA_SEED);
        await expect(page).toHaveURL(/\/login$/);
        await expect(page.locator('body')).toContainText('Muitas tentativas');

        await context.clearCookies();
        await entrarNoDashboard(page, OUTRA, SENHA_SEED);
    });

    test('e-mail inexistente é tratado igual, sem revelar nada', async ({ page }) => {
        await errar(page, 'ninguem-limite@teste.com', 5);

        await entrar(page, 'ninguem-limite@teste.com', 'senha-errada-123');
        await expect(page.locator('body')).toContainText('Muitas tentativas');
    });

    test('um login certo zera a contagem da conta', async ({ page, context }) => {
        await errar(page, ZERADA, 4);
        await entrarNoDashboard(page, ZERADA, SENHA_SEED);
        await context.clearCookies();

        await errar(page, ZERADA, 4);
        await entrarNoDashboard(page, ZERADA, SENHA_SEED);
    });

    test('e-mail ou senha enviados como lista não geram erro 500', async ({ page }) => {
        await page.goto('login');
        const token = await page.locator('input[name="csrf_token"]').getAttribute('value');

        for (const corpo of ['email[]=x&senha=y', 'email=x@x.com&senha[]=y']) {
            const resposta = await page.request.post('login', {
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: `csrf_token=${token}&${corpo}`,
                maxRedirects: 0,
            });
            expect(resposta.status(), corpo).toBe(302);
        }
    });
});
