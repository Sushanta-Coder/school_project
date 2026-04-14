<?php include('../includes/config.php') ?>
<?php include('../includes/marksheet-functions.php') ?>
<?php include('header.php') ?>
<?php include('sidebar.php') ?>
<?php
$classes = get_posts(['type' => 'class', 'status' => 'publish']);
$selected_class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
if ($selected_class_id <= 0 && !empty($classes)) {
    $selected_class_id = (int)$classes[0]->id;
}
$selected_section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
$selected_exam_name = isset($_GET['exam_name']) ? trim($_GET['exam_name']) : '';
$sections = get_class_sections($selected_class_id);
if ($selected_section_id <= 0 && !empty($sections)) {
    $selected_section_id = (int)$sections[0]->id;
}

$result_id = isset($_GET['result_id']) ? (int)$_GET['result_id'] : 0;
$result = $result_id > 0 ? get_post(['id' => $result_id]) : null;
$result_meta = $result_id > 0 ? get_result_meta_map($result_id) : [];
$result_marks = $result_id > 0 ? get_result_marks($result_id) : [];
?>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">View Marksheet</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Admin</a></li>
          <li class="breadcrumb-item active">View Marksheet</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <?php if (isset($_GET['action']) && $_GET['action'] === 'print' && $result) {
        $student_id = !empty($result_meta['student_id']) ? (int)$result_meta['student_id'] : 0;
        $student = $student_id > 0 ? get_user_data($student_id) : [];
        $class_post = !empty($result_meta['class_id']) ? get_post(['id' => $result_meta['class_id']]) : null;
        $section_post = !empty($result_meta['section_id']) ? get_post(['id' => $result_meta['section_id']]) : null;
        $subject_list = !empty($result_meta['class_id']) ? get_class_subjects((int)$result_meta['class_id']) : [];
    ?>
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <h4 class="mb-1">Marksheet</h4>
              <div><strong>Student:</strong> <?php echo !empty($student['name']) ? $student['name'] : '-'; ?></div>
              <div><strong>Class:</strong> <?php echo !empty($class_post) ? $class_post->title : '-'; ?></div>
              <div><strong>Section:</strong> <?php echo !empty($section_post) ? $section_post->title : '-'; ?></div>
              <div><strong>Exam:</strong> <?php echo !empty($result_meta['exam_name']) ? $result_meta['exam_name'] : '-'; ?></div>
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
                <?php $count = 1; foreach ($subject_list as $subject_row) {
                    $marks_value = isset($result_marks[(string)$subject_row->id]) ? $result_marks[(string)$subject_row->id] : (isset($result_marks[$subject_row->id]) ? $result_marks[$subject_row->id] : 0);
                ?>
                  <tr>
                    <td><?php echo $count++; ?></td>
                    <td><?php echo htmlspecialchars($subject_row->title); ?></td>
                    <td><?php echo htmlspecialchars((string)$marks_value); ?></td>
                  </tr>
                <?php } ?>
                <tr><th colspan="2" class="text-right">Total Obtained</th><th><?php echo !empty($result_meta['total_obtained']) ? $result_meta['total_obtained'] : 0; ?></th></tr>
                <tr><th colspan="2" class="text-right">Total Marks</th><th><?php echo !empty($result_meta['total_marks']) ? $result_meta['total_marks'] : 0; ?></th></tr>
                <tr><th colspan="2" class="text-right">Percentage</th><th><?php echo !empty($result_meta['percentage']) ? $result_meta['percentage'] . '%' : '0%'; ?></th></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php } else { ?>
      <div class="card mb-3">
        <div class="card-header py-2">
          <h3 class="card-title">Filter Marksheet</h3>
        </div>
        <div class="card-body">
          <form method="get" class="row">
            <div class="col-md-3">
              <label>Class</label>
              <select name="class_id" class="form-control" onchange="this.form.submit()">
                <option value="">Select Class</option>
                <?php foreach ($classes as $class) { ?>
                  <option value="<?php echo $class->id; ?>" <?php echo ($selected_class_id === (int)$class->id) ? 'selected' : ''; ?>><?php echo $class->title; ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="col-md-3">
              <label>Section</label>
              <select name="section_id" class="form-control">
                <option value="">Select Section</option>
                <?php foreach ($sections as $section) { ?>
                  <option value="<?php echo $section->id; ?>" <?php echo ($selected_section_id === (int)$section->id) ? 'selected' : ''; ?>><?php echo $section->title; ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="col-md-4">
              <label>Exam Name</label>
              <input type="text" name="exam_name" class="form-control" value="<?php echo htmlspecialchars($selected_exam_name); ?>" placeholder="Terminal Exam">
            </div>
            <div class="col-md-2 d-flex align-items-end">
              <button type="submit" class="btn btn-primary btn-block">Apply</button>
            </div>
          </form>
        </div>
      </div>

      <?php if ($selected_class_id > 0 && $selected_section_id > 0) {
          $results_sql = "SELECT p.* FROM posts p
                          INNER JOIN metadata mc ON (mc.item_id = p.id AND mc.meta_key = 'class_id' AND mc.meta_value = '$selected_class_id')
                          INNER JOIN metadata ms ON (ms.item_id = p.id AND ms.meta_key = 'section_id' AND ms.meta_value = '$selected_section_id')
                          WHERE p.type = 'result' AND p.status = 'publish'";
          if (!empty($selected_exam_name)) {
              $exam_name_sql = mysqli_real_escape_string($db_conn, strtolower($selected_exam_name));
              $results_sql .= " AND EXISTS (SELECT 1 FROM metadata me WHERE me.item_id = p.id AND me.meta_key = 'exam_name' AND LOWER(me.meta_value) = '$exam_name_sql')";
          }
          $results_sql .= " ORDER BY p.publish_date DESC, p.id DESC";
          $results_query = mysqli_query($db_conn, $results_sql);
      ?>
        <div class="card">
          <div class="card-header py-2">
            <h3 class="card-title">Saved Mark Sheets</h3>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th>S.No</th>
                    <th>Student</th>
                    <th>Exam</th>
                    <th>Total</th>
                    <th>Percentage</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    $index = 1;
                    if ($results_query && mysqli_num_rows($results_query) > 0) {
                        while ($result_row = mysqli_fetch_object($results_query)) {
                            $meta = get_result_meta_map($result_row->id);
                            $student_data = !empty($meta['student_id']) ? get_user_data((int)$meta['student_id']) : [];
                            echo '<tr>';
                            echo '<td>' . $index++ . '</td>';
                            echo '<td>' . (!empty($student_data['name']) ? htmlspecialchars($student_data['name']) : '-') . '</td>';
                            echo '<td>' . (!empty($meta['exam_name']) ? htmlspecialchars($meta['exam_name']) : '-') . '</td>';
                            echo '<td>' . (!empty($meta['total_obtained']) ? htmlspecialchars($meta['total_obtained']) : '0') . '</td>';
                            echo '<td>' . (!empty($meta['percentage']) ? htmlspecialchars($meta['percentage']) . '%' : '0%') . '</td>';
                            echo '<td><a class="btn btn-sm btn-primary" href="?action=print&result_id=' . (int)$result_row->id . '"><i class="fa fa-eye"></i> View</a></td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="6" class="text-center">No marksheets found for this class and section.</td></tr>';
                    }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      <?php } ?>
    <?php } ?>
  </div>
</section>

<?php include('footer.php') ?>