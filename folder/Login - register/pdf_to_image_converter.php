<?php
/**
 * PDF to Image Converter for Template Background
 * This converts the first page of SFR Temp.pdf to an image for use as background
 */

function convertPdfToImage($pdfPath, $outputImagePath) {
    // Try using ImageMagick if available
    if (extension_loaded('imagick') && class_exists('Imagick')) {
        try {
            $imagick = new Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($pdfPath . '[0]'); // Read first page only
            $imagick->setImageFormat('png');
            $imagick->writeImage($outputImagePath);
            $imagick->clear();
            $imagick->destroy();
            return true;
        } catch (Exception $e) {
            error_log('ImageMagick conversion failed: ' . $e->getMessage());
        }
    }
    
    // Try using Ghostscript command line
    $gsCommand = 'gs -dNOPAUSE -dBATCH -sDEVICE=png16m -dFirstPage=1 -dLastPage=1 -r150 -sOutputFile=' . escapeshellarg($outputImagePath) . ' ' . escapeshellarg($pdfPath) . ' 2>&1';
    $output = shell_exec($gsCommand);
    
    if (file_exists($outputImagePath)) {
        return true;
    }
    
    // Try using pdftoppm (part of poppler-utils)
    $tempFile = str_replace('.png', '_%d.png', $outputImagePath);
    $pdftoppmCommand = 'pdftoppm -png -f 1 -l 1 -scale-to 1500 ' . escapeshellarg($pdfPath) . ' ' . str_replace('_%d.png', '', $outputImagePath) . ' 2>&1';
    $output = shell_exec($pdftoppmCommand);
    
    // Rename the first page if it was created
    $firstPageFile = str_replace('%d', '1', $tempFile);
    if (file_exists($firstPageFile)) {
        rename($firstPageFile, $outputImagePath);
        return true;
    }
    
    return false;
}

function getTemplateImagePath($pdfPath) {
    $cacheDir = __DIR__ . '/cache/';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $pdfHash = md5_file($pdfPath);
    $imagePath = $cacheDir . 'sfr_template_' . $pdfHash . '.png';
    
    if (!file_exists($imagePath)) {
        if (convertPdfToImage($pdfPath, $imagePath)) {
            return $imagePath;
        }
    }
    
    return file_exists($imagePath) ? $imagePath : false;
}
?>
