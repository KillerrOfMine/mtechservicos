<?php
require_once 'db_connect_horarios.php';
if (!isset($_GET['id'])) {
    echo '<p>Professor não encontrado.</p>';
    echo '<a href="professores.php" class="btn">Voltar</a>';
    exit;
}
$id = $_GET['id'];
// Remove vínculos de disciplinas
$conn->prepare("DELETE FROM professores_disciplinas WHERE professor_id = ?")->execute([$id]);
// Remove o professor
$conn->prepare("DELETE FROM professores WHERE id = ?")->execute([$id]);
echo '<script>alert("Professor excluído com sucesso!"); window.location.href = "professores.php";</script>';
exit;
?>