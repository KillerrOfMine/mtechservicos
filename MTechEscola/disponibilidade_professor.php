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

$professor_id = $_GET['professor_id'] ?? '';
$msg = '';

try {
    // Busca professores
    $professores = $conn->query("SELECT id, nome FROM professores WHERE ativo = TRUE ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

    // Busca intervalos/horários disponíveis
    $intervalos = $conn->query("SELECT id, hora_inicio, hora_fim FROM intervalos GROUP BY id, hora_inicio, hora_fim ORDER BY hora_inicio")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}

$dias_semana = [1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta'];

// Salvar disponibilidade
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $professor_id) {
    $disponibilidades = $_POST['disponibilidade'] ?? [];
    
    // Limpa disponibilidades anteriores
    $stmt = $conn->prepare("DELETE FROM horarios_disponiveis_professor WHERE professor_id = ?");
    $stmt->execute([$professor_id]);
    
    // Insere novas disponibilidades
    $count = 0;
    foreach ($disponibilidades as $key => $value) {
        list($dia, $hora_inicio) = explode('_', $key);
        $intervalo = null;
        foreach ($intervalos as $i) {
            if ($i['hora_inicio'] == $hora_inicio) {
                $intervalo = $i;
                break;
            }
        }
        if ($intervalo) {
            $disponivel = ($value === 'disponivel');
            $stmt = $conn->prepare("INSERT INTO horarios_disponiveis_professor (professor_id, dia_semana, hora_inicio, hora_fim, disponivel) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$professor_id, $dia, $intervalo['hora_inicio'], $intervalo['hora_fim'], $disponivel]);
            $count++;
        }
    }
    $msg = "$count horários configurados com sucesso!";
}

// Busca disponibilidades atuais
$disponibilidades_atuais = [];
if ($professor_id) {
    $stmt = $conn->prepare("SELECT * FROM horarios_disponiveis_professor WHERE professor_id = ?");
    $stmt->execute([$professor_id]);
    $disp = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($disp as $d) {
        $key = $d['dia_semana'] . '_' . $d['hora_inicio'];
        $disponibilidades_atuais[$key] = $d['disponivel'] ? 'disponivel' : 'indisponivel';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Disponibilidade de Professores - MTech Escola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(120deg, #0f2027, #2c5364 80%); min-height: 100vh; margin: 0; font-family: 'Orbitron', Arial, sans-serif; color: #fff; }
        .container { background: rgba(20, 30, 50, 0.85); border-radius: 24px; box-shadow: 0 8px 32px #000a; padding: 32px; max-width: 1000px; margin: 40px auto; }
        h1 { font-size: 2em; font-weight: 700; letter-spacing: 2px; margin-bottom: 24px; background: linear-gradient(90deg, #00c3ff, #ffff1c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .filters { background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; margin-bottom: 24px; }
        select { width: 100%; max-width: 400px; padding: 10px; border-radius: 8px; border: none; font-size: 1em; }
        table { width: 100%; border-collapse: collapse; background: rgba(255,255,255,0.05); margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid rgba(255,255,255,0.1); text-align: center; }
        th { background: rgba(0,195,255,0.2); color: #ffff1c; font-weight: 700; }
        td { background: rgba(255,255,255,0.03); }
        .status-cell { display: flex; gap: 10px; justify-content: center; align-items: center; }
        .status-btn { padding: 6px 12px; border-radius: 6px; cursor: pointer; border: 2px solid transparent; transition: all 0.3s; }
        .status-btn.disponivel { background: #4caf50; color: #fff; }
        .status-btn.indisponivel { background: #f44336; color: #fff; }
        .status-btn.selected { border-color: #ffff1c; box-shadow: 0 0 10px #ffff1c; }
        input[type="radio"] { display: none; }
        .btn { background: linear-gradient(90deg, #00c3ff 40%, #ffff1c 100%); color: #222; font-weight: 700; border: none; border-radius: 12px; padding: 12px 32px; font-size: 1em; cursor: pointer; box-shadow: 0 2px 8px #0005; margin-top: 24px; }
        .btn:hover { transform: scale(1.05); box-shadow: 0 4px 16px #00c3ff55; }
        .msg { background:#00c3ff;color:#222;padding:12px;border-radius:8px;margin-bottom:16px;font-weight:700;text-align:center; }
        .legend { margin-top: 20px; display: flex; gap: 20px; justify-content: center; font-size: 0.9em; }
        .legend-item { display: flex; align-items: center; gap: 8px; }
        .legend-color { width: 20px; height: 20px; border-radius: 4px; }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
    <h1>Gerenciar Disponibilidade de Professores</h1>
    
    <?php if ($msg): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    
    <div class="filters">
        <form method="get">
            <label for="professor_id">Selecione o Professor:</label>
            <select name="professor_id" id="professor_id" onchange="this.form.submit()" required>
                <option value="">Selecione um professor</option>
                <?php foreach ($professores as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $professor_id == $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    
    <?php if ($professor_id): ?>
        <form method="post">
            <p style="text-align: center; margin-bottom: 20px; color: #ffff1c;">
                Clique nas células para marcar como <strong style="color:#4caf50;">Disponível</strong> ou <strong style="color:#f44336;">Indisponível</strong>
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
                            <td><strong><?= substr($intervalo['hora_inicio'], 0, 5) ?> - <?= substr($intervalo['hora_fim'], 0, 5) ?></strong></td>
                            <?php foreach ($dias_semana as $dia_num => $dia_nome): ?>
                                <td>
                                    <?php
                                    $key = $dia_num . '_' . $intervalo['hora_inicio'];
                                    $current_value = $disponibilidades_atuais[$key] ?? 'disponivel';
                                    ?>
                                    <div class="status-cell">
                                        <input type="radio" name="disponibilidade[<?= $key ?>]" value="disponivel" id="<?= $key ?>_d" <?= $current_value === 'disponivel' ? 'checked' : '' ?>>
                                        <label for="<?= $key ?>_d" class="status-btn disponivel <?= $current_value === 'disponivel' ? 'selected' : '' ?>" onclick="selectStatus('<?= $key ?>', 'disponivel')">
                                            Livre
                                        </label>
                                        
                                        <input type="radio" name="disponibilidade[<?= $key ?>]" value="indisponivel" id="<?= $key ?>_i" <?= $current_value === 'indisponivel' ? 'checked' : '' ?>>
                                        <label for="<?= $key ?>_i" class="status-btn indisponivel <?= $current_value === 'indisponivel' ? 'selected' : '' ?>" onclick="selectStatus('<?= $key ?>', 'indisponivel')">
                                            Ocupado
                                        </label>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="text-align: center;">
                <button type="submit" class="btn">Salvar Disponibilidade</button>
                <a href="interface_horarios.php?view=professor&professor_id=<?= $professor_id ?>" class="btn" style="background: linear-gradient(90deg, #666 40%, #999 100%);">Ver Horários</a>
            </div>
            
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-color" style="background:#4caf50;"></div>
                    <span>Disponível para lecionar</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background:#f44336;"></div>
                    <span>Indisponível (ocupado)</span>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
function selectStatus(key, status) {
    // Marca o radio correto
    document.getElementById(key + '_' + (status === 'disponivel' ? 'd' : 'i')).checked = true;
    
    // Remove selected de ambos
    document.querySelectorAll(`label[for^="${key}"]`).forEach(el => el.classList.remove('selected'));
    
    // Adiciona selected no clicado
    document.getElementById(key + '_' + (status === 'disponivel' ? 'd' : 'i')).nextElementSibling.classList.add('selected');
}
</script>
</body>
</html>
