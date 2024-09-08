<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TOD VOD Generator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            color: #333;
            text-align: center;
            margin: 20px;
        }
        h1 {
            color: #4CAF50;
        }
        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: inline-block;
            width: 60%;
        }
        label {
            font-size: 1.2em;
            color: #333;
        }
        input[type="file"],
        input[type="submit"],
        input[type="radio"] {
            margin: 10px 0;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
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
    </style>
</head>
<body>
    <h1>TOD VOD Generator</h1>
    <form action="generate_xml.php" method="POST" enctype="multipart/form-data">
        <label for="content_type">Content Type:</label><br>
        <input type="radio" id="movies" name="content_type" value="movies" checked>
        <label for="movies">Entertainment - Movies</label><br>
        <input type="radio" id="movies_trailers" name="content_type" value="movies_trailers">
        <label for="movies_trailers">Entertainment - Movies + Trailers</label><br><br>

        <label for="file">Upload Excel File:</label><br>
        <input type="file" name="file" id="file" class="file-input" accept=".xlsx"><br><br>

        <input type="submit" value="Generate XML">
    </form>
</body>
</html>
