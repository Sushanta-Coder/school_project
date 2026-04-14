<?php include('../includes/config.php') ?>
<?php
  $current_year = (int)date('Y');
  $month_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  $monthly_registrations = array_fill(1, 12, 0);

  $total_students = 0;
  $total_students_query = mysqli_query($db_conn, "SELECT COUNT(*) AS total FROM accounts WHERE type = 'student'");
  if($total_students_query && mysqli_num_rows($total_students_query) > 0){
    $total_students = (int)mysqli_fetch_assoc($total_students_query)['total'];
  }

  $doa_query = mysqli_query($db_conn, "SELECT um.meta_value AS doa
                                      FROM usermeta um
                                      INNER JOIN accounts a ON a.id = um.user_id
                                      WHERE a.type = 'student' AND um.meta_key = 'doa'");
  if($doa_query){
    while($row = mysqli_fetch_assoc($doa_query)){
      $ts = strtotime($row['doa']);
      if($ts !== false && (int)date('Y', $ts) === $current_year){
        $month_no = (int)date('n', $ts);
        if($month_no >= 1 && $month_no <= 12){
          $monthly_registrations[$month_no]++;
        }
      }
    }
  }

  $monthly_reg_values = [];
  for($i = 1; $i <= 12; $i++){
    $monthly_reg_values[] = $monthly_registrations[$i];
  }
?>
<?php include('header.php') ?>
<?php include('sidebar.php') ?>
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-dark">Dashboard</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Admin</a></li>
              <li class="breadcrumb-item active">Dashboard</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <!-- Info boxes -->
        <div class="row">
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
              <span class="info-box-icon bg-info elevation-1"><i class="fas fa-graduation-cap"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Total Students</span>
                <span class="info-box-number">
                  <?=$total_students?>
                </span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <!-- /.col -->
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
              <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-users"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Total Teachers</span>
                <span class="info-box-number">50</span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <!-- /.col -->

          <!-- fix for small devices only -->
          <div class="clearfix hidden-md-up"></div>

          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
              <span class="info-box-icon bg-success elevation-1"><i class="fas fa-book-open"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Total Courses</span>
                <span class="info-box-number">100</span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <!-- /.col -->
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
              <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-question"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">New Inquiries</span>
                <span class="info-box-number">10</span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->

        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Student Registrations (<?=$current_year?>)</h3>
          </div>
          <div class="card-body">
            <div class="chart">
              <canvas id="studentRegistrationChart" style="min-height: 260px; height: 260px; max-height: 260px;"></canvas>
            </div>
          </div>
        </div>
      </div><!--/. container-fluid -->
    </section>
    <!-- /.content -->
<script src="../plugins/chart.js/Chart.min.js"></script>
<script>
  (function(){
    var chartEl = document.getElementById('studentRegistrationChart');
    if(!chartEl){ return; }

    new Chart(chartEl, {
      type: 'line',
      data: {
        labels: <?=json_encode($month_labels)?>,
        datasets: [{
          label: 'Students Registered',
          data: <?=json_encode($monthly_reg_values)?>,
          borderColor: '#007bff',
          backgroundColor: 'rgba(0, 123, 255, 0.15)',
          pointBackgroundColor: '#007bff',
          pointBorderColor: '#007bff',
          pointRadius: 3,
          fill: true,
          lineTension: 0.3
        }]
      },
      options: {
        maintainAspectRatio: false,
        responsive: true,
        legend: { display: true },
        scales: {
          yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }]
        }
      }
    });
  })();
</script>
<?php include('footer.php') ?>