<?php
session_start();
require_once 'db_connect_horarios.php';
try {
    if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_perfil'], ['admin','professor'])) {
        header('Location: login.php');
        exit;
    }
    // Busca dados da escola
    $escola = $conn->query("SELECT nome, endereco, cep, cidade, uf, logo FROM dados_empresa LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    // Busca turmas e disciplinas
    $turmas = $conn->query("SELECT id, nome FROM turmas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
    $disciplinas = $conn->query("SELECT id, nome FROM disciplinas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
    $ano = date('Y');
    $turma_id = $_GET['turma_id'] ?? '';
    $disciplina_id = $_GET['disciplina_id'] ?? '';
    $alunos = [];
    $datas_aulas = [];
    $presencas = [];
    $medias = [];
    if ($turma_id && $disciplina_id) {
        $alunos = $conn->query("SELECT id, nome FROM alunos WHERE turma_id = $turma_id ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
        
        // Busca todas as datas de aulas registradas (distintas)
        $datas_stmt = $conn->prepare("SELECT DISTINCT aula_data FROM presencas WHERE turma_id = ? AND disciplina_id = ? ORDER BY aula_data ASC");
        $datas_stmt->execute([$turma_id, $disciplina_id]);
        $datas_aulas = $datas_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Presenças por aluno/data da aula
        $presenca_stmt = $conn->prepare("SELECT aluno_id, aula_data, status FROM presencas WHERE turma_id = ? AND disciplina_id = ?");
        $presenca_stmt->execute([$turma_id, $disciplina_id]);
        while ($row = $presenca_stmt->fetch(PDO::FETCH_ASSOC)) {
            // Mapeia status (presente/ausente) para cada data de aula
            $presencas[$row['aluno_id']][$row['aula_data']] = ($row['status'] === 'presente' || $row['status'] === 'P') ? 1 : 0;
        }
        
        // Médias automáticas (simples: média das notas das atividades)
        $media_stmt = $conn->prepare("SELECT aluno_id, AVG(nota) as media FROM notas WHERE atividade_id IN (SELECT id FROM atividades WHERE turma_id = ? AND disciplina_id = ?) GROUP BY aluno_id");
        $media_stmt->execute([$turma_id, $disciplina_id]);
        while ($row = $media_stmt->fetch(PDO::FETCH_ASSOC)) {
            $medias[$row['aluno_id']] = round($row['media'],2);
        }
    }
} catch (Exception $e) {
    echo '<div style="background:#ff3c3c;color:#fff;padding:16px;border-radius:8px;margin:24px 0;font-weight:700;text-align:center;">Erro: '.htmlspecialchars($e->getMessage()).'</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Folha de Chamada - MTech Escola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; background: #fff; color: #222; }
        .container { max-width: 1200px; margin: 30px auto; background: #f9f9f9; border-radius: 16px; box-shadow: 0 4px 24px #0002; padding: 32px; }
        h1 { text-align: center; font-size: 2em; margin-bottom: 16px; }
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
        .logo { height: 80px; }
        .dados-escola { font-size: 1em; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { border: 1px solid #222; padding: 6px; text-align: center; font-size: 0.95em; }
        th { background: #e0e0e0; }
        .aluno { text-align: left; }
        .print-btn, .export-btn { margin: 12px 8px; padding: 8px 24px; border-radius: 8px; border: none; background: #00c3ff; color: #fff; font-weight: 700; cursor: pointer; }
        .print-btn:hover, .export-btn:hover { background: #0077b6; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <?php if (!empty($escola['logo'])): ?>
            <img src="<?= htmlspecialchars($escola['logo']) ?>" class="logo" alt="Logo">
        <?php endif; ?>
        <div class="dados-escola">
            <?= htmlspecialchars($escola['nome']) ?><br>
            <?= htmlspecialchars($escola['endereco']) ?><br>
            CEP: <?= htmlspecialchars($escola['cep']) ?> <?= htmlspecialchars($escola['cidade']) ?> - <?= htmlspecialchars($escola['uf']) ?>
        </div>
        <div>
            <strong>Ano:</strong> <?= $ano ?>
        </div>
    </div>
    <form method="get" style="margin-bottom:24px;">
        <label for="turma_id">Turma:</label>
        <select name="turma_id" id="turma_id" required onchange="this.form.submit()">
            <option value="">Selecione</option>
            <?php foreach ($turmas as $turma): ?>
                <option value="<?= $turma['id'] ?>" <?= ($turma_id == $turma['id']) ? 'selected' : '' ?>><?= htmlspecialchars($turma['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <label for="disciplina_id">Disciplina:</label>
        <select name="disciplina_id" id="disciplina_id" required onchange="this.form.submit()">
            <option value="">Selecione</option>
            <?php foreach ($disciplinas as $disc): ?>
                <option value="<?= $disc['id'] ?>" <?= ($disciplina_id == $disc['id']) ? 'selected' : '' ?>><?= htmlspecialchars($disc['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php if ($turma_id && $disciplina_id): ?>
    <table>
        <tr>
            <th>Nº</th>
            <th>Alunos</th>
            <?php foreach ($datas_aulas as $data): ?>
                <th><?= date('d/m', strtotime($data)) ?></th>
            <?php endforeach; ?>
            <th>Faltas</th>
            <th>Média</th>
        </tr>
        <?php foreach ($alunos as $i => $aluno): ?>
        <tr>
            <td><?= $i+1 ?></td>
            <td class="aluno"><?= htmlspecialchars($aluno['nome']) ?></td>
            <?php foreach ($datas_aulas as $data): ?>
                <td>
                    <?php
                    // Presença pela data da aula
                    $presente = $presencas[$aluno['id']][$data] ?? null;
                    echo $presente === null ? '-' : ($presente ? '✔' : 'F');
                    ?>
                </td>
            <?php endforeach; ?>
            <td>
                <?php
                // Total de faltas
                $faltas = 0;
                foreach ($datas_aulas as $data) {
                    $presente = $presencas[$aluno['id']][$data] ?? null;
                    if ($presente === 0) $faltas++;
                }
                echo $faltas;
                ?>
            </td>
            <td>
                <?= $medias[$aluno['id']] ?? '-' ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <button class="print-btn" onclick="window.print();return false;">Imprimir</button>
    <button class="export-btn" onclick="window.location.href='exportar_folha_chamada.php?turma_id=<?= $turma_id ?>&disciplina_id=<?= $disciplina_id ?>'">Exportar Excel</button>
    <button class="export-btn" onclick="window.location.href='exportar_folha_chamada_pdf.php?turma_id=<?= $turma_id ?>&disciplina_id=<?= $disciplina_id ?>'">Exportar PDF</button>
    <?php endif; ?>
</div>
</body>
</html>
