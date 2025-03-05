<?php

function get_user_custom_images($userid, $filearea)
{
    global $CFG;
    // Ensure you have access to Moodle's global configuration and libraries.
    require_once($CFG->libdir . '/filelib.php');

    // Get the file storage instance.
    $fs = get_file_storage();

    // Get the user context for the given user id.
    $context = context_user::instance($userid);

    $filearea = "files_" . $filearea;

    // Retrieve all files in the specified file area. 
    // The parameters are: contextid, component, filearea, itemid, sort order, and whether to include directories.
    $files = $fs->get_area_files($context->id, 'profilefield_file', $filearea, 0, 'sortorder, id', false);

    $urlimagenes = array();
    // Loop through each file and generate its URL.
    foreach ($files as $file) {
        $fileurl = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );
        $urlimagenes[] = $fileurl;
    }
    return $urlimagenes;
}


function get_question_scores($plugin, $itemid)
{
    global $DB;

    // Determine the table based on the plugin
    $table = '';
    if ($plugin == 'careybutton') {
        $table = 'surveyprofield_careybutton';
    } elseif ($plugin == 'sliders') {
        $table = 'surveyprofield_sliders';
    } else {
        throw new InvalidArgumentException("Unsupported plugin: $plugin");
    }

    // Fetch the scores from the respective table
    $sql = "SELECT peso, puntajemin, puntajemax
            FROM {{$table}}
            WHERE itemid = :itemid";

    $params = ['itemid' => $itemid];
    $record = $DB->get_record_sql($sql, $params);

    if (!$record) {
        throw new InvalidArgumentException("No record found for itemid: $itemid in plugin: $plugin");
    }

    // Calculate the minimum and maximum scores
    $min_score = $record->peso * $record->puntajemin;
    $max_score = $record->peso * $record->puntajemax;

    return [
        'min_score' => $min_score,
        'max_score' => $max_score,
        'peso' => $record->peso
    ];
}

/**
 * The function `transform_answer_to_score` converts different answer types into corresponding scores
 * based on predefined rules.
 * 
 * @param answer Please provide the answer for which you want to calculate the score.
 * @param min_score The `min_score` parameter represents the minimum score that can be assigned to an
 * answer. This score is given when the answer falls under certain categories like 'none', 'incorrect',
 * 'incorrect2', 'incorrect3', or in any unexpected case.
 * @param max_score The `max_score` parameter represents the highest possible score that can be
 * assigned to an answer. This score is given when the answer is considered to be fully correct or
 * meets the highest criteria.
 * 
 * @return The function `transform_answer_to_score` takes an answer as input and returns a score based
 * on the rules defined in the function. If the answer is numeric, it calculates its percentage within
 * the range and returns it rounded to one decimal place. If the answer matches specific strings like
 * 'idk', 'none', 'incorrect', 'correct', 'mid', etc., it assigns a corresponding score based on
 */
function transform_answer_to_score($answer, $min_score, $max_score)
{

    if (is_numeric($answer)) {
        return $answer;
    }

    // Transform the answer into a score based on the rules
    switch ($answer) {
        case 'idk':
            return null; // Omit this answer, no contribution to the score
        case 'none':
        case 'incorrect':
        case 'incorrect2':
        case 'incorrect3':
            return $min_score; // Assign minimum score
        case 'correct':
        case 'correct2':
        case 'correct3':
        case 'yes':
            return $max_score; // Assign maximum score
        case 'mid':
            return ($min_score + $max_score) / 2; // Assign average score
        default:
            return null; // Handle any unexpected case by omitting
    }
}

function get_numero_evaluated_items($materiaid, $dimension)
{
    global $DB;

    // Validar entradas.
    if (!$materiaid || !$dimension) {
        throw new InvalidArgumentException("Debe proporcionar un ID de materia y una dimensión válida.");
    }

    // Inicializar array de resultados.
    $evaluated_items = [];

    // Listado de tablas a verificar.
    $plugin_tables = [
        'sliders' => 'surveyprofield_sliders',
        'careybutton' => 'surveyprofield_careybutton'
    ];

    // Recorrer cada tabla asociada.
    foreach ($plugin_tables as $plugin => $table) {
        $sql = "SELECT itemid as itemid
                FROM {{$table}}
                WHERE idmateria = :idmateria 
                  AND dimension = :dimension 
                  AND peso != 0";

        $params = [
            'idmateria' => $materiaid,
            'dimension' => $dimension
        ];

        // Consultar los items evaluados en la tabla actual.
        $records = $DB->get_records_sql($sql, $params);
        foreach ($records as $record) {
            $evaluated_items[] = [
                'itemid' => $record->itemid,
                'plugin' => $plugin
            ];
        }
    }

    return $evaluated_items;
}

/**
 * The function `get_user_answer` retrieves a user's answer content from a database table based on
 * submission ID and item ID, returning 'NC' if no answer is found.
 * 
 * @param submissionid Submission ID is a unique identifier for a specific submission in a survey or
 * form. It helps to distinguish one submission from another.
 * @param itemid The `itemid` parameter in the `get_user_answer` function represents the ID of the item
 * for which you want to retrieve the user's answer. This ID is used to identify the specific item
 * within the survey or questionnaire for which you are fetching the answer.
 * @param plugin It seems like the `plugin` parameter is not being used in the `get_user_answer`
 * function. If you intended to use it for something specific within the function, you can modify the
 * function to include the `plugin` parameter in the query or logic.
 * 
 * @return The function `get_user_answer` checks if a user's answer exists in the database for a
 * specific submission ID and item ID. If the answer exists, it fetches and returns the content of the
 * user's answer. If the answer does not exist, it returns 'NC' to indicate that no answer was found.
 */
function get_user_answer($submissionid, $itemid)
{
    global $DB;

    // Check if the user's answer exists
    $exists = $DB->record_exists('surveypro_answer', [
        'submissionid' => $submissionid,
        'itemid' => $itemid
    ]);

    // Return "NC" if no answer is found
    if (!$exists) {
        return 'NC';
    }

    // Fetch the user's answer content
    $sql = "SELECT content
            FROM {surveypro_answer}
            WHERE submissionid = :submissionid AND itemid = :itemid";

    $params = [
        'submissionid' => $submissionid,
        'itemid' => $itemid
    ];

    $content = $DB->get_field_sql($sql, $params);

    return $content;
}


/**
 * The function `map_plugin_answer` processes user responses based on different plugins like
 * `careybutton` and `sliders` to map and calculate values accordingly.
 * 
 * @param itemid The `itemid` parameter in the `map_plugin_answer` function represents the unique
 * identifier of the item for which the mapping of the answer is being performed. This identifier is
 * used to retrieve specific data related to the item from the database tables associated with the
 * plugins (`careybutton` and `sliders
 * @param content The `map_plugin_answer` function takes three parameters: ``, ``, and
 * ``. It processes the content based on the plugin type provided.
 * @param plugin The `map_plugin_answer` function takes three parameters: ``, ``, and
 * ``. The function checks the value of the `` parameter to determine how to process the
 * `` parameter.
 * 
 * @return The function `map_plugin_answer` takes in parameters ``, ``, and ``,
 * and based on the value of ``, it performs different operations to map and process the answer.
 */
function map_plugin_answer($itemid, $content, $plugin)
{
    global $DB;

    if ($content === 'NC') {
        return 'NC';
    }

    if ($plugin == 'careybutton') {
        $sql = "SELECT options, puntajemax, peso
					FROM {surveyprofield_careybutton}
					WHERE itemid = :itemid";

        $record = $DB->get_record_sql($sql, ['itemid' => $itemid]);

        $options = $record->options;
        $puntajemax = $record->puntajemax;
        $peso = $record->peso;

        // Convert options into an array
        $options_array = explode("\n", $options);
        $options_map = [];
        foreach ($options_array as $index => $option) {
            $parts = explode('::', trim($option));
            $options_map[$index] = $parts[0]; // Take the first part only
        }

        //si $options_map es numerico, multiplicar por el peso
        if (is_numeric($options_map[$content])) {
            return $options_map[$content] * $peso;
        }
        // Return the mapped answer
        return $options_map[$content] ?? $content;
    }

    if ($plugin == 'sliders') {
        $sql = "SELECT options, puntajemax, peso
				FROM {surveyprofield_sliders}
				WHERE itemid = :itemid";

        $record = $DB->get_record_sql($sql, ['itemid' => $itemid]);

        $options = $record->options;
        $puntajemax = $record->puntajemax;
        $peso = $record->peso;

        $options_array = explode("\n", $options);
        $selected_indices = explode(';', $content);

        //si a los indices seleccionadas le sobra uno al final, y no esta vacio, se trata de un usuario que responde "other", vamos a devolver el valor de la ultima opcion
		if (count($selected_indices) === count($options_array) + 1 && !empty(end($selected_indices))) {
			return end($selected_indices);
		}

        // Check for special cases like idk:: or none::
        $special_case = sliders_check_special_cases($options_array, $selected_indices);
        if ($special_case !== null) {
            return $special_case;
        }

        // Check if the user-selected answers contain '::' with numeric identifiers
        $contains_numeric_identifiers = false;
        foreach ($selected_indices as $index => $selected) {
            if ($selected == '1') {
                $option = trim($options_array[$index]);
                if (strpos($option, '::') !== false) {
                    $contains_numeric_identifiers = true;
                    break;
                }
            }
        }



        if ($contains_numeric_identifiers) {
            $percentaje = sliders_sum_numeric_identifiers($options_array, $selected_indices);

            return $percentaje * $peso;
        } else {
            $percentage = sliders_calculate_selection_percentage($options_array, $selected_indices) / 100;

            // Calculate the final value
            $value = $puntajemax * $percentage * $peso;

            return $value; // Return the calculated value
        }
    }

    return $content;
}

/**
 * The function `sliders_sum_numeric_identifiers` calculates the sum of numeric identifiers from
 * selected options in an array.
 * 
 * @param options_array The `options_array` parameter is an array containing options for sliders. Each
 * element in the array is a string representing an option in the format "numeric_identifier::label".
 * The numeric identifier is the part before the double colon "::".
 * @param selected_indices Selected_indices is an array that contains the selected indices of options.
 * Each element in the array corresponds to an option in the options_array. If the value at a
 * particular index is '1', it means that the option at that index is selected.
 * 
 * @return The function `sliders_sum_numeric_identifiers` returns the sum of the numeric identifiers
 * extracted from the selected options in the `` based on the indices provided in the
 * `` array. The sum is rounded to one decimal place before being returned.
 */
function sliders_sum_numeric_identifiers($options_array, $selected_indices)
{
    //print_r ($selected_indices);
    $sum = 0;

    foreach ($selected_indices as $index => $selected) {
        if ($selected == '1') {
            $option = trim($options_array[$index]);
            $parts = explode('::', $option);
            if (is_numeric($parts[0])) {
                $sum += $parts[0];
            }
        }
    }

    return $sum;
}

/**
 * Calculate the percentage of selected options that do not contain '::'.
 *
 * @param array $options_array The array of options.
 * @param array $selected_indices The array of selected indices.
 * @return float The percentage of selected options.
 */
function sliders_calculate_selection_percentage($options_array, $selected_indices)
{
    // Filter options to include only those without '::'
    $filtered_options = array_filter($options_array, function ($option) {
        return strpos($option, '::') === false;
    });

    $total_filtered_options = count($filtered_options);
    $selected_count = 0;



    foreach ($selected_indices as $index => $selected) {
        if ($selected == '1') {
            $option = trim($options_array[$index]);
            // Count only selected options that are part of the filtered list
            if (strpos($option, '::') === false) {
                $selected_count++;
            }
        }
    }

    if ($total_filtered_options === 0) {
        return 0; // Avoid division by zero
    }

    return ($selected_count / $total_filtered_options) * 100; // Return percentage
}

/**
 * Check for special cases in the selected options.
 *
 * @param array $options_array The array of options.
 * @param array $selected_indices The array of selected indices.
 * @return string|null The special case found, or null if no special case is found.
 */
function sliders_check_special_cases($options_array, $selected_indices)
{
    foreach ($selected_indices as $index => $selected) {
        if ($selected == '1') {
            $option = trim($options_array[$index]);
            if (strpos($option, 'idk::') === 0) {
                return 'idk';
            } elseif (strpos($option, 'none::') === 0) {
                return 'none';
            } elseif (strpos($option, 'incorrect::') === 0) {
                return 'incorrect';
            }
        }
    }

    return null; // No special case found
}

/**
 * The function `generate_percentage_bar` creates a progress bar with dynamic color and width based on
 * the input percentage.
 * 
 * @param percentage The function `generate_percentage_bar` takes a percentage value as input and
 * generates a progress bar with a color gradient based on the percentage. The progress bar will have
 * different colors and widths depending on the value of the percentage.
 * 
 * @return The function `generate_percentage_bar` returns a Bootstrap-compatible progress bar HTML
 * element with a dynamic width and color gradient based on the input percentage. The progress bar
 * visually represents the percentage value provided as input.
 */
function generate_percentage_bar($percentage)
{
    // Determine the color and bar style based on the percentage
    $bar_style = ''; // Style includes both background and dynamic width

    if ($percentage <= 30) {
        $bar_style = "background: linear-gradient(90deg, #eb0b0b 0%, #76080f 100%);";
    } elseif ($percentage > 70) {
        $bar_style = "background: linear-gradient(90deg, #28a745 0%, #1b5e20 100%);";
    } else {
        $bar_style = "background: linear-gradient(90deg, #ffc107 0%, #ff9800 100%);";
    }

    // Add dynamic width to the style
    $width_style = $percentage > 0
        ? "width: {$percentage}%;"
        : "width: 1%; min-width: 7%;"; // Ensure visibility for 0%

    // Combine all styles
    $combined_style = $bar_style . $width_style;

    // Generate the Bootstrap-compatible HTML for the bar
    return "
    <div class='progress' style='height: 25px; background-color: #f0f0f0;'>
        <div class='progress-bar' role='progressbar' style='{$combined_style}' aria-valuenow='{$percentage}' aria-valuemin='0' aria-valuemax='100'>
            {$percentage}%
        </div>
    </div>";
}



function generar_html_items_evaluados($qid)
{
    global $DB;

    // 1. Obtener todas las materias asociadas al cuestionario.
    $materias = $DB->get_records('surveypro_materias', ['qid' => $qid]);

    if (!$materias) {
        throw new Exception("No se encontraron materias asociadas al cuestionario con ID $qid.");
    }

    // 2. Definir las dimensiones.
    $dimensiones = ['percepcion', 'conocimiento', 'cumplimiento'];

    // 3. Inicializar el HTML.
    $html = "<h1>Resultados de las Materias y Dimensiones</h1>";

    // 4. Recorrer materias y dimensiones.
    foreach ($materias as $materia) {
        $html .= "<h2>Materia: {$materia->fullname} (ID: {$materia->id})</h2>";

        foreach ($dimensiones as $dimension) {
            // Llamar a la función para obtener el número de ítems evaluados.
            $num_items = get_numero_evaluated_items($materia->id, $dimension);

            // Agregar los resultados al HTML.
            $html .= "<h3>Dimensión: $dimension</h3>";
            $html .= "<p>Número de preguntas evaluadas: <strong>" . count($num_items) . "</strong></p>";

            echo "<pre>";
            print_r($num_items);
            echo "</pre>";
        }
    }

    // Retornar el HTML generado.
    return $html;
}

function verificar_errores_usointerno($qid)
{
    global $DB;

    // Listado de tablas a verificar.
    $plugin_tables = [
        'sliders' => 'surveyprofield_sliders',
        'careybutton' => 'surveyprofield_careybutton'
    ];

    echo "<h1>Errores en ítems con dimensión 'usointerno' y peso distinto de cero</h1>";

    foreach ($plugin_tables as $plugin_name => $table) {
        echo "<h2>Tabla: {$table}</h2>";

        // Consulta para encontrar los ítems con errores.
        $sql = "SELECT *
                FROM {{$table}}
                WHERE dimension = :dimension
                  AND peso != 0
                  AND id IN (
                      SELECT i.id
                      FROM {surveypro_item} i
                      WHERE i.surveyproid = :qid
                  )";

        $params = [
            'dimension' => 'usointerno',
            'qid' => $qid
        ];

        $items_with_errors = $DB->get_records_sql($sql, $params);

        if (!$items_with_errors) {
            echo "<p>No se encontraron errores en la tabla {$table}.</p>";
        } else {
            echo "<ul>";
            foreach ($items_with_errors as $item) {
                echo "<li>Ítem ID: {$item->id}, Peso: {$item->peso}, Dimensión: {$item->dimension}</li>";
            }
            echo "</ul>";
        }
    }
}


function extraer_indicadores_respuestas($itemid, $table)
{
    global $DB;

    // Validar entradas.
    if (!$itemid || !$table) {
        throw new InvalidArgumentException("Debe proporcionar un ID de ítem y una tabla válida.");
    }

    // Obtener las opciones del ítem desde la tabla especificada.
    $record = $DB->get_record($table, ['id' => $itemid], 'options');

    if (!$record) {
        throw new Exception("No se encontró el ítem con ID $itemid en la tabla $table.");
    }

    $options = $record->options;

    // Dividir las opciones por líneas.
    $lines = explode("\n", $options);

    $indicadores = [];
    foreach ($lines as $line) {
        // Buscar la parte antes de '::'.
        $parts = explode('::', $line, 2);
        if (count($parts) > 1 && trim($parts[0]) !== '') {
            $indicadores[] = trim($parts[0]); // Indicador válido.
        } else {
            $indicadores[] = "sin indicador"; // Línea sin indicador.
        }
    }

    return $indicadores;
}


function procesar_items_tabla($table, $qid)
{
    global $DB;

    // Validar entrada.
    if (!$table || !$qid) {
        throw new InvalidArgumentException("Debe proporcionar una tabla válida y un ID de encuesta.");
    }

    // Obtener todos los ítems de la tabla, excluyendo los de dimensión 'usointerno'.
    $sql = "SELECT id, content, dimension, options, idmateria
            FROM {{$table}}
            WHERE dimension != :dimension";

    $params = [
        'dimension' => 'usointerno'
    ];

    $items = $DB->get_records_sql($sql, $params);

    if (!$items) {
        echo "<p>No se encontraron ítems para procesar en la tabla $table.</p>";
        return;
    }

    echo "<h1>Procesando ítems de la tabla $table</h1>";

    // Iterar sobre los ítems y procesar los indicadores.
    foreach ($items as $item) {
        echo "<h2>Ítem ID: {$item->id}, {$item->content} Dimensión: {$item->dimension} Materia {$item->idmateria}</h2>";

        try {
            $indicadores = extraer_indicadores_respuestas($item->id, $table);

            // Verificar para `surveyprofield_careybutton`: indicadores válidos.
            if ($table === 'surveyprofield_careybutton') {
                $valid_sets = [
                    ['correct', 'incorrect'],
                    ['correct', 'incorrect', 'idk'],
                    ['correct', 'mid', 'incorrect', 'idk'],
                    ['correct', 'mid', 'incorrect']
                ];

                sort($indicadores);

                $is_valid = false;
                foreach ($valid_sets as $valid_set) {
                    sort($valid_set); // Ordenar el conjunto válido.
                    if ($indicadores === $valid_set) {
                        $is_valid = true;
                        break;
                    }
                }

                if ($is_valid) {
                    continue;
                } else {
                    echo "<p style='color:red;'>Error: Indicadores no válidos (ID: {$item->id}) - Encontrados: " . implode(', ', $indicadores) . "</p>";
                }
            }

            echo "<ul>";
            foreach ($indicadores as $indicador) {
                echo "<li>Indicador: $indicador</li>";
            }
            echo "</ul>";

            // Verificar si en la tabla `sliders` ningún indicador es "none" o "incorrect".
            if ($table === 'surveyprofield_sliders') {
                if (!in_array('none', $indicadores) && !in_array('incorrect', $indicadores)) {
                    echo "<p style='color:orange;'>Warning: Ningún indicador es 'none' o 'incorrect' para el ítem ID {$item->id}.</p>";
                }
            }
        } catch (Exception $e) {
            echo "<p>Error procesando ítem ID {$item->id}: " . $e->getMessage() . "</p>";
        }
    }
}

/**
 * Obtener la última página contestada de una encuesta.
 *
 * @param int $encuestaid ID de la encuesta.
 * @param int $envioid ID del envío (submissionid).
 * @return int|null Número de la última página contestada o null si no se encuentra.
 */
function obtener_ultima_pagina_contestada($encuestaid, $envioid)
{
    global $DB;

    // Paso 1: Obtener el último ID de respuesta (id en surveypro_answer) para el envío dado
    $consulta_respuesta = '
        SELECT id, itemid
        FROM {surveypro_answer}
        WHERE submissionid = :envioid
        ORDER BY id DESC
        LIMIT 1';
    $respuesta = $DB->get_record_sql($consulta_respuesta, ['envioid' => $envioid]);

    if (!$respuesta) {
        // No se encontró ninguna respuesta para el envío
        return null;
    }

    // Paso 2: Usar el itemid de la última respuesta para encontrar la página del formulario
    $consulta_pagina = '
        SELECT formpage
        FROM {surveypro_item}
        WHERE id = :itemid AND surveyproid = :encuestaid';
    $pagina_formulario = $DB->get_field_sql($consulta_pagina, [
        'itemid' => $respuesta->itemid,
        'encuestaid' => $encuestaid
    ]);

    return $pagina_formulario !== false ? $pagina_formulario : null;
}

/**
 * Get a record from the table corresponding to an itemid.
 *
 * @param int $itemid The ID of the item to retrieve data for.
 * @return object|null The full record from the plugin table, or null if not found.
 * @throws dml_exception
 */
function get_plugin_record_by_itemid($itemid)
{
    global $DB;

    // Step 1: Get the plugin from the mdl_surveypro_item table.
    $item = $DB->get_record('surveypro_item', ['id' => $itemid], 'plugin');
    if (!$item) {
        throw new moodle_exception('Invalid itemid provided.');
    }

    // Step 2: Construct the table name.
    $tablename = 'surveyprofield_' . $item->plugin;

    // Step 3: Get the full record from the plugin table.
    $record = $DB->get_record($tablename, ['itemid' => $itemid]);

    return $record;
}
