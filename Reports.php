<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "watchtower360";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, (int)$port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all incidents for the table
$sql = "SELECT id, title, type, location, date_time, urgency, status FROM incidents ORDER BY date_time DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #eef2f7;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            transition: background 0.5s ease-in-out;
        }

        .container {
            width: 90%;
            max-width: 1000px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
            animation: fadeInUp 1s ease-out;
        }

        .top-buttons {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .top-buttons a {
            display: inline-block; /* Ensures visibility */
            background-color: #007BFF; /* Make sure there's a background */
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 16px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            align-items: center;
            gap: 8px;
        }

        .top-buttons a:hover {
            background: linear-gradient(135deg, #0056b3, #00408a); /* Darker on hover */
            transform: scale(1.05);
            box-shadow: 2px 2px 15px rgba(0, 0, 255, 0.4);
        }

        h2 { color: #333; text-transform: uppercase; letter-spacing: 1px; }

        table { width: 100%; border-collapse: collapse; }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            transition: background 0.3s ease-in-out;
        }

        th {
            background-color: #007BFF;
            color: white;
            cursor: pointer;
            position: relative;
            text-transform: uppercase;
            transition: background 0.3s ease-in-out;
        }

        th:hover { background-color: #0056b3; }

        tbody tr {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInRow 0.6s forwards ease-in-out;
        }

        tbody tr:hover {
            background-color: #f1f1f1;
            transition: background 0.3s ease-in-out;
        }

        .view-btn {
            background: linear-gradient(135deg, #007BFF, #0056b3);
            color: white;
            padding: 8px 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 2px 2px 10px rgba(0, 0, 255, 0.2);
        }

        .view-btn:hover {
            transform: scale(1.05);
            box-shadow: 2px 2px 15px rgba(0, 0, 255, 0.4);
        }

        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 600px;
            text-align: left;
            animation: zoomIn 0.4s;
        }

        /* Container for the buttons to align them horizontally */
        .modal-buttons {
            display: flex; /* Use Flexbox */
            justify-content: space-between; /* Align buttons to the right */
            gap: 10px; /* Add spacing between buttons */
            margin-top: 20px; /* Add spacing above the buttons */
        }

        /* Style for the Mark as Resolved Button */
        .resolve-btn {
            background: linear-gradient(135deg, #28a745, #218838); /* Green gradient */
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .resolve-btn:hover {
            background: linear-gradient(135deg, #218838, #1a6e2e); /* Darker green gradient on hover */
            transform: translateY(-2px); /* Slight lift effect */
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }

        .resolve-btn:active {
            transform: translateY(0); /* Reset lift effect on click */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Style for the Close Button */
        .close-btn {
            background: linear-gradient(135deg, #ff4444, #cc0000); /* Red gradient */
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .close-btn:hover {
            background: linear-gradient(135deg, #cc0000, #990000); /* Darker red gradient on hover */
            transform: translateY(-2px); /* Slight lift effect */
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }

        .close-btn:active {
            transform: translateY(0); /* Reset lift effect on click */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .status {
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .status.pending { background: orange; color: white; }
        .status.reviewed { background: blue; color: white; }
        .status.resolved { background: green; color: white; }

        /* Loading Spinner */
        .spinner {
            display: none;
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #007BFF;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: auto;
        }

        @keyframes fadeInRow {
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="top-buttons">
            <a href="Security_dashboard.php" class="dashboard-btn"><i class="fas fa-home"></i> Go to Dashboard</a>
        </div>

        <h2>Incident Reports</h2>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Date & Time</th>
                    <th>Urgency</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr data-id="<?= $row['id']; ?>">
                        <td><?= htmlspecialchars($row['title']); ?></td>
                        <td><?= htmlspecialchars($row['type']); ?></td>
                        <td><?= htmlspecialchars($row['location']); ?></td>
                        <td><?= htmlspecialchars($row['date_time']); ?></td>
                        <td><?= htmlspecialchars($row['urgency']); ?></td>
                        <td>
                            <span class="status <?= isset($row['status']) ? htmlspecialchars($row['status']) : 'pending'; ?>">
                                <?= isset($row['status']) ? ucfirst(htmlspecialchars($row['status'])) : 'Pending'; ?>
                            </span>
                        </td>
                        <td>
                            <button class="view-btn" onclick="viewIncident(<?= $row['id']; ?>)" aria-label="View Incident">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal for Incident Details -->
    <div class="modal" id="incident-modal">
        <div class="modal-content">
            <h2 id="modal-title"></h2>
            <p><strong>Type:</strong> <span id="modal-type"></span></p>
            <p><strong>Location:</strong> <span id="modal-location"></span></p>
            <p><strong>Date:</strong> <span id="modal-date"></span></p>
            <p><strong>Urgency:</strong> <span id="modal-urgency"></span></p>
            <p><strong>Description:</strong> <span id="modal-description"></span></p>
            
            <!-- Image Display -->
            <img id="modal-image" src="" alt="Incident Image" style="display:none; width:100%; max-height:300px; object-fit:cover;">

            <!-- Buttons Container -->
            <div class="modal-buttons">
                <button id="mark-resolved-btn" class="resolve-btn" aria-label="Mark as Resolved">
                    <i class="fas fa-check"></i> Mark as Resolved
                </button>
                <button class="close-btn" onclick="closeModal()" aria-label="Close">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>

            <!-- Loading Spinner -->
            <div class="spinner" id="loading-spinner"></div>
        </div>
    </div>

    <script>
        let currentIncidentId = null; // Store the current incident ID globally

        function viewIncident(id) {
            currentIncidentId = id; // Set the current incident ID
            document.getElementById("incident-modal").style.display = "flex";
            document.getElementById("modal-title").textContent = "Loading...";

            fetch(`get_incident.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById("modal-title").textContent = data.title;
                    document.getElementById("modal-type").textContent = data.type;
                    document.getElementById("modal-location").textContent = data.location;
                    document.getElementById("modal-date").textContent = data.date_time;
                    document.getElementById("modal-urgency").textContent = data.urgency;
                    document.getElementById("modal-description").textContent = data.description;

                    const imgElement = document.getElementById("modal-image");
                    if (data.media) {
                        imgElement.src = data.media;
                        imgElement.style.display = "block";
                    } else {
                        imgElement.style.display = "none";
                    }

                    // Update the status to "reviewed"
                    updateStatus(id);
                })
                .catch(error => console.error("Error loading incident:", error));
        }

        function closeModal() {
            document.getElementById("incident-modal").style.display = "none";
            location.reload(); // Reload the page to reflect changes
        }

        function updateStatus(incidentId) {
            fetch('update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + incidentId
            })
            .then(response => response.text())
            .then(data => {
                if (data === 'success') {
                    console.log("Status updated to reviewed.");
                } else {
                    console.error("Failed to update status.");
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Add event listener for the "Mark as Resolved" button
        document.getElementById("mark-resolved-btn").addEventListener("click", () => {
            const resolveBtn = document.getElementById("mark-resolved-btn");
            resolveBtn.disabled = true; // Disable the button
            resolveBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Resolving...`; // Update button text

            if (currentIncidentId) {
                fetch('mark_resolved.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + currentIncidentId
                })
                .then(response => response.text())
                .then(data => {
                    if (data === 'success') {
                        console.log("Status updated to resolved.");
                        // Update the status in the table dynamically
                        const statusCell = document.querySelector(`tr[data-id="${currentIncidentId}"] .status`);
                        if (statusCell) {
                            statusCell.textContent = "Resolved";
                            statusCell.className = "status resolved";
                        }
                        closeModal(); // Close the modal after resolving
                    } else {
                        console.error("Failed to update status.");
                        resolveBtn.disabled = false; // Re-enable the button on error
                        resolveBtn.innerHTML = `<i class="fas fa-check"></i> Mark as Resolved`; // Reset button text
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    resolveBtn.disabled = false; // Re-enable the button on error
                    resolveBtn.innerHTML = `<i class="fas fa-check"></i> Mark as Resolved`; // Reset button text
                });
            }
        });
    </script>
</body>
</html>