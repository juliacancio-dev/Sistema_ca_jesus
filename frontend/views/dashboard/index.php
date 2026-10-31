
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Dashboard</h1>
                    <p class="text-sm text-gray-600 mt-1">Resumo geral do sistema e movimentações recentes</p>
                </div>
                <div class="flex items-center gap-3 text-sm text-gray-500">
                    <i class="fas fa-calendar text-blue-600"></i>
                    <span><?= date('d/m/Y') ?></span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow hover:shadow-md transition p-5 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-500">Total Produtos</p>
                        <p class="text-2xl font-extrabold text-gray-900 mt-1"><?= $stats['total_produtos'] ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center">
                        <i class="fas fa-boxes text-blue-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow hover:shadow-md transition p-5 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-500">Estoque Total</p>
                        <p class="text-2xl font-extrabold text-gray-900 mt-1"><?= $stats['estoque_total'] ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-green-50 flex items-center justify-center">
                        <i class="fas fa-warehouse text-green-600"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow hover:shadow-md transition p-5 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-500">Movimentações Hoje</p>
                        <p class="text-2xl font-extrabold text-gray-900 mt-1"><?= $stats['movimentacoes_hoje'] ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-yellow-50 flex items-center justify-center">
                        <i class="fas fa-exchange-alt text-yellow-600"></i>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['user']['tipo']) && $_SESSION['user']['tipo'] == 1): ?>
                <div class="bg-white rounded-xl shadow hover:shadow-md transition p-5 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-500">Saldo Financeiro</p>
                            <p class="text-2xl font-extrabold text-gray-900 mt-1">R$ <?= number_format($stats['saldo_financeiro'] ?? 0, 2, ',', '.') ?></p>
                        </div>
                        <div class="w-12 h-12 rounded-lg bg-purple-50 flex items-center justify-center">
                            <i class="fas fa-sack-dollar text-purple-600"></i>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($atividades) && empty($dadosGraficos)): ?>
            <div class="bg-yellow-50 text-yellow-800 p-4 rounded-lg border border-yellow-200">AVISO: Os dados estão vazios. Verifique se há registros no banco de dados.</div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white rounded-xl shadow p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base md:text-lg font-semibold text-gray-800">Movimentações dos Últimos 7 Dias</h3>
                </div>
                <div class="w-full h-64 md:h-80">
                    <canvas id="movimentacoes-chart" style="width:100%; height:100%;"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base md:text-lg font-semibold text-gray-800">Top 5 Produtos em Estoque</h3>
                </div>
                <div class="w-full h-64 md:h-80">
                    <canvas id="estoque-chart" style="width:100%; height:100%;"></canvas>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base md:text-lg font-semibold text-gray-800">Atividades Recentes</h3>
                <span class="text-xs text-gray-400">Últimas 24h</span>
            </div>
            <div id="recent-activities" class="space-y-3">
                <?php if (empty($atividades)) { ?>
                    <div class="border border-dashed border-gray-200 rounded-lg p-6 text-center">
                        <i class="fas fa-clock text-gray-300 text-2xl mb-2"></i>
                        <p class="text-gray-500">Nenhuma atividade recente</p>
                    </div>
                    <?php } else {
                    foreach ($atividades as $atividade) {
                        $isEntrada = ($atividade['acao'] === 'entrada');
                        $color = $isEntrada ? 'red' : 'green';
                    ?>
                        <div class="p-3 rounded-lg border border-gray-100 bg-gray-50 flex items-start justify-between">
                            <div class="flex items-start gap-3">
                                <div class="w-9 h-9 rounded-full bg-<?= $color ?>-100 flex items-center justify-center">
                                    <i class="fas fa-<?= $isEntrada ? 'arrow-up' : 'arrow-down' ?> text-<?= $color ?>-600 text-sm"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800 leading-tight"><?= htmlspecialchars($atividade['produto']) ?></p>
                                    <p class="text-xs text-gray-600">
                                        <?= $isEntrada ? 'Entrada' : 'Saída' ?> de <span class="font-semibold"><?= (int)$atividade['quantidade'] ?></span> unidades
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500 mb-1"><?= date('d/m/Y', strtotime($atividade['data'])) ?></p>
                                <?php
                                $valorUnit = $isEntrada ? ($atividade['preco_custo'] ?? ($atividade['preco'] ?? 0)) : ($atividade['preco_venda'] ?? ($atividade['preco'] ?? 0));
                                $total = ($atividade['quantidade'] ?? 0) * $valorUnit;
                                $sinal = $isEntrada ? '-' : '+';
                                $valorClasse = $isEntrada ? 'text-red-600' : 'text-green-600';
                                ?>
                                <p class="text-xs font-semibold <?= $valorClasse ?>">
                                    <?= $sinal ?> R$ <?= number_format($total, 2, ',', '.') ?>
                                </p>
                            </div>
                        </div>
                <?php }
                } ?>
            </div>
        </div>

    </div>
</div>

<script type="application/json" id="dados-graficos"><?= json_encode($dadosGraficos, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="<?= BASE_URL ?>/assets/js/dashboard.js" defer></script>
