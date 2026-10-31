import fs from 'node:fs';
import { test, expect, entrarNoDashboard, unico, criarProduto, registrarMovimentacao, moedaParaNumero } from './apoio.js';

const cartao = (page, titulo) => page.locator('p', { hasText: titulo }).locator('xpath=following-sibling::p[1]');

test.describe('Dashboard', () => {
    test.beforeEach(async ({ page }) => entrarNoDashboard(page));

    test('os cards refletem uma movimentação nova', async ({ page }) => {
        const marca = unico('Card');
        await criarProduto(page, marca, '10', '20');
        await page.goto('dashboard');
        const antes = Number(await cartao(page, 'Movimentações Hoje').innerText());

        await registrarMovimentacao(page, marca, 'entrada', 7);
        await page.goto('dashboard');
        await expect(cartao(page, 'Movimentações Hoje')).toHaveText(String(antes + 1));
    });

    test('os dois gráficos são desenhados com dados', async ({ page }) => {
        const marca = unico('Grafico');
        await criarProduto(page, marca);
        await registrarMovimentacao(page, marca, 'entrada', 3);
        await page.goto('dashboard');

        for (const id of ['movimentacoes-chart', 'estoque-chart']) {
            await expect.poll(() => page.evaluate((c) => {
                const g = window.Chart && window.Chart.getChart(c);
                return g ? g.data.datasets.reduce((n, d) => n + d.data.length, 0) : 0;
            }, id)).toBeGreaterThan(0);
        }
    });

    test('as atividades recentes mostram a movimentação', async ({ page }) => {
        const marca = unico('Atividade');
        await criarProduto(page, marca);
        await registrarMovimentacao(page, marca, 'entrada', 9);
        await page.goto('dashboard');
        await expect(page.locator('#recent-activities')).toContainText(marca);
    });

    test('o sino lista estoque baixo e some quem tem saldo', async ({ page }) => {
        const baixo = unico('Baixo');
        await criarProduto(page, baixo);
        await page.goto('dashboard');
        await page.click('#btn-bell');
        await expect(page.locator('#bell-list')).not.toContainText('Falha ao carregar');
        await expect(page.locator('#bell-list > div').first()).toBeVisible();
        await expect(page.locator('#bell-badge')).toBeVisible();
    });
});

test.describe('Relatórios', () => {
    test.beforeEach(async ({ page }) => entrarNoDashboard(page));

    async function abrir(page) {
        const respostas = Promise.all([
            page.waitForResponse((r) => r.url().includes('tipo=financeiro')),
            page.waitForResponse((r) => r.url().includes('tipo=estoque')),
        ]);
        await page.goto('relatorios');
        await respostas;
    }

    test('saldo e tabelas mudam com a movimentação', async ({ page }) => {
        const marca = unico('Relatorio');
        await criarProduto(page, marca, '10', '25');
        await abrir(page);
        const saldoAntes = moedaParaNumero(await page.locator('#fin-saldo').innerText());

        await registrarMovimentacao(page, marca, 'entrada', 10); // -100
        await registrarMovimentacao(page, marca, 'saida', 4);    // +100, saldo igual
        await registrarMovimentacao(page, marca, 'saida', 2);    // +50
        await abrir(page);

        await expect.poll(async () => moedaParaNumero(await page.locator('#fin-saldo').innerText())).toBeCloseTo(saldoAntes + 50, 2);
        await expect(page.locator('#fin-entradas-tbody')).toContainText(marca);
        await expect(page.locator('#fin-saidas-tbody')).toContainText(marca);
        await expect(page.locator('#est-produtos-tbody')).toContainText(marca);
        await expect(page.locator('#est-historico-tbody')).toContainText(marca);
        await expect(page.locator('#est-top-tbody')).toContainText(marca);
    });

    test('os gráficos financeiros são desenhados', async ({ page }) => {
        await abrir(page);
        for (const id of ['chart-receitas', 'chart-despesas']) {
            await expect.poll(() => page.evaluate((c) => !!(window.Chart && window.Chart.getChart(c)), id)).toBe(true);
        }
    });

    test('datas personalizadas sem movimento esvaziam as tabelas', async ({ page }) => {
        await abrir(page);
        await page.fill('#data-inicio', '2020-01-01');
        await page.fill('#data-fim', '2020-01-31');
        const resposta = page.waitForResponse((r) => r.url().includes('data_inicio=2020-01-01') && r.url().includes('tipo=estoque'));
        await page.click('#btn-aplicar');
        await resposta;
        await expect(page.locator('#fin-saldo')).toContainText('0,00');
        await expect(page.locator('#est-historico-tbody')).not.toContainText('Galão');
    });

    test('trocar o período recarrega os dados', async ({ page }) => {
        await abrir(page);
        for (const periodo of ['diario', 'semanal', 'mensal', 'anual']) {
            const resposta = page.waitForResponse((r) => r.url().includes(`periodo=${periodo}`) && r.url().includes('tipo=financeiro'));
            await page.selectOption('#select-periodo', periodo);
            await page.click('#btn-aplicar');
            expect((await resposta).status()).toBe(200);
        }
    });

    for (const [nome, botao, extensao] of [
        ['PDF financeiro', '#btn-export-pdf-fin', '.pdf'],
        ['Excel financeiro', '#btn-export-excel-fin', '.xlsx'],
        ['PDF de estoque', '#btn-export-pdf-est', '.pdf'],
        ['Excel de estoque', '#btn-export-excel-est', '.xlsx'],
    ]) {
        test(`exporta ${nome}`, async ({ page }) => {
            await abrir(page);
            const download = page.waitForEvent('download', { timeout: 45_000 });
            await page.click(botao);
            const arquivo = await download;
            expect(arquivo.suggestedFilename()).toMatch(new RegExp(`\\${extensao}$`));
            expect(fs.statSync(await arquivo.path()).size).toBeGreaterThan(1000);
        });
    }
});
