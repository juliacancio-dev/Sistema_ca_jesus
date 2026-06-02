<?php

class RelatorioController extends Controller
{
    public function index()
    {
        $title = "Relatórios - Sistema de Gestão de Estoque";
        $pageTitle = "Relatórios";

        $this->renderView('layouts/relatorio/index', [
            'title' => $title,
            'pageTitle' => $pageTitle
        ]);
    }

    public function gerar()
    {
        try {
            ErrorHelper::logSuccess('Relatório gerado com sucesso', 'Geração de Relatório');
            header('Content-Type: application/json');
            echo json_encode(['status' => 'ok', 'mensagem' => 'Relatório gerado com sucesso!']);
        } catch (Exception $e) {
            ErrorHelper::handle($e, 'Erro ao gerar relatório', 'Geração de Relatório');
            header('Content-Type: application/json');
            echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
        }
        exit;
    }

    public function getDados()
    {
        header('Content-Type: application/json');

        $tipo = $_GET['tipo'] ?? 'financeiro';
        $periodo = $_GET['periodo'] ?? 'mensal';
        $dataInicio = $_GET['data_inicio'] ?? null;
        $dataFim = $_GET['data_fim'] ?? null;

        $movimentacaoModel = new MovimentacaoModel();
        $produtoModel = new ProdutoModel();

        [$inicio, $fim] = $this->determinarIntervaloDatas($periodo, $dataInicio, $dataFim);

        $movimentacoes = $movimentacaoModel->buscarPorPeriodo($inicio, $fim);

        if ($tipo === 'financeiro') {
            $dados = $this->montarDadosFinanceiros($movimentacoes, $periodo, $inicio, $fim);
            echo json_encode(['dados' => $dados]);
            exit;
        }

        if ($tipo === 'estoque') {
            $produtos = $produtoModel->listarTodos();
            $dados = $this->montarDadosEstoque($movimentacoes, $produtos, $periodo, $inicio, $fim);
            echo json_encode(['dados' => $dados]);
            exit;
        }

        echo json_encode(['erro' => 'Tipo de relatório inválido']);
        exit;
    }

    private function determinarIntervaloDatas(string $periodo, ?string $dataInicio, ?string $dataFim): array
    {
        if ($dataInicio && $dataFim) {
            return [$dataInicio, $dataFim];
        }

        $hoje = date('Y-m-d');
        switch (strtolower($periodo)) {
            case 'diario':
                return [$hoje, $hoje];
            case 'semanal':
                return [date('Y-m-d', strtotime('-6 days')), $hoje];
            case 'anual':
                return [date('Y-01-01'), $hoje];
            case 'mensal':
            default:
                return [date('Y-m-01'), $hoje];
        }
    }

    private function montarDadosFinanceiros(array $movimentacoes, string $periodo, string $inicio, string $fim): array
    {
        $receitas = 0.0;
        $custosVendidos = 0.0;
        $despesasCompras = 0.0;

        $entradasDetalhadas = [];
        $saidasDetalhadas = [];

        $serieReceitas = [];
        $serieDespesas = [];

        $mapReceitasDia = [];
        $mapDespesasDia = [];

        foreach ($movimentacoes as $m) {
            $acao = $m['acao'] ?? '';
            $qtd = (float)($m['quantidade'] ?? 0);
            $pv = (float)($m['preco_venda'] ?? 0);
            $pc = (float)($m['preco_custo'] ?? 0);
            $data = substr($m['data'], 0, 10);
            $produto = $m['produto'] ?? '-';

            if ($acao === 'saida') {
                $valorVenda = $pv * $qtd;
                $valorCusto = $pc * $qtd;
                $receitas += $valorVenda;
                $custosVendidos += $valorCusto;
                $entradasDetalhadas[] = [
                    'data' => $m['data'],
                    'descricao' => $produto,
                    'valor' => $valorVenda
                ];
                $mapReceitasDia[$data] = ($mapReceitasDia[$data] ?? 0) + $valorVenda;
            } elseif ($acao === 'entrada') {
                $valorCompra = $pc * $qtd;
                $despesasCompras += $valorCompra;
                $saidasDetalhadas[] = [
                    'data' => $m['data'],
                    'descricao' => $produto,
                    'valor' => $valorCompra,
                ];
                $mapDespesasDia[$data] = ($mapDespesasDia[$data] ?? 0) + $valorCompra;
            }
        }

        $lucro = $receitas - $custosVendidos;
        $saldoFinal = $receitas - $despesasCompras;

        $cursor = strtotime($inicio);
        $end = strtotime($fim);
        while ($cursor <= $end) {
            $d = date('Y-m-d', $cursor);
            $serieReceitas[] = ['data' => $d, 'valor' => (float)($mapReceitasDia[$d] ?? 0)];
            $serieDespesas[] = ['data' => $d, 'valor' => (float)($mapDespesasDia[$d] ?? 0)];
            $cursor = strtotime('+1 day', $cursor);
        }

        return [
            'tipo' => 'financeiro',
            'periodo' => $periodo,
            'data_inicio' => $inicio,
            'data_fim' => $fim,
            'resumo' => [
                'receitas' => $receitas,
                'despesas' => $despesasCompras,
                'total_receitas' => $receitas,
                'total_despesas' => $despesasCompras,
                'saldo_final' => $saldoFinal,
                'lucro' => $lucro
            ],
            'entradas_detalhadas' => $entradasDetalhadas,
            'saidas_detalhadas' => $saidasDetalhadas,
            'series' => [
                'receitas' => $serieReceitas,
                'despesas' => $serieDespesas
            ]
        ];
    }

    private function montarDadosEstoque(array $movimentacoes, array $produtos, string $periodo, string $inicio, string $fim): array
    {
        $totalEstoque = 0;
        foreach ($produtos as $p) {
            $totalEstoque += (int)($p['estoque'] ?? 0);
        }

        $entradasEstoque = [];
        $saidasEstoque = [];
        $historico = [];
        $mapSaidasPorProduto = [];

        foreach ($movimentacoes as $m) {
            $acao = $m['acao'] ?? '';
            $qtd = (int)($m['quantidade'] ?? 0);
            $produto = $m['produto'] ?? '-';
            $reg = [
                'data' => $m['data'],
                'produto' => $produto,
                'tipo' => $acao,
                'quantidade' => $qtd,
                'observacao' => $m['observacao'] ?? ''
            ];

            $historico[] = $reg;
            if ($acao === 'entrada') $entradasEstoque[] = $reg;
            if ($acao === 'saida') {
                $saidasEstoque[] = $reg;
                $mapSaidasPorProduto[$produto] = ($mapSaidasPorProduto[$produto] ?? 0) + $qtd;
            }
        }

        arsort($mapSaidasPorProduto);
        $produtosMaisVendidos = [];
        foreach (array_slice($mapSaidasPorProduto, 0, 10, true) as $produto => $qtd) {
            $produtosMaisVendidos[] = ['produto' => $produto, 'quantidade' => $qtd];
        }

        $listaProdutos = array_map(function ($p) {
            return [
                'nome' => $p['marca'] ?? ($p['marca_produto'] ?? ''),
                'codigo' => $p['id'],
                'estoque_atual' => $p['estoque'] ?? ($p['estoque_produto'] ?? 0)
            ];
        }, $produtos);

        return [
            'tipo' => 'estoque',
            'periodo' => $periodo,
            'data_inicio' => $inicio,
            'data_fim' => $fim,
            'resumo' => [
                'quantidade_total_estoque' => $totalEstoque
            ],
            'produtos' => $listaProdutos,
            'entradas_estoque' => $entradasEstoque,
            'saidas_estoque' => $saidasEstoque,
            'historico_movimentacoes' => $historico,
            'produtos_mais_vendidos' => $produtosMaisVendidos
        ];
    }
}
