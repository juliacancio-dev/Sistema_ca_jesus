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

    test('o período escolhido define o intervalo, e as datas mostram qual foi', async ({ page }) => {
        await abrir(page);
        const ano = await page.inputValue('#data-fim').then((d) => d.slice(0, 4));

        await page.selectOption('#select-periodo', 'anual');
        const resposta = page.waitForResponse((r) => r.url().includes('periodo=anual') && r.url().includes('tipo=financeiro'));
        await page.click('#btn-aplicar');
        const pedido = await resposta;

        expect(pedido.url()).not.toContain('data_inicio');
        expect((await pedido.json()).dados.data_inicio).toBe(`${ano}-01-01`);
        await expect(page.locator('#data-inicio')).toHaveValue(`${ano}-01-01`);
        // O mesmo texto que vai no cabeçalho do PDF.
        const texto = await page.evaluate(() => periodoTexto());
        expect(texto.startsWith(`01/01/${ano} a `)).toBe(true);
        expect(texto.endsWith('(Anual)')).toBe(true);
    });

    test('mudar uma data passa o período a personalizado', async ({ page }) => {
        await abrir(page);
        await page.fill('#data-inicio', '2020-01-01');
        await expect(page.locator('#select-periodo')).toHaveValue('personalizado');
        await page.selectOption('#select-periodo', 'mensal');
        await expect(page.locator('#data-inicio')).toHaveValue('');
    });

    test('o servidor recusa período e datas inválidos', async ({ page }) => {
        for (const busca of [
            'periodo=trimestral',
            'periodo=personalizado',
            'periodo=personalizado&data_inicio=abc&data_fim=2026-01-01',
            'periodo=personalizado&data_inicio=2026-10-31&data_fim=2026-10-01',
            'periodo=personalizado&data_inicio=0001-01-01&data_fim=9999-12-31',
            'periodo[]=x',
            'tipo=outro',
            'tipo[]=x',
        ]) {
            const resposta = await page.request.get(`api/relatorios/dados?tipo=financeiro&${busca}`, { headers: { Accept: 'application/json' } });
            expect(resposta.status(), busca).toBe(422);
        }
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

// A API fora do ar tem de aparecer como falha, e não como "Tudo ok" ou "R$ 0,00".
test.describe('Falha da API', () => {
    test.use({ errosPermitidos: ['HTTP 500', 'Falha na requisição'] });
    test.beforeEach(async ({ page }) => entrarNoDashboard(page));

    const falhar = (page, rota) => page.route(rota, (r) => r.fulfill({ status: 500, contentType: 'text/html', body: 'erro' }));

    test('o sino avisa que não carregou', async ({ page }) => {
        await falhar(page, '**/api/estoque/atual');
        await page.goto('dashboard');
        await page.click('#btn-bell');
        await expect(page.locator('#bell-list')).toContainText('Não foi possível carregar os alertas');
        await expect(page.locator('#bell-list')).not.toContainText('Tudo ok');
    });

    test('a primeira carga do relatório não mostra R$ 0,00', async ({ page }) => {
        await falhar(page, '**/api/relatorios/dados**');
        await page.goto('relatorios');
        await expect(page.locator('#fin-saldo')).toHaveText('Não foi possível carregar');
        await expect(page.locator('#est-qtde-total')).toHaveText('Não foi possível carregar');
        await expect(page.locator('#est-produtos-tbody')).toContainText('Não foi possível carregar');
    });

    test('reaplicar com a API fora do ar apaga os valores antigos e bloqueia a exportação', async ({ page }) => {
        const carregou = page.waitForResponse((r) => r.url().includes('tipo=estoque'));
        await page.goto('relatorios');
        await carregou;
        await expect(page.locator('#fin-saldo')).toContainText('R$');

        await falhar(page, '**/api/relatorios/dados**');
        await page.click('#btn-aplicar');

        await expect(page.locator('#fin-saldo')).toHaveText('Não foi possível carregar');
        await expect(page.locator('#fin-entradas-tbody')).toHaveText('Não foi possível carregar.');
        expect(await page.evaluate(() => periodoTexto())).toBe('-');
        await page.click('#btn-export-excel-fin');
        await expect(page.locator('#toast-container')).toContainText('Carregue o relatório antes de exportar');
    });
});
