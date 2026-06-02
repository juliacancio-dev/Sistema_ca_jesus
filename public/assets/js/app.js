// Sistema de Gestão de Estoque - C.A de Jesus
// JS principal da aplicação

// define as funções globais logo de cara pra não dar erro
window.showLoading = function() {
    const loading = document.getElementById('loading');
    if (loading) loading.classList.remove('hidden');
};

window.hideLoading = function() {
    const loading = document.getElementById('loading');
    if (loading) loading.classList.add('hidden');
};

// mostra notificações bonitinhas
window.showToast = function(message, type = 'info', duration = 4000) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `px-4 py-3 rounded-lg shadow-lg text-white transform transition-all duration-300 translate-x-full`;
    
    // escolhe a cor dependendo do tipo
    switch (type) {
        case 'success':
            toast.classList.add('bg-green-500');
            break;
        case 'error':
            toast.classList.add('bg-red-500');
            break;
        case 'warning':
            toast.classList.add('bg-yellow-500');
            break;
        default:
            toast.classList.add('bg-blue-500');
    }
    
    toast.innerHTML = `
        <div class="flex items-center justify-between">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    container.appendChild(toast);
    
    // anima a entrada
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);
    
    // remove depois de um tempo
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 300);
    }, duration);
};

// abre um modal
window.openModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
};

// fecha o modal
window.closeModal = function(modal) {
    if (typeof modal === 'string') {
        modal = document.getElementById(modal);
    }
    
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
};

// faz requisições AJAX de forma fácil
window.makeRequest = function(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const finalOptions = { ...defaultOptions, ...options };
    
    showLoading();
    
    return fetch(url, finalOptions)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('Request error:', error);
            showToast('Erro na requisição: ' + error.message, 'error');
            throw error;
        })
        .finally(() => {
            hideLoading();
        });
};

// formata números bonitinho
window.formatNumber = function(number, decimals = 2) {
    return new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(number);
};

// formata valores em reais
window.formatCurrency = function(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
};

// formata datas no padrão brasileiro
window.formatDate = function(date, format = 'dd/MM/yyyy') {
    if (!(date instanceof Date)) {
        date = new Date(date);
    }
    
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    }).format(date);
};

// debounce pra não ficar fazendo requisição a cada letra digitada
window.debounce = function(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

document.addEventListener('DOMContentLoaded', function() {
    console.log('Sistema C.A de Jesus carregado!');
    
    // inicializa tudo
    initializeComponents();
    
    // configura os eventos
    setupGlobalEvents();
});

// inicializa os componentes da aplicação
function initializeComponents() {
    setupLoadingSpinner();
    setupForms();
    setupModals();
    setupTooltips();
}

// configura eventos globais
function setupGlobalEvents() {
    // toggle do sidebar no mobile
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('-translate-x-full');
        });
    }
    
    // fecha o sidebar se clicar fora (mobile)
    document.addEventListener('click', function(event) {
        if (window.innerWidth < 1024) { // breakpoint lg do tailwind
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebar-toggle');
            
            if (sidebar && !sidebar.contains(event.target) && 
                sidebarToggle && !sidebarToggle.contains(event.target)) {
                sidebar.classList.add('-translate-x-full');
            }
        }
    });
    
    // destaca o item ativo no menu
    highlightActiveMenuItem();
}

// configura o loading spinner
function setupLoadingSpinner() {
    // as funções já tão definidas lá em cima
    console.log('Loading spinner pronto');
}

// configura os formulários
function setupForms() {
    // validação dos forms
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
    
    // auto-save em campos específicos
    const autoSaveFields = document.querySelectorAll('[data-auto-save]');
    autoSaveFields.forEach(field => {
        field.addEventListener('change', function() {
            // implementar auto-save se precisar
            console.log('Auto-save:', this.name, this.value);
        });
    });
}

// valida o formulário
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'Este campo é obrigatório');
            isValid = false;
        } else {
            clearFieldError(field);
        }
    });
    
    return isValid;
}

// mostra erro no campo
function showFieldError(field, message) {
    clearFieldError(field);
    
    field.classList.add('border-red-500');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'text-red-500 text-sm mt-1 field-error';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

// limpa o erro do campo
function clearFieldError(field) {
    field.classList.remove('border-red-500');
    
    const errorDiv = field.parentNode.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

// configura os modais
function setupModals() {
    // fecha o modal se clicar no fundo
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
            closeModal(e.target.closest('.modal'));
        }
    });
    
    // fecha o modal com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal:not(.hidden)');
            if (openModal) {
                closeModal(openModal);
            }
        }
    });
}

// configura os tooltips
function setupTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

// mostra o tooltip
function showTooltip(e) {
    const text = e.target.getAttribute('data-tooltip');
    if (!text) return;
    
    const tooltip = document.createElement('div');
    tooltip.className = 'absolute bg-gray-800 text-white text-sm px-2 py-1 rounded shadow-lg z-50 tooltip';
    tooltip.textContent = text;
    
    document.body.appendChild(tooltip);
    
    const rect = e.target.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
}

// esconde o tooltip
function hideTooltip() {
    const tooltip = document.querySelector('.tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

// destaca o item ativo no menu
function highlightActiveMenuItem() {
    const currentPath = window.location.pathname;
    const menuItems = document.querySelectorAll('.nav-item');
    
    menuItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href && currentPath.includes(href.replace('/sistema_ca_jesus/public', ''))) {
            item.classList.add('bg-blue-50', 'text-blue-600');
        }
    });
}

// exporta as funções pra usar em qualquer lugar
window.SistemaCAJesus = {
    showToast,
    makeRequest,
    formatNumber,
    formatCurrency,
    formatDate,
    debounce,
    openModal,
    closeModal,
    showLoading,
    hideLoading
};