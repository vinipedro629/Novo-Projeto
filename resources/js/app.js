import './bootstrap';
import '../css/app.css';

// Recursos comuns do Portal Corporativo
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar componentes Bootstrap
    if (typeof bootstrap !== 'undefined') {
        // Tooltip
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Modal
        var modalElements = document.querySelectorAll('.modal');
        modalElements.forEach(function(modalEl) {
            modalEl.addEventListener('hidden.bs.modal', function() {
                // Limpar formulários quando modal fechar
                var forms = modalEl.querySelectorAll('form');
                forms.forEach(function(form) {
                    form.reset();
                });
            });
        });
    }

    // Confirmar ações destrutivas
    document.querySelectorAll('[data-confirm]').forEach(function(element) {
        element.addEventListener('click', function(e) {
            var message = this.getAttribute('data-confirm');
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Máscara para campos de telefone
    document.querySelectorAll('input[type="tel"]').forEach(function(input) {
        input.addEventListener('input', function(e) {
            var value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                if (value.length <= 10) {
                    value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
                } else {
                    value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                }
                e.target.value = value;
            }
        });
    });

    // Máscara para CPF
    document.querySelectorAll('input[data-mask="cpf"]').forEach(function(input) {
        input.addEventListener('input', function(e) {
            var value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                e.target.value = value;
            }
        });
    });
});

// Funções utilitárias globais
window.PortalUtils = {
    // Formatação de moeda
    formatCurrency: function(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    },

    // Formatação de data
    formatDate: function(date, options = {}) {
        return new Intl.DateTimeFormat('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            ...options
        }).format(new Date(date));
    },

    // Exibir notificação
    showNotification: function(message, type = 'info') {
        // Implementar sistema de notificação se necessário
        console.log(`[${type.toUpperCase()}] ${message}`);
    }
};
