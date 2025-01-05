<?php
require_once(__DIR__ . '/../../config.php');
require_once ('careylib.php');
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// Conexión a la base de datos de Moodle.
global $DB, $OUTPUT, $PAGE;

// Configuración de la página.
$PAGE->set_url(new moodle_url('/mod/surveypro/crud_materias.php'));
$PAGE->set_context($context);
$PAGE->set_title('Analisis DEV');
$PAGE->set_heading('Analisis DEV');
echo $OUTPUT->header();

// Uso de la función
$qid = 'PDP'; // ID del cuestionario.
$html_output = generar_html_items_evaluados($qid);

// Imprimir el resultado en HTML.
echo $html_output;

verificar_errores_usointerno($qid);



$tables = ['surveyprofield_sliders', 'surveyprofield_careybutton'];

foreach ($tables as $table) {

procesar_items_tabla($table, $qid);

}

$itemid = 56; // Por ejemplo.
try {
    $record = get_plugin_record_by_itemid($itemid);
    if ($record) {
        print_object($record);
    } else {
        echo 'No record found for this itemid.';
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

echo $OUTPUT->footer();