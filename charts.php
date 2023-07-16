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
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
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
                    <li class="active">
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
                            Grafik Klasifikasi
                        </h3>
                    </div>
                </div>
                <!-- /.row -->

                <!-- Filter by hastag -->
                <div class="row">
                    <div class="col-lg-12">
                        <form method="post" action="">
                            <div class="form-group">
                                <label for="hastag">Pilih hastag:</label>
                                <select class="form-control" id="hastag" name="hastag">
                                    <option value="">-- Pilih hastag --</option>
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
                            <button type="submit" class="btn btn-primary" name="submit">Lihat Hasil</button>
                        </form>
                    </div>
                </div>
                <!-- /.row -->

                <?php
                // Check if form is submitted
                if (isset($_POST["submit"])) {
                    $selectedhastag = $_POST["hastag"];

                    // Fetching data from the "tweets" table based on selected hastag
                    $sql = "SELECT label FROM tweets WHERE hastag = '$selectedhastag'";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        // Count the number of labels
                        $positiveCount = 0;
                        $negativeCount = 0;
                        $neutralCount = 0;

                        while ($row = $result->fetch_assoc()) {
                            $label = $row["label"];
                            if ($label == "Positive") {
                                $positiveCount++;
                            } elseif ($label == "Negative") {
                                $negativeCount++;
                            } elseif ($label == "Neutral") {
                                $neutralCount++;
                            }
                        }

                        // Prepare data for the donut chart
                        $donutData = [
                            ['Label', 'Count'],
                            ['Positive', $positiveCount],
                            ['Negative', $negativeCount],
                            ['Neutral', $neutralCount]
                        ];

                        // Prepare data for the bar chart
                        $barData = [
                            ['Label', 'Count', ['role' => 'style']],
                            ['Positive', $positiveCount, '#5cb85c'],
                            ['Negative', $negativeCount, '#d9534f'],
                            ['Neutral', $neutralCount, '#f0ad4e']
                        ];

                        // Convert data to JSON format
                        $donutJson = json_encode($donutData);
                        $barJson = json_encode($barData);

                        // Display the donut chart
                        echo "<div class='row'>
                                <div class='col-lg-6'>
                                    <h4>Grafik Donut</h4>
                                    <div id='donut-chart'></div>
                                </div>";

                        // Display the bar chart
                        echo "<div class='col-lg-6'>
                                <h4>Grafik Bar</h4>
                                <div id='bar-chart'></div>
                            </div>
                        </div>";

                        // JavaScript code to draw the charts
                        echo "<script type='text/javascript'>
                                google.charts.load('current', {'packages':['corechart']});
                                google.charts.setOnLoadCallback(drawCharts);

                                function drawCharts() {
                                    // Draw the donut chart
                                    var donutData = google.visualization.arrayToDataTable($donutJson);
                                    var donutOptions = {
                                        title: 'Klasifikasi Sentimen',
                                        pieHole: 0.4
                                    };
                                    var donutChart = new google.visualization.PieChart(document.getElementById('donut-chart'));
                                    donutChart.draw(donutData, donutOptions);

                                    // Draw the bar chart
                                    var barData = google.visualization.arrayToDataTable($barJson);
                                    var barOptions = {
                                        title: 'Klasifikasi Sentimen',
                                        legend: { position: 'none' }
                                    };
                                    var barChart = new google.visualization.ColumnChart(document.getElementById('bar-chart'));
                                    barChart.draw(barData, barOptions);
                                }
                            </script>";
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
            <!-- /.container-fluid -->
        </div>
        <!-- /#page-wrapper -->
    </div>
    <!-- /#wrapper -->

    <!-- jQuery Version 1.11.0 -->
    <script src="js/jquery-1.11.0.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="js/bootstrap.min.js"></script>
</body>

</html>
