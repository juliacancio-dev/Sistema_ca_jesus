let chartEntradas = null;
let chartSaidas = null;

function moeda(v) {
    return (v || 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
}

function formatDateTime(iso) {
    try {
        return new Date(iso).toLocaleString('pt-BR');
    } catch {
        return iso;
    }
}

function filtrosAtuais() {
    const periodo = document.getElementById('select-periodo').value;
    const dataInicio = document.getElementById('data-inicio').value;
    const dataFim = document.getElementById('data-fim').value;
    const params = new URLSearchParams({
        periodo
    });
    if (dataInicio && dataFim) {
        params.set('data_inicio', dataInicio);
        params.set('data_fim', dataFim);
    }
    return params;
}

function periodoTexto() {
    const periodo = document.getElementById('select-periodo').value;
    const di = document.getElementById('data-inicio').value;
    const df = document.getElementById('data-fim').value;
    const b = s => s ? s.split('-').reverse().join('/') : '-';
    const base = di && df ? `${b(di)} a ${b(df)}` : periodo.charAt(0).toUpperCase() + periodo.slice(1);
    return `${base} (${periodo})`;
}

function atualizarPeriodoLabels() {
    const label = periodoTexto();
    const now = new Date().toLocaleString('pt-BR');
    const periode = document.getElementById('label-periodo-global');
    if (periode) periode.textContent = label;
    const ge = document.getElementById('label-gerado-em');
    if (ge) ge.textContent = now;
}

function carregarRelatorios() {
    const mostrarFin = document.getElementById('cb-financeiro').checked;
    const mostrarEst = document.getElementById('cb-estoque').checked;

    document.getElementById('card-financeiro').classList.toggle('hidden', !mostrarFin);
    document.getElementById('card-estoque').classList.toggle('hidden', !mostrarEst);

    const grid = document.getElementById('grid-relatorios');
    if (mostrarFin && mostrarEst) {
        grid.classList.remove('md:grid-cols-1');
        grid.classList.add('md:grid-cols-2');
    } else {
        grid.classList.remove('md:grid-cols-2');
        grid.classList.add('md:grid-cols-1');
    }

    const params = filtrosAtuais();

    if (mostrarFin) {
        const pFin = new URLSearchParams(params);
        pFin.set('tipo', 'financeiro');
        requisicaoJson(APP_BASE_URL + '/api/relatorios/dados?' + pFin.toString())
            .then(json => {
                if (!json || !json.dados) return;
                window.__dados_financeiro = json.dados;
                preencherFinanceiro(json.dados);
                atualizarPeriodoLabels();
            })
            .catch(err => console.error('Erro ao carregar relatório financeiro:', err));
    }

    if (mostrarEst) {
        const pEst = new URLSearchParams(params);
        pEst.set('tipo', 'estoque');
        requisicaoJson(APP_BASE_URL + '/api/relatorios/dados?' + pEst.toString())
            .then(json => {
                if (!json || !json.dados) return;
                window.__dados_estoque = json.dados;
                preencherEstoque(json.dados);
                atualizarPeriodoLabels();
            })
            .catch(err => console.error('Erro ao carregar relatório de estoque:', err));
    }
}

function preencherFinanceiro(dados) {
    const {
        resumo,
        series,
        entradas_detalhadas,
        saidas_detalhadas,
        comparativo
    } = dados;
    document.getElementById('fin-saldo').textContent = moeda(resumo.saldo_final);

    const entTbody = document.getElementById('fin-entradas-tbody');
    entTbody.innerHTML = '';
    (entradas_detalhadas || []).forEach(e => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td class="px-2 py-2">${formatDateTime(e.data)}</td>
                    <td class="px-2 py-2">${escapeHtml(e.descricao)}</td>
                    <td class="px-2 py-2 text-right">${moeda(e.valor)}</td>`;
        entTbody.appendChild(tr);
    });

    const saiTbody = document.getElementById('fin-saidas-tbody');
    saiTbody.innerHTML = '';
    (saidas_detalhadas || []).forEach(e => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td class="px-2 py-2">${formatDateTime(e.data)}</td>
                    <td class="px-2 py-2">${escapeHtml(e.descricao)}</td>
                    <td class="px-2 py-2 text-right">${moeda(e.valor)}</td>`;
        saiTbody.appendChild(tr);
    });

    const labels = ['Total'];
    const dadosEntradas = [(series?.receitas || []).reduce((sum, p) => sum + (p.valor || 0), 0)];
    const dadosSaidas = [(series?.despesas || []).reduce((sum, p) => sum + (p.valor || 0), 0)];

    const ctxR = document.getElementById('chart-receitas').getContext('2d');
    if (chartEntradas) chartEntradas.destroy();
    chartEntradas = new Chart(ctxR, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Vendas (saídas de estoque)',
                data: dadosEntradas,
                backgroundColor: '#16a34a',
                borderColor: '#16a34a'
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    const ctxD = document.getElementById('chart-despesas').getContext('2d');
    if (chartSaidas) chartSaidas.destroy();
    chartSaidas = new Chart(ctxD, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Compras (entradas de estoque)',
                data: dadosSaidas,
                backgroundColor: '#dc2626',
                borderColor: '#dc2626'
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

}

function preencherEstoque(dados) {
    const {
        resumo,
        produtos,
        entradas_estoque,
        saidas_estoque,
        historico_movimentacoes,
        produtos_mais_vendidos
    } = dados;

    document.getElementById('est-qtde-total').textContent = resumo?.quantidade_total_estoque || 0;

    const prodTbody = document.getElementById('est-produtos-tbody');
    prodTbody.innerHTML = '';
    (produtos || []).slice().sort((a, b) => Number(a.codigo) - Number(b.codigo)).forEach(p => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td class="px-2 py-2">${escapeHtml(p.codigo)}</td>
                    <td class="px-2 py-2">${escapeHtml(p.nome)}</td>
                    <td class="px-2 py-2 text-right">${p.estoque_atual ?? ''}</td>`;
        prodTbody.appendChild(tr);
    });

    const entTbody = document.getElementById('est-entradas-tbody');
    entTbody.innerHTML = '';
    (entradas_estoque || []).forEach(e => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td class="px-2 py-2">${formatDateTime(e.data)}</td>
                    <td class="px-2 py-2">${escapeHtml(e.produto)}</td>
                    <td class="px-2 py-2 text-right">${e.quantidade}</td>`;
        entTbody.appendChild(tr);
    });

    const saiTbody = document.getElementById('est-saidas-tbody');
    saiTbody.innerHTML = '';
    (saidas_estoque || []).forEach(e => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td class="px-2 py-2">${formatDateTime(e.data)}</td>
                    <td class="px-2 py-2">${escapeHtml(e.produto)}</td>
                    <td class="px-2 py-2 text-right">${e.quantidade}</td>`;
        saiTbody.appendChild(tr);
    });

    const histTbody = document.getElementById('est-historico-tbody');
    histTbody.innerHTML = '';
    (historico_movimentacoes || []).forEach(h => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td class="px-2 py-2">${formatDateTime(h.data)}</td>
                    <td class="px-2 py-2">${escapeHtml(h.produto)}</td>
                    <td class="px-2 py-2">${escapeHtml(h.tipo)}</td>
                    <td class="px-2 py-2 text-right">${h.quantidade}</td>
                    <td class="px-2 py-2">${escapeHtml(h.observacao)}</td>`;
        histTbody.appendChild(tr);
    });

    const topTbody = document.getElementById('est-top-tbody');
    topTbody.innerHTML = '';
    (produtos_mais_vendidos || []).forEach(t => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td class="px-2 py-2">${escapeHtml(t.produto)}</td>
                    <td class="px-2 py-2 text-right">${t.quantidade}</td>`;
        topTbody.appendChild(tr);
    });
}

async function exportPDF(tipo) {
    const containerId = tipo === 'financeiro' ? 'card-financeiro' : 'card-estoque';
    const container = document.getElementById(containerId);
    if (!container) return;

    const meta = document.createElement('div');
    meta.className = 'pdf-meta flex items-center justify-between';
    const periodoTxt = periodoTexto();
    const agora = new Date().toLocaleString('pt-BR');
    meta.innerHTML = `
    <div>
        <h2 style="font-weight:700;">${tipo === 'financeiro' ? 'Relatório Financeiro' : 'Relatório de Estoque'}</h2>
        <small>Período: ${periodoTxt}</small>
    </div>
    <div class="text-right">
        <small>Sistema CA Jesus</small><br>
        <small>Gerado em ${agora}</small>
    </div>
`;
    container.prepend(meta);

    const footer = document.createElement('div');
    footer.className = 'export-footer text-right';
    footer.innerHTML = '<span>Documento gerado automaticamente pelo sistema · confidencial</span>';
    container.appendChild(footer);

    const _toHide = container.querySelectorAll('.export-controls, .no-export, [data-export-hide="true"]');
    const _prevDisplay = [];
    _toHide.forEach((el, i) => {
        _prevDisplay[i] = el.style.display;
        el.style.display = 'none';
    });

    try {
        const {
            jsPDF
        } = window.jspdf;
        const canvas = await html2canvas(container, {
            scale: 2,
            useCORS: true,
            scrollY: 0,
            backgroundColor: '#ffffff'
        });
        const pdf = new jsPDF('p', 'mm', 'a4');
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        const margins = {
            left: 10,
            right: 10,
            top: 12,
            bottom: 18,
            footer: 8
        };
        const usablePageHeight = pageHeight - margins.top - margins.bottom - margins.footer;
        const imgWidth = pageWidth - margins.left - margins.right; 

        const pxPerMm = canvas.width / imgWidth; 
        const pageHeightPx = Math.floor(usablePageHeight * pxPerMm);

        let y = 0;
        let pageIndex = 0;
        while (y < canvas.height) {
            const sliceHeight = Math.min(pageHeightPx, canvas.height - y);
            const sliceCanvas = document.createElement('canvas');
            sliceCanvas.width = canvas.width;
            sliceCanvas.height = sliceHeight;
            const sctx = sliceCanvas.getContext('2d');
            sctx.drawImage(canvas, 0, y, canvas.width, sliceHeight, 0, 0, canvas.width, sliceHeight);
            const sliceImg = sliceCanvas.toDataURL('image/png');

            if (pageIndex > 0) pdf.addPage();
            const sliceHeightMm = sliceHeight / pxPerMm;
            pdf.addImage(sliceImg, 'PNG', margins.left, margins.top, imgWidth, sliceHeightMm);

            pdf.setFillColor(255, 255, 255);
            pdf.rect(0, pageHeight - (margins.bottom + margins.footer), pageWidth, (margins.bottom + margins.footer), 'F');

            y += sliceHeight;
            pageIndex++;
        }

        const totalPages = pdf.getNumberOfPages();
        for (let i = 1; i <= totalPages; i++) {
            pdf.setPage(i);
            pdf.setFontSize(9);
            pdf.setTextColor(100);
            pdf.text(`Página ${i} de ${totalPages}`, pageWidth - 20, pageHeight - 5, {
                align: 'right'
            });
        }

        const filename = tipo === 'financeiro' ? 'relatorio_financeiro.pdf' : 'relatorio_estoque.pdf';
        pdf.save(filename);
    } catch (e) {
        console.error('Erro ao exportar PDF:', e);
        showToast('Não foi possível exportar para PDF.', 'error');
    } finally {
        _toHide.forEach((el, i) => {
            el.style.display = _prevDisplay[i] || '';
        });
        meta.remove();
        footer.remove();
    }
}

function exportExcel(tipo) {
    try {
        const dados = tipo === 'financeiro' ? window.__dados_financeiro : window.__dados_estoque;
        if (!dados) {
            showToast('Carregue o relatório antes de exportar.', 'warning');
            return;
        }
        const loadingToast = showToast('Gerando arquivo Excel...', 'info', 0);
        const wb = XLSX.utils.book_new();

        function addFormattedSheet(name, data, options = {}) {
            if (!data || data.length === 0) {
                const ws = XLSX.utils.aoa_to_sheet([
                    ['Nenhum dado disponível']
                ]);
                ws['!cols'] = [{
                    wch: 24
                }];
                XLSX.utils.book_append_sheet(wb, ws, name);
                return;
            }
            const ws = XLSX.utils.json_to_sheet(data);
            try {
                const range = XLSX.utils.decode_range(ws['!ref']);
                const cols = [];
                for (let C = range.s.c; C <= range.e.c; ++C) {
                    let maxW = 15;
                    for (let R = range.s.r; R <= range.e.r; ++R) {
                        const cell = ws[XLSX.utils.encode_cell({
                            r: R,
                            c: C
                        })];
                        if (cell && cell.v != null) {
                            const v = String(cell.v);
                            maxW = Math.max(maxW, Math.min(v.length + 4, 60));
                        }
                    }
                    cols.push({
                        wch: maxW
                    });
                }
                ws['!cols'] = cols;
            } catch (err) {
            }
            XLSX.utils.book_append_sheet(wb, ws, name);
        }

        if (tipo === 'financeiro') {
            const res = dados.resumo || {};
            const resumoData = [{
                    'INDICADOR': 'Saldo Final',
                    'VALOR': res.saldo_final || 0
                },
                {
                    'INDICADOR': 'Total Vendas (saídas)',
                    'VALOR': res.total_receitas || res.receitas || 0
                },
                {
                    'INDICADOR': 'Total Compras (entradas)',
                    'VALOR': res.total_despesas || res.despesas || 0
                },
            ];
            addFormattedSheet('Resumo Financeiro', resumoData);

            const entradasFormatadas = (dados.entradas_detalhadas || []).map(e => ({
                'DATA': formatDateTime(e.data),
                'DESCRIÇÃO': e.descricao,
                'VALOR': e.valor
            }));
            addFormattedSheet('Vendas (saídas)', entradasFormatadas);
            const saidasFormatadas = (dados.saidas_detalhadas || []).map(e => ({
                'DATA': formatDateTime(e.data),
                'DESCRIÇÃO': e.descricao,
                'VALOR': e.valor
            }));
            addFormattedSheet('Compras (entradas)', saidasFormatadas);
        } else {
            const resumoEstoque = [{
                    'INDICADOR': 'Quantidade Total em Estoque',
                    'VALOR': dados.resumo?.quantidade_total_estoque || 0
                },
                {
                    'INDICADOR': 'Total de Produtos',
                    'VALOR': (dados.produtos || []).length
                },
            ];
            addFormattedSheet('Resumo Estoque', resumoEstoque);

            const produtosFormatados = (dados.produtos || []).slice().sort((a, b) => Number(a.codigo) - Number(b.codigo)).map(p => ({
                'CÓDIGO': p.codigo,
                'NOME': p.nome,
                'ESTOQUE ATUAL': p.estoque_atual || 0
            }));
            addFormattedSheet('Produtos', produtosFormatados);

            const entradasEstoque = (dados.entradas_estoque || []).map(e => ({
                'DATA': formatDateTime(e.data),
                'PRODUTO': e.produto,
                'QUANTIDADE': e.quantidade
            }));
            addFormattedSheet('Entradas', entradasEstoque);

            const saidasEstoque = (dados.saidas_estoque || []).map(e => ({
                'DATA': formatDateTime(e.data),
                'PRODUTO': e.produto,
                'QUANTIDADE': e.quantidade
            }));
            addFormattedSheet('Saídas', saidasEstoque);
        }

        const filename = `${tipo === 'financeiro' ? 'relatorio_financeiro' : 'relatorio_estoque'}_${new Date().toISOString().slice(0,10)}.xlsx`;
        XLSX.writeFile(wb, filename);
        if (loadingToast) loadingToast.remove();
        showToast('Arquivo Excel exportado com sucesso!', 'success');
    } catch (e) {
        console.error('Erro ao exportar Excel:', e);
        showToast('Não foi possível exportar para Excel. Verifique os dados e tente novamente.', 'error');
    }
}

document.getElementById('btn-aplicar').addEventListener('click', carregarRelatorios);

document.getElementById('cb-financeiro').addEventListener('change', carregarRelatorios);

document.getElementById('cb-estoque').addEventListener('change', carregarRelatorios);

const btnPdfFin = document.getElementById('btn-export-pdf-fin');
const btnXlsFin = document.getElementById('btn-export-excel-fin');
const btnPdfEst = document.getElementById('btn-export-pdf-est');
const btnXlsEst = document.getElementById('btn-export-excel-est');

if (btnPdfFin) btnPdfFin.addEventListener('click', () => exportPDF('financeiro'));
if (btnXlsFin) btnXlsFin.addEventListener('click', () => exportExcel('financeiro'));
if (btnPdfEst) btnPdfEst.addEventListener('click', () => exportPDF('estoque'));
if (btnXlsEst) btnXlsEst.addEventListener('click', () => exportExcel('estoque'));


document.addEventListener('DOMContentLoaded', () => {
    const hoje = new Date();
    const inicio = new Date(hoje.getFullYear(), hoje.getMonth(), 1).toISOString().slice(0, 10);
    const fim = hoje.toISOString().slice(0, 10);
    document.getElementById('data-inicio').value = inicio;
    document.getElementById('data-fim').value = fim;
    carregarRelatorios();
});
