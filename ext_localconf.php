<?php
defined('TYPO3') or die();

// Registrierung des klassischen Daten-Hooks an der korrekten, globalen Core-Schnittstelle
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamap_beforeStart']['tsparser'] =
    \JambageCom\Tsparser\Hook\ConstantDataMapCleaner::class . '->processDatamap_beforeStart';
