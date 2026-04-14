<?php include('../includes/config.php') ?>
<?php include('../includes/marksheet-functions.php') ?>
<?php include('header.php') ?>
<?php include('sidebar.php') ?>
<?php
$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$classes = get_posts(['type' => 'class', 'status' => 'publish']);
$months = [];

$selected_class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
if ($selected_class_id <= 0 && !empty($classes)) {
    $selected_class_id = (int)$classes[0]->id;
}

$selected_section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
$selected_exam_name = isset($_GET['exam_name']) ? trim($_GET['exam_name']) : 'Terminal Exam';
if ($selected_exam_name === '') {
    $selected_exam_name = 'Terminal Exam';
}

$sections = get_class_sections($selected_class_id);
if ($selected_section_id <= 0 && !empty($sections)) {
    $selected_section_id = (int)$sections[0]->id;
}
$subjects = get_class_subjects($selected_class_id);
$students = get_class_students($selected_class_id, $selected_section_id);

if (isset($_POST['save_result'])) {
    $post_class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $post_section_id = isset($_POST['section_id']) ? (int)$_POST['section_id'] : 0;
    $post_exam_name = isset($_POST['exam_name']) ? trim($_POST['exam_name']) : '';
    $marks_payload = isset($_POST['marks']) && is_array($_POST['marks']) ? $_POST['marks'] : [];

    if ($post_class_id <= 0 || $post_section_id <= 0 || $post_exam_name === '') {
        $_SESSION['error_msg'] = 'Please select class, section and exam name.';
        header('Location: results.php?class_id=' . $post_class_id . '&section_id=' . $post_section_id . '&exam_name=' . urlencode($post_exam_name));
        exit;
    }

    $post_sections = get_class_sections($post_class_id);
    $section_valid = false;
    foreach ($post_sections as $section_row) {
        if ((int)$section_row->id === $post_section_id) {
            $section_valid = true;
            break;
        }
    }
    if (!$section_valid) {
        $_SESSION['error_msg'] = 'Selected section does not belong to selected class.';
        header('Location: results.php?class_id=' . $post_class_id . '&section_id=' . $post_section_id . '&exam_name=' . urlencode($post_exam_name));
        exit;
    }

    $post_subjects = get_class_subjects($post_class_id);
    $subject_ids = [];
    foreach ($post_subjects as $subject_row) {
        $subject_ids[] = (int)$subject_row->id;
    }

    $saved_count = 0;
    foreach ($marks_payload as $student_id => $subject_marks) {
        $student_id = (int)$student_id;
        if ($student_id <= 0 || !is_array($subject_marks)) {
            continue;
        }

        $clean_marks = [];
        foreach ($subject_ids as $subject_id) {
            $mark_value = isset($subject_marks[$subject_id]) ? trim((string)$subject_marks[$subject_id]) : '';
            if ($mark_value === '') {
                $mark_value = 0;
            }
            $clean_marks[$subject_id] = is_numeric($mark_value) ? (float)$mark_value : 0;
        }

        save_result_record($student_id, $post_class_id, $post_section_id, $post_exam_name, $clean_marks, $current_user_id);
        $saved_count++;
    }

    $_SESSION['success_msg'] = 'Marksheet updated successfully for ' . $saved_count . ' student(s).';
    header('Location: results.php?class_id=' . $post_class_id . '&section_id=' . $post_section_id . '&exam_name=' . urlencode($post_exam_name));
    exit;
}
?>

<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0 text-dark">Manage Results</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Admin</a></li>
          <li class="breadcrumb-item active">Results</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <?php if(isset($_SESSION['success_msg'])) { ?>
      <div class="alert alert-success"><?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></div>
    <?php } ?>
    <?php if(isset($_SESSION['error_msg'])) { ?>
      <div class="alert alert-danger"><?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?></div>
    <?php } ?>

    <?php
    if (isset($_GET['action']) && $_GET['action'] === 'view-marksheet') {
        $result_id = isset($_GET['result_id']) ? (int)$_GET['result_id'] : 0;
        $result = $result_id > 0 ? get_post(['id' => $result_id]) : null;
        $result_meta = $result_id > 0 ? get_result_meta_map($result_id) : [];
        $result_marks = $result_id > 0 ? get_result_marks($result_id) : [];
        $student_id = isset($result_meta['student_id']) ? (int)$result_meta['student_id'] : 0;
        $student = $student_id > 0 ? get_user_data($student_id) : [];
        $class_post = !empty($result_meta['class_id']) ? get_post(['id' => $result_meta['class_id']]) : null;
        $section_post = !empty($result_meta['section_id']) ? get_post(['id' => $result_meta['section_id']]) : null;
        $subject_list = !empty($result_meta['class_id']) ? get_class_subjects((int)$result_meta['class_id']) : [];
    ?>
      <div class="card">
        <div class="card-body">
          <?php if (!$result) { ?>
            <div class="alert alert-warning">Marksheet not found.</div>
          <?php } else { ?>
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
                  <?php
                    $count = 1;
                    foreach ($subject_list as $subject_row) {
                        $marks_value = isset($result_marks[(string)$subject_row->id]) ? $result_marks[(string)$subject_row->id] : (isset($result_marks[$subject_row->id]) ? $result_marks[$subject_row->id] : 0);
                        echo '<tr>';
                        echo '<td>' . $count++ . '</td>';
                        echo '<td>' . htmlspecialchars($subject_row->title) . '</td>';
                        echo '<td>' . htmlspecialchars((string)$marks_value) . '</td>';
                        echo '</tr>';
                    }
                  ?>
                  <tr>
                    <th colspan="2" class="text-right">Total Obtained</th>
                    <th><?php echo !empty($result_meta['total_obtained']) ? $result_meta['total_obtained'] : 0; ?></th>
                  </tr>
                  <tr>
                    <th colspan="2" class="text-right">Total Marks</th>
                    <th><?php echo !empty($result_meta['total_marks']) ? $result_meta['total_marks'] : 0; ?></th>
                  </tr>
                  <tr>
                    <th colspan="2" class="text-right">Percentage</th>
                    <th><?php echo !empty($result_meta['percentage']) ? $result_meta['percentage'] . '%' : '0%'; ?></th>
                  </tr>
                </tbody>
              </table>
            </div>
          <?php } ?>
        </div>
      </div>
    <?php } else { ?>
      <div class="card">
        <div class="card-header py-2">
          <h3 class="card-title">Add / Update Marksheet</h3>
        </div>
        <div class="card-body">
          <form method="get" class="mb-4">
            <div class="row">
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
                <button class="btn btn-primary btn-block" type="submit">Load Sheet</button>
              </div>
            </div>
          </form>

          <?php if ($selected_class_id > 0 && $selected_section_id > 0 && !empty($subjects) && !empty($students)) { ?>
            <form method="post">
              <input type="hidden" name="class_id" value="<?php echo $selected_class_id; ?>">
              <input type="hidden" name="section_id" value="<?php echo $selected_section_id; ?>">
              <input type="hidden" name="exam_name" value="<?php echo htmlspecialchars($selected_exam_name, ENT_QUOTES); ?>">
              <div class="table-responsive">
                <table class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th style="min-width:180px;">Student</th>
                      <?php foreach ($subjects as $subject) { ?>
                        <th style="min-width:120px; text-align:center;"><?php echo htmlspecialchars($subject->title); ?></th>
                      <?php } ?>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($students as $student_row) {
                        $existing_result = find_result_record($student_row->id, $selected_class_id, $selected_section_id, $selected_exam_name);
                        $existing_marks = $existing_result ? get_result_marks($existing_result->id) : [];
                    ?>
                      <tr>
                        <td><?php echo htmlspecialchars($student_row->name); ?></td>
                        <?php foreach ($subjects as $subject) {
                            $value = isset($existing_marks[(string)$subject->id]) ? $existing_marks[(string)$subject->id] : (isset($existing_marks[$subject->id]) ? $existing_marks[$subject->id] : '');
                        ?>
                          <td>
                            <input type="number" min="0" step="0.01" max="100" name="marks[<?php echo $student_row->id; ?>][<?php echo $subject->id; ?>]" class="form-control" value="<?php echo htmlspecialchars((string)$value); ?>" placeholder="0-100" required>
                          </td>
                        <?php } ?>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
              <button type="submit" name="save_result" class="btn btn-success">Save Marksheet</button>
            </form>
          <?php } else { ?>
            <div class="alert alert-info mb-0">Select class, section and exam name to load students and subjects.</div>
          <?php } ?>
        </div>
      </div>

    <?php } ?>
  </div>
</section>

<?php include('footer.php') ?>
