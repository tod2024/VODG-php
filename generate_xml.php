<?php
require 'vendor/autoload.php'; // PhpSpreadsheet autoload

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use ZipArchive;

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

        // Create a folder to save XML files (if it doesn't already exist)
        if (!is_dir('generated_xmls')) {
            mkdir('generated_xmls', 0777, true);
        }

        // ZIP file initialization
        $zip = new ZipArchive();
        $zipFileName = 'generated_xmls/generated_xmls.zip';

        if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            exit("Cannot open <$zipFileName>\n");
        }

        // Loop through rows to create XML
        $mediaId = ''; // Store the media ID for naming the file
        foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><movie></movie>');

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
                        $mediaId = $value; // Store media ID
                        $xml->addChild('mediaid', $value);
                        break;
                    case 'publish date':
                        $xml->addChild('startVod', convertToEpoch($value));
                        break;
                    case 'end date':
                        $xml->addChild('endVod', convertToEpoch($value));
                        break;
                    case 'duration':
                        $xml->addChild('duration', $value);
                        break;
                    // Add more cases as needed...
                }
            }

            if (!empty($mediaId)) {
                // Save XML to a file
                $fileName = "generated_xmls/$mediaId.xml";
                $xml->asXML($fileName);
                $zip->addFile($fileName, "$mediaId.xml"); // Add to ZIP archive
            } else {
                echo "<h2>Error: Media ID is missing for row $rowIndex. XML not generated.</h2><br>";
            }
        }

        $zip->close();

        echo "<h2>All XMLs Generated Successfully!</h2>";
        echo "<a href='$zipFileName' download>Download ZIP File</a><br><br>";

        // List all generated XMLs for preview
        echo "<h3>Preview Generated XML Files:</h3>";
        foreach (scandir('generated_xmls') as $xmlFile) {
            if (pathinfo($xmlFile, PATHINFO_EXTENSION) === 'xml') {
                echo "<a href='generated_xmls/$xmlFile' target='_blank'>$xmlFile</a><br>";
            }
        }

    }
} else {
    header("Location: index.php");
}
?>
