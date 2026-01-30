<?php
// barcode_simple.php
class SimpleBarcodeGenerator {
    
    public static function generateCode39($text, $height = 50, $width = 2) {
        // Patrón Code 39
        $code39 = array(
            '0' => '101001101101', '1' => '110100101011', '2' => '101100101011',
            '3' => '110110010101', '4' => '101001101011', '5' => '110100110101',
            '6' => '101100110101', '7' => '101001011011', '8' => '110100101101',
            '9' => '101100101101', 'A' => '110101001011', 'B' => '101101001011',
            'C' => '110110100101', 'D' => '101011001011', 'E' => '110101100101',
            'F' => '101101100101', 'G' => '101010011011', 'H' => '110101001101',
            'I' => '101101001101', 'J' => '101011001101', 'K' => '110101010011',
            'L' => '101101010011', 'M' => '110110101001', 'N' => '101011010011',
            'O' => '110101101001', 'P' => '101101101001', 'Q' => '101010110011',
            'R' => '110101011001', 'S' => '101101011001', 'T' => '101011011001',
            'U' => '110010101011', 'V' => '100110101011', 'W' => '110011010101',
            'X' => '100101101011', 'Y' => '110010110101', 'Z' => '100110110101',
            '-' => '100101011011', '.' => '110010101101', ' ' => '100110101101',
            '*' => '100101101101', '$' => '100100100101', '/' => '100100101001',
            '+' => '100101001001', '%' => '101001001001'
        );
        
        // Agregar asterisco al inicio y final
        $text = '*' . strtoupper($text) . '*';
        $binario = '';
        
        // Convertir texto a binario según Code 39
        for ($i = 0; $i < strlen($text); $i++) {
            $char = $text[$i];
            if (isset($code39[$char])) {
                $binario .= $code39[$char] . '0'; // 0 es el espacio entre caracteres
            }
        }
        
        // Crear imagen
        $imgWidth = strlen($binario) * $width;
        $imgHeight = $height + 30; // Espacio para texto
        
        $im = imagecreate($imgWidth, $imgHeight);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        
        // Dibujar barras
        for ($i = 0; $i < strlen($binario); $i++) {
            if ($binario[$i] == '1') {
                imagefilledrectangle($im, $i * $width, 0, ($i + 1) * $width - 1, $height, $black);
            }
        }
        
        // Agregar texto
        imagestring($im, 3, 5, $height + 5, $text, $black);
        
        // Salida
        header('Content-Type: image/png');
        imagepng($im);
        imagedestroy($im);
        exit;
    }
    
    public static function generateSimpleCode($text, $height = 50) {
        // Generar un código de barras simple (no estándar, pero funcional)
        $text = strtoupper($text);
        $hash = crc32($text);
        $binario = decbin($hash);
        $binario = str_pad($binario, 32, '0', STR_PAD_LEFT);
        
        $width = 2;
        $imgWidth = strlen($binario) * $width;
        $imgHeight = $height + 30;
        
        $im = imagecreate($imgWidth, $imgHeight);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        $gray = imagecolorallocate($im, 200, 200, 200);
        
        // Fondo
        imagefilledrectangle($im, 0, 0, $imgWidth, $imgHeight, $white);
        
        // Barras
        for ($i = 0; $i < strlen($binario); $i++) {
            $color = ($binario[$i] == '1') ? $black : $white;
            imagefilledrectangle($im, $i * $width, 0, ($i + 1) * $width - 1, $height, $color);
        }
        
        // Marco
        imagerectangle($im, 0, 0, $imgWidth - 1, $imgHeight - 1, $gray);
        
        // Texto
        imagestring($im, 3, 10, $height + 5, $text, $black);
        imagestring($im, 1, 10, $height + 20, 'ID: ' . $hash, $gray);
        
        header('Content-Type: image/png');
        imagepng($im);
        imagedestroy($im);
        exit;
    }
}

// Uso directo
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $type = $_GET['type'] ?? 'simple';
    
    if ($type == 'code39') {
        SimpleBarcodeGenerator::generateCode39($code);
    } else {
        SimpleBarcodeGenerator::generateSimpleCode($code);
    }
}
?>