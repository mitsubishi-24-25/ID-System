<?php
/**
 * Manual FPDI implementation for TCPDF
 * This allows importing PDF pages as templates using Ghostscript
 */

require_once(__DIR__ . '/tcpdf/tcpdf.php');

class FPDI_TCPDF extends TCPDF {
    protected $templateFiles = array();
    protected $templatePages = array();
    
    public function setSourceFile($filename) {
        if (!file_exists($filename)) {
            throw new Exception('Template file not found: ' . $filename);
        }
        
        $this->templateFiles[] = $filename;
        $pageIndex = count($this->templateFiles) - 1;
        
        // For now, we'll assume PDF has at least 1 page
        $this->templatePages[$pageIndex] = 1;
        
        return count($this->templateFiles);
    }
    
    public function importPage($pageNumber) {
        return $pageNumber - 1; // Return zero-based index
    }
    
    public function useTemplate($templateId, $x = 0, $y = 0, $width = 0, $height = 0) {
        if (!isset($this->templateFiles[$templateId])) {
            return false;
        }
        
        $templateFile = $this->templateFiles[$templateId];
        
        // Convert PDF to image using Ghostscript command line
        $tempImage = $this->convertPdfPageToImage($templateFile, 0);
        
        if ($tempImage && file_exists($tempImage)) {
            $pageWidth = $width > 0 ? $width : $this->getPageWidth();
            $pageHeight = $height > 0 ? $height : $this->getPageHeight();
            
            $this->Image($tempImage, $x, $y, $pageWidth, $pageHeight, '', '', '', false, 300, '', false, false, 0, false, false, true);
            
            // Clean up temp file
            unlink($tempImage);
            return true;
        }
        
        return false;
    }
    
    private function convertPdfPageToImage($pdfFile, $pageIndex) {
        $tempDir = sys_get_temp_dir();
        $tempImage = $tempDir . '/template_' . uniqid() . '.png';
        
        // Try Ghostscript command line with full path
        $gsPath = '"C:\Program Files\gs\gs10.06.0\bin\gswin64c.exe"';
        $gsCommand = sprintf(
            '%s -dNOPAUSE -dBATCH -sDEVICE=png16m -dFirstPage=%d -dLastPage=%d -r150 -sOutputFile="%s" "%s" 2>&1',
            $gsPath,
            $pageIndex + 1,
            $pageIndex + 1,
            $tempImage,
            $pdfFile
        );
        
        error_log('Ghostscript command: ' . $gsCommand);
        $output = shell_exec($gsCommand);
        error_log('Ghostscript output: ' . $output);
        
        if (file_exists($tempImage)) {
            return $tempImage;
        }
        
        // Try alternative Ghostscript command
        $gsCommand2 = sprintf(
            'gs -dNOPAUSE -dBATCH -sDEVICE=png16m -dFirstPage=%d -dLastPage=%d -r150 -sOutputFile="%s" "%s" 2>&1',
            $pageIndex + 1,
            $pageIndex + 1,
            $tempImage,
            $pdfFile
        );
        
        $output = shell_exec($gsCommand2);
        error_log('Alternative Ghostscript output: ' . $output);
        
        if (file_exists($tempImage)) {
            return $tempImage;
        }
        
        return false;
    }
}
?>
