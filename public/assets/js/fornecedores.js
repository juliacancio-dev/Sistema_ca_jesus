        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('fornecedor-modal');
            const newBtn = document.getElementById('new-fornecedor-btn');
            const closeBtn = document.getElementById('close-fornecedor-modal');
            const form = document.getElementById('fornecedor-form');
            const searchInput = document.getElementById('search-fornecedor');
            const clearSearchBtn = document.getElementById('clear-search');
            const filterStatus = document.getElementById('filter-status');

            if (newBtn) {
                newBtn.addEventListener('click', () => openModal());
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', () => closeModal());
            }

            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal();
                }
            });

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const term = this.value.toLowerCase();
                    if (term) {
                        clearSearchBtn.classList.remove('hidden');
                    } else {
                        clearSearchBtn.classList.add('hidden');
                    }
                    filterTable();
                });
            }

            if (clearSearchBtn) {
                clearSearchBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    this.classList.add('hidden');
                    filterTable();
                });
            }

            if (filterStatus) {
                filterStatus.addEventListener('change', filterTable);
            }

            const cepInput = document.getElementById('fornecedor-cep');
            if (cepInput) {
                cepInput.addEventListener('blur', function() {
                    const cep = this.value.replace(/\D/g, '');
                    if (cep.length === 8) {
                        buscarCep(cep, 'fornecedor-');
                    }
                });
            }

            setupInputMasks();

            function openModal(fornecedor = null) {
                resetForm();

                if (fornecedor) {
                    document.getElementById('fornecedor-modal-title').textContent = 'Editar Fornecedor';
                    document.getElementById('submit-text').textContent = 'Atualizar Fornecedor';

                    fillForm(fornecedor);
                } else {
                    document.getElementById('fornecedor-modal-title').textContent = 'Novo Fornecedor';
                    document.getElementById('submit-text').textContent = 'Criar Fornecedor';
                }

                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
                resetForm();
            }

            function resetForm() {
                form.reset();
                clearErrors();
                document.getElementById('fornecedor-id').value = '';
            }

            function fillForm(fornecedor) {
                document.getElementById('fornecedor-id').value = fornecedor.id;
                document.getElementById('fornecedor-nome').value = fornecedor.nome;
                document.getElementById('fornecedor-cnpj').value = formatCnpj(fornecedor.cnpj);
                document.getElementById('fornecedor-email').value = fornecedor.email;
                document.getElementById('fornecedor-telefone').value = formatTelefone(fornecedor.telefone);
                document.getElementById('fornecedor-cep').value = formatCep(fornecedor.cep);
                document.getElementById('fornecedor-endereco').value = fornecedor.endereco;
                document.getElementById('fornecedor-numero').value = fornecedor.numero;
                document.getElementById('fornecedor-complemento').value = fornecedor.complemento || '';
                document.getElementById('fornecedor-bairro').value = fornecedor.bairro;
                document.getElementById('fornecedor-cidade').value = fornecedor.cidade;
                document.getElementById('fornecedor-estado').value = fornecedor.estado;
            }

            function validateForm() {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        showFieldError(field, 'Este campo é obrigatório');
                        isValid = false;
                    } else {
                        hideFieldError(field);

                        if (field.name === 'email' && !validateEmail(field.value)) {
                            showFieldError(field, 'Email inválido');
                            isValid = false;
                        } else if (field.name === 'cnpj' && !validateCNPJ(field.value)) {
                            showFieldError(field, 'CNPJ inválido');
                            isValid = false;
                        } else if (field.name === 'cep' && !validateCEP(field.value)) {
                            showFieldError(field, 'CEP inválido');
                            isValid = false;
                        }
                    }
                });

                return isValid;
            }

            function showFieldError(field, message) {
                const errorDiv = field.parentNode.querySelector('.error-message');
                if (errorDiv) {
                    errorDiv.textContent = message;
                    errorDiv.classList.remove('hidden');
                }
                field.classList.add('border-red-500');
            }

            function hideFieldError(field) {
                const errorDiv = field.parentNode.querySelector('.error-message');
                if (errorDiv) {
                    errorDiv.classList.add('hidden');
                }
                field.classList.remove('border-red-500');
            }

            function clearErrors() {
                document.querySelectorAll('.error-message').forEach(error => {
                    error.classList.add('hidden');
                });
                document.querySelectorAll('.border-red-500').forEach(field => {
                    field.classList.remove('border-red-500');
                });
            }

            function setupInputMasks() {
                mascaraCnpj(document.getElementById('fornecedor-cnpj'));
                mascaraTelefone(document.getElementById('fornecedor-telefone'));
                mascaraCep(cepInput);
            }

            function filterTable() {
                const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
                const statusFilter = filterStatus ? filterStatus.value : '';

                const rows = document.querySelectorAll('.fornecedor-row');
                let visibleCount = 0;

                rows.forEach(row => {
                    const nome = row.dataset.nome || '';
                    const email = row.dataset.email || '';
                    const cnpj = row.dataset.cnpj || '';
                    const status = row.dataset.status || '';

                    const matchesSearch = !searchTerm ||
                        nome.includes(searchTerm) ||
                        email.includes(searchTerm) ||
                        cnpj.includes(searchTerm);

                    const matchesStatus = !statusFilter || status === statusFilter;

                    if (matchesSearch && matchesStatus) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                const emptyState = document.getElementById('empty-state');
                if (emptyState) {
                    emptyState.style.display = visibleCount === 0 ? '' : 'none';
                }
            }

            function validateEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }

            function validateCNPJ(cnpj) {
                cnpj = cnpj.replace(/[^\d]+/g, '');
                if (cnpj == '') return false;
                if (cnpj.length != 14) return false;
                if (/^(\d)\1+$/.test(cnpj)) return false;

                let tamanho = cnpj.length - 2
                let numeros = cnpj.substring(0, tamanho);
                let digitos = cnpj.substring(tamanho);
                let soma = 0;
                let pos = tamanho - 7;
                for (let i = tamanho; i >= 1; i--) {
                    soma += numeros.charAt(tamanho - i) * pos--;
                    if (pos < 2) pos = 9;
                }
                let resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
                if (resultado != digitos.charAt(0)) return false;

                tamanho = tamanho + 1;
                numeros = cnpj.substring(0, tamanho);
                soma = 0;
                pos = tamanho - 7;
                for (let i = tamanho; i >= 1; i--) {
                    soma += numeros.charAt(tamanho - i) * pos--;
                    if (pos < 2) pos = 9;
                }
                resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
                if (resultado != digitos.charAt(1)) return false;

                return true;
            }

            function validateCEP(cep) {
                cep = cep.replace(/\D/g, '');
                return cep.length === 8;
            }

            form.addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                }
            });

            window.openFornecedorModal = openModal;
        });

        function viewFornecedor(id) {
            const row = document.querySelector(`.fornecedor-row[data-id='${id}']`);
            if (!row) {
                alert('Fornecedor não encontrado.');
                return;
            }
            const fornecedor = {
                id: id,
                nome: row.dataset.nome || '',
                cnpj: row.dataset.cnpj || '',
                email: row.dataset.email || '',
                telefone: row.dataset.telefone || '',
                cep: row.dataset.cep || '',
                endereco: row.dataset.endereco || '',
                numero: row.dataset.numero || '',
                complemento: row.dataset.complemento || '',
                bairro: row.dataset.bairro || '',
                cidade: row.dataset.cidade || '',
                estado: row.dataset.estado || '',
                ativo: row.dataset.status || 1,
                data_criacao: row.dataset.dataCriacao || ''
            };
            showFornecedorDetails(fornecedor);
        }

        function editFornecedor(id) {
            const row = document.querySelector(`.fornecedor-row[data-id='${id}']`);
            if (!row) {
                alert('Fornecedor não encontrado.');
                return;
            }
            const fornecedor = {
                id: id,
                nome: row.dataset.nomeOriginal || row.dataset.nome || '',
                cnpj: row.dataset.cnpj || '',
                email: row.dataset.emailOriginal || row.dataset.email || '',
                telefone: row.dataset.telefone || '',
                cep: row.dataset.cep || '',
                endereco: row.dataset.endereco || '',
                numero: row.dataset.numero || '',
                complemento: row.dataset.complemento || '',
                bairro: row.dataset.bairro || '',
                cidade: row.dataset.cidade || '',
                estado: row.dataset.estado || '',
                ativo: row.dataset.status || 1
            };
            window.openFornecedorModal(fornecedor);
        }

        function toggleFornecedorStatus(id, novoStatus) {
            const acao = novoStatus ? 'ativar' : 'desativar';
            if (confirm(`Deseja ${acao} este fornecedor?`)) {
                requisicaoJson(`${APP_BASE_URL}/fornecedores/toggle-status/${id}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            status: novoStatus
                        })
                    })
                    .then(resposta => {
                        if (resposta.sucesso) {
                            window.location.reload();
                        } else {
                            alert(resposta.mensagem);
                        }
                    });
            }
        }

        function deleteFornecedor(id, nome) {
            if (confirm(`Tem certeza que deseja excluir o fornecedor "${nome}"?`)) {
                requisicaoJson(`${APP_BASE_URL}/fornecedores/excluir/${id}`, {
                        method: 'POST'
                    })
                    .then(resposta => {
                        if (resposta.sucesso) {
                            window.location.reload();
                        } else {
                            alert(resposta.mensagem);
                        }
                    });
            }
        }

        function showFornecedorDetails(fornecedor) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50';
            modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Detalhes do Fornecedor</h3>
            <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div><label class="block text-sm font-medium text-gray-600">Nome / Razão Social</label><p class="text-gray-900">${escapeHtml(fornecedor.nome || '-')}</p></div>
                    <div><label class="block text-sm font-medium text-gray-600">CNPJ</label><p class="text-gray-900">${formatCnpj(fornecedor.cnpj || '')}</p></div>
                    <div><label class="block text-sm font-medium text-gray-600">Email</label><p class="text-gray-900">${escapeHtml(fornecedor.email || '-')}</p></div>
                    <div><label class="block text-sm font-medium text-gray-600">Telefone</label><p class="text-gray-900">${formatTelefone(fornecedor.telefone || '')}</p></div>
                </div>
                <div class="space-y-4">
                    <div><label class="block text-sm font-medium text-gray-600">Status</label><p class="text-gray-900">${(fornecedor.ativo ?? 1) ? 'Ativo' : 'Inativo'}</p></div>
                    <div><label class="block text-sm font-medium text-gray-600">Data de Cadastro</label><p class="text-gray-900">${fornecedor.data_criacao ? new Date(fornecedor.data_criacao).toLocaleString('pt-BR') : '-'}</p></div>
                </div>
            </div>
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h4 class="text-md font-semibold text-gray-800 mb-4">Endereço</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-600">CEP</label><p class="text-gray-900">${escapeHtml(fornecedor.cep || '-')}</p></div>
                    <div><label class="block text-sm font-medium text-gray-600">Endereço</label><p class="text-gray-900">${escapeHtml(fornecedor.endereco || '-')}</p></div>
                    <div><label class="block text-sm font-medium text-gray-600">Número</label><p class="text-gray-900">${escapeHtml(fornecedor.numero || '-')}</p></div>
                    <div><label class="block text-sm font-medium text-gray-600">Bairro</label><p class="text-gray-900">${escapeHtml(fornecedor.bairro || '-')}</p></div>
                    <div><label class="block text-sm font-medium text-gray-600">Cidade</label><p class="text-gray-900">${escapeHtml(fornecedor.cidade || '-')}</p></div>
                    <div><label class="block text-sm font-medium text-gray-600">Estado</label><p class="text-gray-900">${escapeHtml(fornecedor.estado || '-')}</p></div>
                </div>
            </div>
        </div>
    </div>
`;
            document.body.appendChild(modal);
        }
