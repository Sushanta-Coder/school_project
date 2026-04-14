<div class="row">
    <div class="col-md-3">
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center">
                    <?php
                    $teacher_photo = !empty($user['photo']) ? '../dist/uploads/teacher-docs/' . $user['photo'] : '../dist/img/AdminLTELogo.png';
                    echo '<img class="profile-user-img img-fluid img-circle" src="' . $teacher_photo . '" alt="Teacher profile picture">';
                    ?>
                </div>
                <h3 class="profile-username text-center"><?php echo isset($user['name']) ? $user['name'] : 'N/A'; ?></h3>
                <p class="text-muted text-center"><?php echo isset($user['email']) ? $user['email'] : ''; ?></p>
                <hr>
                <p>
                    <strong><i class="fa-fw fas fa-id-card mr-1"></i> Employee ID</strong>
                    <span class="text-muted float-right"><?php echo isset($user['emp_id']) ? $user['emp_id'] : 'N/A'; ?></span>
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
                <hr>
                <p>
                    <strong><i class="fa-fw fas fa-user-tag mr-1"></i> Gender</strong>
                    <span class="text-muted float-right"><?php echo isset($user['gender']) ? $user['gender'] : 'N/A'; ?></span>
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
                <hr>
                <strong><i class="fas fa-briefcase mr-1"></i> Date of Joining</strong>
                <p class="text-muted"><?php echo isset($user['doj']) ? $user['doj'] : 'N/A'; ?></p>
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
                                <p><strong>Religion:</strong> <?php echo isset($user['religion']) ? $user['religion'] : 'N/A'; ?></p>
                                <p><strong>Citizenship No:</strong> <?php echo isset($user['citizenship_number']) ? $user['citizenship_number'] : 'N/A'; ?></p>
                            </div>
                        </div>

                        <hr>

                        <h5>Subjects</h5>
                        <p>
                            <?php
                            $teacher_subjects = [];
                            if (!empty($user['subjects'])) {
                                $parsed_subjects = @unserialize($user['subjects']);
                                if (is_array($parsed_subjects)) {
                                    $teacher_subjects = $parsed_subjects;
                                }
                            }

                            if (!empty($teacher_subjects)) {
                                foreach ($teacher_subjects as $subject_id) {
                                    $subject = get_post(['id' => $subject_id]);
                                    if ($subject && isset($subject->title)) {
                                        echo '<span class="badge badge-info mr-1 mb-1">' . $subject->title . '</span>';
                                    }
                                }
                            } else {
                                echo '<span class="text-muted">No subjects assigned.</span>';
                            }
                            ?>
                        </p>

                        <h5>Teaching Area</h5>
                        <p>
                            <?php
                            $teacher_areas = [];
                            if (!empty($user['teaching_area'])) {
                                $parsed_areas = @unserialize($user['teaching_area']);
                                if (is_array($parsed_areas)) {
                                    $teacher_areas = $parsed_areas;
                                }
                            }

                            $zone_labels = [
                                'nursery' => 'Nursery (LKG, UKG)',
                                'primary' => 'Primary (1-5)',
                                'junior' => 'Junior (6-8)',
                                'sencondary' => 'Secondary (9-10)',
                                'higersencondary' => 'Higher Secondary (11-12)',
                            ];

                            if (!empty($teacher_areas)) {
                                foreach ($teacher_areas as $area) {
                                    $label = isset($zone_labels[$area]) ? $zone_labels[$area] : $area;
                                    echo '<span class="badge badge-secondary mr-1 mb-1">' . $label . '</span>';
                                }
                            } else {
                                echo '<span class="text-muted">No teaching area assigned.</span>';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>