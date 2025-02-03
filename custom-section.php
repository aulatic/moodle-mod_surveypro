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
//add new row in two columns, with an image in one side and a title and text in the other
echo '<div class="row imagen-text standard-section">';
echo '<div class="col-md-6">';
echo '<img src="https://placecats.com/bella/300/200?fit=contain&position=top" alt="User Image" class="img-responsive" />';
echo '</div>';
echo '<div class="col-md-6">';
//echo '<h2>Lorem Ipsum</h2>';
echo '<p>La plataforma de autodiagnóstico dispone de un cuestionario que se ajusta a las
necesidades específicas de la Empresa. Está compuesto inicialmente por 20 materias,
las que se despliegan según sus respuestas, de modo tal que sólo deberá responder
las preguntas que sean necesarias. Puede completarlo a su propio ritmo, pausando y
retomando cuando lo desee, sin la presión de un límite de tiempo. Esta metodología y
tecnología permite reducir la brecha de tiempo y recursos que antes requería el
proceso inicial de entrevistas, reduciendo las entrevistas a las estrictamente necesarias
o confirmatorias y focalizando el trabajo de campo de manera eficiente.</p>';
echo '</div>';
echo '</div>';


echo '<div class="row imagen-text standard-section">';
echo '<div class="col-md-6">';


echo '<p>Los resultados se presentan en un dashboard obtenido mediante análisis de datos. Se
obtiene una evaluación integral, que comprende 3 niveles: (1) percepción de
cumplimiento; (2) conocimiento de la Ley de Protección de Datos y (3) cumplimiento
efectivo de la misma en la Empresa. Los resultados se muestran tanto a nivel general
como por materia, permitiéndole identificar áreas que requieren refuerzo.</p>';
echo '</div>';
echo '<div class="col-md-6">';
echo '<img src="https://placecats.com/bella/300/200?fit=contain&position=top" alt="User Image" class="img-responsive" />';
echo '</div>';
echo '</div>';


/* echo '<div class="row imagen-text standard-section">';
echo '<div class="col-md-12">';
echo '<h2>Title</h2>';
echo '<p>Text</p>';
echo '<button type="button" class="btn btn-primary">Click me!</button>';
echo '</div>';
echo '</div>';
 */