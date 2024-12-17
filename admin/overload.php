<?php
include('./includes/authentication.php');
include('./includes/header.php');
include('./includes/sidebar.php');
include('./includes/topbar.php');
?>
              <div class="tabular--wrapper">
                <div class="add">
                  <div class="filter">
                    <select id="role">
                        <option value="" disabled selected>For the Month of</option>
                        <option value="option1">January</option>
                        <option value="option2">February</option>
                        <option value="option3">March</option>
                        <option value="option4">April</option>
                        <option value="option5">May</option>
                        <option value="option6">June</option>
                        <option value="option7">July</option>
                        <option value="option8">August</option>
                        <option value="option9">September</option>
                        <option value="option10">October</option>
                        <option value="option11">November</option>
                        <option value="option12">December </option>
                    </select>
                  </div>
                  <div class="filter">
                        <select id="role">
                            <option value="" disabled selected>Select Academic Year</option>
                            <option value="option1">2024-2025</option>
                            <option value="option2">2025-2026</option>
                            <option value="option3">2026-2027</option>
                        </select>
                  </div>
                  <div class="filter">
                        <select id="role">
                            <option value="" disabled selected>Select Academic Semester</option>
                            <option value="option1">1st Semester</option>
                            <option value="option2">2nd Semester</option>
                        </select>
                  </div>
                  
                  
                </div>
                          
             
               <div class="table-container">
                  <table>
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Faculty</th>
                        <th>Designation</th>
                        <th>January</th>
                        <th>Febuary</th>
                        <th>March</th>
                        <th>Action</th>
                      </tr>
                      <tbody>
                       
                    </tbody>
                    </thead>
                  </table>
                  <div class="pagination" id="pagination">
                
                </div>
               </div>

               <!-- Modal -->
              <div id="fillUpModal" class="modal" style="display: none;">
                  <div class="modal-content">
                      <span class="close-btn">&times;</span>
                      <h2>Upload ITL file</h2>
                      <form id="fillUpForm" action="process_form.php" method="POST" enctype="multipart/form-data">
                          <label for="fullName">Name</label>
                          <input type="text" id="fullName" name="fullName" readonly>

                          <label for="id">ID</label>
                          <input type="text" id="id" name="id" readonly>

                          <label for="academicYear">Academic Year:</label>
                          <select id="academicYear" name="academicYear" required>
                              <option value="">Select Academic Year</option>
                              <?php
                              $currentYear = date("Y");
                              for ($i = 0; $i < 5; $i++) { 
                                  $startYear = $currentYear - $i;
                                  $endYear = $startYear + 1;
                                  echo "<option value='{$startYear}-{$endYear}'>{$startYear}-{$endYear}</option>";
                              }
                              ?>
                          </select>

                          <label for="academicSemester">Academic Semester:</label>
                          <select id="academicSemester" name="academicSemester" required>
                              <option value="">Select Semester</option>
                              <option value="First Semester">First Semester</option>
                              <option value="Second Semester">Second Semester</option>
                              <option value="Summer Semester">Summer Semester</option>
                          </select>

                          <label for="month">For the Month of:</label>
                          <select id="month" name="month" required>
                              <option value="">Select Month</option>
                              <option value="January">January</option>
                              <option value="February">February</option>
                              <option value="March">March</option>
                              <option value="April">April</option>
                              <option value="May">May</option>
                              <option value="June">June</option>
                              <option value="July">July</option>
                              <option value="August">August</option>
                              <option value="September">September</option>
                              <option value="October">October</option>
                              <option value="November">November</option>
                              <option value="December">December</option>
                          </select>


                          <label for="uploadFile">Upload File:</label>
                          <input type="file" id="uploadFile" name="uploadFile" accept=".pdf,.docx,.txt" required>

                          <button type="submit">Submit</button>
                      </form>
                  </div>
              </div>

                </div>
              </div>
            </div>
                     
<?php
include('./includes/footer.php');
?>
    

 