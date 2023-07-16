<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Analisis Sentimen Pariwisata</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/sb-admin.css" rel="stylesheet">
    <link href="font-awesome-4.1.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
</head>

<body>
    <div id="wrapper">
        <!-- Navigation -->
        <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="index.php">Analisis Sentimen</a>
            </div>
            <div class="collapse navbar-collapse navbar-ex1-collapse">
                <ul class="nav navbar-nav side-nav">
                    <li>
                        <a href="index.php"><i class="fa fa-fw fa-dashboard"></i> Pencarian Tweets</a>
                    </li>
                    <li>
                        <a href="charts.php"><i class="fa fa-fw fa-bar-chart-o"></i> Dashboard Hasil Analisa</a>
                    </li>
                </ul>
            </div>
            <!-- /.navbar-collapse -->
        </nav>

        <div id="page-wrapper">
            <div class="container-fluid">
                <!-- Page Heading -->
                <div class="row">
                    <div class="col-lg-12">
                        <h3 class="page-header">
                            Pilih Data
                        </h3>
                    </div>
                </div>
                <!-- Displaying data from the "tweets" table -->
                <div class="row">
                    <div class="col-lg-12">
                        <form method="post" action="">
                            <div class="form-group">
                                <label for="hastag">Pilih Hashtag:</label>
                                <select class="form-control" id="hastag" name="hastag">
                                    <option value="">-- Pilih Hashtag --</option>
                                    <?php
                                    // Database connection
                                    $host = "localhost";  // Your database host
                                    $username = "root";  // Your database username
                                    $password = "";  // Your database password
                                    $database = "sentimen2";  // Your database name

                                    // Create connection
                                    $conn = new mysqli($host, $username, $password, $database);

                                    // Check connection
                                    if ($conn->connect_error) {
                                        die("Connection failed: " . $conn->connect_error);
                                    }

                                    // Fetching hastags from the "tweets" table
                                    $sql = "SELECT DISTINCT hastag FROM tweets";
                                    $result = $conn->query($sql);

                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='" . $row["hastag"] . "'>" . $row["hastag"] . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary" name="submit">Tampilkan</button>
                        </form>
                    </div>
                </div>
                <!-- /.row -->

                <?php
                // Check if form is submitted
                if (isset($_POST["submit"])) {
                    $selectedhastag = $_POST["hastag"];

                    // Database connection
                    $conn = new mysqli($host, $username, $password, $database);

                    // Check connection
                    if ($conn->connect_error) {
                        die("Connection failed: " . $conn->connect_error);
                    }

                    // Alter table to add columns if they don't exist
                    $alterSql = "ALTER TABLE tweets
                        ADD COLUMN IF NOT EXISTS text_clean TEXT,
                        ADD COLUMN IF NOT EXISTS text_stem TEXT,
                        ADD COLUMN IF NOT EXISTS label VARCHAR(10)";
                    if ($conn->query($alterSql) === TRUE) {
                        echo "Columns added successfully<br>";
                    } else {
                        echo "Error adding columns: " . $conn->error . "<br>";
                    }

                    // Load stopwords from file
                    $stopwords = file('stopwords.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                    // Load positive words from file
                    $positiveWords = file('opinion_word/positif.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                    // Load negative words from file
                    $negativeWords = file('opinion_word/negatif.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                    // Fetching data from the "tweets" table based on selected hastag
                    $sql = "SELECT id, text_dirty, hastag FROM tweets WHERE hastag = '$selectedhastag'";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        echo "<div class='row'>
                                <div class='col-lg-12'>
                                    <h4>Hasil Preprocessing Tweets: " . $selectedhastag . "</h4>
                                    <table class='table table-bordered'>
                                        <thead>
                                            <tr>
                                                <th>Text Clean</th>
                                                <th>Text Stem</th>
                                                <th>Hashtag</th>
                                                <th>Label</th>
                                            </tr>
                                        </thead>
                                        <tbody>";

                        // Load Sastrawi library for stemming
                        require_once 'vendor/autoload.php';
                        $stemmerFactory = new \Sastrawi\Stemmer\StemmerFactory();
                        $stemmer = $stemmerFactory->createStemmer();

                        // Update data with cleaned and stemmed text
                        while ($row = $result->fetch_assoc()) {
                            $text = $row["text_dirty"];

                            // Cleaning
                            $text = preg_replace('/[#@]\w+/', '', $text);
                            $text = preg_replace('/RT/', '', $text);
                            $text = preg_replace('/[^a-zA-Z0-9]/', ' ', $text);

                            // Case folding
                            $text = strtolower($text);

                            // Tokenization
                            $tokens = explode(' ', $text);

                            // Stopword removal
                            $tokens = array_diff($tokens, $stopwords);

                            // Stemming
                            $stemmedTokens = array_map(function ($token) use ($stemmer) {
                                return $stemmer->stem($token);
                            }, $tokens);

                            $textClean = implode(' ', $tokens);
                            $textStem = implode(' ', $stemmedTokens);

                            // Classify sentiment based on positive and negative words
                            $positiveCount = 0;
                            $negativeCount = 0;

                            $words = explode(' ', $textStem);
                            foreach ($words as $word) {
                                if (in_array($word, $positiveWords)) {
                                    $positiveCount++;
                                } elseif (in_array($word, $negativeWords)) {
                                    $negativeCount++;
                                }
                            }

                            $sentiment = "";
                            if ($positiveCount > $negativeCount) {
                                $sentiment = "Positive";
                            } elseif ($positiveCount < $negativeCount) {
                                $sentiment = "Negative";
                            } else {
                                $sentiment = "Neutral";
                            }

                            // Update row with cleaned and stemmed text and sentiment
                            $updateSql = "UPDATE tweets SET text_clean = '$textClean', text_stem = '$textStem', label = '$sentiment' WHERE id = " . $row["id"];
                            if ($conn->query($updateSql) === TRUE) {
                                echo "<tr>
                                        <td>" . $textClean . "</td>
                                        <td>" . $textStem . "</td>
                                        <td>" . $row["hastag"] . "</td>
                                        <td>" . $sentiment . "</td>
                                    </tr>";
                            } else {
                                echo "<tr>
                                        <td colspan='4'>Error updating data: ID " . $row["id"] . ": " . $conn->error . "</td>
                                    </tr>";
                            }
                        }

                        echo "</tbody></table>
                                </div>
                            </div>";
                    } else {
                        echo "<div class='row'>
                                <div class='col-lg-12'>
                                    <h4>Tidak ada data untuk hastag: " . $selectedhastag . "</h4>
                                </div>
                            </div>";
                    }
                }
                $conn->close();
                ?>
            </div>
        </div>
    </div>
    <!-- /#wrapper -->

    <!-- jQuery Version 1.11.0 -->
    <script src="js/jquery-1.11.0.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="js/bootstrap.min.js"></script>
</body>

</html>
