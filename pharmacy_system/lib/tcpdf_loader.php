<?php
/**
 * TCPDF loader — checks common install paths and provides a clear
 * error if the library isn't installed.
 *
 * Install one of two ways:
 *
 *   1) Composer:    composer require tecnickcom/tcpdf
 *      (creates vendor/autoload.php at the project root)
 *
 *   2) Manual:      download TCPDF from https://github.com/tecnickcom/TCPDF
 *      and unzip into:  lib/tcpdf/   (so that lib/tcpdf/tcpdf.php exists)
 */

if (class_exists('TCPDF')) return;

$candidates = [
    __DIR__ . '/../vendor/autoload.php',     // Composer
    __DIR__ . '/tcpdf/tcpdf.php',            // manual: lib/tcpdf/
    __DIR__ . '/../tcpdf/tcpdf.php',         // manual: project_root/tcpdf/
    __DIR__ . '/../TCPDF-main/tcpdf.php',    // GitHub zip default folder name
    __DIR__ . '/../TCPDF/tcpdf.php',         // alternate capitalisation
];
foreach ($candidates as $path) {
    if (is_file($path)) {
        require_once $path;
        if (class_exists('TCPDF')) break;
    }
}

if (!class_exists('TCPDF')) {
    if (function_exists('flash')) {
        flash('error',
            'TCPDF is not installed. Run "composer require tecnickcom/tcpdf" '
            . 'OR drop the TCPDF library into lib/tcpdf/ (so lib/tcpdf/tcpdf.php exists).');
    }
    if (function_exists('redirect')) {
        redirect('index.php?page=reports');
    }
    http_response_code(500);
    exit('TCPDF not installed. See lib/tcpdf_loader.php for instructions.');
}
