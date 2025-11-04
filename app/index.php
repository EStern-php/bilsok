<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

//Skulle man byggt vidare så hade jag ändrat strukturen lite och lagt till en autoloader.

require __DIR__ . '/model.php';
require __DIR__ . '/controller.php';
require __DIR__ . '/databaseModel.php';

$controller = new controller();

$controller->execute();