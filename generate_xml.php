<?php
require 'vendor/autoload.php'; // PhpSpreadsheet autoload

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;

use PhpOffice\PhpSpreadsheet\Shared\Date;



// Function to convert date to Epoch time
function convertToEpoch($date)
{
    // Convert Excel date to PHP DateTime object
    $newDate = Date::excelToDateTimeObject($date);
    // Format date as needed
    $formattedDate = $newDate->format('d-m-Y');
    // Convert to epoch
    return strtotime($formattedDate);
}


// Function to format XML with indentation

function formatXml(SimpleXMLElement $xml)

{

    $dom = new DOMDocument('1.0', 'UTF-8');

    $dom->preserveWhiteSpace = false;

    $dom->formatOutput = true;

    $dom->loadXML($xml->asXML());

    return $dom->saveXML();
}

function deleteGeneratedXmls()
{
    $folderPath = 'generated_xmls'; // Replace with the actual path

    // Check if the folder exists
    if (!is_dir($folderPath)) {
        return false;
    }

    // Loop through the folder contents
    $files = array_diff(scandir($folderPath), ['.', '..']);
    foreach ($files as $file) {
        $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
        if (is_file($filePath)) {
            // Delete files
            unlink($filePath);
        }
    }
    return true;
}

// Clean up previously generated XMLs
array_map('unlink', glob("generated_xmls/*.xml"));
deleteGeneratedXmls();
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file']) && !empty($_FILES['file']['tmp_name'])) {


    //define contet type text
    $moviesOnly = 'Movie(s) Only';
    $moviesTRL = 'Movie(s) With Trailers';
    $showsOnly = 'Show(s) Only';
    $showsTRL = 'Show(s) With Trailers';
    $episodes = 'Episode(s)';
    //Get content type
    $contentType = $_POST['content_type'];


    // define mediaID or showID based on content type
    $iD = '';
    $idTag = '';

    if ($contentType ==  $moviesOnly || $contentType ==   $moviesTRL || $contentType ==  $episodes) {
        $iD = 'media id';
        $idTag = 'mediaid';
    } elseif ($contentType ==  $showsOnly || $contentType ==  $showsTRL) {
        $iD = 'show id';
        $idTag = 'showid';
    }


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
    // Set the timezone to Qatar
    date_default_timezone_set('Asia/Qatar');

    $zipFileName = 'generated_xmls/' . date("YmdHHis") . '.zip'; // zip file name
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
    function getCrewrole($crew, $lang)
    {

        return trim(substr($crew, strlen("$lang crew ")));
    }
    $xmlPassedCounter = 0;
    $xmlFailedCounter = 0;
    // Loop through the rows, skipping the first row (header)
    foreach ($worksheet->getRowIterator(2) as $row) {
        $rowIndex = $row->getRowIndex();
        $contentId = ''; // To store the media ID for naming the XML file
        $enTitle = ''; // To store the EN title
        $arTitle = ''; // To store the AR title
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><movie></movie>');
        $imageFormatTRL = "";
        $startVodTRL = "";
        // Cast and crew variables
        $castEN = "";
        $crewEN = array();
        $crewENTXT = "en crew";
        $castAR = array();
        $crewAR = array();
        $crewARTXT = "ar crew";
        $audioCounter = 0;
        $audioTracks = [];
        $packageValue = "VOD";
        $LPSD = ''; // license product start date
        $releaseDate = ''; //startVOD and license start date
        $exclusiveEndDate = ''; //Exclusive End Date
        $LPED = ''; // License Product End Date
        $NumOfSeason = '';
        $seasonTitleAR = '';
        $seasonTitleEN = '';
        $seasonDesAR = '';
        $seasonDesEN = '';
        $seasonReleaseYear = '';



        // Iterate through each column in the current row
        foreach ($columnIterator as $column) {
            $colIndex = $column->getColumnIndex();
            $value = $worksheet->getCell($colIndex . $rowIndex)->getValue();
            $headerName = $header[$colIndex];

            // echo "headername:: $headerName<br>";
            // Map headers to appropriate XML tags
            switch ($headerName) {
                case 'ar title':
                    $arTitle = trim(htmlspecialchars($value));
                    $xml->addChild('title', htmlspecialchars($arTitle))->addAttribute('lang', 'ar');
                    break;
                case 'en title':
                    $enTitle = trim(htmlspecialchars($value));
                    $xml->addChild('title', htmlspecialchars($enTitle))->addAttribute('lang', 'en');

                    break;

                    //media ID Or Show ID
                case $iD:

                    $contentId = trim($value); // Store media ID for naming the file

                    $xml->addChild($idTag, $contentId);
                    break;


                case 'release date':
                    if (!empty($value)) {
                        $releaseDate = convertToEpoch($value);
                    }


                    break;


                case 'publish date':
                    if (!empty($value)) {
                        $releaseDate = convertToEpoch($value);
                    }
                    break;


                case 'lpsd':

                    if (!empty($value)) {

                        $LPSD = convertToEpoch($value);
                    }
                    break;

                case 'end date':

                    if (!empty($value)) {

                        $LPED = convertToEpoch($value);
                    }
                    break;


                case 'exclusive end date':

                    if (!empty($value)) {
                        $exclusiveEndDate = convertToEpoch($value);
                    }

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
                case 'type':
                    $xml->addChild('type', $value);
                    break;
                case 'en category':
                    $xml->addChild('category', $value)->addAttribute('lang', 'en');
                    break;

                case 'ar category':
                    $xml->addChild('category', $value)->addAttribute('lang', 'ar');
                    break;

                case 'en cast':
                    $castEN = ucfirst($value);
                    break;

                case 'ar cast':
                    $castAR = ucfirst($value);
                    break;

                case 'season':

                    if (!empty($value)) {
                        $NumOfSeason = $value;

                        if ($contentType == $episodes) {

                            $xml->addChild('season')->addAttribute('id', $value);
                        }
                    }
                    break;

                case 'episode':

                    if ($contentType == $episodes) {

                        $xml->addChild('episodeNumber', $value);
                    }
                    break;

                case 'show id':

                    if ($contentType == $episodes) {

                        $xml->addChild('show')->addAttribute('id', $value);
                    }
                    break;

                case 'ar season title':

                    if (!empty($value)) {

                        $seasonTitleAR = $value;
                    }
                    break;

                case 'en season title':

                    if (!empty($value)) {

                        $seasonTitleEN = $value;
                    }
                    break;

                case 'ar season description':

                    if (!empty($value)) {

                        $seasonDesAR = $value;
                    }
                    break;

                case 'en season description':

                    if (!empty($value)) {

                        $seasonDesEN = $value;
                    }
                    break;

                case 'season release year':

                    if (!empty($value)) {

                        $seasonReleaseYear = $value;
                    }
                    break;

                case 'releaseyear':
                    $xml->addChild('releaseYear', $value);
                    break;
                case 'rating':
                    $xml->addChild('rating', $value);
                    break;
                case 'content_rating':
                    $xml->addChild('content_ratings')->addChild('content_rating', $value)->addAttribute('country_code', 'en');
                    break;

                case 'is4k':
                    $is4k = "True";
                    if (strtolower($value) == 'f') {
                        $is4k = "False";
                    }
                    $xml->addChild('is4K', $is4k);
                    break;

                case 'kids content':
                    $isKidsContent = "True";
                    if (strtolower($value) == 'f') {
                        $isKidsContent = "False";
                    }
                    $xml->addChild('isKidsContent', $isKidsContent);
                    break;

                case 'package':
                    $xml->addChild('package', !empty($value) ? $value : $packageValue);
                    break;

                case 'image format':
                    $imageEXT = !empty($value) ? $value : 'png';
                    
                    // Add images section
                    $images = $xml->addChild('images');
                    if ($contentType != $episodes) {
                        $imageCategoriesAndFormats = [
                            'Hero Card'    => '3:1',
                            'Logo'         => '',
                            'Poster'       => '2:3',
                            'Tile'         => '16:9',
                            // 'Title Block'  => '4:3',
                            'Wallpaper'    => '16:9',
                            'Hero Block'   => '3:4',
                            // 'Square Title' => '1:1',
                            // 'Tall Image'   => '1:2',
                            'Thumbnail'    => '16:9',
                        ];

                        foreach ($imageCategoriesAndFormats as $category => $format) {
                            if ($category == "Thumbnail") {
                                $image = $images->addChild('image', $contentId . '_' . "TILE" . ".$imageEXT");
                            } else {
                                $image = $images->addChild('image', $contentId . '_' . Strtoupper(strtolower(str_replace(' ', '', $category))) . ".$imageEXT");
                            }

                            $image->addAttribute('lang', 'en');
                            $image->addAttribute('category', $category);
                            $image->addAttribute('format', $format);

                            // !empty($formats[$index]) ? $formats[$index] : '.png'
                        }
                    } else {

                        $image = $images->addChild('image', $contentId . '_' . Strtoupper(strtolower(str_replace(' ', '', 'Wallpaper'))) . ".$imageEXT");

                        $image->addAttribute('lang', 'en');
                        $image->addAttribute('category', 'Wallpaper');
                        $image->addAttribute('format', '16:9');
                    }

                    break;

                case 'trailer image format':
                    if (!empty($value)) {
                        $imageFormatTRL = $value;
                    } else {
                        $imageFormatTRL = "png";
                    }
                    break;

                case 'trailer publish date':
                    $startVodTRL = convertToEpoch($value);
                    break;
                default:
                    break;


                case 'start_of_intro':
                    $xml->addChild('start_of_intro', $value);
                    break;

                case 'end_of_intro':
                    $xml->addChild('end_of_intro', $value);
                    break;

                case 'start_of_credits':
                    $xml->addChild('start_of_credits', $value);
                    break;
            }


            // Map headers to appropriate XML tags with contains condition 
            switch (true) {

                    //adding cast and crew tags
                case (strpos($headerName, 'en crew') !== false && strlen($headerName) > 4):

                    $crewEN["name"] = $value;
                    $crewEN["role"] = getCrewrole($headerName, 'en');

                    break;

                    //adding cast and crew tags
                case (strpos($headerName, 'ar crew') !== false && strlen($headerName) > 4):


                    $crewAR["name"] = $value;
                    $crewAR["role"] = getCrewrole($headerName, 'ar');
                    break;

                    //identifying who many Audio the content has
                case (strpos($headerName, 'audio_') !== false):
                    $audioTracks[$audioCounter] = $value;
                    $audioCounter++;
                    break;
                default:
                    break;
            }
        }

        //IF content type contains Shows
        if ($contentType == $showsOnly || $contentType == $showsTRL) {
            $seasons = $xml->addChild('seasons');
            $season = $seasons->addChild('season');
            $season->addAttribute('season', $NumOfSeason);

            if (!empty($seasonTitleEN)) {
                $seasonenTitle = trim(htmlspecialchars($seasonTitleEN));
                $season->addChild('title', htmlspecialchars($seasonenTitle))->addAttribute('lang', 'en');
            }
            if (!empty($seasonTitleEN)) {
                $seasonarTitle = trim(htmlspecialchars($seasonTitleAR));
                $season->addChild('title', htmlspecialchars($seasonarTitle))->addAttribute('lang', 'ar');
            }

            if (!empty($seasonDesEN)) {
                $enDes = trim(htmlspecialchars($seasonDesEN));

                $season->addChild('description', htmlspecialchars($enDes))->addAttribute('lang', 'en');
            }

            if (!empty($seasonDesAR)) {
                $arDes = trim(htmlspecialchars($seasonDesAR));
                $season->addChild('description', htmlspecialchars($arDes))->addAttribute('lang', 'ar');
            }

            $season->addChild('releaseYear', $seasonReleaseYear);
        }


        //Create startVod
        $xml->addChild('startVod', $releaseDate);
        //Create endVod
        $xml->addChild('endVod', $LPED);

        //add package tag
        $xml->addChild('package', $packageValue);

        if ($contentType != $episodes) {

            //Create ondemand_rights tag
            $ondemandRights = $xml->addChild('ondemand_rights');
            //Create ondemand_right tag
            $ondemandRight = $ondemandRights->addChild('ondemand_rights');
            if (!empty($LPSD)) {
                $ondemandRight->addAttribute('start_date', $LPSD);
            } else {
                $ondemandRight->addAttribute('start_date', '');
            }

            if (!empty($LPED)) {
                $ondemandRight->addAttribute('end_date', $LPED);
            } else {
                $ondemandRight->addAttribute('end_date', '');
            }

            $ondemandRight->addAttribute('blackoutStartDate', '');
            $ondemandRight->addAttribute('blackoutEndDate', '');

            //Create channel_groups tag
            $channelGroups = $ondemandRights->addChild('channel_groups');
            //Create channel_group tag
            $channelGroups->addChild('channel_group', 'TOD');

            //Create region_groups tag
            $regionGroups = $ondemandRights->addChild('region_groups');
            //Create region_group tag
            $regionGroups->addChild('region_group', 'MENA');



            //Handle exclusive start date
            // Check if 'exclusivity' already exists in the XML
            if (!$xml->exclusivity) {
                // Create 'exclusivity' tag if it doesn't exist
                $exclusivity = $xml->addChild('exclusivity');
            } else {
                // If it exists, use the existing 'exclusivity' tag
                $exclusivity = $xml->exclusivity;
            }

            // Check if 'is_exclusive' already exists inside 'exclusivity'
            if (!$exclusivity->is_exclusive) {
                // Add 'is_exclusive' if it doesn't exist
                $isExclusive = $exclusivity->addChild('is_exclusive');
            } else {
                // Use the existing 'is_exclusive' tag
                $isExclusive = $exclusivity->is_exclusive;
            }


            if (!empty($releaseDate)) {
                $isExclusive->addAttribute('start_date', $releaseDate);
            } else {
                $isExclusive->addAttribute('start_date', '');
            }


            if (!empty($exclusiveEndDate)) {

                $isExclusive->addAttribute('end_date', $exclusiveEndDate);
            } else {
                $isExclusive->addAttribute('end_date', '');
            }
        }

        //initializing  credits en
        if (sizeof($crewEN) > 0 || !empty($castEN)) {
            //initializing  credits en tag
            $credits = $xml->addChild('credits');
            $credits->addAttribute('lang', 'en');

            //initializing cast en
            if (!empty($castEN)) {
                $casts = $credits->addChild('casts');
                $cast = $casts->addChild('cast');
                $castName = $cast->addChild('name', ucfirst($castEN));
            }

            //initializing crew en
            if (sizeof($crewEN) > 0) {
                $crews = $credits->addChild('crews');
                $crew = $crews->addChild('crew');
                $crewRole = $crew->addChild('role', ucfirst($crewEN['role']));
                $crewName = $crew->addChild('name', ucfirst($crewEN['name']));
            }
        }

        //initializing  credits ar
        if (sizeof($crewAR) > 0 || !empty($castAR)) {
            //initializing  credits ar tag
            $credits = $xml->addChild('credits');
            $credits->addAttribute('lang', 'ar');

            //initializing cast ar
            if (!empty($castAR)) {
                $casts = $credits->addChild('casts');
                $cast = $casts->addChild('cast');
                $castName = $cast->addChild('name', ucfirst($castAR));
            }

            //initializing crew ar
            if (sizeof($crewAR) > 0) {
                $crews = $credits->addChild('crews');
                $crew = $crews->addChild('crew');
                $crewRole = $crew->addChild('role', ucfirst($crewAR['role']));
                $crewName = $crew->addChild('name', ucfirst($crewAR['name']));
            }
        }

        //initializing audio tracks
        if (sizeof($audioTracks) > 0) {
            $audioTracksXML = $xml->addChild('audio_tracks');
            foreach ($audioTracks as $track) {
                $aduioTrackXML = $audioTracksXML->addChild('audio_track', $track);
                $aduioTrackXML->addAttribute('format', 'Stereo');
            }
        }
        //IF content type is with trailer
        if ($contentType == $moviesTRL || $contentType == $showsTRL) {
            $trailers = $xml->addChild('trailers');
            $trailer = $trailers->addChild('trailer');
            $trailer->addChild($idTag, $contentId . "_TRL");
            $trailer->addChild('title', "$arTitle - Trailer")->addAttribute('lang', 'ar');
            $trailer->addChild('title', "$enTitle - Trailer")->addAttribute('lang', 'en');
            $trailer->addChild('startVod', $startVodTRL);
            $trailer->addChild('package', 'FREE');
            $imagesTRL = $trailer->addChild('images');
            $imageTRL = $imagesTRL->addChild('image', $contentId . "_WALLPAPER." . $imageFormatTRL);
            $imageTRL->addAttribute('lang', 'en');
            $imageTRL->addAttribute('category', "Trailer");
            $imageTRL->addAttribute('format', "16:9");
            if (sizeof($audioTracks) > 0) {
                $audioTracksXMLTRL = $trailer->addChild('audio_tracks');
                foreach ($audioTracks as $trackTRL) {
                    $aduioTrackXMLTRL = $audioTracksXMLTRL->addChild('audio_track', $trackTRL);
                    $aduioTrackXMLTRL->addAttribute('format', 'Stereo');
                }
            }
        }

        $formattedXmlContent = formatXml($xml);
        // If Media ID is present and the row is not the first (header) row, save the XML file
        if (!empty($contentId)) {

            $xmlPassedCounter++;
            $filePath = "generated_xmls/$contentId.xml";
            // $xml->asXML($filePath);
            file_put_contents($filePath, $formattedXmlContent);
            $zip->addFile($filePath, "$contentId.xml");
        } else {
            $xmlFailedCounter++;
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
    style='margin: 0 auto;
  padding: 10px 10px;
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
    echo "a:hover { color: #E19005; }";
    echo "div.result-container { background-color: #ffbb00c3; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); display: inline-block; width: 60%; margin-top: 20px; }";
    echo "div.info-container { border: 1px solid grey; padding: 10px; margin-bottom: 20px; display: inline-block; width: 60%; text-align: center; color: #ffbb00c3; background-color: #000 }"; /* Grey border around info */
    echo "#searchInput { margin-bottom: 10px; padding: 10px; font-size: 1rem; width: 50%; }"; // Search input style
    // echo "table { margin: 0 auto; border-collapse: collapse; text-align: center; }";
    // echo "th, td { padding: 15px; border: 1px solid #ccc; }";
    echo "</style>";
    echo "<script>

    function searchTable() {

        var input, filter, table, tr, td, i, txtValue;

        input = document.getElementById('searchInput');

        filter = input.value.toUpperCase();

        table = document.getElementById('xmlTable');

        tr = table.getElementsByTagName('tr');

        for (i = 1; i < tr.length; i++) {

            td = tr[i].getElementsByTagName('td')[0];

            if (td) {

                txtValue = td.textContent || td.innerText;

                if (txtValue.toUpperCase().indexOf(filter) > -1) {

                    tr[i].style.display = '';

                } else {

                    tr[i].style.display = 'none';

                }

            }

        }

    }

  </script>";
    echo "</head>";
    echo "<body>";
    echo "<h1>XML Files Generated Successfully!</h1>";
    echo "<div class='info-container'>";  // Grey border container
    echo "<h2>Content Type: $contentType</h2>";
    echo "<h3 style='color:#008000;'>Number of Generated XML Files: $xmlPassedCounter</h3>";
    echo "<h3 style='color:Tomato;'>Number of Not Generated XML Files: $xmlFailedCounter</h3>";
    echo "</div>";  // End of grey border container
    echo "<div class='result-container'>";
    echo "<h2>Download ZIP of all files:</h2>";
    echo "<a href='$zipFileName'> <button class='btn' download>Download ZIP</button></a><br><br>";
    // Search input
    echo "<h3>Preview Generated XML Files:</h3>";
    echo "<input type='text' id='searchInput' onkeyup='searchTable()' placeholder='Search for Media ID...' />";
    echo "<table id='xmlTable' border='1'  style='  padding: 10px; text-align: center; margin: 0 auto; border-collapse: collapse; text-align: center;'><thead><tr><th>Media ID</th><th style='padding: 15px;'>Preview</th><th>Download</th></tr></thead><tbody>"; // List all generated XMLs for preview

    foreach (scandir('generated_xmls') as $xmlFile) {

        if (pathinfo($xmlFile, PATHINFO_EXTENSION) === 'xml' && strpos($xmlFile, 'Media ID') === false) {



            echo "<tr>";

            echo "<td style='padding: 15px;'>$xmlFile</td>";

            // Read the XML content and format it for display

            $xmlContent = file_get_contents("generated_xmls/$xmlFile");

            $formattedXml = htmlentities($xmlContent); // Escaping special characters for HTML display

            echo "<td style='padding: 10px; text-align: center;'><a href='generated_xmls/$xmlFile' target='_blank'><button class='btn'>Preview</button></a></td>";

            echo "<td style='padding: 10px; text-align: center;'><a href='generated_xmls/$xmlFile' download><button class='btn'>Download</button></a></td>";

            echo "</tr>";
        }
    }
    echo "</tbody></table>";

    echo "</div>";
    echo "</body>";
    echo "</html>";

    $contentType = "";
} else {
    header("Location: index.php");
}
