<?php

function get_question_scores($plugin, $itemid) {
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

function transform_answer_to_score($answer, $min_score, $max_score) {
    // If the answer is numeric, calculate its percentage within the range
    if (is_numeric($answer)) {
        return round($answer,1);
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

function get_numero_evaluated_items($materiaid, $dimension) {
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

function get_user_answer($submissionid, $itemid, $plugin) {
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


function map_plugin_answer($itemid, $content, $plugin) {
    global $DB;

    if ($content === 'NC') {
        return 'NC';
    }

	if ($plugin == 'careybutton') {
		$sql = "SELECT options
					FROM {surveyprofield_careybutton}
					WHERE itemid = :itemid";

		$options = $DB->get_field_sql($sql, ['itemid' => $itemid]);

		// Convert options into an array
		$options_array = explode("\n", $options);
		$options_map = [];
		foreach ($options_array as $index => $option) {
			$parts = explode('::', trim($option));
			$options_map[$index] = $parts[0]; // Take the first part only
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

function sliders_sum_numeric_identifiers($options_array, $selected_indices) {
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

    return round($sum, 1);
}

function sliders_calculate_selection_percentage($options_array, $selected_indices) {
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

function sliders_check_special_cases($options_array, $selected_indices) {
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

function generate_percentage_bar($percentage) {
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
function generar_html_items_evaluados($qid) {
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
			print_r ($num_items);
			echo "</pre>";

        }
    }

    // Retornar el HTML generado.
    return $html;
}

function verificar_errores_usointerno($qid) {
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


function extraer_indicadores_respuestas($itemid, $table) {
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


function procesar_items_tabla($table, $qid) {
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
function obtener_ultima_pagina_contestada($encuestaid, $envioid) {
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
function get_plugin_record_by_itemid($itemid) {
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
