# SIGEPOLI - Sistema de Design

## Visão Geral

O SIGEPOLI agora utiliza um sistema de design moderno e profissional baseado em variáveis CSS, componentes reutilizáveis e princípios de design consistentes. Este sistema garante uma aparência única e profissional em todo o projeto.

## Estrutura de Arquivos

```
Context/CSS/
├── design-system.css      # Sistema de design base com variáveis e componentes
├── styles.css            # Estilos principais do dashboard
├── login.css             # Estilos específicos da página de login
├── alunos.css            # Estilos do módulo de alunos
├── professores.css       # Estilos do módulo de professores
├── cursos.css            # Estilos do módulo de cursos
├── matriculas.css        # Estilos do módulo de matrículas
├── auditoria.css         # Estilos do módulo de auditoria
├── perfil.css            # Estilos do módulo de perfil
├── usuarios.css          # Estilos do módulo de usuários
├── custo.css             # Estilos do módulo de custos
└── criar.css             # Estilos para formulários de criação
```

## Paleta de Cores

### Cores Primárias
- `--primary-50` a `--primary-950`: Tons de azul (3B82F6)
- `--secondary-50` a `--secondary-950`: Tons de rosa (D946EF)

### Cores de Estado
- `--success-50` a `--success-950`: Tons de verde (22C55E)
- `--warning-50` a `--warning-950`: Tons de amarelo (F59E0B)
- `--error-50` a `--error-950`: Tons de vermelho (EF4444)

### Cores Neutras
- `--gray-50` a `--gray-950`: Escala de cinzas

### Cores de Fundo
- `--bg-primary`: Branco (#FFFFFF)
- `--bg-secondary`: Cinza claro (#F8FAFC)
- `--bg-tertiary`: Cinza mais claro (#F1F5F9)

## Tipografia

### Família de Fontes
- **Primária**: Inter (Google Fonts)
- **Monospace**: JetBrains Mono

### Tamanhos de Fonte
- `--text-xs`: 0.75rem (12px)
- `--text-sm`: 0.875rem (14px)
- `--text-base`: 1rem (16px)
- `--text-lg`: 1.125rem (18px)
- `--text-xl`: 1.25rem (20px)
- `--text-2xl`: 1.5rem (24px)
- `--text-3xl`: 1.875rem (30px)
- `--text-4xl`: 2.25rem (36px)
- `--text-5xl`: 3rem (48px)

### Pesos de Fonte
- `--font-light`: 300
- `--font-normal`: 400
- `--font-medium`: 500
- `--font-semibold`: 600
- `--font-bold`: 700
- `--font-extrabold`: 800

## Espaçamentos

### Sistema de Espaçamento
- `--spacing-xs`: 0.25rem (4px)
- `--spacing-sm`: 0.5rem (8px)
- `--spacing-md`: 1rem (16px)
- `--spacing-lg`: 1.5rem (24px)
- `--spacing-xl`: 2rem (32px)
- `--spacing-2xl`: 3rem (48px)
- `--spacing-3xl`: 4rem (64px)

## Bordas e Sombras

### Raios de Borda
- `--radius-none`: 0
- `--radius-sm`: 0.25rem (4px)
- `--radius-md`: 0.375rem (6px)
- `--radius-lg`: 0.5rem (8px)
- `--radius-xl`: 0.75rem (12px)
- `--radius-2xl`: 1rem (16px)
- `--radius-3xl`: 1.5rem (24px)
- `--radius-full`: 9999px

### Sombras
- `--shadow-xs`: Sombra muito sutil
- `--shadow-sm`: Sombra pequena
- `--shadow-md`: Sombra média
- `--shadow-lg`: Sombra grande
- `--shadow-xl`: Sombra extra grande
- `--shadow-2xl`: Sombra máxima
- `--shadow-inner`: Sombra interna

## Componentes

### Botões
```css
.btn {
  /* Estilo base para todos os botões */
}

.btn-primary {
  /* Botão primário com gradiente */
}

.btn-secondary {
  /* Botão secundário com borda */
}

.btn-success, .btn-warning, .btn-error {
  /* Botões de estado */
}
```

### Cards
```css
.card {
  /* Container de card com sombra e borda */
}

.card-header {
  /* Cabeçalho do card */
}

.card-body {
  /* Corpo do card */
}
```

### Formulários
```css
.form-group {
  /* Grupo de campo de formulário */
}

.form-input {
  /* Campo de entrada */
}

.form-label {
  /* Rótulo do campo */
}
```

### Alertas
```css
.alert {
  /* Alerta base */
}

.alert-success, .alert-error, .alert-warning, .alert-info {
  /* Variantes de alerta */
}
```

### Badges
```css
.badge {
  /* Badge base */
}

.badge-primary, .badge-secondary, .badge-success, .badge-warning, .badge-error {
  /* Variantes de badge */
}
```

## Animações

### Transições
- `--transition-fast`: 150ms ease-in-out
- `--transition-normal`: 250ms ease-in-out
- `--transition-slow`: 350ms ease-in-out
- `--transition-bounce`: 250ms cubic-bezier(0.68, -0.55, 0.265, 1.55)
- `--transition-spring`: 400ms cubic-bezier(0.175, 0.885, 0.32, 1.275)

### Animações Principais
- `fadeIn`: Fade in com movimento vertical
- `slideIn`: Slide in horizontal
- `scaleIn`: Scale in com fade
- `spin`: Rotação contínua

## Responsividade

### Breakpoints
- `--breakpoint-sm`: 640px
- `--breakpoint-md`: 768px
- `--breakpoint-lg`: 1024px
- `--breakpoint-xl`: 1280px
- `--breakpoint-2xl`: 1536px

### Classes Utilitárias
- `.d-none`, `.d-block`, `.d-flex`: Display
- `.text-left`, `.text-center`, `.text-right`: Alinhamento de texto
- `.m-0`, `.p-0`, etc.: Margens e paddings
- `.rounded`, `.shadow`: Bordas e sombras

## Características Especiais

### Efeitos Visuais
- **Gradientes animados**: Bordas superiores com gradientes que se movem
- **Hover effects**: Transformações suaves nos elementos
- **Glass morphism**: Efeito de vidro com backdrop-filter
- **Micro-interações**: Animações sutis para feedback visual

### Acessibilidade
- Contraste adequado entre cores
- Estados de foco visíveis
- Suporte a navegação por teclado
- Textos alternativos para ícones

### Performance
- CSS otimizado com variáveis
- Animações usando transform e opacity
- Lazy loading de fontes
- Scrollbar personalizada

## Como Usar

### 1. Importar o Design System
```css
@import url('design-system.css');
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
```

### 2. Usar Variáveis CSS
```css
.meu-componente {
  background: var(--bg-primary);
  color: var(--text-primary);
  padding: var(--spacing-lg);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
}
```

### 3. Aplicar Classes Utilitárias
```html
<div class="card p-4 m-2 rounded-lg shadow-md">
  <h2 class="text-2xl font-bold text-primary">Título</h2>
  <p class="text-secondary">Descrição</p>
</div>
```

### 4. Criar Componentes Customizados
```css
.meu-botao {
  @extend .btn;
  @extend .btn-primary;
  background: linear-gradient(135deg, var(--primary-600), var(--secondary-600));
}
```

## Manutenção

### Adicionando Novas Cores
1. Adicione as variáveis no `design-system.css`
2. Use a escala de 50-950 para consistência
3. Teste o contraste para acessibilidade

### Criando Novos Componentes
1. Siga o padrão de nomenclatura existente
2. Use as variáveis CSS disponíveis
3. Inclua estados hover e focus
4. Adicione suporte responsivo

### Atualizando o Sistema
1. Mantenha compatibilidade com versões anteriores
2. Documente mudanças significativas
3. Teste em diferentes navegadores
4. Valide acessibilidade

## Benefícios

- **Consistência**: Design uniforme em todo o projeto
- **Manutenibilidade**: Mudanças centralizadas
- **Performance**: CSS otimizado e eficiente
- **Acessibilidade**: Padrões WCAG seguidos
- **Responsividade**: Funciona em todos os dispositivos
- **Modernidade**: Visual atual e profissional
- **Escalabilidade**: Fácil de expandir e modificar

## Suporte de Navegadores

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Opera 76+

## Licença

Este sistema de design foi desenvolvido especificamente para o projeto SIGEPOLI e está sob a mesma licença do projeto principal. 