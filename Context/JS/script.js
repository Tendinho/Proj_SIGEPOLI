// Função para confirmar exclusões
function confirmarExclusao() {
    return confirm("Tem certeza que deseja excluir este registro?");
}

// Função para formatar datas
function formatarData(data) {
    if (!data) return '';
    const date = new Date(data);
    return date.toLocaleDateString('pt-AO');
}

// Função para inicializar tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Tooltips
    const tooltips = document.querySelectorAll('[data-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseover', function() {
            const tooltipText = this.getAttribute('title');
            const tooltipElement = document.createElement('div');
            tooltipElement.className = 'custom-tooltip';
            tooltipElement.textContent = tooltipText;
            document.body.appendChild(tooltipElement);
            
            const rect = this.getBoundingClientRect();
            tooltipElement.style.top = `${rect.top - tooltipElement.offsetHeight - 10}px`;
            tooltipElement.style.left = `${rect.left + rect.width / 2 - tooltipElement.offsetWidth / 2}px`;
            
            this.addEventListener('mouseout', function() {
                document.body.removeChild(tooltipElement);
            });
        });
    });
    
    // Máscaras para campos de entrada
    const telefoneInputs = document.querySelectorAll('input[type="tel"]');
    telefoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
        });
    });
    
    // Validação de formulários
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    isValid = false;
                } else {
                    field.style.borderColor = '#ddd';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios!');
            }
        });
    });
});

// Função para exibir mensagens de sucesso/erro
function exibirMensagem(tipo, mensagem) {
    const mensagemElement = document.createElement('div');
    mensagemElement.className = `alert alert-${tipo}`;
    mensagemElement.textContent = mensagem;
    
    const container = document.querySelector('.content') || document.body;
    container.insertBefore(mensagemElement, container.firstChild);
    
    setTimeout(() => {
        mensagemElement.style.opacity = '0';
        setTimeout(() => {
            container.removeChild(mensagemElement);
        }, 500);
    }, 5000);
}

// Função para carregar dados dinamicamente
function carregarDados(url, elementoId) {
    fetch(url)
        .then(response => response.json())
        .then(data => {
            const elemento = document.getElementById(elementoId);
            if (elemento) {
                elemento.innerHTML = ''; // Limpar conteúdo existente
                
                if (Array.isArray(data)) {
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = item.nome;
                        elemento.appendChild(option);
                    });
                }
            }
        })
        .catch(error => console.error('Erro ao carregar dados:', error));
}

// Exemplo de uso para carregar cursos em um select
// carregarDados('api/cursos.php', 'curso_id');