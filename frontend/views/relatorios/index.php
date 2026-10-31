<style>
    :root {
        --brand-primary: #0F766E;
        --brand-secondary: #1D4ED8;
        --brand-accent: #2563EB;
        --text-dark: #0f172a;
        --muted: #64748b;
        --surface: #ffffff;
        --border: #e5e7eb;
    }

    .report-banner {
        background: #ffffff;
        border: 1px solid var(--border);
    }

    .brand-title {
        letter-spacing: 0.3px;
    }

    #card-financeiro,
    #card-estoque {
        border: 1px solid var(--border);
        border-radius: 14px;
    }

    #card-financeiro>div.mb-4,
    #card-estoque>div.mb-4 {
        border-bottom: 1px solid var(--border);
        background: linear-gradient(180deg, rgba(241, 245, 249, 0.65), rgba(255, 255, 255, 0));
        padding: 12px;
        border-radius: 10px;
    }

    .report-table thead th {
        background: #0F172A;
        color: #fff;
        font-weight: 600;
    }

    .report-table th,
    .report-table td {
        border-bottom: 1px solid var(--border);
    }

    .report-table tbody tr:nth-child(odd) {
        background: #F8FAFC;
    }

    .report-table tbody tr:hover {
        background: #EEF2FF;
    }

    .pdf-meta {
        border-bottom: 1px solid var(--border);
        padding-bottom: 8px;
        margin-bottom: 16px;
    }

    .pdf-meta h2 {
        margin: 0;
        font-size: 18px;
    }

    .pdf-meta small {
        color: var(--muted);
    }

    .export-footer {
        margin-top: 16px;
        padding-top: 8px;
        border-top: 1px dashed var(--border);
        color: var(--muted);
        font-size: 11px;
    }

    @media (max-width: 640px) {
        .report-actions {
            justify-content: flex-start !important;
            gap: 8px;
            flex-wrap: wrap;
        }
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<div class="max-w-7xl mx-auto py-2 sm:py-4 lg:py-6">
    <div class="report-banner rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="flex items-center gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold brand-title">Relatórios</h1>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-4 mb-6" data-export-hide="true">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-2">
                <i class="fas fa-layer-group text-blue-600"></i>
                <span class="font-medium text-gray-800">Exibir seções</span>
            </div>
            <div class="flex items-center gap-6">
                <label class="inline-flex items-center">
                    <input id="cb-financeiro" type="checkbox" class="mr-2" checked>
                    <span class="text-sm text-gray-800">Relatório Financeiro</span>
                </label>
                <label class="inline-flex items-center">
                    <input id="cb-estoque" type="checkbox" class="mr-2" checked>
                    <span class="text-sm text-gray-800">Relatório de Estoque</span>
                </label>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-4 mb-6" data-export-hide="true">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Período</label>
                <select id="select-periodo" class="w-full border-gray-300 rounded-lg">
                    <option value="diario">Diário</option>
                    <option value="semanal">Semanal</option>
                    <option value="mensal" selected>Mensal</option>
                    <option value="anual">Anual</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data início</label>
                <input id="data-inicio" type="date" class="w-full border-gray-300 rounded-lg" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data fim</label>
                <input id="data-fim" type="date" class="w-full border-gray-300 rounded-lg" />
            </div>
            <div class="flex items-end">
                <button id="btn-aplicar" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Aplicar</button>
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-2">Escolha um período predefinido ou defina datas específicas. Se datas forem preenchidas, elas prevalecem.</p>
    </div>

    <div id="grid-relatorios" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <section id="card-financeiro" class="bg-white rounded-xl shadow p-4 print-section">
            <header class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-blue-50 text-blue-700">
                        <i class="fas fa-file-invoice-dollar mr-1"></i> Relatório Financeiro
                    </span>
                </div>
                <div class="flex items-center gap-2 report-actions export-controls" data-export-hide="true">
                    <button id="btn-export-pdf-fin" class="bg-red-600 text-white px-3 py-1.5 rounded-lg hover:bg-red-700 transition text-sm shadow-md hover:shadow-lg transform hover:scale-105" aria-label="Exportar relatório financeiro em PDF">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                    <button id="btn-export-excel-fin" class="bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700 transition text-sm shadow-md hover:shadow-lg transform hover:scale-105 relative overflow-hidden" aria-label="Exportar relatório financeiro em Excel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>
                </div>
            </header>

            <div class="rounded-lg border p-3">
                <div class="text-xs text-gray-500">Saldo Financeiro</div>
                <div id="fin-saldo" class="text-lg font-semibold text-blue-600">R$ 0,00</div>
            </div>
            <div class="space-y-4 mb-4">
                <div>
                    <div class="text-sm font-medium text-gray-800 mb-2">Total de vendas (saídas de estoque)</div>
                    <canvas id="chart-receitas" height="120" aria-label="Gráfico de total de vendas (saídas de estoque)"></canvas>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-800 mb-2">Total de compras (entradas de estoque)</div>
                    <canvas id="chart-despesas" height="120" aria-label="Gráfico de total de compras (entradas de estoque)"></canvas>
                </div>
            </div>


            <div class="rounded-lg border p-3 mb-4">
                <div class="text-sm font-medium text-gray-800 mb-2">Vendas detalhadas (saídas)</div>
                <div class="overflow-x-auto">
                    <table class="report-table min-w-full text-xs">
                        <thead>
                            <tr>
                                <th class="px-2 py-2 text-left">Data</th>
                                <th class="px-2 py-2 text-left">Descrição</th>
                                <th class="px-2 py-2 text-right">Valor</th>
                            </tr>
                        </thead>
                        <tbody id="fin-entradas-tbody" class="divide-y"></tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-lg border p-3">
                <div class="text-sm font-medium text-gray-800 mb-2">Compras detalhadas (entradas)</div>
                <div class="overflow-x-auto">
                    <table class="report-table min-w-full text-xs">
                        <thead>
                            <tr>
                                <th class="px-2 py-2 text-left">Data</th>
                                <th class="px-2 py-2 text-left">Descrição</th>
                                <th class="px-2 py-2 text-right">Valor</th>
                            </tr>
                        </thead>
                        <tbody id="fin-saidas-tbody" class="divide-y"></tbody>
                    </table>
                </div>
            </div>
        </section>

        <section id="card-estoque" class="bg-white rounded-xl shadow p-4 print-section">
            <header class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-green-50 text-green-700">
                        <i class="fas fa-boxes-stacked mr-1"></i> Relatório de Estoque
                    </span>
                </div>
                <div class="flex items-center gap-2 report-actions export-controls" data-export-hide="true">
                    <button id="btn-export-pdf-est" class="bg-red-600 text-white px-3 py-1.5 rounded-lg hover:bg-red-700 transition text-sm shadow-md hover:shadow-lg transform hover:scale-105" aria-label="Exportar relatório de estoque em PDF">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                    <button id="btn-export-excel-est" class="bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700 transition text-sm shadow-md hover:shadow-lg transform hover:scale-105 relative overflow-hidden" aria-label="Exportar relatório de estoque em Excel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>
                </div>
            </header>

            <div class="grid grid-cols-1 gap-3 mb-4">
                <div class="rounded-lg border p-3">
                    <div class="text-xs text-gray-500">Quantidade total em estoque</div>
                    <div id="est-qtde-total" class="text-xl font-semibold text-blue-600">0</div>
                </div>
            </div>

            <div class="rounded-lg border p-3 mb-4">
                <div class="text-sm font-medium text-gray-800 mb-2">Produtos cadastrados</div>
                <div class="overflow-x-auto">
                    <table class="report-table min-w-full text-xs">
                        <thead>
                            <tr>
                                <th class="px-2 py-2 text-left">Código</th>
                                <th class="px-2 py-2 text-left">Nome</th>
                                <th class="px-2 py-2 text-right">Estoque</th>
                            </tr>
                        </thead>
                        <tbody id="est-produtos-tbody" class="divide-y"></tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 mb-4">
                <div class="rounded-lg border p-3">
                    <div class="text-sm font-medium text-gray-800 mb-2">Entradas de estoque</div>
                    <div class="overflow-x-auto">
                        <table class="report-table min-w-full text-xs">
                            <thead>
                                <tr>
                                    <th class="px-2 py-2 text-left">Data</th>
                                    <th class="px-2 py-2 text-left">Produto</th>
                                    <th class="px-2 py-2 text-right">Quantidade</th>
                                </tr>
                            </thead>
                            <tbody id="est-entradas-tbody" class="divide-y"></tbody>
                        </table>
                    </div>
                </div>
                <div class="rounded-lg border p-3">
                    <div class="text-sm font-medium text-gray-800 mb-2">Saídas de estoque</div>
                    <div class="overflow-x-auto">
                        <table class="report-table min-w-full text-xs">
                            <thead>
                                <tr>
                                    <th class="px-2 py-2 text-left">Data</th>
                                    <th class="px-2 py-2 text-left">Produto</th>
                                    <th class="px-2 py-2 text-right">Quantidade</th>
                                </tr>
                            </thead>
                            <tbody id="est-saidas-tbody" class="divide-y"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border p-3 mb-4">
                <div class="text-sm font-medium text-gray-800 mb-2">Histórico de movimentações</div>
                <div class="overflow-x-auto">
                    <table class="report-table min-w-full text-xs">
                        <thead>
                            <tr>
                                <th class="px-2 py-2 text-left">Data</th>
                                <th class="px-2 py-2 text-left">Produto</th>
                                <th class="px-2 py-2 text-left">Tipo</th>
                                <th class="px-2 py-2 text-right">Quantidade</th>
                                <th class="px-2 py-2 text-left">Observação</th>
                            </tr>
                        </thead>
                        <tbody id="est-historico-tbody" class="divide-y"></tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-lg border p-3">
                <div class="text-sm font-medium text-gray-800 mb-2">Produtos mais vendidos</div>
                <div class="overflow-x-auto">
                    <table class="report-table min-w-full text-xs">
                        <thead>
                            <tr>
                                <th class="px-2 py-2 text-left">Produto</th>
                                <th class="px-2 py-2 text-right">Quantidade</th>
                            </tr>
                        </thead>
                        <tbody id="est-top-tbody" class="divide-y"></tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/relatorios.js" defer></script>
