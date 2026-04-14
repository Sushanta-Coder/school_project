<?php include('../includes/config.php') ?>
<?php include('../includes/marksheet-functions.php') ?>
<?php include('header.php') ?>
<?php include('sidebar.php') ?>
<?php
$student_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$student = $student_id > 0 ? get_user_data($student_id) : [];
$student_meta = $student_id > 0 ? get_user_metadata($student_id) : [];
$class_id = isset($student_meta['class']) ? (int)$student_meta['class'] : 0;
$section_id = isset($student_meta['section']) ? (int)$student_meta['section'] : 0;
$class_post = $class_id > 0 ? get_post(['id' => $class_id]) : null;
$section_post = $section_id > 0 ? get_post(['id' => $section_id]) : null;

$selected_result_id = isset($_GET['result_id']) ? (int)$_GET['result_id'] : 0;
$selected_result = null;
if ($selected_result_id > 0) {
    $candidate = get_post(['id' => $selected_result_id]);
    if ($candidate) {
        $candidate_meta = get_result_meta_map($selected_result_id);
        if (!empty($candidate_meta['student_id']) && (int)$candidate_meta['student_id'] === $student_id) {
            $selected_result = $candidate;
        }
    }
}

if (!$selected_result) {
    $selected_result = get_student_latest_result($student_id, $class_id, $section_id);
  if (!$selected_result && ($class_id > 0 || $section_id > 0)) {
    $selected_result = get_student_latest_result($student_id);
  }
    $selected_result_id = $selected_result ? (int)$selected_result->id : 0;
}

$selected_meta = $selected_result_id > 0 ? get_result_meta_map($selected_result_id) : [];
$selected_marks = $selected_result_id > 0 ? get_result_marks($selected_result_id) : [];
$subject_list = $class_id > 0 ? get_class_subjects($class_id) : [];
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">Marksheet</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Student</a></li>
          <li class="breadcrumb-item active">Marksheet</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="card mb-3">
      <div class="card-header">
        <h3 class="card-title">Student Detail</h3>
      </div>
      <div class="card-body">
        <strong>Name: </strong> <?php echo isset($student['name']) ? $student['name'] : ''; ?><br>
        <strong>Class: </strong> <?php echo !empty($class_post) ? $class_post->title : '-'; ?><br>
        <strong>Section: </strong> <?php echo !empty($section_post) ? $section_post->title : '-'; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Latest Marksheet</h3>
      </div>
      <div class="card-body">
        <?php if (!$selected_result) { ?>
          <div class="alert alert-info mb-0">No marksheet has been published yet.</div>
        <?php } else { ?>
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <div><strong>Exam:</strong> <?php echo !empty($selected_meta['exam_name']) ? $selected_meta['exam_name'] : '-'; ?></div>
              <div><strong>Published:</strong> <?php echo !empty($selected_result->publish_date) ? date('d M, Y', strtotime($selected_result->publish_date)) : '-'; ?></div>
            </div>
            <div>
              <a href="javascript:window.print()" class="btn btn-success btn-sm"><i class="fa fa-print"></i> Print</a>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>S.No</th>
                  <th>Subject</th>
                  <th>Marks</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $count = 1;
                  foreach ($subject_list as $subject_row) {
                      $marks_value = isset($selected_marks[(string)$subject_row->id]) ? $selected_marks[(string)$subject_row->id] : (isset($selected_marks[$subject_row->id]) ? $selected_marks[$subject_row->id] : 0);
                      echo '<tr>';
                      echo '<td>' . $count++ . '</td>';
                      echo '<td>' . htmlspecialchars($subject_row->title) . '</td>';
                      echo '<td>' . htmlspecialchars((string)$marks_value) . '</td>';
                      echo '</tr>';
                  }
                ?>
                <tr><th colspan="2" class="text-right">Total Obtained</th><th><?php echo !empty($selected_meta['total_obtained']) ? $selected_meta['total_obtained'] : 0; ?></th></tr>
                <tr><th colspan="2" class="text-right">Total Marks</th><th><?php echo !empty($selected_meta['total_marks']) ? $selected_meta['total_marks'] : 0; ?></th></tr>
                <tr><th colspan="2" class="text-right">Percentage</th><th><?php echo !empty($selected_meta['percentage']) ? $selected_meta['percentage'] . '%' : '0%'; ?></th></tr>
              </tbody>
            </table>
          </div>
        <?php } ?>
      </div>
    </div>

  </div>
</section>

<?php include('footer.php') ?>
