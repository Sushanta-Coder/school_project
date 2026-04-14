<?php include('../includes/config.php') ?>
<?php include('../includes/marksheet-functions.php') ?>
<?php
  $parent_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
  $parent_data = $parent_id > 0 ? get_user_data($parent_id) : [];
  $children = [];
  if (!empty($parent_data['children'])) {
    $parsed_children = @unserialize($parent_data['children']);
    if (is_array($parsed_children)) {
      foreach ($parsed_children as $child_id) {
        $child_id = (int)$child_id;
        if ($child_id > 0) {
          $children[] = $child_id;
        }
      }
    }
  }

  $selected_child_id = isset($_GET['child_id']) ? (int)$_GET['child_id'] : 0;
  if (!in_array($selected_child_id, $children, true)) {
    $selected_child_id = !empty($children) ? (int)$children[0] : 0;
  }

  $selected_child = $selected_child_id > 0 ? get_user_data($selected_child_id) : [];
  $selected_child_meta = $selected_child_id > 0 ? get_user_metadata($selected_child_id) : [];
  $selected_class_id = !empty($selected_child_meta['class']) ? (int)$selected_child_meta['class'] : 0;
  $selected_section_id = !empty($selected_child_meta['section']) ? (int)$selected_child_meta['section'] : 0;

  $latest_result = null;
  if ($selected_child_id > 0) {
    $latest_result = get_student_latest_result($selected_child_id, $selected_class_id, $selected_section_id);
    if (!$latest_result) {
      $latest_result = get_student_latest_result($selected_child_id);
    }
  }

  $latest_result_id = $latest_result ? (int)$latest_result->id : 0;
  $latest_result_meta = $latest_result_id > 0 ? get_result_meta_map($latest_result_id) : [];
  $latest_result_marks = $latest_result_id > 0 ? get_result_marks($latest_result_id) : [];
  $subject_list = $selected_class_id > 0 ? get_class_subjects($selected_class_id) : [];

  $subject_labels = [];
  $subject_scores = [];
  $mapped_subject_ids = [];

  foreach ($subject_list as $subject_row) {
    $subject_id = (int)$subject_row->id;
    $marks_value = 0;
    if (isset($latest_result_marks[(string)$subject_id])) {
      $marks_value = (float)$latest_result_marks[(string)$subject_id];
    } elseif (isset($latest_result_marks[$subject_id])) {
      $marks_value = (float)$latest_result_marks[$subject_id];
    }

    $subject_labels[] = $subject_row->title;
    $subject_scores[] = $marks_value;
    $mapped_subject_ids[] = $subject_id;
  }

  foreach ($latest_result_marks as $subject_id => $marks_value) {
    $subject_id = (int)$subject_id;
    if ($subject_id <= 0 || in_array($subject_id, $mapped_subject_ids, true)) {
      continue;
    }

    $subject_post = get_post(['id' => $subject_id]);
    $subject_labels[] = $subject_post && !empty($subject_post->title) ? $subject_post->title : ('Subject ' . $subject_id);
    $subject_scores[] = (float)$marks_value;
  }

  $total_students = 0;
  $total_students_query = mysqli_query($db_conn, "SELECT COUNT(*) AS total FROM accounts WHERE type = 'student'");
  if($total_students_query && mysqli_num_rows($total_students_query) > 0){
    $total_students = (int)mysqli_fetch_assoc($total_students_query)['total'];
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
              <li class="breadcrumb-item"><a href="#">Parent</a></li>
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
            <h3 class="card-title">Child Subject Scores</h3>
          </div>
          <div class="card-body">
            <?php if($selected_child_id <= 0){ ?>
              <div class="alert alert-info mb-0">No child is linked to this parent account.</div>
            <?php } elseif($latest_result_id <= 0){ ?>
              <div class="alert alert-info mb-0">No marksheet has been published for this child yet.</div>
            <?php } elseif(empty($subject_labels)){ ?>
              <div class="alert alert-warning mb-0">No subject marks found in the latest marksheet.</div>
            <?php } else { ?>
            <div class="mb-3">
              <strong>Child:</strong> <?=!empty($selected_child['name']) ? htmlspecialchars($selected_child['name']) : 'N/A'?>
              <span class="ml-3"><strong>Exam:</strong> <?=!empty($latest_result_meta['exam_name']) ? htmlspecialchars($latest_result_meta['exam_name']) : 'N/A'?></span>
            </div>
            <div class="chart">
              <canvas id="childSubjectScoreChart" style="min-height: 260px; height: 260px; max-height: 260px;"></canvas>
            </div>
            <?php } ?>
          </div>
        </div>
        <hr>
      </div><!--/. container-fluid -->
    </section>
    <!-- /.content -->
<script src="../plugins/chart.js/Chart.min.js"></script>
<script>
  (function(){
    var chartEl = document.getElementById('childSubjectScoreChart');
    if(!chartEl){ return; }

    new Chart(chartEl, {
      type: 'bar',
      data: {
        labels: <?=json_encode($subject_labels)?>,
        datasets: [{
          label: 'Marks',
          data: <?=json_encode($subject_scores)?>,
          borderColor: '#28a745',
          backgroundColor: 'rgba(40, 167, 69, 0.6)',
          borderWidth: 1
        }]
      },
      options: {
        maintainAspectRatio: false,
        responsive: true,
        legend: { display: true },
        scales: {
          yAxes: [{ ticks: { beginAtZero: true, max: 100 } }]
        }
      }
    });
  })();
</script>
<?php include('footer.php') ?>