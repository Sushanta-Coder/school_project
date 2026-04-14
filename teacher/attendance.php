<?php include('../includes/config.php') ?>
<?php include('header.php') ?>
<?php include('sidebar.php') ?>

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Teacher Attendance</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Teacher</a></li>
                    <li class="breadcrumb-item active">Attendance</li>
                </ol>
            </div><!-- /.col -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Take Attendance</h3>
            </div>
            <div class="card-body">
                <?php
                if (isset($_POST['mark_attendance'])) {
                    $class_id = intval($_POST['class_id']);
                    $attendance_date = $_POST['attendance_date'];
                    $attendance_data = isset($_POST['attendance']) ? $_POST['attendance'] : [];
                    $current_month = strtolower(date('F', strtotime($attendance_date)));
                    $current_year = date('Y', strtotime($attendance_date));
                    $day = date('d', strtotime($attendance_date));

                    foreach ($attendance_data as $student_id => $status) {
                        $student_id = intval($student_id);
                        $signin_at = ($status === 'present') ? time() : '';
                        $signout_at = ($status === 'present') ? time() : '';
                        $check_query = mysqli_query($db_conn, "SELECT * FROM `attendance` WHERE `attendance_month` = '$current_month' AND year(current_session) = $current_year AND std_id = $student_id");
                        if (mysqli_num_rows($check_query) > 0) {
                            $row = mysqli_fetch_object($check_query);
                            $existing_attendance = unserialize($row->attendance_value);
                            if (!is_array($existing_attendance)) {
                                $existing_attendance = [];
                            }
                            $existing_attendance[$day] = [
                                'signin_at' => $signin_at,
                                'signout_at' => $signout_at,
                                'date' => $day,
                            ];
                            $updated_attendance = serialize($existing_attendance);
                            mysqli_query($db_conn, "UPDATE `attendance` SET `attendance_value` = '$updated_attendance' WHERE `attendance_month` = '$current_month' AND year(current_session) = $current_year AND std_id = $student_id");
                        } else {
                            $new_attendance = [];
                            for ($i = 1; $i <= 31; $i++) {
                                $new_attendance[$i] = [
                                    'signin_at' => '',
                                    'signout_at' => '',
                                    'date' => $i,
                                ];
                            }
                            $new_attendance[$day] = [
                                'signin_at' => $signin_at,
                                'signout_at' => $signout_at,
                                'date' => $day,
                            ];
                            $new_attendance_serialized = serialize($new_attendance);
                            mysqli_query($db_conn, "INSERT INTO `attendance` (`attendance_month`, `attendance_value`, `std_id`) VALUES ('$current_month', '$new_attendance_serialized', $student_id)");
                        }
                    }
                    echo '<div class="alert alert-success">Attendance marked successfully.</div>';
                }
                ?>
                <form method="post" class="mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Select Class</label>
                                <select name="class_id" class="form-control" required>
                                    <option value="">Select Class</option>
                                    <?php
                                    $classes = get_posts(['type' => 'class', 'status' => 'publish']);
                                    foreach ($classes as $class) {
                                        echo '<option value="' . $class->id . '">' . $class->title . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Date</label>
                                <input type="date" name="attendance_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4 align-self-end">
                            <button type="submit" name="load_students" class="btn btn-primary">Load Students</button>
                        </div>
                    </div>
                </form>
                <?php
                if (isset($_POST['load_students']) && !empty($_POST['class_id'])) {
                    $class_id = intval($_POST['class_id']);
                    $attendance_date = $_POST['attendance_date'];
                    $student_query = mysqli_query($db_conn, "SELECT a.id, a.name FROM `accounts` a JOIN `usermeta` m ON a.id = m.user_id WHERE a.type = 'student' AND m.meta_key = 'class' AND m.meta_value = '$class_id'");
                    $students = [];
                    while ($student = mysqli_fetch_object($student_query)) {
                        $students[] = $student;
                    }

                    if (!empty($students)) {
                        echo '<form method="post">';
                        echo '<input type="hidden" name="class_id" value="' . $class_id . '">';
                        echo '<input type="hidden" name="attendance_date" value="' . htmlspecialchars($attendance_date, ENT_QUOTES) . '">';
                        echo '<table class="table table-bordered">';
                        echo '<thead><tr><th>Student Name</th><th>Status</th></tr></thead><tbody>';
                        foreach ($students as $student) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($student->name, ENT_QUOTES) . '</td>';
                            echo '<td>';
                            echo '<label class="mr-3"><input type="radio" name="attendance[' . $student->id . ']" value="present" checked> Present</label>';
                            echo '<label><input type="radio" name="attendance[' . $student->id . ']" value="absent"> Absent</label>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                        echo '<button type="submit" name="mark_attendance" class="btn btn-success">Mark Attendance</button>';
                        echo '</form>';
                    } else {
                        echo '<div class="alert alert-warning">No students found for the selected class.</div>';
                    }
                }
                ?>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<?php include('footer.php') ?>
