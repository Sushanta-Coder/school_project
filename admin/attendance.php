<?php include('../includes/config.php') ?>
<?php include('header.php') ?>
<?php include('sidebar.php') ?>

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Manage Student Attendance</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Student</a></li>
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

            <?php
            $class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
            $std_id = isset($_GET['std_id']) ? intval($_GET['std_id']) : 0;
            $classes = get_posts(['type' => 'class', 'status' => 'publish']);
            $students = [];
            $selected_class = null;

            if ($class_id) {
                $selected_class = get_post(['id' => $class_id]);
                $student_query = mysqli_query($db_conn, "SELECT a.id, a.name FROM `accounts` a JOIN `usermeta` m ON a.id = m.user_id WHERE a.type = 'student' AND m.meta_key = 'class' AND m.meta_value = '$class_id'");
                while ($student = mysqli_fetch_object($student_query)) {
                    $students[] = $student;
                }
            }

            if ($std_id && !$class_id) {
                $tmp_meta = get_user_metadata($std_id);
                if (!empty($tmp_meta['class'])) {
                    $class_id = intval($tmp_meta['class']);
                    $selected_class = get_post(['id' => $class_id]);
                }
            }
            ?>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Select Student</h3>
                </div>
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Class</label>
                            <select name="class_id" class="form-control" onchange="this.form.submit()">
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $class) {
                                    $selected = ($class_id == $class->id) ? 'selected' : '';
                                    echo '<option value="' . $class->id . '" ' . $selected . '>' . $class->title . '</option>';
                                } ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Student</label>
                            <select name="std_id" class="form-control" <?php echo empty($students) ? 'disabled' : ''; ?> onchange="this.form.submit()">
                                <option value="">Select Student</option>
                                <?php foreach ($students as $student) {
                                    $selected = ($std_id == $student->id) ? 'selected' : '';
                                    echo '<option value="' . $student->id . '" ' . $selected . '>' . $student->name . '</option>';
                                } ?>
                            </select>
                        </div>
                        <div class="col-md-4 align-self-end">
                            <button type="submit" class="btn btn-primary">Load Attendance</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($std_id) {
                $usermeta = get_user_metadata($std_id);
                $student_data = get_users(['id' => $std_id]);
                $student = !empty($student_data[0]) ? $student_data[0] : null;
                $class_title = 'N/A';
                if (!empty($usermeta['class'])) {
                    $class_post = get_post(['id' => $usermeta['class']]);
                    if ($class_post && isset($class_post->title)) {
                        $class_title = $class_post->title;
                    }
                }
            ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Student Detail</h3>
                    </div>
                    <div class="card-body">
                        <strong>Name: </strong> <?php echo $student ? $student->name : 'Unknown'; ?> <br>
                        <strong>Class: </strong> <?php echo $class_title ?>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Attendance</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <td>Date</td>
                                    <td>Status</td>
                                    <td>Singin Time</td>
                                    <td>Singout Time</td>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                                $current_month = strtolower(date('F'));
                                $current_year = date('Y');
                                $sql = "SELECT * FROM `attendance` WHERE `attendance_month` = '$current_month' AND year(current_session) = $current_year AND std_id = $std_id";
                                $query = mysqli_query($db_conn, $sql);
                                if ($query && mysqli_num_rows($query) > 0) {
                                    $row = mysqli_fetch_object($query);
                                    $attendance_values = unserialize($row->attendance_value);
                                    if (is_array($attendance_values)) {
                                        foreach ($attendance_values as $date => $value) {
                                            ?>
                                            <tr>
                                                <td><?php echo $date; ?></td>
                                                <td><?php echo (!empty($value['signin_at'])) ? 'Present' : 'Absent'; ?></td>
                                                <td><?php echo (!empty($value['signin_at'])) ? date('d-m-Y h:i:s', $value['signin_at']) : ''; ?></td>
                                                <td><?php echo (!empty($value['signout_at'])) ? date('d-m-Y h:i:s', $value['signout_at']) : ''; ?></td>
                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        echo '<tr><td colspan="4">No attendance data available for this student.</td></tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="4">No attendance records found for this student.</td></tr>';
                                }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php } else { ?>
                <div class="alert alert-info">Please select a class and then a student to view attendance details.</div>
            <?php } ?>

        </div><!--/. container-fluid -->
    </section>
    <!-- /.content -->

<?php include('footer.php') ?>
