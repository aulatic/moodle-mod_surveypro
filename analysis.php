<?php


require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/careylib.php');


$submissionid = required_param('submissionid', PARAM_INT);
$surveyid = required_param('s', PARAM_INT);
$materia = optional_param('materia', '', PARAM_INT);

$surveypro = $DB->get_record('surveypro', ['id' => $surveyid], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $surveypro->course], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('surveypro', $surveypro->id, $course->id, false, MUST_EXIST);

$materias = [];
if ($surveyid == 2) {
    $materias = $DB->get_records('surveypro_materias', ['qid' => 'PDP']);
    $dimensiones = [
        'percepcion'   => 'Percepción',
        'conocimiento' => 'Conocimiento',
        'cumplimiento'      => 'Madurez'
    ];
}

require_course_login($course, false, $cm);
$context = \context_module::instance($cm->id);

$urlparams = array('submissionid' => $submissionid, 'surveyid' => $surveyid);
if (!empty($materia)) {
    $urlparams['materia'] = $materia;
}
$PAGE->set_url('/mod/surveypro/analysis.php', $urlparams);

$PAGE->set_context($context);
$PAGE->set_title('Análisis Cuantitativo');
$PAGE->set_heading('Análisis Cuantitativo');
$PAGE->add_body_class('analysis-page');

$submission = $DB->get_record('surveypro_submission', ['id' => $submissionid], '*', MUST_EXIST);
$user = $DB->get_record('user', ['id' => $submission->userid], '*', MUST_EXIST);

$cargo = $DB->get_field('surveypro_answer', 'content', ['itemid' => 50, 'submissionid' => $submissionid]);
$cargo = $cargo !== false ? $cargo : "Desconocido";

$canmanegeitems = has_capability('mod/surveypro:manageitems', $context);

echo $OUTPUT->header();
?>

<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>DASHBOARD</h1>
            <p class="text-left"><span class="atributo">Informante:</span> <?php echo fullname($user); ?></p>
            <p class="text-left"><span class="atributo">Empresa:</span> <?php echo $user->institution; ?></p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <form method="get" action="" class="form-inline justify-content-center">
                <input type="hidden" name="submissionid" value="<?php echo $submissionid; ?>">
                <input type="hidden" name="s" value="<?php echo $surveyid; ?>">
                <div class="form-group mx-sm-3 mb-2">
                    <label for="materia" class="sr-only">Materia</label>
                    <select class="form-control" id="materia" name="materia">
                        <option value="">Resultados Generales</option>
                        <?php
                        if (!empty($materias)) {
                            foreach ($materias as $materia_item) {
                                $selected = ($materia_item->id == $materia) ? 'selected' : '';
                                echo '<option value="' . $materia_item->id . '" ' . $selected . '>' . $materia_item->fullname . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary mb-2">Ver</button>
            </form>
        </div>
    </div>

    <?php if (empty($materia)) { ?>
        <div class="row">
            <?php foreach ($dimensiones as $dimensionKey => $dimensionLabel) { ?>

                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title"><?php echo $dimensionLabel; ?></h3>

                            <?php

                            $puntajegeneraldimension = 0;
                            $sumapesosmaterias = 0;

                            foreach ($materias as $materia_item) {
                                $evaluated_items = get_numero_evaluated_items($materia_item->id, $dimensionKey);
                                $num_evaluated_items = count($evaluated_items);
                                $num_unanswered = 0;

                                if (!empty($evaluated_items)) {
                                    $total_score = 0;
                                    $num_unanswered = 0;
                                    $num_omitted = 0; // Contador de respuestas omitidas
                                    $num_valid = 0;
                                    $total_min_score = 0; // Suma de puntajes mínimos
                                    $total_max_score = 0; // Suma de puntajes máximos

                                    foreach ($evaluated_items as $item) {
                                        $answer_content = get_user_answer($submissionid, $item['itemid'], $item['plugin']);
                                        //echo "answer_content" . $answer_content;
                                        if ($answer_content === 'NC') {
                                            $num_unanswered++;
                                            continue;
                                        }

                                        $answer = map_plugin_answer($item['itemid'], $answer_content, $item['plugin']);
                                        $scores = get_question_scores($item['plugin'], $item['itemid']);
                                        $score = transform_answer_to_score($answer, $scores['min_score'], $scores['max_score']);

                                        // Contar respuestas omitidas y no acumular sus puntajes mínimos ni máximos
                                        if ($score === null && $answer === 'idk') {
                                            $num_omitted++;
                                            continue; // Salta a la siguiente iteración, omitiendo suma de puntajes
                                        }

                                        $num_valid++;

                                        // Acumular el puntaje total si es válido y  Sumar puntajes mínimos y máximos
                                        if ($score !== null) {
                                            $total_score += $score;
                                            $total_min_score += $scores['min_score'];
                                            $total_max_score += $scores['max_score'];
                                        }
                                    }
                                    if ($total_max_score > $total_min_score) {
                                        $percentage = round((($total_score - $total_min_score) / ($total_max_score - $total_min_score)) * 100, 5);
                                        $percentageponderado = $percentage * $materia_item->peso;
                                    } else {
                                        //si no se puede calcular el porcentaje, se omite la materia
                                        //echo "<br>Porcentaje No se puede calcular";
                                        continue;
                                    }
                                    $puntajegeneraldimension += $percentageponderado;
                                    $sumapesosmaterias += $materia_item->peso;
                                    //imprimo valores solo para debugeo
                                    if ($canmanegeitems) {
                                        echo "<br>Dimension: " . $dimensionLabel;
                                        echo "<br>Materia: " . $materia_item->fullname;
                                        echo "<br>Puntaje: " . $percentage;
                                        echo "<br>Peso: " . $materia_item->peso;
                                        echo "<br>Puntaje Ponderado: " . $percentageponderado;
                                        echo "<br>Suma Pesos: " . $sumapesosmaterias;
                                        echo "<br>Puntaje General Dimension: " . $puntajegeneraldimension;
                                        echo "<br>---------------------------------";
                                    }
                                }
                            }

                            $puntajegeneraldimension = round($puntajegeneraldimension / $sumapesosmaterias, 2);
                            ?>

                            <!-- <p>Puntaje General: <?php echo $puntajegeneraldimension; ?>%</p> -->
                            <!-- <?php echo generate_percentage_bar($puntajegeneraldimension); ?> -->
                            <div class="progress-circle" style="--value: <?php echo $puntajegeneraldimension; ?>">
                                <div class="progress-value"><?php echo $puntajegeneraldimension; ?>%</div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php } ?>

        </div>

    <?php } ?>

    <?php if (!empty($materia)) { ?>
        <div class="row">
            <?php foreach ($dimensiones as $dimensionKey => $dimensionLabel) {
                $evaluated_items = get_numero_evaluated_items($materia, $dimensionKey);
                $num_evaluated_items = count($evaluated_items);
                $num_unanswered = 0;
            ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title"><?php echo $dimensionLabel; ?></h3>

                            <?php if (!empty($evaluated_items)) { ?>
                                <ul>
                                    <?php
                                    $total_score = 0;
                                    $num_unanswered = 0;
                                    $num_omitted = 0; // Contador de respuestas omitidas
                                    $num_valid = 0;
                                    $total_min_score = 0; // Suma de puntajes mínimos
                                    $total_max_score = 0; // Suma de puntajes máximos

                                    foreach ($evaluated_items as $item) {
                                        $answer_content = get_user_answer($submissionid, $item['itemid']);
                                        //echo "answer_content" . $answer_content;
                                        if ($answer_content === 'NC') {
                                            $num_unanswered++;
                                            continue;
                                        }



                                        $answer = map_plugin_answer($item['itemid'], $answer_content, $item['plugin']);
                                        //echo "answer" . $answer;
                                        $scores = get_question_scores($item['plugin'], $item['itemid']);

                                        // Transformar la respuesta en puntaje
                                        $score = transform_answer_to_score($answer, $scores['min_score'], $scores['max_score']);

                                        // Contar respuestas omitidas y no acumular sus puntajes mínimos ni máximos
                                        if ($score === null && $answer === 'idk') {
                                            $num_omitted++;
                                            continue; // Salta a la siguiente iteración, omitiendo suma de puntajes
                                        }

                                        $num_valid++;


                                        // Acumular el puntaje total si es válido y  Sumar puntajes mínimos y máximos
                                        if ($score !== null) {
                                            $total_score += $score;
                                            $total_min_score += $scores['min_score'];
                                            $total_max_score += $scores['max_score'];
                                        }
                                    ?>
                                        <?php if ($canmanegeitems) { ?>
                                            <li>

                                                <?php echo $item['itemid']; ?> - <?php echo $item['plugin']; ?>: <?php echo $answer; ?>

                                                <br> Puntaje Mínimo: <?php echo $score !== null ? $scores['min_score']: 'Omitido'; ?>
                                                <br> Puntaje Máximo: <?php echo $score !== null ? $scores['max_score']: 'Omitido'; ?>
                                                <br> Puntaje Asignado: <?php echo $score !== null ? $score : 'Omitido'; ?>
                                            </li>
                                        <?php } ?>
                                    <?php } ?>
                                </ul>

                                <?php if ($canmanegeitems) { ?>
                                    <p>Puntaje Total: <?php echo $total_score; ?></p>
                                    <p>Suma de Mínimos: <?php echo $total_min_score; ?></p>
                                    <p>Suma de Máximos: <?php echo $total_max_score; ?></p>
                                <?php } ?>

                                <?php
                                // Calcular porcentaje basado en el puntaje obtenido y el rango entre mínimos y máximos
                                if ($total_max_score > $total_min_score) {
                                    $percentage = round((($total_score - $total_min_score) / ($total_max_score - $total_min_score)) * 100, 1);
                                    $percentage_output = number_format($percentage, 2) . '%';

                                ?>

                                    <div class="progress-circle" style="--value: <?php echo $percentage; ?>">
                                        <div class="progress-value"><?php echo $percentage; ?>%</div>
                                    </div>

                                <?php
                                    echo "<br>";
                                    echo "<p>Preguntas respondidas 'Desconozco': " . $num_omitted . "</p>";
                                    //echo generate_percentage_bar($percentage);
                                } else {
                                    echo '<p>Porcentaje No se puede calcular</p>';
                                }
                                ?>
                            <?php } else { ?>
                                <p>No hay respuestas disponibles.</p>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

    <?php
    echo $OUTPUT->footer();
