<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['role'], ['admin', 'superuser'])) {
    header('Location: /erp/login.php');
    exit;
}
require_once __DIR__ . '/includes/db_connect.php';
// Carregar configuração atual
$stmt = $pdo->query('SELECT * FROM configuracoes ORDER BY id DESC LIMIT 1');
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Função para remover imagem
if (isset($_POST['remover'])) {
    $campo = $_POST['remover'];
    if (in_array($campo, ['logo_clara', 'logo_escura', 'favicon'])) {
        $stmt = $pdo->prepare("UPDATE configuracoes SET $campo = '' WHERE id = :id");
        $stmt->execute([':id' => $config['id']]);
        header('Location: personalizacao.php?ok=1');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar'])) {
    $campo = $_POST['salvar'];
    $img_path = '';
    if (!empty($_FILES[$campo]['name'])) {
        $ext = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));
        $img_name = $campo . '_' . time() . '.' . $ext;
        $dest = __DIR__ . '/assets/img/' . $img_name;
        if (move_uploaded_file($_FILES[$campo]['tmp_name'], $dest)) {
            $img_path = '/erp/assets/img/' . $img_name;
        }
    }
    if ($img_path) {
        $stmt = $pdo->prepare("UPDATE configuracoes SET $campo = :img WHERE id = :id");
        $stmt->execute([':img' => $img_path, ':id' => $config['id']]);
        header('Location: personalizacao.php?ok=1');
        exit;
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<style>
.personalizacao-cards {
    display: flex;
    gap: 32px;
    justify-content: center;
    margin-bottom: 32px;
    flex-wrap: wrap;
}
.card-upload {
    background: #fff;
    border-radius: 24px;
    box-shadow: 0 2px 16px rgba(24,87,216,0.10);
    padding: 24px 18px 18px 18px;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 220px;
    max-width: 260px;
    min-height: 320px;
    position: relative;
}
.card-upload label {
    font-weight: 700;
    color: #1857d8;
    margin-bottom: 12px;
    font-size: 1.15em;
    text-align: center;
}
.card-upload .preview {
    width: 140px;
    height: 140px;
    border-radius: 20px;
    background: #f8fafc;
    box-shadow: 0 2px 8px rgba(24,87,216,0.10);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 18px;
    overflow: hidden;
}
.card-upload .preview.clara {
    background: #23272f;
}
.card-upload .preview.favicon {
    border-radius: 50%;
    width: 120px;
    height: 120px;
}
.card-upload img, .card-upload div svg {
    max-width: 100%;
    max-height: 100%;
    display: block;
}
.card-upload .actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    margin-top: 12px;
}
.card-upload button {
    background: linear-gradient(90deg,#1857d8,#22aaff);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 8px 24px;
    font-size: 1em;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
    box-shadow: 0 2px 8px rgba(24,87,216,0.10);
}
.card-upload button:hover {
    background: linear-gradient(90deg,#22aaff,#1857d8);
    color: #fff;
}
@media (max-width: 900px) {
    .personalizacao-cards {
        flex-direction: column;
        gap: 18px;
        align-items: stretch;
    }
    .card-upload {
        min-width: 120px;
        max-width: 100%;
    }
}
</style>
<div class="container" style="max-width:900px;margin:32px auto;background:#fff;border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.08);padding:32px 24px;">
    <h1 style="font-size:2rem;margin-bottom:24px;text-align:center;color:#1857d8;">Personalização do Sistema</h1>
    <?php if (isset($_GET['ok'])): ?>
        <div class="ok" style="color:#1857d8;font-weight:bold;margin-bottom:16px;text-align:center;">Configurações salvas com sucesso!</div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" style="width:100%;">
        <div class="personalizacao-cards">
            <div class="card-upload">
                <label for="logo_clara">Logo Clara</label>
                <div class="preview clara">
                    <?php if (!empty($config['logo_clara'])): ?>
                        <?php
                        $logo_path = __DIR__ . '/' . ltrim($config['logo_clara'], '/\\');
                        $ext = strtolower(pathinfo($logo_path, PATHINFO_EXTENSION));
                        if ($ext === 'svg' && is_file($logo_path)) {
                            echo '<div style="width:100%;height:100%;">'.file_get_contents($logo_path).'</div>';
                        } else {
                            echo '<img src="'.htmlspecialchars($config['logo_clara']).'" alt="Logo Clara">';
                        }
                        ?>
                    <?php endif; ?>
                </div>
                <input type="file" name="logo_clara" id="logo_clara">
                <div class="actions">
                    <button type="submit" name="remover" value="logo_clara">Remover</button>
                    <button type="submit" name="salvar" value="logo_clara">Salvar</button>
                </div>
            </div>
            <div class="card-upload">
                <label for="logo_escura" style="color:#1857d8;">Logo Escura</label>
                <div class="preview" style="background:#f8fafc;">
                    <?php if (!empty($config['logo_escura'])): ?>
                        <?php
                        $logo_path = __DIR__ . '/' . ltrim($config['logo_escura'], '/\\');
                        $ext = strtolower(pathinfo($logo_path, PATHINFO_EXTENSION));
                        if ($ext === 'svg' && is_file($logo_path)) {
                            echo '<div style="width:100%;height:100%;">'.file_get_contents($logo_path).'</div>';
                        } else {
                            echo '<img src="'.htmlspecialchars($config['logo_escura']).'" alt="Logo Escura">';
                        }
                        ?>
                    <?php endif; ?>
                </div>
                <input type="file" name="logo_escura" id="logo_escura">
                <div class="actions">
                    <button type="submit" name="remover" value="logo_escura">Remover</button>
                    <button type="submit" name="salvar" value="logo_escura">Salvar</button>
                </div>
            </div>
            <div class="card-upload">
                <label for="favicon">Fav Icon</label>
                <div class="preview favicon">
                    <?php if (!empty($config['favicon'])): ?>
                        <img src="<?= htmlspecialchars($config['favicon']) ?>" alt="Favicon" style="border-radius:50%;">
                    <?php endif; ?>
                </div>
                <input type="file" name="favicon" id="favicon">
                <div class="actions">
                    <button type="submit" name="remover" value="favicon">Remover</button>
                    <button type="submit" name="salvar" value="favicon">Salvar</button>
                </div>
            </div>
        </div>
    </form>
</div>
