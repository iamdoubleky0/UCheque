<?php
// Include necessary files
include('./includes/authentication.php');
include('./includes/header.php');
include('./includes/sidebar.php');
include('./includes/topbar.php');
?>

<div class="tabular--wrapper">
    <div class="add">
        <div class="filter">
            <form method="GET" action="">
                <input type="text" name="search_user" placeholder="Search user..." 
                       value="<?php echo isset($_GET['search_user']) ? htmlspecialchars($_GET['search_user']) : ''; ?>" 
                       style="width: 200px; margin-right: 10px;" 
                       onkeydown="if(event.key === 'Enter') this.form.submit();">

                <select name="academic_year" onchange="this.form.submit()" style="width: 200px; margin-right: 10px;">
                    <option value="" selected>Select Academic Year</option>
                    <?php
                    $years = [
                        '2024-2025', '2025-2026', '2026-2027', '2027-2028', '2028-2029', '2029-2030'
                    ];
                    foreach ($years as $year) {
                        $selected = (isset($_GET['academic_year']) && $_GET['academic_year'] == $year) ? 'selected' : '';
                        echo "<option value='$year' $selected>$year</option>";
                    }
                    ?>
                </select>

                <select name="semester" onchange="this.form.submit()" style="width: 200px; margin-right: 10px;">
                    <option value="" selected>Select Semester</option>
                    <option value="1st Semester" <?php if (isset($_GET['semester']) && $_GET['semester'] == '1st Semester') echo 'selected'; ?>>First Semester</option>
                    <option value="2nd Semester" <?php if (isset($_GET['semester']) && $_GET['semester'] == '2nd Semester') echo 'selected'; ?>>Second Semester</option>
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
                    <th>No.</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Academic Year</th>
                    <th>Semester</th>
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

                // Get filter inputs
                $search_user = isset($_GET['search_user']) ? $con->real_escape_string($_GET['search_user']) : '';
                $academic_year = isset($_GET['academic_year']) ? $con->real_escape_string($_GET['academic_year']) : '';
                $semester = isset($_GET['semester']) ? $con->real_escape_string($_GET['semester']) : '';

                // Build the WHERE clause dynamically
                $whereClauses = ["employee_role.role_id = 2"];

                if (!empty($search_user)) {
                    $whereClauses[] = "(employee.firstName LIKE '%$search_user%' OR employee.lastName LIKE '%$search_user%')";
                }

                if (!empty($academic_year)) {
                    $whereClauses[] = "itl_extracted_data.academicYear = '$academic_year'";
                }

                if (!empty($semester)) {
                    $whereClauses[] = "itl_extracted_data.semester = '$semester'";
                }

                $whereClause = implode(' AND ', $whereClauses);

                // Count total rows for pagination
                $totalQuery = "
                    SELECT COUNT(*) as total
                    FROM employee
                    INNER JOIN itl_extracted_data ON employee.userId = itl_extracted_data.userId
                    INNER JOIN employee_role ON employee.userId = employee_role.userId
                    WHERE $whereClause";

                $totalResult = $con->query($totalQuery);
                if (!$totalResult) {
                    die("Error executing query: " . $con->error);
                }
                $totalRow = $totalResult->fetch_assoc();
                $totalRows = isset($totalRow['total']) ? (int)$totalRow['total'] : 0;
                $totalPages = ceil($totalRows / $limit);

                // Main data query with filtering
                $sql = "
                    SELECT
                        employee.employeeId,
                        employee.firstName,
                        employee.middleName,
                        employee.lastName,
                        CASE 
                            WHEN department.departmentName = 'Information Technology' THEN 'IT'
                            WHEN department.departmentName = 'Technology Communication Management' THEN 'TCM'
                            WHEN department.departmentName = 'Computer Science' THEN 'CS'
                            WHEN department.departmentName = 'Data Science' THEN 'DS'
                            ELSE department.departmentName
                        END AS departmentAcronym,
                        itl_extracted_data.totalOverload,
                        itl_extracted_data.designated,
                        itl_extracted_data.userId,
                        itl_extracted_data.academicYear,
                        itl_extracted_data.semester
                    FROM
                        employee
                    LEFT JOIN
                        itl_extracted_data ON employee.userId = itl_extracted_data.userId
                    LEFT JOIN
                        employee_role ON employee.userId = employee_role.userId
                    LEFT JOIN
                        department ON employee.department = department.id
                    WHERE $whereClause
                    LIMIT $limit OFFSET $offset";

                $result = $con->query($sql);

                if (!$result) {
                    die("Error executing query: " . $con->error);
                }

                $counter = $offset;

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $counter++;
                        $fullName = trim($row['firstName'] . ' ' . $row['middleName'] . ' ' . $row['lastName']);
                        $totalOverload = (isset($row['totalOverload']) && $row['totalOverload'] <= 0) ? "No overload" : htmlspecialchars($row['totalOverload']);
                        echo "<tr>
                                <td>$counter</td>
                                <td>" . htmlspecialchars($fullName) . "</td>
                                <td>" . htmlspecialchars($row['departmentAcronym']) . "</td>
                                <td>" . htmlspecialchars($row['designated']) . "</td>
                                <td>" . htmlspecialchars($row['academicYear']) . "</td>
                                <td>" . htmlspecialchars($row['semester']) . "</td>
                                <td>$totalOverload</td>
                                <td>
                                    <a href='edit-act.php?employee_id=" . htmlspecialchars($row['userId']) . "' class='action'>Download</a>
                                    <a href='#1' class='action'>Delete</a>
                                </td>
                              </tr>";
                    }
                } else {
                    echo '<tr><td colspan="8">No users found.</td></tr>';
                }
                ?>
            </tbody>
        </table>

        <div class="pagination" id="pagination">
            <?php
            $paginationUrl = "?search_user=$search_user&academic_year=$academic_year&semester=$semester&page=";

            if ($totalPages > 1) {
                echo '<a href="' . $paginationUrl . '1" class="pagination-button">&laquo;</a>';
                $prevPage = max(1, $page - 1);
                echo '<a href="' . $paginationUrl . $prevPage . '" class="pagination-button">&lsaquo;</a>';

                for ($i = 1; $i <= $totalPages; $i++) {
                    $activeClass = ($i == $page) ? 'active' : '';
                    echo '<a href="' . $paginationUrl . $i . '" class="pagination-button ' . $activeClass . '">' . $i . '</a>';
                }

                $nextPage = min($totalPages, $page + 1);
                echo '<a href="' . $paginationUrl . $nextPage . '" class="pagination-button">&rsaquo;</a>';
                echo '<a href="' . $paginationUrl . $totalPages . '" class="pagination-button">&raquo;</a>';
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
            <label for="academic_year" class="form-label">Select Academic Year</label>
            <select class="form-control" id="academicYear" name="academicYear" required>
            <option value="" selected>Select Academic Year</option>
                <option value="2019-2020">2024-2025</option>
                <option value="2020-2021">2025-2026</option>
                <option value="2021-2022">2026-2027</option>
                <option value="2022-2023">2027-2028</option>
                <option value="2023-2024">2028-2029</option>
                <option value="2024-2025">2029-2030</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="semester" class="form-label">Select Semester</label>
            <select class="form-control" id="semester" name="semester" required>
            <option value="" selected>Select Semester</option>
                <option value="1st Semester">1st Semester</option>
                <option value="2nd Semester">2nd Semester</option>
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
