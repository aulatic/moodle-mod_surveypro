<?php

// Retrieve all custom image URLs for the user.
$images = get_user_custom_images($USER->id);

// Limit the number of images to 3 if there are more.
if (count($images) > 3) {
    $images = array_slice($images, 0, 3);
}

// Count the number of images to determine Bootstrap column width.
$imageCount = count($images);

if ($imageCount > 0) {
    // Calculate column width based on the number of images.
    // For 1 image: col-md-12, for 2 images: col-md-6, for 3 images: col-md-4.
    $colWidth = 12 / $imageCount;  // This works because $imageCount is 1, 2, or 3.
    $colClass = 'col-md-' . $colWidth;

    // Start the Bootstrap row.
    echo '<div class="row imagenes custom-section">';

    // Loop through the images and output each in a responsive Bootstrap column.
    foreach ($images as $image) {
        echo '<div class="' . $colClass . '">';
        echo '<img src="' . $image . '" alt="User Image" class="img-responsive" />';
        echo '</div>';
    }

    // Close the Bootstrap row.
    echo '</div>';
}
?>


<div class="row imagen-text standard-section">
    <div class="col-md-6">
        <img src="https://autodiagnostico.carey.cl/webassets/encuesta.png" alt="User Image" class="img-responsive" />
    </div>
    <div class="col-md-6">
        <h2>RESPONDA UN CUESTIONARIO</h2>

        <h1>RESPONDA A SU RITMO</h1>

        <p>
            <span class="platform-name">Carey Data Delve</span> dispone de un cuestionario que se ajusta a las necesidades específicas de la Empresa.
        </p>

        <ul>
            <li>
                <h3>Materias:</h3> El cuestionario está compuesto por 20 materias.
            </li>
            <li>
                <h3>Medición:</h3> Se incluyen todas las materias incluidas en la Ley de Protección de Datos Personales.
            </li>
        </ul>
    </div>
</div>


<div class="row imagen-text standard-section">
    <div class="col-md-6">

        <h2>OBTENGA UN INFORME DASHBOARD</h2>

        <h1>OBTENGA RESULTADOS</h1>

        <p>
            Los resultados se presentan en un dashboard obtenido mediante análisis de datos.
            Se obtiene una evaluación, que comprende 3 ámbitos:
        </p>

        <ul>
            <li>
                <h3>Percepción:</h3> Del informante respecto al cumplimiento en materia de Protección de Datos Personales.
            </li>
            <li>
                <h3>Conocimiento:</h3> Del informante respecto de la Ley de Protección de Datos Personales.
            </li>
            <li>
                <h3>Madurez:</h3> De la Empresa en materia de Protección de Datos Personales.
            </li>
        </ul>

        <p>
            Los resultados se muestran tanto a nivel general como por materia, permitiéndole identificar las áreas que requieren refuerzo.
        </p>
    </div>
    <div class="col-md-6">
        <img src="https://autodiagnostico.carey.cl/webassets/graphs.png" alt="User Image" class="img-responsive" />
    </div>
</div>


<div class="row imagen-text standard-section">
    <div class="col-md-6">
        <img src="https://autodiagnostico.carey.cl/webassets/encuesta.png" alt="User Image" class="img-responsive" />
    </div>
    <div class="col-md-6">
        <h2>OBTENGA UN INFORME DASHBOARD</h2>

        <h1>OBTENGA RESULTADOS</h1>

        <p>
            Los resultados se presentan en un dashboard obtenido mediante análisis de datos.
            Se obtiene una evaluación, que comprende 3 ámbitos:
        </p>

        <ul>
            <li>
                <h3>Percepción:</h3> Del informante respecto al cumplimiento en materia de Protección de Datos Personales.
            </li>
            <li>
                <h3>Conocimiento:</h3> Del informante respecto de la Ley de Protección de Datos Personales.
            </li>
            <li>
                <h3>Madurez:</h3> De la Empresa en materia de Protección de Datos Personales.
            </li>
        </ul>

        <p>
            Los resultados se muestran tanto a nivel general como por materia, permitiéndole identificar las áreas que requieren refuerzo.
        </p>

        <h2>METODOLOGÍA DE AUTODIAGNÓSTICO</h2>

        <h1>EFICIENCIA</h1>

        <ul>
            <li>
                <h3>Accesibilidad:</h3> Podrá acceder a Carey Data Delve desde cualquier dispositivo con conexión a internet.
            </li>
            <li>
                <h3>Interfaz amigable:</h3> La plataforma de Carey Data Delve es intuitiva, facilitando su navegación y uso.
            </li>
            <li>
                <h3>Adaptado:</h3> El cuestionario se ajusta según las respuestas entregadas.
            </li>
            <li>
                <h3>Flexibilidad:</h3> Complete el cuestionario a su propio ritmo. Puede hacer pausas y no cuenta con un límite de tiempo.
            </li>
            <li>
                <h3>Ahorro de tiempo y recursos:</h3> Esta metodología permite reducir la brecha de tiempo y recursos que antes requería el proceso inicial de entrevistas.
            </li>
            <li>
                <h3>Enfoque eficiente:</h3> Limitamos las entrevistas a las estrictamente necesarias o confirmatorias, enfocando el trabajo de campo de manera más eficiente.
            </li>
            <li>
                <h3>Concientización:</h3> <span class="platform-name">Carey Data Delve</span> permite iniciar un proceso de concientización sobre la relevancia de los datos personales al interior de la empresa.
            </li>
            <li>
                <h3>Compatible:</h3> <span class="platform-name">Carey Data Delve</span> se complementa con las metodologías de data mapping y planes de cumplimiento desarrolladas por Carey.
            </li>
        </ul>
    </div>
</div>

<div class="row imagen-text standard-section">
    <div class="col-md-6">

        <h2>¿PREGUNTAS?</h2>

        <p>
            <img src="https://cdn-icons-png.flaticon.com/512/545/545682.png" alt="Icono de pregunta" width="20" height="20">
            Revise la sección de <strong>Preguntas Frecuentes</strong>.
        </p>

        <p>
            <img src="https://cdn-icons-png.flaticon.com/512/732/732200.png" alt="Icono de correo" width="20" height="20">
            Si no ve una respuesta a su pregunta, no dude en contactarnos a
            <a href="mailto:datadelve@carey.cl">datadelve@carey.cl</a>.
        </p>
    </div>
    <div class="col-md-6">
        <img src="https://autodiagnostico.carey.cl/webassets/graphs.png" alt="User Image" class="img-responsive" />
    </div>
</div>