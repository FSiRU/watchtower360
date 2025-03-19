<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "watchtower360";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, (int)$port);

// Check database connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch incident locations from the database
$sql = "SELECT id, title, description, date_time, latitude, longitude FROM incidents WHERE latitude IS NOT NULL AND longitude IS NOT NULL";
$result = $conn->query($sql);

// Check if the query executed successfully
if (!$result) {
    die("SQL Error: " . $conn->error);
}

// Fetch data if query is successful
$incidents = [];
while ($row = $result->fetch_assoc()) {
    $incidents[] = $row;
}

// Determine the dashboard link based on user role
$dashboard_url = "login.php"; // Default in case something goes wrong

if (isset($_SESSION['user_role'])) {
    switch ($_SESSION['user_role']) {
        case 'admin':
            $dashboard_url = "admin_dashboard.php";
            break;
        case 'resident':
            $dashboard_url = "resident_dashboard.php";
            break;
        case 'security':
            $dashboard_url = "security_dashboard.php";
            break;
        default:
            // Debugging: Role is set but not recognized
            // echo "Unrecognized role: " . htmlspecialchars($_SESSION['user_role']);
            break;
    }
} else {
    // Debugging: Role is not set in the session
    // echo "Role is not set in the session.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotspot Areas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css" />

    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 900px;
            margin: auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 2px 2px 15px rgba(0, 0, 0, 0.1);
        }
        /* Back & Refresh Buttons */
        .top-buttons {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .btn-refresh {
            background: #28a745;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-refresh:hover {
            background: #218838;
        }
        #map {
            height: 500px;
            border-radius: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body class="container mt-5">

    <div class="top-buttons">
        <!-- Go Back to Dashboard Button -->
        <a href="<?php echo htmlspecialchars($dashboard_url); ?>" class="btn btn-primary">⬅ Go Back to Dashboard</a>
        <button class="btn-refresh" onclick="reloadMap()">🔄 Refresh Map</button>
    </div>

    <h2 class="text-center">Hotspot Areas</h2>
    <p class="text-center">This map highlights areas where security incidents have been reported.</p>

    <!-- Map Container -->
    <div id="map"></div>

    <!-- Leaflet.js -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>

    <script>
        // Initialize the map
        var map = L.map('map').setView([-1.2921, 36.8219], 12); // Default location (Nairobi)

        // Tile layers
        var lightTheme = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        });

        var darkTheme = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        });

        // Default to light theme
        lightTheme.addTo(map);

        // Custom Red Location Marker
        var redIcon = L.icon({
            iconUrl: 'https://cdn-icons-png.flaticon.com/512/2776/2776067.png',
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -30]
        });

        // Incident Data from PHP
        var incidents = <?php echo json_encode($incidents); ?>;

        // Marker Cluster Group
        var markers = L.markerClusterGroup();

        // Fit map to markers
        var bounds = L.latLngBounds();

        // Add Markers for Incidents
        incidents.forEach(incident => {
            var lat = parseFloat(incident.latitude);
            var lng = parseFloat(incident.longitude);

            if (!isNaN(lat) && !isNaN(lng)) {
                var marker = L.marker([lat, lng], { icon: redIcon })
                    .bindPopup(`<b>${incident.title}</b><br>${incident.description}<br><small>${incident.date_time}</small>`);

                markers.addLayer(marker);
                bounds.extend(marker.getLatLng());
            }
        });

        map.addLayer(markers);

        // Adjust map to fit all markers
        if (incidents.length > 0) {
            map.fitBounds(bounds, { padding: [50, 50] });
        }

        // Reload Map Function
        function reloadMap() {
            location.reload();
        }
    </script>

</body>
</html>