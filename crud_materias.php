<?php
require_once(__DIR__ . '/../../config.php');
require_login();
$context = context_system::instance();
//require_capability('moodle/site:config', $context);
//Aceptar gestores
require_capability('moodle/site:manageblocks', $context);

// Conexión a la base de datos de Moodle.
global $DB, $OUTPUT, $PAGE;

// Configuración de la página.
$PAGE->set_url(new moodle_url('/mod/surveypro/crud_materias.php'));
$PAGE->set_context($context);
$PAGE->set_title('CRUD Materias');
$PAGE->set_heading('Administrar Materias');

// Acción (create, read, update, delete).
$action = optional_param('action', 'list', PARAM_TEXT);
$id = optional_param('id', 0, PARAM_INT);

// Verificación de envío del formulario.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $data = (object) $_POST;  // Convertimos los datos enviados a un objeto.
	
	// Validación adicional de los campos del formulario.
    $data->qid = clean_param($data->qid, PARAM_ALPHANUMEXT);
    $data->shortname = clean_param($data->shortname, PARAM_TEXT);
    $data->fullname = clean_param($data->fullname, PARAM_TEXT);
    $data->peso = clean_param($data->peso, PARAM_INT);

    // Validamos si es actualización o inserción.
    if (!empty($data->id)) {
        $DB->update_record('surveypro_materias', $data);
    } else {
        unset($data->id);  // Aseguramos que el ID no se incluya al insertar.
        $DB->insert_record('surveypro_materias', $data);
    }
    redirect(new moodle_url('/mod/surveypro/crud_materias.php'), 'Operación exitosa', 2);
}

echo $OUTPUT->header();

// Formulario para crear o editar.
if ($action == 'edit' || $action == 'add') {
    $record = ($id) ? $DB->get_record('surveypro_materias', ['id' => $id]) : (object) ['id' => 0, 'qid' => '', 'shortname' => '', 'fullname' => '', 'peso' => 0];
    $url = new moodle_url('/mod/surveypro/crud_materias.php', ['action' => $action]);

    echo '<form method="POST" action="' . $url . '">';
    echo '<input type="hidden" name="sesskey" value="' . sesskey() . '"/>';

    if ($record->id) {
        echo '<input type="hidden" name="id" value="' . $record->id . '"/>';
    }

    echo '<div>';
    echo '<label>QID: <input type="text" name="qid" value="' . s($record->qid) . '" required/></label><br>';
    echo '<label>Shortname: <input type="text" name="shortname" value="' . s($record->shortname) . '" required/></label><br>';
    echo '<label>Fullname: <input type="text" name="fullname" value="' . s($record->fullname) . '" required/></label><br>';
    echo '<label>Peso: <input type="number" name="peso" value="' . s($record->peso) . '" required/></label><br>';
    echo '</div>';
    echo '<button type="submit" class="btn btn-primary">Guardar</button>';
    echo '</form>';
    echo '<br><a href="?action=list" class="btn btn-secondary">Volver a la lista</a>';
} else {
    // Listar todos los registros con una tabla estilo Moodle.
    $records = $DB->get_records('surveypro_materias');

    $table = new html_table();
    $table->head = ['ID', 'QID', 'Shortname', 'Fullname', 'Peso', 'Acciones'];
    foreach ($records as $record) {
        $editurl = new moodle_url('/mod/surveypro/crud_materias.php', ['action' => 'edit', 'id' => $record->id]);
        $deleteurl = new moodle_url('/mod/surveypro/crud_materias.php', ['action' => 'delete', 'id' => $record->id, 'sesskey' => sesskey()]);

        $actions = html_writer::link($editurl, 'Editar') . ' | ' .
                   html_writer::link($deleteurl, 'Eliminar');

        $table->data[] = [
            $record->id,
            s($record->qid),
            s($record->shortname),
            s($record->fullname),
            s($record->peso),
            $actions
        ];
    }

    echo $OUTPUT->heading('Lista de Materias', 2);
    echo html_writer::table($table);
    echo '<br><a href="?action=add" class="btn btn-primary">Agregar Nuevo</a>';
}

echo $OUTPUT->footer();
