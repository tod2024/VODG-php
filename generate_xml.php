<?php
require 'vendor/autoload.php'; // PhpSpreadsheet autoload

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use ZipArchive;
use PhpOffice\PhpSpreadsheet\Shared\Date;

// Function to convert date to Epoch time
function convertToEpoch($date)
{
    // Convert Excel date to PHP DateTime object
    $newDate = Date::excelToDateTimeObject($date);
    // Format date as needed
    $formattedDate = $newDate->format('d-m-Y');
    // $timestamp = $value->getTimestamp();

    $afdate = strtotime($formattedDate);

    return strtotime($formattedDate);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file']) && !empty($_FILES['file']['tmp_name'])) {
    // Load the Excel file
    $file = $_FILES['file']['tmp_name'];
    try {
        $spreadsheet = IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
    } catch (Exception $e) {
        die("Error loading Excel file: " . $e->getMessage());
    }

    // Create folder to save XML files (if it doesn't exist)
    if (!is_dir('generated_xmls')) {
        mkdir('generated_xmls', 0777, true);
    }

    // ZIP file initialization
    $zip = new ZipArchive();
    $zipFileName = 'generated_xmls/generated_xmls.zip';
    if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        exit("Cannot open <$zipFileName>\n");
    }

    // Extract the first row as the header
    $header = [];
    $headerRow = 1; // Row 1 is the header
    $columnIterator = $worksheet->getColumnIterator();
    foreach ($columnIterator as $column) {
        $colIndex = $column->getColumnIndex();
        $header[$colIndex] = strtolower(trim($worksheet->getCell($colIndex . $headerRow)->getValue()));
    }

    // Loop through the rows, skipping the first row (header)
    foreach ($worksheet->getRowIterator(2) as $row) {
        $rowIndex = $row->getRowIndex();
        $mediaId = ''; // To store the media ID for naming the XML file
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><movie></movie>');

        // Iterate through each column in the current row
        foreach ($columnIterator as $column) {
            $colIndex = $column->getColumnIndex();
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
                    $mediaId = $value; // Store media ID for naming the file
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
                case 'image format':
                    // Add images section
                    $images = $xml->addChild('images');
                    $imageCategories = ['Hero Card', 'Logo', 'Poster', 'Tile', 'Title Block', 'Wallpaper', 'Hero Block'];
                    $formats = ['3:1', '', '2:3', '16:9', '4:3', '16:9', '3:4'];
                    foreach ($imageCategories as $index => $category) {
                        $image = $images->addChild('image', $mediaId . '_' . strtolower(str_replace(' ', '', $category)) . '.png');
                        $image->addAttribute('lang', 'en');
                        $image->addAttribute('category', $category);
                        $image->addAttribute('format', !empty($formats[$index]) ? $formats[$index] : '.png');
                    }
                    break;
                default:
                    break;
            }
        }
        // If Media ID is present and the row is not the first (header) row, save the XML file
        if (!empty($mediaId)) {
            $filePath = "generated_xmls/$mediaId.xml";
            $xml->asXML($filePath);
            $zip->addFile($filePath, "$mediaId.xml");
        } else {
            echo "<h2>Error: Media ID is missing for row $rowIndex. XML not generated.</h2><br>";
        }
    }

    // Close the ZIP archive
    $zip->close();

    // Display success message and download link for ZIP file
    echo "<!DOCTYPE html>";
    echo "<html lang='en'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    echo "<title>XML Files Generated</title>";
    echo "<style>";
    echo "body { font-family: Arial, sans-serif; background-color: #fff; color: #000000; text-align: center; margin: 20px; }";
    echo "h1 { color: #ffbb00c3; }";
    echo " .btn {
  padding: 10px 20px;
    font-size: 1.2rem;
    border-radius: 5px;
    border: 2px solid #ccc;
    background-color: #333;
  color: white;
  cursor: pointer;
  font-size: 20px;
margin-right: 10px;
   
}";
    echo ".btn:hover {
  background-color: #555;
}";
    echo "a { color: #000000; text-decoration: none;  }";
    echo "a:hover { color: #ffbb00; }";
    echo "div.result-container { background-color: #ffbb00c3; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); display: inline-block; width: 60%; margin-top: 20px; }";
    echo "</style>";
    echo "</head>";
    echo "<body>";
    echo "<h1>XML Files Generated Successfully!</h1>";
    echo "<div class='result-container'>";
    echo "<h2>Download ZIP of all files:</h2>";
    echo "<a href='$zipFileName'> <button class='btn' download>Download ZIP</button></a><br><br>";

    // List all generated XMLs for preview
    echo "<h3>Preview Generated XML Files:</h3>";
    foreach (scandir('generated_xmls') as $xmlFile) {

        if (pathinfo($xmlFile, PATHINFO_EXTENSION) === 'xml' && strpos($xmlFile, 'Media ID') === false) {

            echo "<a href='generated_xmls/$xmlFile' target='_blank'>$xmlFile</a><br>";
        }
    }

    echo "</div>";
    echo "</body>";
    echo "</html>";
} else {
    header("Location: index.php");
}
