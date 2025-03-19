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

$sql = "SELECT * FROM incidents ORDER BY date_time DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #eef2f7;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .container {
            width: 90%;
            max-width: 1000px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .top-buttons {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .top-buttons a {
            text-decoration: none;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .dashboard-btn { background-color: #007BFF; }
        .dashboard-btn:hover { background-color: #0056b3; }
        .report-btn { background-color: #28a745; }
        .report-btn:hover { background-color: #1e7e34; }

        h2 { color: #333; }

        .search-box {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        table { width: 100%; border-collapse: collapse; }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #007BFF;
            color: white;
            cursor: pointer;
            user-select: none;
            position: relative;
        }

        th span.sort-icon {
            margin-left: 5px;
            font-size: 14px;
        }

        th:hover { background-color: #0056b3; }

        tbody tr:nth-child(even) { background-color: #f9f9f9; }

        tbody tr:hover { background-color: #f1f1f1; transition: background 0.3s; }

        .delete-btn {
            background: red;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .delete-btn:hover { background: darkred; }

        @media (max-width: 768px) {
            .container { width: 100%; }
            table, th, td { font-size: 14px; }
            .top-buttons { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="top-buttons">
            <a href="Resident_dashboard.php" class="dashboard-btn"><i class="fas fa-home"></i> Go to Dashboard</a>
            <a href="report_incident.php" class="report-btn"><i class="fas fa-plus-circle"></i> Report an Incident</a>
        </div>
        
        <h2>Incident Reports</h2>
        <input type="text" id="search" class="search-box" placeholder="Search incidents...">
        
        <table>
            <thead>
                <tr>
                    <th onclick="sortTable(0)">Title <span class="sort-icon">⬍</span></th>
                    <th onclick="sortTable(1)">Type <span class="sort-icon">⬍</span></th>
                    <th onclick="sortTable(2)">Location <span class="sort-icon">⬍</span></th>
                    <th onclick="sortTable(3)">Date <span class="sort-icon">⬍</span></th>
                    <th onclick="sortTable(4)">Urgency <span class="sort-icon">⬍</span></th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="incident-list">
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr id='row-" . $row['id'] . "'>";
                        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['type']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['location']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['date_time']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['urgency']) . "</td>";
                        echo '<td><button class="delete-btn" onclick="deleteIncident(' . $row['id'] . ')"><i class="fas fa-trash-alt"></i> Delete</button></td>';
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align: center; font-weight: bold; color: #666;'>No incidents found</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>

    <script>
        document.getElementById("search").addEventListener("input", function () {
            let filter = this.value.toLowerCase();
            document.querySelectorAll("#incident-list tr").forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(filter) ? "" : "none";
            });
        });

        function sortTable(columnIndex) {
            let table = document.querySelector("table");
            let tbody = table.querySelector("tbody");
            let rows = Array.from(tbody.rows);
            let asc = table.getAttribute(`data-order-${columnIndex}`) !== "asc";

            rows.sort((rowA, rowB) => {
                let cellA = rowA.cells[columnIndex].textContent.trim().toLowerCase();
                let cellB = rowB.cells[columnIndex].textContent.trim().toLowerCase();
                return asc ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
            });

            rows.forEach(row => tbody.appendChild(row));
            table.setAttribute(`data-order-${columnIndex}`, asc ? "asc" : "desc");

            let headers = document.querySelectorAll("th span.sort-icon");
            headers.forEach(icon => icon.textContent = "⬍");
            headers[columnIndex].textContent = asc ? "⬆" : "⬇";
        }

        function deleteIncident(id) {
            if (!confirm("Are you sure you want to delete this incident?")) return;

            fetch("delete_incident.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id=${id}`
            })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === "success") {
                    document.getElementById("row-" + id).remove();
                } else {
                    alert("Error deleting incident.");
                }
            });
        }
    </script>
</body>
</html>
