        document.addEventListener('DOMContentLoaded', function() {
            // Elementos
            const modal = document.getElementById('usuario-modal');
            const newBtn = document.getElementById('new-usuario-btn');
            const closeBtn = document.getElementById('close-usuario-modal');
            const form = document.getElementById('usuario-form');
            const searchInput = document.getElementById('search-usuario');
            const clearSearchBtn = document.getElementById('clear-search');
            const filterTipo = document.getElementById('filter-tipo');
            const filterStatus = document.getElementById('filter-status');

            let currentStep = 1;
            const totalSteps = 3;
            const steps = document.querySelectorAll('.step');
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');

            updateStepDisplay();

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

            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('next-step') || e.target.closest('.next-step')) {
                    e.preventDefault();
                    if (validateCurrentStep()) {
                        nextStep();
                    }
                }

                if (e.target.id === 'prev-step-2' || e.target.closest('#prev-step-2')) {
                    e.preventDefault();
                    prevStep();
                }

                if (e.target.id === 'prev-step-3' || e.target.closest('#prev-step-3')) {
                    e.preventDefault();
                    prevStep();
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

            if (filterTipo) {
                filterTipo.addEventListener('change', filterTable);
            }

            if (filterStatus) {
                filterStatus.addEventListener('change', filterTable);
            }

            const cepInput = document.getElementById('usuario-cep');
            if (cepInput) {
                cepInput.addEventListener('blur', function() {
                    const cep = this.value.replace(/\D/g, '');
                    if (cep.length === 8) {
                        buscarCep(cep, 'usuario-');
                    }
                });
            }

            setupInputMasks();

            function openModal(usuario = null) {
                resetForm();
                currentStep = 1;
                updateStepDisplay();

                if (usuario) {
                    document.getElementById('usuario-modal-title').textContent = 'Editar Usuário';
                    document.getElementById('submit-text').textContent = 'Atualizar Usuário';
                    document.getElementById('senha-help').classList.remove('hidden');
                    document.getElementById('senha-label').innerHTML = 'Nova Senha <span class="text-gray-500 text-xs ml-1">(Deixe em branco para manter atual)</span>';

                    fillForm(usuario);
                } else {
                    document.getElementById('usuario-modal-title').textContent = 'Novo Usuário';
                    document.getElementById('submit-text').textContent = 'Criar Usuário';
                    document.getElementById('senha-help').classList.add('hidden');
                    document.getElementById('senha-label').innerHTML = 'Senha * <span class="text-gray-500 text-xs ml-1">(Mínimo 8 caracteres)</span>';
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
                currentStep = 1;
                updateStepDisplay();
                clearErrors();
                document.getElementById('usuario-id').value = '';
            }

            function fillForm(usuario) {
                document.getElementById('usuario-id').value = usuario.id;
                document.getElementById('usuario-nome').value = usuario.nome;
                document.getElementById('usuario-email').value = usuario.email;
                document.getElementById('usuario-cpf').value = formatCpf(usuario.cpf);
                document.getElementById('usuario-rg').value = usuario.rg;
                document.getElementById('usuario-telefone').value = formatTelefone(usuario.telefone);
                document.getElementById('usuario-sexo').value = usuario.sexo;
                document.getElementById('usuario-cep').value = formatCep(usuario.cep);
                document.getElementById('usuario-endereco').value = usuario.endereco;
                document.getElementById('usuario-numero').value = usuario.numero;
                document.getElementById('usuario-complemento').value = usuario.complemento || '';
                document.getElementById('usuario-bairro').value = usuario.bairro;
                document.getElementById('usuario-cidade').value = usuario.cidade;
                document.getElementById('usuario-estado').value = usuario.estado;
                document.getElementById('usuario-tipo').value = usuario.tipo;
                document.getElementById('usuario-ativo').checked = usuario.ativo == 1;
            }

            function nextStep() {
                if (currentStep < totalSteps) {
                    steps[currentStep - 1].classList.add('hidden');
                    currentStep++;
                    steps[currentStep - 1].classList.remove('hidden');
                    updateStepDisplay();
                }
            }

            function prevStep() {
                if (currentStep > 1) {
                    steps[currentStep - 1].classList.add('hidden');
                    currentStep--;
                    steps[currentStep - 1].classList.remove('hidden');
                    updateStepDisplay();
                }
            }

            function updateStepDisplay() {
                const progress = (currentStep / totalSteps) * 100;
                progressBar.style.width = progress + '%';
                progressText.textContent = `${currentStep} / ${totalSteps}`;

                steps.forEach((step, index) => {
                    if (index === currentStep - 1) {
                        step.classList.remove('hidden');
                    } else {
                        step.classList.add('hidden');
                    }
                });
                const finalNav = document.getElementById('final-nav');
                if (finalNav) {
                    if (currentStep === totalSteps) {
                        finalNav.classList.remove('hidden');
                    } else {
                        finalNav.classList.add('hidden');
                    }
                }
            }

            function validateCurrentStep() {
                const currentStepElement = steps[currentStep - 1];
                const requiredFields = currentStepElement.querySelectorAll('[required]');
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
                        } else if (field.name === 'cpf' && !validateCPF(field.value)) {
                            showFieldError(field, 'CPF inválido');
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
                mascaraCpf(document.getElementById('usuario-cpf'));
                mascaraTelefone(document.getElementById('usuario-telefone'));
                mascaraCep(cepInput);
            }

            function filterTable() {
                const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
                const tipoFilter = filterTipo ? filterTipo.value : '';
                const statusFilter = filterStatus ? filterStatus.value : '';

                const rows = document.querySelectorAll('.usuario-row');
                let visibleCount = 0;

                rows.forEach(row => {
                    const nome = row.dataset.nome || '';
                    const email = row.dataset.email || '';
                    const cpf = row.dataset.cpf || '';
                    const tipo = row.dataset.tipo || '';
                    const status = row.dataset.status || '';

                    const matchesSearch = !searchTerm ||
                        nome.includes(searchTerm) ||
                        email.includes(searchTerm) ||
                        cpf.includes(searchTerm);

                    const matchesTipo = !tipoFilter || tipo === tipoFilter;
                    const matchesStatus = !statusFilter || status === statusFilter;

                    if (matchesSearch && matchesTipo && matchesStatus) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                const emptyState = document.getElementById('empty-state');
                if (emptyState) {
                    if (visibleCount === 0) {
                        emptyState.style.display = '';
                    } else {
                        emptyState.style.display = 'none';
                    }
                }

                updateStats();
            }

            function updateStats() {
                const visibleRows = document.querySelectorAll('.usuario-row:not([style*="display: none"])');
                const total = visibleRows.length;
                const ativos = Array.from(visibleRows).filter(row => row.dataset.status === '1').length;
                const admins = Array.from(visibleRows).filter(row => row.dataset.tipo === '1').length;
                const funcionarios = Array.from(visibleRows).filter(row => row.dataset.tipo === '2').length;

                document.getElementById('stats-total').textContent = total;
                document.getElementById('stats-ativos').textContent = ativos;
                document.getElementById('stats-admins').textContent = admins;
                document.getElementById('stats-funcionarios').textContent = funcionarios;
            }

            function validateEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }

            function validateCPF(cpf) {
                cpf = cpf.replace(/\D/g, '');
                if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;

                let sum = 0;
                for (let i = 0; i < 9; i++) {
                    sum += parseInt(cpf.charAt(i)) * (10 - i);
                }
                let remainder = 11 - (sum % 11);
                if (remainder === 10 || remainder === 11) remainder = 0;
                if (remainder !== parseInt(cpf.charAt(9))) return false;

                sum = 0;
                for (let i = 0; i < 10; i++) {
                    sum += parseInt(cpf.charAt(i)) * (11 - i);
                }
                remainder = 11 - (sum % 11);
                if (remainder === 10 || remainder === 11) remainder = 0;
                return remainder === parseInt(cpf.charAt(10));
            }

            function validateCEP(cep) {
                cep = cep.replace(/\D/g, '');
                return cep.length === 8;
            }

            form.addEventListener('submit', function(e) {
                if (!validateCurrentStep()) {
                    e.preventDefault();
                }
            });

            window.openUsuarioModal = openModal;
        });

        function viewUsuario(id) {
            requisicaoJson(`${APP_BASE_URL}/usuarios/buscar/${id}`)
                .then(resposta => {
                    if (resposta.sucesso) {
                        showUsuarioDetails(resposta.dados);
                    } else {
                        alert(resposta.mensagem);
                    }
                });
        }

        function editUsuario(id) {
            requisicaoJson(`${APP_BASE_URL}/usuarios/buscar/${id}`)
                .then(resposta => {
                    if (resposta.sucesso) {
                        window.openUsuarioModal(resposta.dados);
                    } else {
                        alert(resposta.mensagem);
                    }
                });
        }

        function toggleUsuarioStatus(id, novoStatus) {
            const acao = novoStatus ? 'ativar' : 'desativar';
            if (confirm(`Deseja ${acao} este usuário?`)) {
                requisicaoJson(`${APP_BASE_URL}/usuarios/toggle-status/${id}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
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

        function deleteUsuario(id, nome) {
            if (confirm(`Tem certeza que deseja excluir o usuário "${nome}"?\n\nEsta ação não pode ser desfeita.`)) {
                requisicaoJson(`${APP_BASE_URL}/usuarios/excluir/${id}`, {
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

        function showUsuarioDetails(usuario) {
            // Criar modal de visualização
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50';
            modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">Detalhes do Usuário</h3>
            <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Nome</label>
                        <p class="text-gray-900">${usuario.nome || '-'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Email</label>
                        <p class="text-gray-900">${usuario.email || '-'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">CPF</label>
                        <p class="text-gray-900">${formatCpf(usuario.cpf || '')}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Telefone</label>
                        <p class="text-gray-900">${formatTelefone(usuario.telefone || '')}</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Tipo</label>
                        <p class="text-gray-900">${usuario.tipo == 1 ? 'Administrador' : 'Funcionário'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Status</label>
                        <p class="text-gray-900">${(usuario.ativo ?? 1) ? 'Ativo' : 'Inativo'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Último Login</label>
                        <p class="text-gray-900">${usuario.ultimo_login ? new Date(usuario.ultimo_login).toLocaleString('pt-BR') : 'Nunca'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Data de Cadastro</label>
                        <p class="text-gray-900">${usuario.data_criacao ? new Date(usuario.data_criacao).toLocaleString('pt-BR') : '-'}</p>
                    </div>
                </div>
            </div>
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h4 class="text-md font-semibold text-gray-800 mb-4">Endereço</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600">CEP</label>
                        <p class="text-gray-900">${usuario.cep || '-'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Endereço</label>
                        <p class="text-gray-900">${usuario.endereco || '-'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Número</label>
                        <p class="text-gray-900">${usuario.numero || '-'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Bairro</label>
                        <p class="text-gray-900">${usuario.bairro || '-'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Cidade</label>
                        <p class="text-gray-900">${usuario.cidade || '-'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Estado</label>
                        <p class="text-gray-900">${usuario.estado || '-'}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
`;
            document.body.appendChild(modal);
        }

