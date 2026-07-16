<?php
$dir = __DIR__ . '/../app/Models';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

foreach ($files as $file) {
    if ($file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $original = $content;
        
        // Remove use statement
        $content = preg_replace('/use App\\\\Traits\\\\BranchScoped;[\r\n]+/', '', $content);
        
        // Remove BranchScoped from use traits inside class
        $content = preg_replace('/use BranchScoped,\s*/', 'use ', $content);
        $content = preg_replace('/,\s*BranchScoped/', '', $content);
        $content = preg_replace('/use BranchScoped;[\r\n]+/', '', $content);

        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
            echo "Updated " . $file->getFilename() . "\n";
        }
    }
}
