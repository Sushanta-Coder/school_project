<div class="row">
    <div class="col-md-3">
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center">
                    <?php
                    $student_photo = !empty($user['photo']) ? '../dist/uploads/student-docs/' . $user['photo'] : '../dist/img/AdminLTELogo.png';
                    echo '<img class="profile-user-img img-fluid img-circle" src="' . $student_photo . '" alt="Student profile picture">';
                    ?>
                </div>
                <h3 class="profile-username text-center"><?php echo isset($user['name']) ? $user['name'] : 'N/A'; ?></h3>
                <p class="text-muted text-center"><?php echo isset($user['address']) ? $user['address'] : ''; ?></p>
                <hr>
                <p>
                    <strong><i class="fa-fw fas fa-id-card mr-1"></i> Enrollment No.</strong>
                    <span class="text-muted float-right"><?php echo isset($user['enrollment_no']) ? $user['enrollment_no'] : 'N/A'; ?></span>
                </p>
                <hr>
                <p>
                    <strong><i class="fa-fw fas fa-chalkboard mr-1"></i> Class</strong>
                    <span class="text-muted float-right">
                        <?php
                        $class = !empty($user['class']) ? get_post(['id' => $user['class']]) : null;
                        $section = !empty($user['section']) ? get_post(['id' => $user['section']]) : null;
                        echo ($class && isset($class->title)) ? $class->title : 'N/A';
                        echo ' (' . (($section && isset($section->title)) ? $section->title : 'N/A') . ')';
                        ?>
                    </span>
                </p>
                <hr>
                <p>
                    <strong><i class="fa-fw fas fa-calendar-alt mr-1"></i> DOB</strong>
                    <span class="text-muted float-right"><?php echo isset($user['dob']) ? $user['dob'] : 'N/A'; ?></span>
                </p>
                <hr>
                <p>
                    <strong><i class="fa-fw fas fa-phone-square mr-1"></i> Mobile</strong>
                    <span class="text-muted float-right"><?php echo isset($user['mobile']) ? $user['mobile'] : 'N/A'; ?></span>
                </p>
            </div>
        </div>

        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">About Me</h3>
            </div>
            <div class="card-body">
                <strong><i class="fas fa-map-marker-alt mr-1"></i> Location</strong>
                <p class="text-muted"><?php echo isset($user['address']) ? $user['address'] : ''; ?>, <?php echo isset($user['state']) ? $user['state'] : ''; ?>, <?php echo isset($user['country']) ? $user['country'] : ''; ?> (<?php echo isset($user['zip']) ? $user['zip'] : ''; ?>)</p>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="card">
            <div class="card-header p-2">
                <ul class="nav nav-pills">
                    <li class="nav-item"><a class="nav-link active" href="#timeline" data-toggle="tab">Profile Details</a></li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane active" id="timeline">
                        <div class="row">
                            <div class="col-lg-6">
                                <p><strong>Father's Name:</strong> <?php echo isset($user['father_name']) ? $user['father_name'] : 'N/A'; ?></p>
                                <p><strong>Father's Mobile:</strong> <?php echo isset($user['father_mobile']) ? $user['father_mobile'] : 'N/A'; ?></p>
                                <p><strong>Mother's Name:</strong> <?php echo isset($user['mother_name']) ? $user['mother_name'] : 'N/A'; ?></p>
                                <p><strong>Mother's Mobile:</strong> <?php echo isset($user['mother_mobile']) ? $user['mother_mobile'] : 'N/A'; ?></p>
                            </div>
                            <div class="col-lg-6">
                                <p><strong>Religion:</strong> <?php echo isset($user['religion']) ? $user['religion'] : 'N/A'; ?></p>
                                <p><strong>Category:</strong> <?php echo isset($user['category']) ? $user['category'] : 'N/A'; ?></p>
                                <p><strong>Date of Admission:</strong> <?php echo isset($user['doa']) ? $user['doa'] : 'N/A'; ?></p>
                                <p><strong>Status:</strong> <?php echo isset($user['status']) ? $user['status'] : 'N/A'; ?></p>
                            </div>
                        </div>

                        <hr>

                        <h5>Last Qualification</h5>
                        <div class="row">
                            <div class="col-lg-6">
                                <p><strong>School Name:</strong> <?php echo isset($user['school_name']) ? $user['school_name'] : 'N/A'; ?></p>
                                <p><strong>Previous Class:</strong> <?php echo isset($user['previous_class']) ? $user['previous_class'] : 'N/A'; ?></p>
                                <p><strong>Total Marks:</strong> <?php echo isset($user['total_marks']) ? $user['total_marks'] : 'N/A'; ?></p>
                            </div>
                            <div class="col-lg-6">
                                <p><strong>Obtain Marks:</strong> <?php echo isset($user['obtain_mark']) ? $user['obtain_mark'] : 'N/A'; ?></p>
                                <p><strong>Percentage:</strong> <?php echo isset($user['previous_percentage']) ? $user['previous_percentage'] : 'N/A'; ?></p>
                                <p><strong>Subject Stream:</strong> <?php echo isset($user['subject_streem']) ? $user['subject_streem'] : 'N/A'; ?></p>
                            </div>
                        </div>

                        <hr>

                        <h5>Documents</h5>
                        <p>
                            <?php
                            if (!empty($user['aadhar'])) {
                                echo '<span class="badge badge-info mr-1 mb-1">AADHAR Uploaded</span>';
                            }
                            if (!empty($user['previous_marksheet'])) {
                                echo '<span class="badge badge-info mr-1 mb-1">Marksheet Uploaded</span>';
                            }
                            if (!empty($user['previous_tc'])) {
                                echo '<span class="badge badge-info mr-1 mb-1">TC Uploaded</span>';
                            }
                            if (empty($user['aadhar']) && empty($user['previous_marksheet']) && empty($user['previous_tc'])) {
                                echo '<span class="text-muted">No documents uploaded.</span>';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>