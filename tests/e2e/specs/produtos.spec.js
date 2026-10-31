import { test, expect, entrarNoDashboard, unico, aceitarDialogos, toast, criarProduto, registrarMovimentacao } from './apoio.js';

const linha = (page, marca) => page.locator('#produtos-table-body tr', { hasText: marca });

test.describe('Produtos', () => {
    test.beforeEach(async ({ page }) => entrarNoDashboard(page));

    test('cadastrar pelo modal', async ({ page }) => {
        const marca = unico('Produto');
        await criarProduto(page, marca, '10.50', '20.75', '2');
        await expect(linha(page, marca)).toBeVisible();
        await expect(linha(page, marca)).toContainText('10,50');
        await expect(linha(page, marca)).toContainText('20,75');
        await expect(linha(page, marca)).toContainText('Ibirá');
    });

    test('venda menor que custo é barrada', async ({ page }) => {
        const marca = unico('Barrado');
        const dialogos = aceitarDialogos(page);
        await page.goto('produtos');
        await page.click('#new-produto-btn');
        await page.fill('#produto-marca', marca);
        await page.fill('#produto-custo', '30');
        await page.fill('#produto-venda', '20');
        await page.selectOption('#produto-fornecedor', '1');
        await page.click('#produto-form button[type="submit"]');

        await expect.poll(() => dialogos.length).toBe(1);
        expect(dialogos[0]).toContain('não pode ser menor');
        await page.goto('produtos');
        await expect(linha(page, marca)).toHaveCount(0);
    });

    test('marca duplicada mostra o motivo', async ({ page }) => {
        const marca = unico('Duplicado');
        await criarProduto(page, marca);
        await page.click('#new-produto-btn');
        await page.fill('#produto-marca', marca);
        await page.fill('#produto-custo', '1');
        await page.fill('#produto-venda', '2');
        await page.selectOption('#produto-fornecedor', '1');
        await page.click('#produto-form button[type="submit"]');
        await expect(toast(page)).toContainText('Já existe um produto com esta marca');
    });

    test('visualizar abre o modal só para leitura', async ({ page }) => {
        const marca = unico('Leitura');
        await criarProduto(page, marca);
        await linha(page, marca).locator('button[onclick^="viewProduto"]').click();
        await expect(page.locator('#produto-modal-title')).toHaveText('Visualizar Produto');
        await expect(page.locator('#produto-marca')).toHaveValue(marca);
        await expect(page.locator('#produto-marca')).toBeDisabled();
        await expect(page.locator('#produto-form button[type="submit"]')).toBeHidden();
    });

    test('editar traz os dados e salva', async ({ page }) => {
        const marca = unico('Editar');
        const nova = `${marca} novo`;
        await criarProduto(page, marca, '10', '20', '1');
        await linha(page, marca).locator('button[onclick^="editProduto"]').click();
        await expect(page.locator('#produto-modal-title')).toHaveText('Editar Produto');
        await expect(page.locator('#produto-marca')).toHaveValue(marca);
        await expect(page.locator('#produto-custo')).toHaveValue('10.00');
        await expect(page.locator('#produto-fornecedor')).toHaveValue('1');

        await page.fill('#produto-marca', nova);
        await page.fill('#produto-venda', '25');
        await page.click('#produto-form button[type="submit"]');
        await expect(toast(page)).toContainText('Produto atualizado com sucesso');
        await expect(linha(page, nova)).toContainText('25,00');
    });

    test('novo depois de visualizar volta a ser editável', async ({ page }) => {
        const marca = unico('Reabrir');
        await criarProduto(page, marca);
        await linha(page, marca).locator('button[onclick^="viewProduto"]').click();
        await page.click('#close-produto-modal');
        await page.click('#new-produto-btn');
        await expect(page.locator('#produto-marca')).toBeEnabled();
        await expect(page.locator('#produto-marca')).toHaveValue('');
        await expect(page.locator('#produto-form button[type="submit"]')).toBeVisible();
    });

    test('excluir sem movimentação remove da lista', async ({ page }) => {
        const marca = unico('Excluir');
        const dialogos = aceitarDialogos(page);
        await criarProduto(page, marca);
        await linha(page, marca).locator('button[onclick^="deleteProduto"]').click();
        await expect(toast(page)).toContainText('Produto excluído com sucesso');
        await expect(linha(page, marca)).toHaveCount(0);
        expect(dialogos[0]).toContain('Tem certeza');
    });

    test('excluir com movimentação é recusado com o motivo', async ({ page }) => {
        const marca = unico('ComHistorico');
        const dialogos = aceitarDialogos(page);
        await criarProduto(page, marca);
        await registrarMovimentacao(page, marca, 'entrada', 1);
        await page.goto('produtos');
        await linha(page, marca).locator('button[onclick^="deleteProduto"]').click();
        await expect.poll(() => dialogos.length).toBe(2);
        expect(dialogos[1]).toContain('movimentações associadas');
        await expect(linha(page, marca)).toBeVisible();
    });

    test('a busca filtra a tabela', async ({ page }) => {
        const marca = unico('Filtrado');
        await criarProduto(page, marca);
        await page.fill('#search-produtos', marca);
        await expect(linha(page, marca)).toBeVisible();
        await expect(linha(page, 'Gás de cozinha')).toBeHidden();
    });

    test('nome com HTML não executa na tela nem no sino', async ({ page }) => {
        const marca = `<img src=x onerror="window.__xss=1"> ${unico('Xss')}`;
        await criarProduto(page, marca);
        await page.click('#btn-bell');
        await expect(page.locator('#bell-list')).toContainText('Xss');
        await page.goto('movimentacoes');
        expect(await page.evaluate(() => window.__xss)).toBeUndefined();
    });
});
