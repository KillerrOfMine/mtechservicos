<?php
session_start();
require_once 'db_connect_horarios.php';

// Debug mode
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$turma_id = $_GET['turma_id'] ?? '';
$msg = '';

if (!$turma_id) {
    header('Location: interface_horarios.php');
    exit;
}

try {
    // Busca informações da turma
    $stmt = $conn->prepare("SELECT * FROM turmas WHERE id = ?");
    $stmt->execute([$turma_id]);
    $turma = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$turma) {
        die("Turma não encontrada!");
    }

    // Busca disciplinas
    $disciplinas = $conn->query("SELECT id, nome FROM disciplinas WHERE ativo = TRUE ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

    // Busca intervalos agrupados por hora
    $intervalos_query = $conn->query("
        SELECT DISTINCT hora_inicio, hora_fim 
        FROM intervalos 
        ORDER BY hora_inicio
    ");
    $intervalos = $intervalos_query->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($intervalos)) {
        die("Nenhum intervalo de horário cadastrado! <a href='verificar_intervalos.php'>Clique aqui para configurar</a>");
    }

    $dias_semana = [1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta'];
    
} catch (Exception $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}

// Processar salvamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $horarios = $_POST['horarios'] ?? [];
        $horario_fixo = isset($_POST['horario_fixo']) ? 'TRUE' : 'FALSE';
        
        // Atualiza status de horário fixo
        $stmt = $conn->prepare("UPDATE turmas SET horario_fixo = $horario_fixo WHERE id = ?");
        $stmt->execute([$turma_id]);
        
        // Remove horários antigos
        $stmt = $conn->prepare("DELETE FROM horarios_aulas WHERE turma_id = ?");
        $stmt->execute([$turma_id]);
        
        $count = 0;
        $erros = [];
        
        // Verifica se tabela tem coluna intervalo_id
        $tem_intervalo_id = false;
        try {
            $check = $conn->query("SELECT intervalo_id FROM horarios_aulas LIMIT 0");
            $tem_intervalo_id = true;
        } catch (Exception $e) {
            $tem_intervalo_id = false;
        }
        
        foreach ($horarios as $key => $data) {
            if (empty($data['disciplina_id'])) continue;
            
            list($dia, $hora_inicio) = explode('_', $key);
            $disciplina_id = $data['disciplina_id'];
            $professor_id = !empty($data['professor_id']) ? $data['professor_id'] : null;
            
            if ($tem_intervalo_id) {
                // ESTRUTURA NOVA: Usa intervalo_id
                $stmt_intervalo = $conn->prepare("
                    SELECT id, hora_fim 
                    FROM intervalos 
                    WHERE dia_semana = ? AND hora_inicio = ? 
                    LIMIT 1
                ");
                $stmt_intervalo->execute([$dia, $hora_inicio]);
                $intervalo = $stmt_intervalo->fetch(PDO::FETCH_ASSOC);
                
                if (!$intervalo) {
                    $erros[] = "Intervalo não encontrado para " . $dias_semana[$dia] . " às " . $hora_inicio;
                    continue;
                }
                
                // Verifica conflito de professor
                if ($professor_id) {
                    $stmt_conflito = $conn->prepare("
                        SELECT COUNT(*) 
                        FROM horarios_aulas ha
                        JOIN intervalos i ON ha.intervalo_id = i.id
                        WHERE ha.professor_id = ? 
                        AND i.dia_semana = ? 
                        AND i.hora_inicio = ? 
                        AND ha.turma_id != ?
                        AND COALESCE(ha.ativo, TRUE) = TRUE
                    ");
                    $stmt_conflito->execute([$professor_id, $dia, $hora_inicio, $turma_id]);
                    
                    if ($stmt_conflito->fetchColumn() > 0) {
                        $erros[] = "Conflito: Professor já tem aula em " . $dias_semana[$dia] . " às " . substr($hora_inicio, 0, 5);
                        continue;
                    }
                }
                
                // Insere horário com intervalo_id
                $stmt_insert = $conn->prepare("
                    INSERT INTO horarios_aulas (turma_id, disciplina_id, professor_id, intervalo_id, ativo) 
                    VALUES (?, ?, ?, ?, TRUE)
                ");
                $stmt_insert->execute([$turma_id, $disciplina_id, $professor_id, $intervalo['id']]);
                
            } else {
                // ESTRUTURA ANTIGA: Usa dia_semana, hora_inicio, hora_fim diretamente
                $hora_fim = null;
                foreach ($intervalos as $i) {
                    if ($i['hora_inicio'] == $hora_inicio) {
                        $hora_fim = $i['hora_fim'];
                        break;
                    }
                }
                
                if (!$hora_fim) {
                    $erros[] = "Horário de fim não encontrado para " . $hora_inicio;
                    continue;
                }
                
                // Verifica conflito de professor
                if ($professor_id) {
                    $stmt_conflito = $conn->prepare("
                        SELECT COUNT(*) 
                        FROM horarios_aulas
                        WHERE professor_id = ? 
                        AND dia_semana = ? 
                        AND hora_inicio = ? 
                        AND turma_id != ?
                        AND COALESCE(ativo, TRUE) = TRUE
                    ");
                    $stmt_conflito->execute([$professor_id, $dia, $hora_inicio, $turma_id]);
                    
                    if ($stmt_conflito->fetchColumn() > 0) {
                        $erros[] = "Conflito: Professor já tem aula em " . $dias_semana[$dia] . " às " . substr($hora_inicio, 0, 5);
                        continue;
                    }
                }
                
                // Insere horário com campos diretos
                $stmt_insert = $conn->prepare("
                    INSERT INTO horarios_aulas (turma_id, disciplina_id, professor_id, dia_semana, hora_inicio, hora_fim, ativo) 
                    VALUES (?, ?, ?, ?, ?, ?, TRUE)
                ");
                $stmt_insert->execute([$turma_id, $disciplina_id, $professor_id, $dia, $hora_inicio, $hora_fim]);
            }
            
            $count++;
        }
        
        if ($count > 0) {
            $msg = "$count horários salvos com sucesso!" . (count($erros) > 0 ? " (" . count($erros) . " erros)" : "");
        }
        if (count($erros) > 0) {
            $msg .= "<br><small>" . implode("<br>", $erros) . "</small>";
        }
        
    } catch (Exception $e) {
        $msg = "Erro ao salvar: " . $e->getMessage();
    }
}

// Busca horários atuais - tenta com intervalo_id primeiro, senão usa campos diretos
try {
    // Tenta buscar com intervalo_id (estrutura nova)
    $stmt = $conn->prepare("
        SELECT ha.*, i.dia_semana, i.hora_inicio, i.hora_fim
        FROM horarios_aulas ha
        JOIN intervalos i ON ha.intervalo_id = i.id
        WHERE ha.turma_id = ? 
        AND COALESCE(ha.ativo, TRUE) = TRUE
    ");
    $stmt->execute([$turma_id]);
    $horarios_atuais = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Se falhar, usa estrutura antiga (dia_semana, hora_inicio diretamente na tabela)
    try {
        $stmt = $conn->prepare("
            SELECT * 
            FROM horarios_aulas 
            WHERE turma_id = ? 
            AND COALESCE(ativo, TRUE) = TRUE
        ");
        $stmt->execute([$turma_id]);
        $horarios_atuais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e2) {
        $horarios_atuais = [];
        $msg = "Aviso: Erro ao carregar horários existentes - " . $e2->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Horário - MTech Escola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(120deg, #0f2027, #2c5364 80%); min-height: 100vh; margin: 0; font-family: 'Orbitron', Arial, sans-serif; color: #fff; }
        .container { background: rgba(20, 30, 50, 0.85); border-radius: 24px; box-shadow: 0 8px 32px #000a; padding: 32px; max-width: 1400px; margin: 40px auto; }
        h1 { font-size: 2em; font-weight: 700; letter-spacing: 2px; margin-bottom: 24px; background: linear-gradient(90deg, #00c3ff, #ffff1c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        table { width: 100%; border-collapse: collapse; background: rgba(255,255,255,0.05); }
        th, td { padding: 10px; border: 1px solid rgba(255,255,255,0.1); text-align: center; vertical-align: top; }
        th { background: rgba(0,195,255,0.2); color: #ffff1c; font-weight: 700; }
        td { background: rgba(255,255,255,0.03); }
        select { width: 100%; padding: 6px; border-radius: 6px; border: none; font-size: 0.9em; margin-bottom: 4px; }
        .btn { background: linear-gradient(90deg, #00c3ff 40%, #ffff1c 100%); color: #222; font-weight: 700; border: none; border-radius: 12px; padding: 12px 32px; font-size: 1em; cursor: pointer; box-shadow: 0 2px 8px #0005; margin-top: 24px; text-decoration: none; display: inline-block; }
        .btn:hover { transform: scale(1.05); box-shadow: 0 4px 16px #00c3ff55; }
        .msg { background:#00c3ff;color:#222;padding:12px;border-radius:8px;margin-bottom:16px;font-weight:700; }
        .checkbox-label { display: flex; align-items: center; gap: 10px; margin: 20px 0; font-size: 1.1em; }
        .checkbox-label input { width: 20px; height: 20px; }
        .actions { display: flex; gap: 16px; justify-content: center; margin-top: 24px; }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
    <h1>Editar Horário - <?= htmlspecialchars($turma['nome']) ?></h1>
    
    <?php if ($msg): ?>
        <div class="msg"><?= $msg ?></div>
    <?php endif; ?>
    
    <form method="post" id="horarioForm">
        <label class="checkbox-label">
            <input type="checkbox" name="horario_fixo" value="1" <?= $turma['horario_fixo'] ? 'checked' : '' ?>>
            <span>Marcar como Horário Fixo (não será alterado na geração automática)</span>
        </label>
        
        <p style="text-align: center; color: #ffff1c; margin-bottom: 20px;">
            Selecione a disciplina e o professor para cada horário. Deixe em branco para horários vazios.
        </p>
        
        <table>
            <thead>
                <tr>
                    <th>Horário</th>
                    <?php foreach ($dias_semana as $dia): ?>
                        <th><?= $dia ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($intervalos as $intervalo): ?>
                    <tr>
                        <td><strong><?= substr($intervalo['hora_inicio'], 0, 5) ?><br><?= substr($intervalo['hora_fim'], 0, 5) ?></strong></td>
                        <?php foreach ($dias_semana as $dia_num => $dia_nome): ?>
                            <td>
                                <?php
                                $key = $dia_num . '_' . $intervalo['hora_inicio'];
                                $horario_atual = null;
                                foreach ($horarios_atuais as $h) {
                                    if ($h['dia_semana'] == $dia_num && $h['hora_inicio'] == $intervalo['hora_inicio']) {
                                        $horario_atual = $h;
                                        break;
                                    }
                                }
                                ?>
                                <select name="horarios[<?= $key ?>][disciplina_id]" id="disc_<?= $key ?>" onchange="loadProfessores('<?= $key ?>', this.value, <?= $dia_num ?>, '<?= $intervalo['hora_inicio'] ?>')">
                                    <option value="">-- Sem aula --</option>
                                    <?php foreach ($disciplinas as $d): ?>
                                        <option value="<?= $d['id'] ?>" <?= ($horario_atual && $horario_atual['disciplina_id'] == $d['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($d['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="horarios[<?= $key ?>][professor_id]" id="prof_<?= $key ?>">
                                    <option value="">Carregando...</option>
                                </select>
                                <?php if ($horario_atual): ?>
                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            loadProfessores('<?= $key ?>', '<?= $horario_atual['disciplina_id'] ?>', <?= $dia_num ?>, '<?= $intervalo['hora_inicio'] ?>', <?= $horario_atual['professor_id'] ?? 'null' ?>);
                                        });
                                    </script>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="actions">
            <button type="submit" class="btn">Salvar Horário</button>
            <a href="interface_horarios.php?turma_id=<?= $turma_id ?>" class="btn" style="background: linear-gradient(90deg, #666 40%, #999 100%);">Cancelar</a>
        </div>
    </form>
</div>

<script>
function loadProfessores(key, disciplinaId, diaSemana, horaInicio, selectedProfId = null) {
    const profSelect = document.getElementById('prof_' + key);
    
    if (!disciplinaId) {
        profSelect.innerHTML = '<option value="">-- Sem aula --</option>';
        return;
    }
    
    profSelect.innerHTML = '<option value="">Carregando...</option>';
    
    fetch(`api_professores_disponiveis.php?disciplina_id=${disciplinaId}&dia_semana=${diaSemana}&hora_inicio=${horaInicio}&turma_id=<?= $turma_id ?>`)
        .then(r => r.json())
        .then(data => {
            profSelect.innerHTML = '<option value="">-- Sem professor --</option>';
            data.forEach(prof => {
                const option = document.createElement('option');
                option.value = prof.id;
                option.textContent = prof.nome + (prof.disponivel ? '' : ' (Ocupado)');
                if (!prof.disponivel) {
                    option.style.color = '#ff3c3c';
                }
                if (selectedProfId && prof.id == selectedProfId) {
                    option.selected = true;
                }
                profSelect.appendChild(option);
            });
        })
        .catch(err => {
            profSelect.innerHTML = '<option value="">Erro ao carregar</option>';
        });
}
</script>
</body>
</html>
