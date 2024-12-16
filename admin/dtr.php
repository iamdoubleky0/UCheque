<?php
include('./includes/authentication.php');
include('./includes/header.php');
include('./includes/sidebar.php');
include('./includes/topbar.php');
?>

<div class="tabular--wrapper">


<div class="add">
    <button class="btn-add" data-bs-toggle="modal" data-bs-target="#importModal">
        <i class='bx bxs-file-import'></i>
        <span class="text">Import DTR</span>
    </button>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Week 1</th>
                <th>Week 2</th>
                <th>Week 3</th>
                <th>Week 4</th>
                <th>Total</th>
                <th>File Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $limit = 10;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $page = max($page, 1);
            $offset = ($page - 1) * $limit;

            // Fix: Query to get the total count of rows
            $totalQuery = "
                            SELECT COUNT(*) as total
                            FROM 
                                employee
                            INNER JOIN 
                                employee_role ON employee.userId = employee_role.userId
                            LEFT JOIN
                                dtr_data ON employee.userId = dtr_data.userId
                            WHERE 
                                employee_role.role_id = 2
                        ";
            $totalResult = $con->query($totalQuery);
            if (!$totalResult) {
                die("Error executing query: " . $con->error);
            }
            $totalRow = $totalResult->fetch_assoc();
            $totalRows = isset($totalRow['total']) ? (int)$totalRow['total'] : 0;
            $totalPages = ceil($totalRows / $limit);

            $sql = "
                SELECT
                        employee.userId, 
                        employee.employeeId, 
                        employee.firstName, 
                        employee.middleName, 
                        employee.lastName, 
                        employee_role.role_id, 
                        dtr_data.*
                    FROM
                        dtr_data
                        INNER JOIN
                        employee
                        ON 
                            dtr_data.userId = employee.userId
                        INNER JOIN
                        employee_role
                        ON 
                            employee.userId = employee_role.userId
                    WHERE
                        employee_role.role_id = 2
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
                            <td>' . htmlspecialchars($row['week1']) . '</td>
                                 <td>' . htmlspecialchars($row['week2']) . '</td>
                                      <td>' . htmlspecialchars($row['week3']) . '</td>
                                           <td>' . htmlspecialchars($row['week4']) . '</td>
                                             <td>' . htmlspecialchars($row['total']) . '</td>
                            <td>' . htmlspecialchars($row['fileName']) . '</td>
                            <td>
                                <a href="edit-act.php?employee_id=' . htmlspecialchars($row['userId']) . '" class="action">Download</a>
                                <a href="#1" class="action">Delete</a>
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
        <h5 class="modal-title" id="importModalLabel">Import Daily Time Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="./controller/import-dtr.php" method="POST" enctype="multipart/form-data" id="importForm">
          
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
            <button type="submit" class="btn btn-primary" id="submitBtn">Import DTR</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
include('./includes/footer.php');
?>
