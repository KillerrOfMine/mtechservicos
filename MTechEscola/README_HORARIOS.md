# Sistema de Gerenciamento de Horários - MTech Escola

## 📋 Funcionalidades Implementadas

### 1. **Visualização de Horários**
- ✅ Por Turma: Grade semanal completa (Segunda a Sexta)
- ✅ Por Professor: Visualizar todos os horários de um professor
- ✅ Exibição de disciplina e professor em cada horário
- ✅ Impressão/PDF com estilo otimizado

### 2. **Gerenciamento de Disponibilidade de Professores**
- ✅ Página dedicada para cada professor cadastrar seus horários
- ✅ Interface visual intuitiva (verde = disponível, vermelho = ocupado)
- ✅ Configuração por dia da semana e horário
- ✅ Validação automática de conflitos

### 3. **Edição de Horários**
- ✅ Edição completa da grade de horários por turma
- ✅ Seleção de disciplina e professor para cada slot
- ✅ Validação em tempo real de professores disponíveis
- ✅ Detecção automática de conflitos (professor em duas turmas ao mesmo tempo)
- ✅ Professores ocupados aparecem marcados em vermelho
- ✅ Opção de marcar turma como "Horário Fixo" (não será alterada na geração automática)

### 4. **Funcionalidades Especiais**
- ✅ Horário Fixo: Turmas podem ter horário fixo para não serem alteradas
- ✅ API para buscar professores disponíveis dinamicamente
- ✅ Responsivo e com design moderno (gradiente cyberpunk)

## 🗄️ Estrutura do Banco de Dados

### Executar SQL de Estrutura
Execute o arquivo: `sql_estrutura_horarios.sql`

Principais tabelas:
- `horarios_aulas` - Grade de horários por turma
- `horarios_disponiveis_professor` - Disponibilidade dos professores
- `intervalos` - Definição dos horários de aula
- `turmas` - Campo adicional: `horario_fixo`

## 📝 Como Usar

### Passo 1: Configurar Intervalos de Horário
Insira os horários de aula na tabela `intervalos`:
```sql
INSERT INTO intervalos (hora_inicio, hora_fim, ordem) VALUES
('07:00', '07:50', 1),
('07:50', '08:40', 2),
('08:40', '09:30', 3),
('09:50', '10:40', 4),
('10:40', '11:30', 5),
('11:30', '12:20', 6);
```

### Passo 2: Configurar Disponibilidade dos Professores
1. Acesse: `disponibilidade_professor.php`
2. Selecione um professor
3. Marque os horários como "Livre" (verde) ou "Ocupado" (vermelho)
4. Salve

### Passo 3: Criar Horário de uma Turma
1. Acesse: `interface_horarios.php`
2. Selecione "Por Turma"
3. Escolha a turma
4. Clique em "Editar Horário"
5. Para cada slot:
   - Selecione a disciplina
   - O sistema carregará automaticamente os professores disponíveis
   - Professores ocupados aparecem em vermelho
6. Marque "Horário Fixo" se não quiser que seja alterado na geração automática
7. Salve

### Passo 4: Visualizar Horários
- **Por Turma**: Selecione a turma e veja a grade completa
- **Por Professor**: Selecione o professor e veja todas as aulas que ele leciona
- **Imprimir**: Clique no botão "Imprimir/PDF" para gerar versão impressa

## 🔧 Arquivos Criados/Modificados

1. `interface_horarios.php` - Página principal de gerenciamento
2. `disponibilidade_professor.php` - Configuração de disponibilidade
3. `editar_horario_turma.php` - Edição da grade de horários
4. `api_professores_disponiveis.php` - API para buscar professores disponíveis
5. `sql_estrutura_horarios.sql` - Script SQL de criação das tabelas
6. `verificar_estrutura.php` - Utilitário para verificar estrutura do banco

## ⚙️ Próximos Passos (Pendente)

### Geração Automática de Horários
Para implementar a geração automática, será necessário:
- Algoritmo de distribuição de disciplinas por turma
- Considerar carga horária de cada disciplina
- Respeitar disponibilidade de professores
- Não alterar turmas com `horario_fixo = TRUE`
- Detectar e evitar conflitos

Arquivo a criar: `gerar_horario_auto.php`

## 🎨 Design
- Tema: Gradiente cyberpunk (azul/amarelo)
- Fonte: Orbitron (futurista)
- Totalmente responsivo
- Otimizado para impressão

## 🔒 Segurança
- Validação de sessão em todas as páginas
- Proteção contra SQL Injection (prepared statements)
- Validação de conflitos no backend

## 📱 Compatibilidade
- ✅ Desktop
- ✅ Tablet
- ✅ Mobile
- ✅ Impressão/PDF

---

**Desenvolvido para MTech Escola** 🚀
