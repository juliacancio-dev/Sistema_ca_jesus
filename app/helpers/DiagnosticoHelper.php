<?php
class DiagnosticoHelper
{
    private static $diagnostico = [];
    private static $tempoInicio;

    public static function iniciarDiagnostico()
    {
        self::$tempoInicio = microtime(true);
        self::$diagnostico = [
            'timestamp' => date('Y-m-d H:i:s'),
            'pagina' => 'Dashboard',
            'tempo_execucao' => 0,
            'memoria_usada' => 0,
            'ambiente' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
                'sistema_operacional' => PHP_OS,
                'memoria_limite' => ini_get('memory_limit'),
                'post_max_size' => ini_get('post_max_size'),
                'max_execution_time' => ini_get('max_execution_time')
            ],
            'variaveis_disponiveis' => [],
            'dados_graficos' => [
                'status' => 'não verificado',
                'detalhes' => []
            ],
            'estatisticas' => [
                'status' => 'não verificado',
                'detalhes' => []
            ],
            'atividades' => [
                'status' => 'não verificado',
                'detalhes' => []
            ],
            'recursos_js' => [
                'status' => 'não verificado',
                'detalhes' => []
            ],
            'recursos_css' => [
                'status' => 'não verificado',
                'detalhes' => []
            ],
            'erros_encontrados' => [],
            'avisos' => [],
            'recomendacoes' => []
        ];
    }

    public static function verificarVariaveis($variaveis)
    {
        foreach ($variaveis as $nome => $valor) {
            self::$diagnostico['variaveis_disponiveis'][$nome] = [
                'disponivel' => isset($valor),
                'tipo' => isset($valor) ? gettype($valor) : 'não definido',
                'vazio' => isset($valor) ? empty($valor) : true
            ];

            if (!isset($valor)) {
                self::$diagnostico['erros_encontrados'][] = "Variável \${$nome} não está definida";
            } elseif (empty($valor)) {
                self::$diagnostico['avisos'][] = "Variável \${$nome} está vazia";
            }
        }
    }

    public static function verificarDadosGraficos($dadosGraficos)
    {
        if (!isset($dadosGraficos)) {
            self::$diagnostico['dados_graficos']['status'] = 'erro';
            self::$diagnostico['erros_encontrados'][] = 'Dados dos gráficos não definidos';
            return;
        }

        self::$diagnostico['dados_graficos']['status'] = 'verificado';

        $estruturaEsperada = [
            'movimentacoes' => ['labels', 'entradas', 'saidas'],
            'estoque' => ['marca', 'estoque']
        ];

        foreach ($estruturaEsperada as $secao => $campos) {
            if (!isset($dadosGraficos[$secao])) {
                self::$diagnostico['erros_encontrados'][] = "Seção '$secao' não encontrada nos dados dos gráficos";
                continue;
            }
        }
        if (isset($dadosGraficos['movimentacoes'])) {
            $mov = $dadosGraficos['movimentacoes'];
            self::$diagnostico['dados_graficos']['detalhes']['movimentacoes'] = [
                'labels_count' => count($mov['labels'] ?? []),
                'entradas_count' => count($mov['entradas'] ?? []),
                'saidas_count' => count($mov['saidas'] ?? []),
                'dados_consistentes' => (
                    isset($mov['labels']) &&
                    isset($mov['entradas']) &&
                    isset($mov['saidas']) &&
                    count($mov['labels']) === count($mov['entradas']) &&
                    count($mov['labels']) === count($mov['saidas'])
                ),
                'periodo_correto' => (
                    isset($mov['labels']) &&
                    count($mov['labels']) === 7
                )
            ];

            if (!self::$diagnostico['dados_graficos']['detalhes']['movimentacoes']['dados_consistentes']) {
                self::$diagnostico['erros_encontrados'][] = 'Inconsistência nos dados de movimentações';
            }

            if (!self::$diagnostico['dados_graficos']['detalhes']['movimentacoes']['periodo_correto']) {
                self::$diagnostico['avisos'][] = 'O período de movimentações não está mostrando os últimos 7 dias completos';
            }
        }

        if (isset($dadosGraficos['movimentacoes'])) {
            $mov = $dadosGraficos['movimentacoes'];
            self::$diagnostico['dados_graficos']['detalhes']['movimentacoes'] = [
                'labels_count' => count($mov['labels'] ?? []),
                'entradas_count' => count($mov['entradas'] ?? []),
                'saidas_count' => count($mov['saidas'] ?? []),
                'dados_consistentes' => (
                    isset($mov['labels']) &&
                    isset($mov['entradas']) &&
                    isset($mov['saidas']) &&
                    count($mov['labels']) === count($mov['entradas']) &&
                    count($mov['labels']) === count($mov['saidas'])
                )
            ];

            if (!self::$diagnostico['dados_graficos']['detalhes']['movimentacoes']['dados_consistentes']) {
                self::$diagnostico['erros_encontrados'][] = 'Inconsistência nos dados de movimentações';
            }
        }

        if (isset($dadosGraficos['estoque'])) {
            self::$diagnostico['dados_graficos']['detalhes']['estoque'] = [
                'total_marcas' => count($dadosGraficos['estoque']),
                'marcas_vazias' => 0,
                'estoque_zero' => 0
            ];

            foreach ($dadosGraficos['estoque'] as $item) {
                if (empty($item['marca'])) {
                    self::$diagnostico['dados_graficos']['detalhes']['estoque']['marcas_vazias']++;
                }
                if ($item['estoque'] == 0) {
                    self::$diagnostico['dados_graficos']['detalhes']['estoque']['estoque_zero']++;
                }
            }
        }
    }

    public static function verificarEstatisticas($stats)
    {
        if (!isset($stats)) {
            self::$diagnostico['estatisticas']['status'] = 'erro';
            self::$diagnostico['erros_encontrados'][] = 'Estatísticas não definidas';
            return;
        }

        self::$diagnostico['estatisticas']['status'] = 'verificado';

        $minimosEsperados = [
            'total_produtos' => 1,
            'estoque_total' => 10,
            'movimentacoes_hoje' => 0,
            'lucro_estimado' => 0
        ];

        $validacoes = [
            'total_produtos' => function ($valor) {
                return $valor >= 0;
            },
            'estoque_total' => function ($valor) {
                return $valor >= 0;
            },
            'movimentacoes_hoje' => function ($valor) {
                return $valor >= 0;
            },
            'lucro_estimado' => function ($valor) {
                return is_numeric($valor);
            }
        ];
        self::$diagnostico['estatisticas']['detalhes'] = [
            'campos_esperados' => [
                'total_produtos' => isset($stats['total_produtos']),
                'estoque_total' => isset($stats['estoque_total']),
                'movimentacoes_hoje' => isset($stats['movimentacoes_hoje']),
                'lucro_estimado' => isset($stats['lucro_estimado'])
            ],
            'valores' => [
                'total_produtos' => $stats['total_produtos'] ?? 'N/A',
                'estoque_total' => $stats['estoque_total'] ?? 'N/A',
                'movimentacoes_hoje' => $stats['movimentacoes_hoje'] ?? 'N/A',
                'lucro_estimado' => $stats['lucro_estimado'] ?? 'N/A'
            ]
        ];
        if (($stats['total_produtos'] ?? 0) === 0) {
            self::$diagnostico['avisos'][] = 'Total de produtos está zerado';
        }
        if (($stats['estoque_total'] ?? 0) === 0) {
            self::$diagnostico['avisos'][] = 'Estoque total está zerado';
        }
    }

    public static function verificarAtividades($atividades)
    {
        if (!isset($atividades)) {
            self::$diagnostico['atividades']['status'] = 'erro';
            self::$diagnostico['erros_encontrados'][] = 'Lista de atividades não definida';
            return;
        }

        self::$diagnostico['atividades']['status'] = 'verificado';

        $camposObrigatorios = [
            'entrada' => ['produto', 'quantidade', 'data', 'acao'],
            'saida' => ['produto', 'quantidade', 'data', 'acao', 'preco_venda', 'preco_custo']
        ];

        $validacoes = [
            'quantidade' => function ($valor) {
                return $valor > 0;
            },
            'data' => function ($valor) {
                return strtotime($valor) !== false &&
                    strtotime($valor) <= time();
            },
            'preco_venda' => function ($valor) {
                return $valor > 0;
            },
            'preco_custo' => function ($valor) {
                return $valor > 0;
            }
        ];
        self::$diagnostico['atividades']['detalhes'] = [
            'total' => count($atividades),
            'tipos' => [
                'entrada' => 0,
                'saida' => 0
            ],
            'campos_invalidos' => 0
        ];

        foreach ($atividades as $atividade) {
            if (isset($atividade['acao'])) {
                self::$diagnostico['atividades']['detalhes']['tipos'][$atividade['acao']]++;
            }

            $campos = ['produto', 'quantidade', 'data', 'acao'];

            foreach ($campos as $campo) {
                if (!isset($atividade[$campo]) || empty($atividade[$campo])) {
                    self::$diagnostico['atividades']['detalhes']['campos_invalidos']++;
                    break;
                }
            }
        }

        if (self::$diagnostico['atividades']['detalhes']['campos_invalidos'] > 0) {
            self::$diagnostico['avisos'][] = 'Existem atividades com campos obrigatórios faltando';
        }
    }

    public static function verificarRecursosJS()
    {
        $recursos = [
            'chart.js' => 'https://cdn.jsdelivr.net/npm/chart.js',
            'font-awesome' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
            'app.js' => BASE_URL . '/public/assets/js/app.js',
            'charts.js' => BASE_URL . '/public/assets/js/charts.js'
        ];

        self::$diagnostico['recursos_js']['status'] = 'verificado';
        self::$diagnostico['recursos_js']['detalhes'] = [];

        foreach ($recursos as $nome => $url) {
            $headers = @get_headers($url);
            $status = $headers ? substr($headers[0], 9, 3) : '000';

            self::$diagnostico['recursos_js']['detalhes'][$nome] = [
                'url' => $url,
                'status' => $status,
                'disponivel' => $status == '200'
            ];

            if ($status != '200') {
                self::$diagnostico['erros_encontrados'][] = "Recurso {$nome} não está acessível (Status: {$status})";
            }
        }
    }

    public static function finalizarDiagnostico()
    {
        self::$diagnostico['tempo_execucao'] = round(microtime(true) - self::$tempoInicio, 4);
        self::$diagnostico['memoria_usada'] = round(memory_get_peak_usage() / 1024 / 1024, 2);

        if (count(self::$diagnostico['erros_encontrados']) > 0) {
            self::$diagnostico['recomendacoes'][] = 'Corrija os erros críticos antes de continuar';
        }
        if (count(self::$diagnostico['avisos']) > 0) {
            self::$diagnostico['recomendacoes'][] = 'Revise os avisos para melhorar a qualidade dos dados';
        }
        if (self::$diagnostico['memoria_usada'] > 64) {
            self::$diagnostico['recomendacoes'][] = 'Considere otimizar o uso de memória';
        }

        $logDir = __DIR__ . '/../../armazenamento/logs/';
        $logFile = $logDir . 'diagnostico_dashboard_' . date('Y-m-d_H-i-s') . '.log';

        $conteudo = "=== DIAGNÓSTICO DO DASHBOARD ===\n";
        $conteudo .= "Data/Hora: " . self::$diagnostico['timestamp'] . "\n\n";

        foreach (self::$diagnostico as $secao => $dados) {
            if ($secao === 'timestamp') continue;

            $conteudo .= strtoupper($secao) . ":\n";
            $conteudo .= str_repeat('-', strlen($secao) + 1) . "\n";
            $conteudo .= print_r($dados, true) . "\n\n";
        }

        file_put_contents($logFile, $conteudo);

        LogHelper::registrar(
            'info',
            'Diagnóstico do Dashboard concluído',
            'Diagnóstico',
            "Arquivo: " . basename($logFile),
            [
                'erros' => count(self::$diagnostico['erros_encontrados']),
                'avisos' => count(self::$diagnostico['avisos']),
                'tempo_execucao' => self::$diagnostico['tempo_execucao'],
                'memoria_usada' => self::$diagnostico['memoria_usada']
            ]
        );
    }

    public static function getDiagnostico()
    {
        return self::$diagnostico;
    }
}
