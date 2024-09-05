<?php
require 'vendor/autoload.php'; // PhpSpreadsheet autoload

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;

// Function to convert date to Epoch time
function convertToEpoch($date) {
    return strtotime($date);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file']) && !empty($_FILES['file']['tmp_name'])) {
    $contentType = $_POST['content_type'];

    // Load the Excel file
    $file = $_FILES['file']['tmp_name'];
    try {
        $spreadsheet = IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
    } catch (Exception $e) {
        die("Error loading Excel file: " . $e->getMessage());
    }

    // Get the first row (header) and validate columns
    $header = [];
    foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
        if ($rowIndex == 1) { // First row is treated as a header
            foreach ($worksheet->getColumnIterator() as $colIndex => $col) {
                $header[$colIndex] = strtolower(trim($worksheet->getCell($colIndex . $rowIndex)->getValue()));
            }
            continue;
        }

        // Create XML structure for each movie
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><movie></movie>');

        $mediaId = ''; // To store the Media ID for naming the XML file

        // Iterate over the rest of the rows and create XML tags
        foreach ($worksheet->getColumnIterator() as $colIndex => $col) {
            $value = $worksheet->getCell($colIndex . $rowIndex)->getValue();
            $headerName = $header[$colIndex];

            // Map headers to appropriate XML tags
            switch ($headerName) {
                case 'ar title':
                    $xml->addChild('title', $value)->addAttribute('lang', 'ar');
                    break;
                case 'en title':
                    $xml->addChild('title', $value)->addAttribute('lang', 'en');
                    break;
                case 'media id':
                    $mediaId = $value; // Store the media ID to name the file
                    $xml->addChild('mediaid', $value);
                    break;
                case 'publish date':
                    $xml->addChild('startVod', convertToEpoch($value));
                    break;
                case 'end date':
                    $xml->addChild('endVod', convertToEpoch($value));
                    break;
                case 'exclusive start_date':
                    if (!isset($exclusivity)) {
                        $exclusivity = $xml->addChild('exclusivity');
                    }
                    $exclusivity->addChild('is_exclusive', '')->addAttribute('start_date', convertToEpoch($value));
                    break;
                case 'exclusive end_date':
                    if (!isset($exclusivity)) {
                        $exclusivity = $xml->addChild('exclusivity');
                    }
                    $exclusivity->addChild('is_exclusive', '')->addAttribute('end_date', convertToEpoch($value));
                    break;
                case 'duration':
                    $xml->addChild('duration', $value);
                    break;
                case 'en synopsis':
                    $xml->addChild('description', $value)->addAttribute('lang', 'en');
                    break;
                case 'ar synopsis':
                    $xml->addChild('description', $value)->addAttribute('lang', 'ar');
                    break;
                case 'en category':
                    $xml->addChild('category', $value)->addAttribute('lang', 'en');
                    break;
                case 'ar category':
                    $xml->addChild('category', $value)->addAttribute('lang', 'ar');
                    break;
                case 'releaseyear':
                    $xml->addChild('releaseYear', $value);
                    break;
                case 'rating':
                    $xml->addChild('rating', $value);
                    break;
                case 'content_rating':
                    $xml->addChild('content_rating', $value);
                    break;
                default:
                    // Handle other cases if needed
                    break;
            }
        }

        // Check if Media ID is set, and use it for the filename
        if (!empty($mediaId)) {
            $filePath = "$mediaId.xml";
            $xml->asXML($filePath);

            echo "<h2>XML for Media ID: $mediaId Generated Successfully!</h2>";
            echo "<a href='$filePath' download>Download $mediaId.xml</a><br><br>";
        } else {
            echo "<h2>Error: Media ID is missing for row $rowIndex. XML not generated.</h2><br>";
        }
    }
} else {
    // Display UI for form input
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>TOD VOD Generator</title>
    </head>
    <body>
        <h1>TOD VOD Generator</h1>
        <form action="" method="POST" enctype="multipart/form-data">
            <label for="content_type">Content Type:</label><br>
            <input type="radio" id="movies" name="content_type" value="movies" checked>
            <label for="movies">Entertainment - Movies</label><br>
            <input type="radio" id="movies_trailers" name="content_type" value="movies_trailers">
            <label for="movies_trailers">Entertainment - Movies + Trailers</label><br><br>

            <label for="file">Upload Excel File:</label>
            <input type="file" name="file" id="file" accept=".xlsx"><br><br>

            <input type="submit" value="Generate XML">
        </form>
    </body>
    </html>
    <?php
}
?>
