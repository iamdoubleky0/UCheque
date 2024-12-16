<?php
// Include necessary files
include('./includes/authentication.php');
include('./includes/header.php');
include('./includes/sidebar.php');
include('./includes/topbar.php');

// Check if there is a success or error message from the redirection
if (isset($_GET['message'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_GET['message']) . '</div>';
} elseif (isset($_GET['error'])) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($_GET['error']) . '</div>';
}
?>
<div class="tabular--wrapper">
    <div class="add">
        <div class="filter">
            <form method="GET" action="">
                <select name="academic_year" onchange="this.form.submit()" style="width: 200px; margin-right: 10px;">
                    <option value="" selected>Select Academic Year</option>
                    <option value="2019-2020" <?php if (isset($_GET['academic_year']) && $_GET['academic_year'] == '2019-2020') echo 'selected'; ?>>2019-2020</option>
                    <option value="2020-2021" <?php if (isset($_GET['academic_year']) && $_GET['academic_year'] == '2020-2021') echo 'selected'; ?>>2020-2021</option>
                    <option value="2021-2022" <?php if (isset($_GET['academic_year']) && $_GET['academic_year'] == '2021-2022') echo 'selected'; ?>>2021-2022</option>
                    <option value="2022-2023" <?php if (isset($_GET['academic_year']) && $_GET['academic_year'] == '2022-2023') echo 'selected'; ?>>2022-2023</option>
                    <option value="2023-2024" <?php if (isset($_GET['academic_year']) && $_GET['academic_year'] == '2023-2024') echo 'selected'; ?>>2023-2024</option>
                    <option value="2024-2025" <?php if (isset($_GET['academic_year']) && $_GET['academic_year'] == '2024-2025') echo 'selected'; ?>>2024-2025</option>
                </select>

                <select name="semester" onchange="this.form.submit()" style="width: 200px; margin-right: 10px;">
                    <option value="" selected>Select Semester</option>
                    <option value="1st Semester" <?php if (isset($_GET['semester']) && $_GET['semester'] == '1st Semester') echo 'selected'; ?>>First Semester</option>
                    <option value="2nd Semester" <?php if (isset($_GET['semester']) && $_GET['semester'] == '2nd Semester') echo 'selected'; ?>>Second Semester</option>
                     <option value="Summer" <?php if (isset($_GET['semester']) && $_GET['semester'] == 'Summer') echo 'selected'; ?>>Summer</option>
                </select>
            </form>
        </div>

        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#importModal">
        <i class='bx bxs-file-import'></i>
        <span class="text">Import ITL</span>
         </button>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Designated</th>
                    <th>Academic Year</th>
                    <th>Semester</th>
                    <th>Total Overload</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $userId = $_SESSION['auth_user']['userId'];
                $limit = 10;

                // Get filter values
                $academicYear = isset($_GET['academic_year']) ? $con->real_escape_string($_GET['academic_year']) : null;
                $semester = isset($_GET['semester']) ? $con->real_escape_string($_GET['semester']) : null;

                // Build filter conditions
                $filters = "WHERE employee_role.role_id = 2"; // Filter by role
                if ($academicYear) {
                    $filters .= " AND itl_extracted_data.academicYear = '$academicYear'";
                }
                if ($semester) {
                    $filters .= " AND itl_extracted_data.semester = '$semester'";
                }

                // Get total rows count
                $totalResult = $con->query("SELECT COUNT(*) AS total 
                                            FROM employee 
                                            INNER JOIN itl_extracted_data ON employee.userId = itl_extracted_data.userId
                                            INNER JOIN employee_role ON employee.userId = employee_role.userId
                                            $filters");

                if (!$totalResult) {
                    die("Error fetching total count: " . $con->error);
                }

                $totalRows = $totalResult->fetch_assoc()['total'];
                $totalPages = ceil($totalRows / $limit);

                // Pagination logic
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $page = max($page, 1);
                $offset = ($page - 1) * $limit;

                // Get filtered user data
                $sql = "
                    SELECT
                        employee.employeeId, 
                        employee.firstName, 
                        employee.middleName, 
                        employee.lastName, 
                        itl_extracted_data.totalOverload, 
                        itl_extracted_data.id, 
                        employee_role.role_id,
                        itl_extracted_data.designated, 
                        itl_extracted_data.userId,
                        itl_extracted_data.id AS itlId,
                        itl_extracted_data.semester,
                        itl_extracted_data.academicYear
                    FROM
                        employee
                    INNER JOIN
                        itl_extracted_data ON employee.userId = itl_extracted_data.userId
                    INNER JOIN
                        employee_role ON employee.userId = employee_role.userId
                    $filters
                    LIMIT $limit OFFSET $offset
                ";
                $result = $con->query($sql);

                if (!$result) {
                    die("Error executing query: " . $con->error);
                }

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $fullName = trim($row['firstName'] . ' ' . $row['middleName'] . ' ' . $row['lastName']);
                        echo '<tr>
                                <td>' . htmlspecialchars($row['employeeId']) . '</td>
                                <td>' . htmlspecialchars($fullName) . '</td>
                                <td>' . htmlspecialchars($row['designated']) . '</td>
                                <td>' . htmlspecialchars($row['academicYear']) . '</td>
                                <td>' . htmlspecialchars($row['semester']) . '</td>
                                <td>' . htmlspecialchars($row['totalOverload']) . '</td>
                                <td>
                                 <a href="./controller/download-itl.php?itl_id=' . htmlspecialchars($row['itlId']) . '" class="action">Download</a>
                                  <a href="./controller/delete-itl.php?itl_id=' . htmlspecialchars($row['itlId']) . '" class="action">Delete</a>
                                </td>
                              </tr>';
                    }
                } else {
                    echo '<tr><td colspan="7">No users found.</td></tr>';
                }
                ?>
            </tbody>
        </table>

        <div class="pagination" id="pagination">
            <?php
            if ($totalPages > 1) {
                echo '<a href="?page=1" class="pagination-button">&laquo;</a>';
                $prevPage = max(1, $page - 1);
                echo '<a href="?page=' . $prevPage . '" class="pagination-button">&lsaquo;</a>';

                for ($i = 1; $i <= $totalPages; $i++) {
                    $activeClass = ($i == $page) ? 'active' : '';
                    echo '<a href="?page=' . $i . '" class="pagination-button ' . $activeClass . '">' . $i . '</a>';
                }

                $nextPage = min($totalPages, $page + 1);
                echo '<a href="?page=' . $nextPage . '" class="pagination-button">&rsaquo;</a>';
                echo '<a href="?page=' . $totalPages . '" class="pagination-button">&raquo;</a>';
            }
            ?>
        </div>
    </div>
</div>


<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="importModalLabel">Import Individual Teacher's Load</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="./controller/import-itl.php" method="POST" enctype="multipart/form-data">
          <!-- User Selection -->
          <div class="mb-3">
            <label for="userId" class="form-label">Select User</label>
            <select class="form-control" id="userId" name="userId" required>
              <option value="" disabled selected>---Select User---</option>
              <?php
              $query = "SELECT employee.userId, employee.employeeId, employee.firstName, employee.middleName, employee.lastName 
                        FROM employee 
                        INNER JOIN employee_role ON employee.userId = employee_role.userId
                        WHERE employee_role.role_id = 2";
              $result = $con->query($query);

              if ($result->num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
                      $fullName = $row['firstName'] . ' ' . $row['lastName'];
                      echo "<option value='" . $row['userId'] . "'>" . htmlspecialchars($fullName) . "</option>";
                  }
              } else {
                  echo "<option value=''>No users found</option>";
              }
              ?>
            </select>
          </div>
          <!-- Academic Year Selection -->
          <div class="mb-3">
            <label for="academicYear" class="form-label">Academic Year</label>
            <select class="form-control" id="academicYear" name="academicYear" required>
              <option value="" disabled selected>---Select Academic Year---</option>
              <?php
              $currentYear = date("Y");
              for ($i = $currentYear - 5; $i <= $currentYear + 1; $i++) {
                  $nextYear = $i + 1;
                  echo "<option value='$i-$nextYear'>$i-$nextYear</option>";
              }
              ?>
            </select>
          </div>
          <!-- Semester Selection -->
          <div class="mb-3">
            <label for="semester" class="form-label">Semester</label>
            <select class="form-control" id="semester" name="semester" required>
              <option value="" disabled selected>---Select Semester---</option>
              <option value="1st Semester">1st Semester</option>
              <option value="2nd Semester">2nd Semester</option>
              <option value="Summer">Summer</option>
            </select>
          </div>
          <!-- File Upload -->
          <div class="mb-3">
            <label for="file" class="form-label">Upload Excel File</label>
            <input type="file" class="form-control" id="file" name="file" accept=".xlsx" required>
          </div>
          <!-- Submit Button -->
          <div class="text-end">
            <button type="submit" class="btn btn-primary">Import Users</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include('./includes/footer.php'); ?>
