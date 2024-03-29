<?php

require_once 'vendor/autoload.php';

$translator = new \CleverSuite\TranslationManager('f3717021-aeca-453e-b05c-1de928595291');
$translator->setHost('http://localhost:9501/graphql');
$translator->authenticate();

if (!$translator->isAuthenticated()) {
    echo 'Authentication failed' . PHP_EOL;
    return;
}

echo json_encode($translator->languages()) . PHP_EOL; 
echo json_encode($translator->namespaces()) . PHP_EOL;
echo json_encode($translator->translations('common', 'da')) . PHP_EOL;
echo json_encode($translator->add('common', ['key', 'key2'])) . PHP_EOL;

echo 'Full export' . PHP_EOL;
$translator->export('./demo/run1');

echo 'Partial export: common' . PHP_EOL;
$translator->export_single('./demo/run2', 'common');

echo 'Import single' . PHP_EOL;
echo json_encode($translator->import_single('da', 'common', 'key', 'value')) . PHP_EOL;
echo 'Import multi' . PHP_EOL;
echo json_encode($translator->import('da', 'common', [
    array( 'key' => 'key2', 'value' => 'value2' ),
    array( 'key' => 'key3', 'value' => 'value3' )
])) . PHP_EOL;

?>
