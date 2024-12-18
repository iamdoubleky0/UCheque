<?php
include('./includes/authentication.php');
include('./includes/header.php');
include('./includes/sidebar.php');
include('./includes/topbar.php');
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<div class="tabular--wrapper">

    <h3 class="main--title">Request DTR</h3>
    <div class="add">
       
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Request Date</th>
                    <th>Employee ID</th> 
                    <th>Name</th> 
                    <th>Start Month</th>
                    <th>End Month</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT r.requestId, r.requestDate, e.employeeId, e.firstName, e.middleName, e.lastName, r.startMonth, r.endMonth, r.status
                        FROM request r
                        JOIN employee e ON r.userId = e.userId 
                        ORDER BY r.requestDate DESC";
                
                $result = $con->query($query); 

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $fullName = htmlspecialchars($row['firstName']) . ' ' . htmlspecialchars($row['middleName']) . ' ' . htmlspecialchars($row['lastName']);
                        
                        $approveLink = $row['status'] != 'Approved' ? "<a href='#' class='btn btn-link' onclick='approveRequest(" . $row['requestId'] . ")'>Approve</a>" : "";

                        echo "<tr id='request-" . $row['requestId'] . "'>
                                <td>" . date("F j, Y", strtotime($row['requestDate'])) . "</td>
                                <td>" . htmlspecialchars($row['employeeId']) . "</td> 
                                <td>" . $fullName . "</td> <!-- Display Full Name -->
                                <td>" . htmlspecialchars($row['startMonth']) . "</td>
                                <td>" . htmlspecialchars($row['endMonth']) . "</td>
                                <td>" . htmlspecialchars($row['status']) . "</td>
                                <td>" . $approveLink . "</td> 
                            </tr>";
                    }
                } else {
                    echo "<tr>
                            <td colspan='7' class='text-center'>No requests found</td>
                        </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include('./includes/footer.php');
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
<script>
function approveRequest(requestId) {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "./controller/approve_request.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            document.getElementById('request-' + requestId).querySelector('td:nth-child(6)').textContent = 'Approved'; 
            document.getElementById('request-' + requestId).querySelector('td:nth-child(7)').innerHTML = ''; 
        }
    };
    xhr.send("requestId=" + requestId);
}
</script>