import { test, expect, entrarNoDashboard, unico, toast, criarProduto, preencherMovimentacao, registrarMovimentacao, saldoNaTela } from './apoio.js';

const BOTAO = 'form[onsubmit] button[type="submit"]';
const historico = (page, marca) => page.locator('table tbody tr', { hasText: marca });

test.describe('Movimentações', () => {
    let marca;

    test.beforeEach(async ({ page }) => {
        await entrarNoDashboard(page);
        marca = unico('Estoque');
        await criarProduto(page, marca, '100', '130');
    });

    test('escolher o produto preenche os preços e mostra o saldo', async ({ page }) => {
        await page.goto('movimentacoes');
        await page.selectOption('#produto-select', marca);
        await expect(page.locator('#preco-custo')).toHaveValue('100.00');
        await expect(page.locator('#preco-venda')).toHaveValue('130.00');
        await expect(page.locator('#estoque-badge')).toContainText('Estoque atual: 0');
    });

    test('entrada e saída corrigem o saldo e entram no histórico', async ({ page }) => {
        await registrarMovimentacao(page, marca, 'entrada', 10);
        await expect(saldoNaTela(page, marca)).toContainText('10 unidade(s)');

        await registrarMovimentacao(page, marca, 'saida', 4);
        await expect(saldoNaTela(page, marca)).toContainText('6 unidade(s)');
        await expect(historico(page, marca)).toHaveCount(2);
    });

    test('saída de todo o saldo zera', async ({ page }) => {
        await registrarMovimentacao(page, marca, 'entrada', 5);
        await registrarMovimentacao(page, marca, 'saida', 5);
        await expect(saldoNaTela(page, marca)).toContainText('0 unidade(s)');
    });

    test('saída acima do saldo mostra o erro, mantém o saldo e libera o botão', async ({ page }) => {
        await registrarMovimentacao(page, marca, 'entrada', 3);
        await preencherMovimentacao(page, marca, 'saida', 99);
        await page.click(BOTAO);

        await expect(toast(page)).toContainText('maior que o estoque disponível (3)');
        await expect(page.locator(BOTAO)).toBeEnabled();
        await expect(page.locator(BOTAO)).toContainText('Registrar Movimentação');
        await expect(page.locator('input[name="quantidade"]')).toHaveValue('99');

        await page.reload();
        await expect(saldoNaTela(page, marca)).toContainText('3 unidade(s)');
        await expect(historico(page, marca)).toHaveCount(1);
    });

    test('saída de produto zerado é recusada', async ({ page }) => {
        await preencherMovimentacao(page, marca, 'saida', 1);
        await page.click(BOTAO);
        await expect(toast(page)).toContainText('Estoque do produto está zerado');
    });

    test('custo maior que venda: cancelar não grava', async ({ page }) => {
        await registrarMovimentacao(page, marca, 'entrada', 5);
        await preencherMovimentacao(page, marca, 'saida', 1, 200, 130);

        let pergunta;
        page.once('dialog', async (d) => {
            pergunta = d.message();
            await d.dismiss();
        });
        await page.click(BOTAO);

        await expect.poll(() => pergunta).toContain('Tem certeza');
        await expect(page.locator(BOTAO)).toBeEnabled();
        await page.reload();
        await expect(saldoNaTela(page, marca)).toContainText('5 unidade(s)');
        await expect(historico(page, marca)).toHaveCount(1);
    });

    test('custo maior que venda: confirmar grava', async ({ page }) => {
        await registrarMovimentacao(page, marca, 'entrada', 5);
        await preencherMovimentacao(page, marca, 'saida', 1, 200, 130);

        page.once('dialog', (d) => d.accept());
        await page.click(BOTAO);

        await expect(toast(page)).toContainText('Movimentação registrada com sucesso');
        await expect(saldoNaTela(page, marca)).toContainText('4 unidade(s)');
    });

    test('custo maior que venda vale também para a entrada', async ({ page }) => {
        await preencherMovimentacao(page, marca, 'entrada', 2, 200, 130);
        page.once('dialog', (d) => d.accept());
        await page.click(BOTAO);
        await expect(toast(page)).toContainText('Movimentação registrada com sucesso');
        await expect(saldoNaTela(page, marca)).toContainText('2 unidade(s)');
    });

    test('custo maior com saída acima do saldo só mostra o erro, sem perguntar', async ({ page }) => {
        await preencherMovimentacao(page, marca, 'saida', 1, 200, 130);
        let perguntou = false;
        page.on('dialog', async (d) => {
            perguntou = true;
            await d.accept();
        });
        await page.click(BOTAO);
        await expect(toast(page)).toContainText('zerado');
        expect(perguntou).toBe(false);
    });

    test('marca com acento e aspas funciona no formulário', async ({ page }) => {
        const especial = `Água "Mineral" d'Ávila ${unico('x')}`;
        await criarProduto(page, especial);
        await registrarMovimentacao(page, especial, 'entrada', 2);
        await expect(saldoNaTela(page, especial)).toContainText('2 unidade(s)');
    });
});
