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
            $usermeta = get_user_metadata($std_id);
            $class = get_post(['id' => $usermeta['class']]);
            ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Student Detail</h3>
                </div>
                <div class="card-body">
                    <strong>Name: </strong> <?php echo get_users(array('id' => $std_id))[0]->name ?> <br>
                    <strong>Class: </strong> <?php echo $class->title ?>

                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Attendance</h3>
                </div>
                <div class="card-body">
                    <?php
                    $current_month = strtolower(date('F'));
                    $today = date('j');

                    $today_sql = "SELECT * FROM `attendance` WHERE `attendance_month` = '$current_month' AND std_id = $std_id ORDER BY id DESC LIMIT 1";
                    $today_query = mysqli_query($db_conn, $today_sql);
                    $today_attendance = [];
                    if ($today_query && mysqli_num_rows($today_query) > 0) {
                        $today_row = mysqli_fetch_object($today_query);
                        $today_attendance = @unserialize($today_row->attendance_value);
                        if (!is_array($today_attendance)) {
                            $today_attendance = [];
                        }
                    }
                    ?>
                    <div class="card mb-0">
                        <div class="card-header">
                            <h4 class="card-title">Today's Attendance</h4>
                        </div>
                        <div class="card-body">
                            <?php if (isset($today_attendance[$today])) :
                                $today_value = $today_attendance[$today];
                                $today_status = !empty($today_value['signin_at']) ? 'Present' : 'Absent';
                            ?>
                                <p><strong>Date:</strong> <?php echo date('d-m-Y'); ?></p>
                                <p><strong>Status:</strong> <?php echo $today_status; ?></p>
                            <?php else : ?>
                                <p><strong>Date:</strong> <?php echo date('d-m-Y'); ?></p>
                                <p><strong>Status:</strong> Absent</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div><!--/. container-fluid -->
    </section>
    <!-- /.content -->

<?php include('footer.php') ?>
