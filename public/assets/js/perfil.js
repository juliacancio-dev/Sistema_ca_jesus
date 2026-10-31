document.addEventListener('DOMContentLoaded', function() {

    mascaraTelefone(document.getElementById('telefone'));
    mascaraCep(document.getElementById('cep'));

    document.getElementById('cep').addEventListener('blur', function(e) {
        const cep = soDigitos(e.target.value);
        if (cep.length === 8) {
            buscarCep(cep);
        }
    });

    const novaSenha = document.getElementById('nova_senha');
    const confirmarSenha = document.getElementById('confirmar_senha');

    function validatePasswordMatch() {
        if (novaSenha.value !== confirmarSenha.value) {
            confirmarSenha.setCustomValidity('As senhas não coincidem');
        } else {
            confirmarSenha.setCustomValidity('');
        }
    }

    novaSenha.addEventListener('input', validatePasswordMatch);
    confirmarSenha.addEventListener('input', validatePasswordMatch);

    novaSenha.addEventListener('input', function() {
        const password = this.value;
        const strength = calculatePasswordStrength(password);
        updateStrengthIndicator(strength);
    });

    function calculatePasswordStrength(password) {
        let score = 0;
        if (password.length >= 8) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        return Math.min(score, 4);
    }

    function updateStrengthIndicator(strength) {
        const indicators = ['strength-1', 'strength-2', 'strength-3', 'strength-4'];
        const colors = ['bg-red-500', 'bg-yellow-500', 'bg-blue-500', 'bg-green-500'];
        const texts = ['Muito fraca', 'Fraca', 'Média', 'Forte', 'Muito forte'];

        indicators.forEach((id, index) => {
            const element = document.getElementById(id);
            element.className = 'h-2 w-1/4 rounded ' + (index < strength ? colors[Math.min(strength - 1, 3)] : 'bg-gray-200');
        });

        document.getElementById('strength-text').textContent = texts[strength] || 'Digite uma senha';
    }
});

function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('bg-blue-50', 'text-blue-700');
        btn.classList.add('text-gray-700');
    });

    document.getElementById('tab-' + tabName).classList.remove('hidden');

    event.target.classList.add('bg-blue-50', 'text-blue-700');
    event.target.classList.remove('text-gray-700');
}

