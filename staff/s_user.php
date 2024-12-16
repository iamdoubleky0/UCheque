<?php
include('./includes/authentication.php');
include('./includes/header.php');
include('./includes/sidebar.php');
include('./includes/topbar.php');
?>

<div class="tabular--wrapper">
    <div class="add">
        <div class="filter">
            <form method="GET" action="">
                <select name="role_filter" onchange="this.form.submit()">
                    <option value="" disabled selected>Select Role</option>
                    <option value="ALL" <?php if (isset($_GET['role_filter']) && $_GET['role_filter'] == 'ALL') echo 'selected'; ?>>All</option>
                    <option value="4" <?php if (isset($_GET['role_filter']) && $_GET['role_filter'] == '4') echo 'selected'; ?>>Staff</option>
                    <option value="2" <?php if (isset($_GET['role_filter']) && $_GET['role_filter'] == '2') echo 'selected'; ?>>Faculty</option>
                    <option value="3" <?php if (isset($_GET['role_filter']) && $_GET['role_filter'] == '3') echo 'selected'; ?>>HR</option>
                </select>
            </form>
        </div>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class='bx bxs-file-import'></i>
            <span class="text">Import User</span>
        </button>
        <a href="add-user.php" class="btn-add">
            <i class='bx bxs-user-plus'></i>
            <span class="text">Add User</span>
        </a>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $userId = $_SESSION['auth_user']['userId'];
                $limit = 10;
                $roleFilter = isset($_GET['role_filter']) ? $_GET['role_filter'] : null;

                if ($roleFilter && $roleFilter != 'ALL') {
                    $roleCondition = "AND employee.userId IN (
                        SELECT userId 
                        FROM employee_role 
                        WHERE role_id = $roleFilter
                    )";
                } else {
                    $roleCondition = "AND (employee.userId NOT IN (
                        SELECT userId 
                        FROM employee_role 
                        WHERE role_id = 1
                    ) OR employee.userId NOT IN (
                        SELECT userId 
                        FROM employee_role
                    ))";
                }

                $totalResult = $con->query("SELECT COUNT(DISTINCT employee.userId) AS total
                    FROM employee
                    LEFT JOIN employee_role ON employee.userId = employee_role.userId
                    WHERE 1 $roleCondition");

                if (!$totalResult) {
                    die("Error fetching total count: " . $con->error);
                }

                $totalRows = $totalResult->fetch_assoc()['total'];
                $totalPages = ceil($totalRows / $limit);

                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $page = max($page, 1);
                $offset = ($page - 1) * $limit;

                $sql = "
                    SELECT 
                        employee.userId, 
                        employee.employeeId, 
                        employee.firstName, 
                        employee.middleName, 
                        employee.lastName, 
                        employee.phoneNumber, 
                        employee.emailAddress, 
                        GROUP_CONCAT(employee_role.role_id) AS roles, 
                        employee.status
                    FROM 
                        employee
                    LEFT JOIN 
                        employee_role ON employee.userId = employee_role.userId
                    WHERE 
                        1 $roleCondition AND employee.userId != $userId
                    GROUP BY 
                        employee.userId 
                    LIMIT $limit OFFSET $offset
                ";
                $result = $con->query($sql);

                if (!$result) {
                    die("Error executing query: " . $con->error);
                }

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Get role names based on role_id
                        $roleNames = [];
                        $roles = explode(',', $row['roles']);
                        foreach ($roles as $role) {
                            switch ($role) {
                                case '2':
                                    $roleNames[] = 'Faculty';
                                    break;
                                case '3':
                                    $roleNames[] = 'HR';
                                    break;
                                case '4':
                                    $roleNames[] = 'Staff';
                                    break;
                                default:
                                    $roleNames[] = 'No Assigned Role';
                                    break;
                            }
                        }
                        $roleList = implode(', ', $roleNames);

                        // Display user information
                        $fullName = trim($row['firstName'] . ' ' . $row['middleName'] . ' ' . $row['lastName']);
                        echo '<tr>
                                <td>' . htmlspecialchars($row['userId']) . '</td>
                                <td>' . htmlspecialchars($fullName) . '</td>
                                <td>' . htmlspecialchars($row['emailAddress']) . '</td>
                                <td>' . htmlspecialchars($row['phoneNumber']) . '</td>
                                <td><span class="status">' . htmlspecialchars($roleList) . '</span></td>
                                <td><span class="status">' . htmlspecialchars($row['status']) . '</span></td>
                                <td>
                                   <a href="javascript:void(0);" 
                                      class="action" 
                                      data-bs-toggle="modal" 
                                      data-bs-target="#editUserModal"
                                      data-userid="' . htmlspecialchars($row['userId']) . '"
                                      data-firstname="' . htmlspecialchars($row['firstName']) . '"
                                      data-middlename="' . htmlspecialchars($row['middleName']) . '"
                                      data-lastname="' . htmlspecialchars($row['lastName']) . '"
                                      data-email="' . htmlspecialchars($row['emailAddress']) . '"
                                      data-phone="' . htmlspecialchars($row['phoneNumber']) . '"
                                      data-roles="' . htmlspecialchars($roleList) . '"
                                      data-status="' . htmlspecialchars($row['status']) . '"
                                   >
                                      Edit
                                   </a>
                                   <a href="javascript:void(0);" 
                                      class="action" 
                                      data-bs-toggle="modal" 
                                      data-bs-target="#archiveConfirmModal"
                                      data-userid="' . htmlspecialchars($row['userId']) . '"
                                   >
                                      Archive
                                   </a>
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

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editUserModalLabel">Edit User Data</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="update_user.php" method="POST">
          <input type="hidden" id="userId" name="userId">
          <div class="mb-3">
            <label for="firstName" class="form-label">First Name</label>
            <input type="text" class="form-control" id="firstName" name="firstName" required>
          </div>
          <div class="mb-3">
            <label for="middleName" class="form-label">Middle Name</label>
            <input type="text" class="form-control" id="middleName" name="middleName">
          </div>
          <div class="mb-3">
            <label for="lastName" class="form-label">Last Name</label>
            <input type="text" class="form-control" id="lastName" name="lastName" required>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
          </div>
          <div class="mb-3">
            <label for="phone" class="form-label">Phone Number</label>
            <input type="text" class="form-control" id="phone" name="phone">
          </div>
          <div class="mb-3">
            <label for="roles" class="form-label">Roles</label>
            <input type="text" class="form-control" id="roles" name="roles" readonly>
          </div>
          <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <input type="text" class="form-control" id="status" name="status" required>
          </div>
          <div class="text-end">
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Archive Confirmation Modal -->
<div class="modal fade" id="archiveConfirmModal" tabindex="-1" aria-labelledby="archiveConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="archiveConfirmModalLabel">Confirm Archive</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to archive this user?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="archiveBtn" class="btn btn-danger">Archive</button>
      </div>
    </div>
  </div>
</div>

<?php include('./includes/footer.php'); ?>
