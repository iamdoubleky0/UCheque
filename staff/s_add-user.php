<?php
include('./includes/authentication.php');
include('./includes/header.php');
include('./includes/sidebar.php');
include('./includes/topbar.php');
?>

<div class="tabulars--wrapper">
    <div class="container">
        <form method="POST" action="./controller/add-user.php" enctype="multipart/form-data">
            <div class="card-body">
                <h4 class="card-title">Personal Information</h4>

                <div class="form-row mt-4">
                    <div class="form-group col-md-6">
                        <label for="employeeId">Employee ID</label>
                        <input type="text" class="form-control" id="employeeId" name="employeeId" placeholder="Enter Employee ID" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="firstName">First Name</label>
                        <input type="text" class="form-control" id="firstName" name="firstName" placeholder="Enter First Name" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="middlename">Middle Name <span style="font-size: 0.85em;">(Optional)</span></label>
                        <input type="text" class="form-control" id="middlename" name="middleName" placeholder="Enter Middle Name">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="lastname">Last Name</label>
                        <input type="text" class="form-control" id="lastname" name="lastName" placeholder="Enter Last Name" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="emailAddress" placeholder="Enter Email" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="contact">Phone Number</label>
                        <input type="text" class="form-control" id="contact" name="phoneNumber" placeholder="Enter Contact Details">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="role">Account Role</label>
                        <select class="form-select" name="role[]" multiple aria-label="multiple select example">
                            <option selected disabled>Select Role</option>
                            <option value="4">Staff</option>
                            <option value="2">Faculty</option>
                            <option value="3">HR</option>
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="profilePicture">Profile Picture</label>
                        <input type="file" class="form-control" id="profilePicture" name="profilePicture" accept="image/*" onchange="previewImage(event)">
                        <div id="imagePreview" class="mt-3"></div>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="col-md-12 text-end">
                    <button type="submit" name="addUser" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='user.php';">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
include('./includes/footer.php');
?>
