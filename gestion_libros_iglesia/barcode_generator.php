<?php
// barcode_generator.php - VERSIÓN SIMPLIFICADA (solo C128 y C39)

require_once __DIR__ . '/vendor/autoload.php';

class BarcodeManager {
    
    private $generatorPNG;
    
    public function __construct($db = null) {
        $this->generatorPNG = new Picqer\Barcode\BarcodeGeneratorPNG();
    }
    
    /**
     * Tipos de código de barras soportados (solo C128 y C39)
     */
    public function getTiposDisponibles() {
        return [
            'C128' => [
                'nombre' => 'CODE 128',
                'descripcion' => 'Estándar industrial, muy compacto, soporta todos los caracteres',
                'recomendado' => true,
                'max_caracteres' => 255
            ],
            'C39' => [
                'nombre' => 'CODE 39',
                'descripcion' => 'Solo mayúsculas, números y algunos símbolos',
                'recomendado' => false,
                'max_caracteres' => 255
            ]
        ];
    }
    
    /**
     * Genera imagen PNG de código de barras
     */
    public function generarPNG($codigo, $tipo = 'C128', $alto = 50, $anchoBarra = 2) {
        try {
            $codigoLimpio = $this->normalizarCodigo($codigo, $tipo);
            
            // Solo soportamos C128 y C39
            $tipoValido = ($tipo === 'C128' || $tipo === 'C39') ? $tipo : 'C128';
            
            return $this->generatorPNG->getBarcode(
                $codigoLimpio, 
                $tipoValido,
                $anchoBarra,
                $alto
            );
            
        } catch (Exception $e) {
            error_log("Error generando barcode: " . $e->getMessage());
            return $this->generarCodigoError();
        }
    }
    
    /**
     * Genera imagen en base64 para usar directamente en HTML
     */
    public function generarBase64($codigo, $tipo = 'C128', $alto = 50, $anchoBarra = 2) {
        try {
            $png = $this->generarPNG($codigo, $tipo, $alto, $anchoBarra);
            return 'data:image/png;base64,' . base64_encode($png);
        } catch (Exception $e) {
            error_log("Error en generarBase64: " . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Genera etiqueta HTML completa para un libro
     */
    public function generarEtiquetaHTML($libro, $config = []) {
        // Configuración por defecto
        $defaultConfig = [
            'tamano' => 'medium',
            'tipo_barcode' => 'C128',
            'mostrar_titulo' => true,
            'mostrar_autor' => true,
            'mostrar_codigo' => true,
            'mostrar_fecha' => false,
            'color_borde' => '#cccccc',
            'color_fondo' => '#ffffff',
            'color_texto' => '#000000',
            'alto_barcode' => 40,
            'ancho_barra' => 2
        ];
        
        $config = array_merge($defaultConfig, $config);
        
        // Validar libro
        if (empty($libro['codigo_interno'])) {
            return '<div class="alert alert-danger">Error: El libro no tiene código interno</div>';
        }
        
        // Generar código de barras
        $barcodeBase64 = $this->generarBase64(
            $libro['codigo_interno'],
            $config['tipo_barcode'],
            $config['alto_barcode'],
            $config['ancho_barra']
        );
        
        // Si hay error, mostrar mensaje
        if (empty($barcodeBase64)) {
            return '<div class="alert alert-danger">Error al generar código de barras</div>';
        }
        
        // Crear etiqueta
        return $this->crearHTML($libro, $barcodeBase64, $config);
    }
    
    /**
     * Normaliza código según tipo
     */
    private function normalizarCodigo($codigo, $tipo) {
        $codigo = trim($codigo);
        
        if ($tipo === 'C39') {
            // Code39 solo permite: 0-9, A-Z, espacio, -, ., $, /, +, %
            $codigo = strtoupper($codigo);
            $codigo = preg_replace('/[^A-Z0-9\s\-\\.\$\+\/%]/', '', $codigo);
        } else {
            // Para Code128, quitar caracteres problemáticos
            $codigo = preg_replace('/[^\w\s\-\.]/', '', $codigo);
        }
        
        return $codigo;
    }
    
    /**
     * Crea HTML de etiqueta individual
     */
    private function crearHTML($libro, $barcodeBase64, $config) {
        // Estilos según tamaño
        $estilosTamano = [
            'small' => 'width: 60mm; height: 30mm; font-size: 8px;',
            'medium' => 'width: 70mm; height: 40mm; font-size: 10px;',
            'large' => 'width: 100mm; height: 60mm; font-size: 12px;'
        ];
        
        $tamanoEstilo = $estilosTamano[$config['tamano']] ?? $estilosTamano['medium'];
        
        $estilos = sprintf(
            '%s border: 1px solid %s; background: %s; color: %s; padding: 5px; display: inline-block; text-align: center; margin: 5px;',
            $tamanoEstilo,
            $config['color_borde'],
            $config['color_fondo'],
            $config['color_texto']
        );
        
        $html = sprintf(
            '<div class="etiqueta" style="%s">',
            $estilos
        );
        
        // Código
        if ($config['mostrar_codigo']) {
            $html .= sprintf(
                '<div style="font-family: monospace; font-weight: bold; margin-bottom: 3px;">%s</div>',
                htmlspecialchars($libro['codigo_interno'])
            );
        }
        
        // Código de barras
        $html .= sprintf(
            '<img src="%s" alt="%s" style="max-width: 100%%; max-height: 60%%; display: block; margin: 0 auto;">',
            $barcodeBase64,
            htmlspecialchars($libro['codigo_interno'])
        );
        
        // Título
        if ($config['mostrar_titulo'] && !empty($libro['titulo'])) {
            $tituloCorto = $this->acortarTexto($libro['titulo'], 30);
            $html .= sprintf(
                '<div style="font-weight: bold; margin-top: 3px;">%s</div>',
                htmlspecialchars($tituloCorto)
            );
        }
        
        // Autor
        if ($config['mostrar_autor'] && !empty($libro['autor'])) {
            $autorCorto = $this->acortarTexto($libro['autor'], 25);
            $html .= sprintf(
                '<div style="color: #666; margin-top: 2px;">%s</div>',
                htmlspecialchars($autorCorto)
            );
        }
        
        // Fecha
        if ($config['mostrar_fecha']) {
            $html .= '<div style="font-size: 8px; color: #999; margin-top: 2px;">' . date('d/m/Y') . '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Acorta texto si es muy largo
     */
    private function acortarTexto($texto, $maxLength) {
        if (strlen($texto) > $maxLength) {
            return substr($texto, 0, $maxLength - 3) . '...';
        }
        return $texto;
    }
    
    /**
     * Genera código de error visual
     */
    private function generarCodigoError() {
        $ancho = 200;
        $alto = 100;
        
        $im = imagecreate($ancho, $alto);
        $rojo = imagecolorallocate($im, 255, 200, 200);
        $negro = imagecolorallocate($im, 0, 0, 0);
        
        imagefilledrectangle($im, 0, 0, $ancho - 1, $alto - 1, $rojo);
        imagestring($im, 2, 10, 10, 'ERROR CODIGO BARRAS', $negro);
        imagestring($im, 1, 10, 30, 'Use C128 o C39', $negro);
        
        ob_start();
        imagepng($im);
        $png = ob_get_clean();
        imagedestroy($im);
        
        return $png;
    }
}

// Función helper para uso rápido
function generar_barcode_base64($codigo, $tipo = 'C128', $alto = 50) {
    static $manager = null;
    
    if ($manager === null) {
        $manager = new BarcodeManager();
    }
    
    return $manager->generarBase64($codigo, $tipo, $alto);
}
?>