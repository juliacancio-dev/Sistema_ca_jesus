import { test as base, expect } from '@playwright/test';

export const SENHA = 'Senha@E2E123';
export const ADMIN = 'e2e-admin@teste.com';
export const FUNCIONARIO = 'e2e-func@teste.com';

const AVISO_PHP = /\b(Warning|Notice|Deprecated|Fatal error|Parse error)(<\/b>)?:/;

// Todo teste falha se a página tiver erro de JavaScript, aviso do PHP ou resposta 500.
export const test = base.extend({
    errosPermitidos: [[], { option: true }],
    page: async ({ page, errosPermitidos }, use) => {
        const problemas = [];
        const leituras = [];

        page.on('pageerror', (e) => problemas.push(`JS: ${e.message}`));
        page.on('console', (m) => {
            if (m.type() === 'error' && !m.text().startsWith('Failed to load resource')) {
                problemas.push(`console: ${m.text()}`);
            }
        });
        page.on('response', (r) => {
            if (!r.url().includes('localhost:8088')) return;
            if (r.status() >= 500) problemas.push(`HTTP ${r.status()}: ${r.url()}`);
            if (r.request().resourceType() === 'document' && r.status() === 200) {
                leituras.push(r.text().then((corpo) => {
                    if (AVISO_PHP.test(corpo)) problemas.push(`PHP em ${r.url()}: ${corpo.match(AVISO_PHP)[0]}`);
                }).catch(() => {}));
            }
        });

        await use(page);

        await Promise.all(leituras);
        expect(problemas.filter((p) => !errosPermitidos.some((e) => p.includes(e)))).toEqual([]);
    },
});

export { expect };

let contador = 0;
export const unico = (prefixo) => `${prefixo} ${Date.now().toString(36)}${contador++}`;

// CPF e CNPJ válidos e únicos: um contador em memória repetiria valores
// quando o Playwright reinicia o processo depois de uma falha.
const digitos = (n) => Array.from({ length: n }, () => Math.floor(Math.random() * 10));

export function cpfValido() {
    const d = digitos(9);
    for (const t of [9, 10]) {
        const soma = d.reduce((acc, v, i) => acc + v * (t + 1 - i), 0);
        d.push(((10 * soma) % 11) % 10);
    }
    return d.join('');
}

export function cnpjValido() {
    const d = [...digitos(8), 0, 0, 0, 1];
    for (const pesos of [[5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]]) {
        const resto = d.reduce((acc, v, i) => acc + v * pesos[i], 0) % 11;
        d.push(resto < 2 ? 0 : 11 - resto);
    }
    return d.join('');
}

export async function entrar(page, email = ADMIN, senha = SENHA) {
    await page.goto('login');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="senha"]', senha);
    await page.click('button[type="submit"]');
}

export async function entrarNoDashboard(page, email = ADMIN, senha = SENHA) {
    await entrar(page, email, senha);
    await expect(page).toHaveURL(/\/dashboard$/);
    // O JS do menu e do sino fica no fim da página, depois de scripts de CDN.
    await page.waitForLoadState('load');
}

// Aceita os diálogos (confirm e alert) e devolve as mensagens em ordem.
export function aceitarDialogos(page) {
    const mensagens = [];
    page.on('dialog', async (d) => {
        mensagens.push(d.message());
        await d.accept();
    });
    return mensagens;
}

export const toast = (page) => page.locator('#toast-container');

export async function criarProduto(page, marca, custo = '10', venda = '20', fornecedor = '1') {
    await page.goto('produtos');
    await page.click('#new-produto-btn');
    await page.fill('#produto-marca', marca);
    await page.fill('#produto-custo', custo);
    await page.fill('#produto-venda', venda);
    await page.selectOption('#produto-fornecedor', fornecedor);
    await page.click('#produto-form button[type="submit"]');
    await expect(toast(page)).toContainText('Produto cadastrado com sucesso');
}

export async function preencherMovimentacao(page, marca, tipo, quantidade, custo, venda) {
    await page.goto('movimentacoes');
    await page.selectOption('#produto-select', { label: marca });
    await page.selectOption('select[name="tipo"]', tipo);
    await page.fill('input[name="quantidade"]', String(quantidade));
    if (custo !== undefined) await page.fill('#preco-custo', String(custo));
    if (venda !== undefined) await page.fill('#preco-venda', String(venda));
}

export async function registrarMovimentacao(page, marca, tipo, quantidade, custo, venda) {
    await preencherMovimentacao(page, marca, tipo, quantidade, custo, venda);
    await page.click('form[onsubmit] button[type="submit"]');
    await expect(toast(page)).toContainText('Movimentação registrada com sucesso');
}

export const saldoNaTela = (page, marca) => page.locator('#estoque-atual > div', { hasText: marca });

export const moedaParaNumero = (texto) => Number(texto.replace(/[^\d,-]/g, '').replace(',', '.'));
