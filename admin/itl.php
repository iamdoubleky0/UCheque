<?php
include('./includes/authentication.php');
include('./includes/header.php');
include('./includes/sidebar.php');
include('./includes/topbar.php');
?>

<style>
    table th:nth-child(1),
    table td:nth-child(1),
    table th:nth-child(3),
    table td:nth-child(3),
    table th:nth-child(4), 
    table td:nth-child(4),
    table th:nth-child(5),
    table td:nth-child(5),
    table th:nth-child(6), 
    table td:nth-child(6) {
        text-align: center;
    } </style>

<div class="tabular--wrapper">
    <div class="add">
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class='bx bxs-file-import'></i>
            <span class="text">Import ITL</span>
        </button>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Total Overload</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $limit = 10;
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $page = max($page, 1);
                $offset = ($page - 1) * $limit;

                // Updated total count query for Faculty role (role_id = 2)
                $totalQuery = "
                    SELECT COUNT(*) as total
                    FROM employee
                    INNER JOIN itl_extracted_data ON employee.userId = itl_extracted_data.userId
                    INNER JOIN employee_role ON employee.userId = employee_role.userId
                    WHERE employee_role.role_id = 2";
                
                $totalResult = $con->query($totalQuery);
                if (!$totalResult) {
                    die("Error executing query: " . $con->error);
                }
                $totalRow = $totalResult->fetch_assoc();
                $totalRows = isset($totalRow['total']) ? (int)$totalRow['total'] : 0;
                $totalPages = ceil($totalRows / $limit);

                // Updated query to select users with the Faculty role and map department names to acronyms
                $sql = "
                  SELECT
                        employee.employeeId, 
                        employee.firstName, 
                        employee.middleName, 
                        employee.lastName, 
                        -- Mapping department names to acronyms
                        CASE 
                            WHEN department.departmentName = 'Information Technology' THEN 'IT'
                            WHEN department.departmentName = 'Technology Communication Management' THEN 'TCM'
                            WHEN department.departmentName = 'Computer Science' THEN 'CS'
                            WHEN department.departmentName = 'Data Science' THEN 'DS'
                            ELSE department.departmentName
                        END AS departmentAcronym, 
                        itl_extracted_data.totalOverload, 
                        itl_extracted_data.id, 
                        employee_role.role_id,
                        itl_extracted_data.designated, 
                        itl_extracted_data.userId
                    FROM
                        employee
                    LEFT JOIN
                        itl_extracted_data ON employee.userId = itl_extracted_data.userId
                    LEFT JOIN
                        employee_role ON employee.userId = employee_role.userId
                    LEFT JOIN
                        department ON employee.department = department.id
                    WHERE
                        employee_role.role_id = 2
                    LIMIT $limit OFFSET $offset"; // Added LIMIT and OFFSET for pagination

                $result = $con->query($sql);

                if (!$result) {
                    die("Error executing query: " . $con->error);
                }

                $counter = ($page - 1) * $limit; // Initialize counter based on the page number

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $counter++; // Increment the counter for each row
                        $fullName = trim($row['firstName'] . ' ' . $row['middleName'] . ' ' . $row['lastName']);
                        // Check if the total overload is negative or zero and display "No overload" if true
                        $totalOverload = (isset($row['totalOverload']) && $row['totalOverload'] <= 0) ? "No overload" : htmlspecialchars($row['totalOverload']);
                        echo '<tr>
                                <td>' . $counter . '</td> <!-- Display the counter instead of ID -->
                                <td>' . htmlspecialchars($fullName) . '</td>
                                <td>' . htmlspecialchars($row['departmentAcronym']) . '</td>
                                <td>' . htmlspecialchars($row['designated']) . '</td>
                                <td>' . $totalOverload . '</td>
                                <td>
                                    <a href="edit-act.php?employee_id=' . htmlspecialchars($row['userId']) . '" class="action">Download</a>
                                    <a href="#1" class="action">Delete</a>
                                </td>
                              </tr>';
                    }
                } else {
                    echo '<tr><td colspan="6">No users found.</td></tr>';
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
          
          <div class="mb-3">
            <label for="userId" class="form-label">Select User</label>
            <select class="form-control" id="userId" name="userId" required>
              <option value="" disabled selected>---Select User---</option>
              <?php
                // Updated query to select Faculty users only
                $query = "SELECT employee.userId, employee.employeeId, employee.firstName, employee.middleName, employee.lastName 
                          FROM employee 
                          INNER JOIN employee_role ON employee.userId = employee_role.userId
                          WHERE employee_role.role_id = 2"; // Only Faculty
                $result = $con->query($query);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $fullName = $row['firstName'] . ' ' . $row['middleName'] . ' ' . $row['lastName'];
                        echo "<option value='" . $row['userId'] . "'>" . htmlspecialchars($fullName) . "</option>";
                    }
                } else {
                    echo "<option value=''>No users found</option>";
                }
              ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="file" class="form-label">Upload Excel File</label>
            <input type="file" class="form-control" id="file" name="file" accept=".xlsx" required>
          </div>
          
          <div class="text-end">
            <button type="submit" class="btn btn-primary">Import Users</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
include('./includes/footer.php');
?>
