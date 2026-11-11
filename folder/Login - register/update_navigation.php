<?php

$files = [
    'add_student.php',
    'add_signature.php',
    'upload_photo.php',
    'upload_excel.php',
    'download_excel_template.php',
    'download_id.php',
    'edit_student.php'
];

$headerSnippet = '<?php
session_start();
require_once "database.php";';

$navSnippet = '<?php include \'dashboard_nav.php\'; ?>';

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        
        if (strpos($content, 'session_start()') === false) {
            $content = str_replace('<?php', $headerSnippet, $content, $count);
            if ($count === 0) {
                $content = $headerSnippet . '\n?>\n' . $content;
            } else {
                $content = str_replace('<?php', '<?php\n' . $headerSnippet, $content);
            }
        }
        
        
        if (strpos($content, '<body') !== false) {
            
            if (strpos($content, '<body class="index-page"') === false) {
                $content = str_replace('<body', '<body class="index-page"', $content);
            }
            
            
            if (strpos($content, 'dashboard_nav.php') === false) {
                $content = preg_replace('/(<body[^>]*>)/', "$1\n    " . $navSnippet, $content);
            }
            
            
            $content = preg_replace('/<div class="offcanvas offcanvas-start".*?<\/div>/s', '', $content);
            $content = preg_replace('/<div[^>]*class="[^"]*hamburger[^"]*"[^>]*>.*?<\/div>/s', '', $content);
            
            file_put_contents($file, $content);
            echo "Updated: $file\n";
        } else {
            echo "Skipped (no body tag found): $file\n";
        }
    } else {
        echo "File not found: $file\n";
    }
}

echo "\nNavigation update complete. Please verify the changes and test all pages.\n";
?>
