// Sistema de Gestão de Estoque - C.A de Jesus
// Gerenciamento dos gráficos

// evita carregar duas vezes
if (typeof window.ChartManager === 'undefined') {

// configs globais do Chart.js
if (typeof Chart !== 'undefined') {
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    Chart.defaults.color = '#6B7280';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.elements.line.borderCapStyle = 'round';
    Chart.defaults.elements.line.borderJoinStyle = 'round';
    Chart.defaults.elements.point.hoverBorderWidth = 2.5;
    Chart.defaults.elements.bar.borderSkipped = false;
    Chart.defaults.animation.duration = 700;
    Chart.defaults.animation.easing = 'easeOutQuart';

    try {
        const beautifyPlugin = {
            id: 'beautifyShadow',
            beforeDatasetDraw(chart, args) {
                const ctx = chart.ctx;
                ctx.save();
                ctx.shadowColor = 'rgba(0,0,0,0.12)';
                ctx.shadowBlur = 8;
                ctx.shadowOffsetY = 4;
                ctx.shadowOffsetX = 0;
            },
            afterDatasetDraw(chart, args) {
                chart.ctx.restore();
            }
        };
        Chart.register(beautifyPlugin);
    } catch (e) {
        console.warn('Plugin beautifyShadow não pôde ser registrado:', e);
    }
}

// classe pra gerenciar os gráficos
window.ChartManager = class ChartManager {
    constructor() {
        this.charts = {};
        this.colors = {
            primary: '#3B82F6',
            success: '#10B981',
            warning: '#F59E0B',
            danger: '#EF4444',
            info: '#06B6D4',
            purple: '#8B5CF6',
            orange: '#F97316',
            lime: '#84CC16'
        };
        
        this.colorPalette = [
            this.colors.primary,
            this.colors.success,
            this.colors.warning,
            this.colors.danger,
            this.colors.info,
            this.colors.purple,
            this.colors.orange,
            this.colors.lime
        ];
    }
    
    // cria o gráfico de movimentações (entradas e saídas)
    createMovimentacoesChart(canvasId, data) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) {
            console.error(`Canvas '${canvasId}' não encontrado`);
            return null;
        }
        
        if (this.charts[canvasId]) {
            this.charts[canvasId].destroy();
        }

        // Gradientes para as linhas
        const gradientIn = ctx.getContext('2d').createLinearGradient(0, 0, 0, ctx.height || 200);
        gradientIn.addColorStop(0, this.colors.success + '55');
        gradientIn.addColorStop(1, this.colors.success + '08');
        const gradientOut = ctx.getContext('2d').createLinearGradient(0, 0, 0, ctx.height || 200);
        gradientOut.addColorStop(0, this.colors.danger + '55');
        gradientOut.addColorStop(1, this.colors.danger + '08');
        
        const config = {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        label: 'Entradas',
                        data: data.entradas || [],
                        borderColor: this.colors.success,
                        backgroundColor: gradientIn,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.45,
                        cubicInterpolationMode: 'monotone',
                        pointBackgroundColor: '#fff',
                        pointBorderColor: this.colors.success,
                        pointBorderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        pointHoverBorderWidth: 2.5
                    },
                    {
                        label: 'Saídas',
                        data: data.saidas || [],
                        borderColor: this.colors.danger,
                        backgroundColor: gradientOut,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.45,
                        cubicInterpolationMode: 'monotone',
                        pointBackgroundColor: '#fff',
                        pointBorderColor: this.colors.danger,
                        pointBorderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        pointHoverBorderWidth: 2.5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 8, right: 12, bottom: 4, left: 8 } },
                interaction: { intersect: false, mode: 'index' },
                animation: { duration: 900, easing: 'easeOutQuart' },
                plugins: {
                    legend: {
                        position: 'top', align: 'end',
                        labels: { boxWidth: 10, boxHeight: 10, padding: 12 }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.9)',
                        titleColor: '#e5e7eb',
                        titleFont: { weight: '600' },
                        bodyColor: '#e5e7eb',
                        bodyFont: { size: 12 },
                        borderColor: '#1f2937',
                        borderWidth: 1,
                        cornerRadius: 10,
                        padding: 12,
                        displayColors: false,
                        caretSize: 6,
                        caretPadding: 8,
                        callbacks: {
                            title: (context) => 'Data: ' + context[0].label,
                            label: (context) => context.dataset.label + ': ' + context.parsed.y + ' unidades'
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { maxRotation: 0 }
                    },
                    y: {
                        beginAtZero: true,
                        min: 0,
                        max: 2000,
                        grid: { color: '#eef2f7' },
                        border: { display: false },
                        ticks: { stepSize: 500, callback: (v) => v + ' un' }
                    }
                }
            }
        };
        
        this.charts[canvasId] = new Chart(ctx, config);
        return this.charts[canvasId];
    }
    
    // cria gráfico de barras Top 5 do estoque (vertical)
    createEstoqueChart(canvasId, data) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) {
            console.error(`Canvas '${canvasId}' não encontrado`);
            return null;
        }
        
        if (this.charts[canvasId]) {
            this.charts[canvasId].destroy();
        }
        
        const items = Array.isArray(data) ? data.slice() : [];
        items.sort((a, b) => (parseFloat(b.estoque || b.quantidade || 0) - parseFloat(a.estoque || a.quantidade || 0)));
        const top = items.slice(0, 5);
        const labels = top.map(item => item.marca || item.nome || 'Sem nome');
        const values = top.map(item => item.estoque || item.quantidade || 0);
        const colors = this.generateColors(labels.length);
        
        const config = {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Estoque',
                    data: values,
                    backgroundColor: (context) => {
                        const {chart, dataIndex} = context;
                        const area = chart.chartArea;
                        const base = colors[dataIndex % colors.length];
                        if (!area) return base + 'CC';
                        const grad = chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
                        grad.addColorStop(0, base + '99');
                        grad.addColorStop(1, base + '33');
                        return grad;
                    },
                    borderColor: (context) => colors[context.dataIndex % colors.length],
                    borderWidth: 1.5,
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 32
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 8, right: 12, bottom: 4, left: 8 } },
                animation: { duration: 900, easing: 'easeOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.9)',
                        titleColor: '#e5e7eb',
                        titleFont: { weight: '600' },
                        bodyColor: '#e5e7eb',
                        bodyFont: { size: 12 },
                        borderColor: '#1f2937',
                        borderWidth: 1,
                        cornerRadius: 10,
                        padding: 12,
                        displayColors: false,
                        caretSize: 6,
                        caretPadding: 8,
                        callbacks: {
                            label: (context) => context.parsed.y + ' unidades'
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { autoSkip: false }
                    },
                    y: {
                        beginAtZero: true,
                        min: 0,
                        max: 300,
                        grid: { color: '#eef2f7' },
                        border: { display: false },
                        ticks: { stepSize: 50, callback: (v) => v + ' un' }
                    }
                }
            }
        };
        
        this.charts[canvasId] = new Chart(ctx, config);
        return this.charts[canvasId];
    }
    
    // cria gráfico de barras
    createBarChart(canvasId, data, options = {}) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) {
            console.error(`Canvas '${canvasId}' não encontrado`);
            return null;
        }
        
        // se já existe, destrói antes
        if (this.charts[canvasId]) {
            this.charts[canvasId].destroy();
        }
        
        const config = {
            type: 'bar',
            data: {
                labels: data.labels || [],
                datasets: [{
                    label: options.label || 'Dados',
                    data: data.values || [],
                    backgroundColor: this.colors.primary + '80',
                    borderColor: this.colors.primary,
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: options.showLegend !== false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: '#374151',
                        borderWidth: 1,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#F3F4F6'
                        },
                        border: {
                            display: false
                        }
                    }
                }
            }
        };
        
        this.charts[canvasId] = new Chart(ctx, config);
        return this.charts[canvasId];
    }
    
    // gera cores pros gráficos
    generateColors(count) {
        const colors = [];
        for (let i = 0; i < count; i++) {
            colors.push(this.colorPalette[i % this.colorPalette.length]);
        }
        return colors;
    }
    
    // atualiza os dados de um gráfico
    updateChart(canvasId, newData) {
        const chart = this.charts[canvasId];
        if (!chart) {
            console.error(`Gráfico '${canvasId}' não encontrado`);
            return;
        }
        
        if (newData.labels) {
            chart.data.labels = newData.labels;
        }
        
        if (newData.datasets) {
            chart.data.datasets = newData.datasets;
        } else if (newData.values) {
            chart.data.datasets[0].data = newData.values;
        }
        
        chart.update();
    }
    
    // destrói um gráfico
    destroyChart(canvasId) {
        if (this.charts[canvasId]) {
            this.charts[canvasId].destroy();
            delete this.charts[canvasId];
        }
    }
    
    // destrói todos os gráficos
    destroyAllCharts() {
        Object.keys(this.charts).forEach(canvasId => {
            this.destroyChart(canvasId);
        });
    }
    
    // redimensiona os gráficos
    resizeCharts() {
        Object.values(this.charts).forEach(chart => {
            chart.resize();
        });
    }
};

// instância global do gerenciador
if (!window.chartManager) {
    window.chartManager = new window.ChartManager();
}

// inicializa os gráficos do dashboard
window.initializeDashboardCharts = function(dadosGraficos) {
    if (!dadosGraficos) {
        console.error('Cadê os dados dos gráficos?');
        return;
    }
    if (dadosGraficos.movimentacoes) {
        window.chartManager.createMovimentacoesChart('movimentacoes-chart', dadosGraficos.movimentacoes);
    }
    if (dadosGraficos.estoque) {
        window.chartManager.createEstoqueChart('estoque-chart', dadosGraficos.estoque);
    }
};

// cria gráfico de vendas (pra relatórios)
window.createVendasChart = function(canvasId, data) {
    return window.chartManager.createBarChart(canvasId, data, {
        label: 'Vendas',
        showLegend: true
    });
};

// cria gráfico de lucro (pra relatórios)
window.createLucroChart = function(canvasId, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels || [],
            datasets: [{
                label: 'Lucro',
                data: data.values || [],
                borderColor: window.chartManager.colors.success,
                backgroundColor: window.chartManager.colors.success + '20',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Lucro: R$ ' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toFixed(2);
                        }
                    }
                }
            }
        }
    });
};

// redimensiona os gráficos quando a janela muda de tamanho
window.addEventListener('resize', function() {
    if (window.chartManager) {
        window.chartManager.resizeCharts();
    }
});

// limpa os gráficos quando sair da página
window.addEventListener('beforeunload', function() {
    if (window.chartManager) {
        window.chartManager.destroyAllCharts();
    }
});

} // fim da verificação de carregamento único

console.log('Charts.js carregado! ChartManager disponível:', typeof window.ChartManager !== 'undefined');