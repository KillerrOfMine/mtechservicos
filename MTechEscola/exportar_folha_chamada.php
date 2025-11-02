<?php
require_once 'db_connect_horarios.php';
$turma_id = $_GET['turma_id'] ?? '';
$disciplina_id = $_GET['disciplina_id'] ?? '';
if (!$turma_id || !$disciplina_id) {
    die('Turma e disciplina obrigatórias!');
}
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename=folha_chamada.xls');
// Busca dados da escola
$escola = $conn->query("SELECT nome, endereco, cep, cidade, uf FROM dados_empresa LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$ano = date('Y');
$alunos = $conn->query("SELECT id, nome FROM alunos WHERE turma_id = $turma_id ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$atividades = $conn->query("SELECT id, nome, data FROM atividades WHERE turma_id = $turma_id AND disciplina_id = $disciplina_id ORDER BY data ASC")->fetchAll(PDO::FETCH_ASSOC);
$presencas = [];
$presenca_stmt = $conn->prepare("SELECT aluno_id, atividade_id, presente FROM presencas WHERE atividade_id IN (SELECT id FROM atividades WHERE turma_id = ? AND disciplina_id = ?)");
$presenca_stmt->execute([$turma_id, $disciplina_id]);
while ($row = $presenca_stmt->fetch(PDO::FETCH_ASSOC)) {
    $presencas[$row['aluno_id']][$row['atividade_id']] = $row['presente'];
}
$medias = [];
$media_stmt = $conn->prepare("SELECT aluno_id, AVG(nota) as media FROM notas WHERE atividade_id IN (SELECT id FROM atividades WHERE turma_id = ? AND disciplina_id = ?) GROUP BY aluno_id");
$media_stmt->execute([$turma_id, $disciplina_id]);
while ($row = $media_stmt->fetch(PDO::FETCH_ASSOC)) {
    $medias[$row['aluno_id']] = round($row['media'],2);
}
echo "<table border='1'>";
echo "<tr><th colspan='2'>".htmlspecialchars($escola['nome'])."</th><th colspan='".(count($atividades)+2)."'>Folha de Chamada - Ano $ano</th></tr>";
echo "<tr><th colspan='2'>Turma</th><th colspan='".(count($atividades)+2)."'>Disciplina</th></tr>";
echo "<tr><th>Nº</th><th>Alunos</th>";
foreach ($atividades as $atv) {
    echo "<th>".date('d/m', strtotime($atv['data']))."</th>";
}
echo "<th>Faltas</th><th>Média</th></tr>";
foreach ($alunos as $i => $aluno) {
    echo "<tr><td>".($i+1)."</td><td>".htmlspecialchars($aluno['nome'])."</td>";
    foreach ($atividades as $atv) {
        $presente = $presencas[$aluno['id']][$atv['id']] ?? null;
        echo "<td>".($presente === null ? '-' : ($presente ? '✔' : 'F'))."</td>";
    }
    $faltas = 0;
    foreach ($atividades as $atv) {
        $presente = $presencas[$aluno['id']][$atv['id']] ?? null;
        if ($presente === 0) $faltas++;
    }
    echo "<td>$faltas</td>";
    echo "<td>".($medias[$aluno['id']] ?? '-')."</td></tr>";
}
echo "</table>";
