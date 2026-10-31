document.addEventListener('DOMContentLoaded', function() {
    const dadosGraficos = JSON.parse(document.getElementById('dados-graficos').textContent);

    if (dadosGraficos && typeof window.chartManager !== 'undefined') {
        if (dadosGraficos.movimentacoes) {
            window.chartManager.createMovimentacoesChart('movimentacoes-chart', dadosGraficos.movimentacoes);
        }
        if (dadosGraficos.estoque) {
            window.chartManager.createEstoqueChart('estoque-chart', dadosGraficos.estoque);
        }
    } else {
        console.warn('ChartManager indisponível, usando fallback básico.');
        if (dadosGraficos && typeof Chart !== 'undefined') {
            const ctx1 = document.getElementById('movimentacoes-chart');
            if (ctx1 && dadosGraficos.movimentacoes) {
                new Chart(ctx1, {
                    type: 'line',
                    data: {
                        labels: dadosGraficos.movimentacoes.labels || [],
                        datasets: [{
                            label: 'Entradas',
                            data: dadosGraficos.movimentacoes.entradas || [],
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            tension: 0.3,
                            fill: true
                        }, {
                            label: 'Saídas',
                            data: dadosGraficos.movimentacoes.saidas || [],
                            borderColor: 'rgb(239, 68, 68)',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            const ctx2 = document.getElementById('estoque-chart');
            if (ctx2 && dadosGraficos.estoque) {
                const items = Array.isArray(dadosGraficos.estoque) ? dadosGraficos.estoque.slice() : [];
                items.sort((a, b) => (parseFloat(b.estoque || b.quantidade || 0) - parseFloat(a.estoque || a.quantidade || 0)));
                const top = items.slice(0, 5);
                new Chart(ctx2, {
                    type: 'bar',
                    data: {
                        labels: top.map(e => e.marca || e.nome || 'Sem nome'),
                        datasets: [{
                            label: 'Estoque',
                            data: top.map(e => e.estoque || e.quantidade || 0),
                            backgroundColor: ['#3B82F6CC', '#10B981CC', '#F59E0BCC', '#EF4444CC', '#8B5CF6CC'],
                            borderColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
                            borderWidth: 1.5,
                            borderRadius: 6,
                            borderSkipped: false,
                            maxBarThickness: 32
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0,0,0,0.8)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: '#374151',
                                borderWidth: 1,
                                cornerRadius: 8,
                                callbacks: {
                                    label: (ctx) => ctx.parsed.y + ' unidades'
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                border: {
                                    display: false
                                },
                                ticks: {
                                    autoSkip: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#F3F4F6'
                                },
                                border: {
                                    display: false
                                },
                                ticks: {
                                    callback: (v) => v + ' un'
                                }
                            }
                        }
                    }
                });
            }
        }
    }
});
