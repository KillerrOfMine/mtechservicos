<?php
require_once 'config.php';

/**
 * Classe para gerenciar temas personalizáveis
 */
class ThemeManager {
    
    private $userId;
    private $theme;
    
    public function __construct($userId) {
        $this->userId = $userId;
        $this->loadTheme();
    }
    
    /**
     * Carrega tema do usuário
     */
    private function loadTheme() {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM tema_config WHERE usuario_id = ?");
        $stmt->execute([$this->userId]);
        $this->theme = $stmt->fetch();
        
        if (!$this->theme) {
            // Cria tema padrão se não existir
            $this->createDefaultTheme();
            $this->loadTheme();
        }
    }
    
    /**
     * Cria tema padrão
     */
    private function createDefaultTheme() {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO tema_config (usuario_id) VALUES (?)");
        $stmt->execute([$this->userId]);
    }
    
    /**
     * Retorna configuração do tema
     */
    public function getTheme() {
        return $this->theme;
    }
    
    /**
     * Atualiza tema
     */
    public function updateTheme($data) {
        $db = getDB();
        
        $allowedFields = [
            'cor_primaria', 'cor_secundaria', 'cor_fundo', 
            'cor_texto', 'cor_card', 'fonte_principal', 'tema_escuro'
        ];
        
        $updates = [];
        $values = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $values[] = $this->userId;
        $sql = "UPDATE tema_config SET " . implode(', ', $updates) . " WHERE usuario_id = ?";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($values);
        
        if ($result) {
            $this->loadTheme(); // Recarrega tema
        }
        
        return $result;
    }
    
    /**
     * Gera CSS com variáveis do tema
     */
    public function generateCSS() {
        $theme = $this->theme;
        
        $css = ":root {\n";
        $css .= "    --cor-primaria: {$theme['cor_primaria']};\n";
        $css .= "    --cor-secundaria: {$theme['cor_secundaria']};\n";
        $css .= "    --cor-fundo: {$theme['cor_fundo']};\n";
        $css .= "    --cor-texto: {$theme['cor_texto']};\n";
        $css .= "    --cor-card: {$theme['cor_card']};\n";
        $css .= "    --fonte-principal: '{$theme['fonte_principal']}', sans-serif;\n";
        
        if ($theme['tema_escuro']) {
            $css .= "    --cor-fundo: #1a1a1a;\n";
            $css .= "    --cor-texto: #e0e0e0;\n";
            $css .= "    --cor-card: #2d2d2d;\n";
        }
        
        $css .= "}\n";
        
        return $css;
    }
    
    /**
     * Retorna temas pré-definidos
     */
    public static function getPresets() {
        return [
            'default' => [
                'nome' => 'Padrão',
                'cor_primaria' => '#1a73e8',
                'cor_secundaria' => '#34a853',
                'cor_fundo' => '#ffffff',
                'cor_texto' => '#202124',
                'cor_card' => '#f8f9fa',
                'tema_escuro' => 0
            ],
            'dark' => [
                'nome' => 'Escuro',
                'cor_primaria' => '#4285f4',
                'cor_secundaria' => '#34a853',
                'cor_fundo' => '#1a1a1a',
                'cor_texto' => '#e0e0e0',
                'cor_card' => '#2d2d2d',
                'tema_escuro' => 1
            ],
            'purple' => [
                'nome' => 'Roxo',
                'cor_primaria' => '#9c27b0',
                'cor_secundaria' => '#e91e63',
                'cor_fundo' => '#ffffff',
                'cor_texto' => '#212121',
                'cor_card' => '#f5f5f5',
                'tema_escuro' => 0
            ],
            'ocean' => [
                'nome' => 'Oceano',
                'cor_primaria' => '#0288d1',
                'cor_secundaria' => '#00acc1',
                'cor_fundo' => '#ffffff',
                'cor_texto' => '#263238',
                'cor_card' => '#e1f5fe',
                'tema_escuro' => 0
            ],
            'forest' => [
                'nome' => 'Floresta',
                'cor_primaria' => '#2e7d32',
                'cor_secundaria' => '#558b2f',
                'cor_fundo' => '#ffffff',
                'cor_texto' => '#1b5e20',
                'cor_card' => '#f1f8e9',
                'tema_escuro' => 0
            ]
        ];
    }
}
