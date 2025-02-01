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
    echo '<div class="row">';

    // Loop through the images and output each in a responsive Bootstrap column.
    foreach ($images as $image) {
        echo '<div class="' . $colClass . '">';
        echo '<img src="' . $image . '" alt="User Image" class="img-responsive" />';
        echo '</div>';
    }

    // Close the Bootstrap row.
    echo '</div>';
} else {
    echo '<p>No images available.</p>';
}