<?php

require_once 'vendor/autoload.php';

$translator = new \CleverSuite\TranslationManager('2409ae4f-20f4-449d-9cdf-be932c356732');
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
$translator->export('./demo/demo2/demo2');

echo 'Partial export: common' . PHP_EOL;
$translator->export('./demo/demo2/demo2', ['common']);

echo 'Import single' . PHP_EOL;
echo json_encode($translator->import_single('da', 'common', 'key', 'value')) . PHP_EOL;
echo 'Import multi' . PHP_EOL;
echo json_encode($translator->import('da', 'common', [
    array( 'key' => 'key2', 'value' => 'value2' ),
    array( 'key' => 'key3', 'value' => 'value3' )
])) . PHP_EOL;

?>
