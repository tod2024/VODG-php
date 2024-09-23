<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TOD VOD Generator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #fff;
            color: #333;
            text-align: center;
            margin: 20px;
        }

        h1 {
            color: #ffbb00c3;

        }

        form {
            background-color: #ffbb00c3;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0);
            display: inline-block;
            width: 60%;
        }

        label {
            font-size: 1.2em;
            color: #333;
        }

        input[type="file"],
        input[type="submit"] {
            margin: 10px 0;
        }

        .radio-group {
            /* display: block; */
            /* display: inline-block; */
            display: block;
            width: 300px;
            height: 50px;
            padding: 5px;
            margin: 10px;
            margin-left: auto;
            margin-right: auto;
            /* text-align: center; */
        }

        .radio-group input[type="radio"] {
            margin-right: 10px;
        }

        input[type="submit"] {
            background-color: #ffbb00c3;
            color: black;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        .file-input {
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .error {
            color: red;
            font-size: 1em;
            display: none;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <h1>TOD VOD Generator</h1>

    <form id="xmlForm" action="generate_xml.php" method="POST" enctype="multipart/form-data">
        <label for="content_type" style='font-weight: bold;'>Content Type:</label>

        <div class="radio-group">
            <input type="radio" id="movies" name="content_type" value="Movie(s) Only">
            <label for="movies">Entertainment - Movies Only</label>
        </div>

        <div class="radio-group">
            <input type="radio" id="movies_trailers" name="content_type" value="Movie(s) With Trailers">
            <label for="movies_trailers">Entertainment - Movies With Trailers</label>
        </div>

        <div class="radio-group">
            <input type="radio" id="shows" name="content_type" value="Show(s) Only">
            <label for="shows">Entertainment - Show(s) Only</label>
        </div>

        <div class="radio-group">
            <input type="radio" id="shows_trailers" name="content_type" value="Show(s) With Trailers">
            <label for="shows_trailers">Entertainment - Show(s) With Trailers</label>
        </div>

        <div class="radio-group">
            <input type="radio" id="episodes" name="content_type" value="Episode(s)">
            <label for="episodes">Entertainment - Episode(s)</label>
        </div>

   

        <label for="file" style='font-weight: bold;'>Upload Excel File:</label><br>
        <input type="file" name="file" id="file" class="file-input" accept=".xlsx"><br>

        <div class="error" id="errorMessage">
            <h2 style='color:red;'>Please select a content type.<h2>
        </div><br>
        <input type="submit" value="Generate XML">
    </form>

    <script>
        document.getElementById("xmlForm").addEventListener("submit", function(event) {
            var contentTypeSelected = document.querySelector('input[name="content_type"]:checked');
            var errorMessage = document.getElementById("errorMessage");

            if (!contentTypeSelected) {
                event.preventDefault(); // Prevent form submission
                errorMessage.style.display = "block"; // Show error message
            } else {
                errorMessage.style.display = "none"; // Hide error message if valid
            }
        });
    </script>
</body>

</html>
