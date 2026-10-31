// Sistema de Gestão de Estoque - C.A de Jesus
// Funções globais usadas pelas telas.

window.showToast = function(message, type = 'info', duration = 4000) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `px-4 py-3 rounded-lg shadow-lg text-white transform transition-all duration-300 translate-x-full`;

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

    const linha = document.createElement('div');
    linha.className = 'flex items-center justify-between';
    const texto = document.createElement('span');
    texto.style.whiteSpace = 'pre-line';
    texto.textContent = message;
    const fechar = document.createElement('button');
    fechar.className = 'ml-3 text-white hover:text-gray-200';
    fechar.innerHTML = '<i class="fas fa-times"></i>';
    fechar.addEventListener('click', () => toast.remove());
    linha.append(texto, fechar);
    toast.appendChild(linha);

    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);

    // duration 0 = fica até ser fechado (ou removido por quem chamou)
    if (duration > 0) {
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 300);
        }, duration);
    }
    return toast;
};

// Escapa texto vindo do banco antes de colocá-lo em HTML montado no JS.
window.escapeHtml = function(valor) {
    return String(valor ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
};

// Chama uma rota que responde no contrato JSON (docs/arquitetura.md).
// Sempre resolve com { sucesso, mensagem, dados, requer_confirmacao }; nunca rejeita.
// Sessão expirada (401) leva ao login.
window.requisicaoJson = async function(url, opcoes = {}) {
    const headers = Object.assign({ 'Accept': 'application/json' }, opcoes.headers || {});
    const metaCsrf = document.querySelector('meta[name="csrf-token"]');
    if (metaCsrf && (opcoes.method || 'GET').toUpperCase() !== 'GET') {
        headers['X-CSRF-Token'] = metaCsrf.content;
    }
    try {
        const res = await fetch(url, Object.assign({}, opcoes, { headers }));
        if (res.status === 401) {
            window.location.href = (window.APP_BASE_URL || '') + '/login';
        }
        return await res.json();
    } catch (e) {
        console.error('Falha na requisição', url, e);
        return {
            sucesso: false,
            mensagem: 'Não foi possível falar com o servidor. Verifique a conexão e tente novamente.',
            dados: null,
            requer_confirmacao: false
        };
    }
};

// Mostra ou esconde a senha no campo; o ícone tem o id "<campo>-eye".
window.togglePassword = function(fieldId) {
    const field = document.getElementById(fieldId);
    const eye = document.getElementById(fieldId + '-eye');

    if (field.type === 'password') {
        field.type = 'text';
        eye.classList.remove('fa-eye');
        eye.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        eye.classList.remove('fa-eye-slash');
        eye.classList.add('fa-eye');
    }
};

window.openModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
};

window.closeModal = function(modal) {
    if (typeof modal === 'string') {
        modal = document.getElementById(modal);
    }

    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
};

document.addEventListener('DOMContentLoaded', function() {
    // Mensagem da requisição anterior (Session::flash), escrita pelo layout.
    const flash = document.getElementById('flash');
    if (flash) {
        window.showToast(flash.dataset.mensagem, flash.dataset.tipo, 6000);
    }

    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('-translate-x-full');
        });
    }

    // fecha o sidebar ao clicar fora (mobile)
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
});
