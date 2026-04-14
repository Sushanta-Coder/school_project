<?php include('../includes/config.php') ?>
<?php
function get_sections_for_class($class_id)
{
    $class_id = (int)$class_id;
    $sections = [];
    if ($class_id <= 0) {
        return $sections;
    }

    $class_sections = get_metadata($class_id, 'section');
    foreach ($class_sections as $meta) {
        $section = get_post(['id' => (int)$meta->meta_value]);
        if ($section && isset($section->id)) {
            $sections[] = $section;
        }
    }

    return $sections;
}

function get_subjects_for_class($class_id)
{
    global $db_conn;
    $class_id = (int)$class_id;
    $subjects = [];
    if ($class_id <= 0) {
        return $subjects;
    }

    $sql = "SELECT p.*
            FROM posts p
            INNER JOIN metadata m ON (m.item_id = p.id)
            WHERE p.type = 'subject'
              AND p.status = 'publish'
              AND m.meta_key = 'class'
              AND m.meta_value = '$class_id'
            ORDER BY p.title ASC";

    $query = mysqli_query($db_conn, $sql);
    while ($row = mysqli_fetch_object($query)) {
        $subjects[] = $row;
    }

    return $subjects;
}

$classes = get_posts([
    'type' => 'class',
    'status' => 'publish'
]);

$selected_class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
if ($selected_class_id <= 0 && !empty($classes)) {
    $selected_class_id = (int)$classes[0]->id;
}

$sections = get_sections_for_class($selected_class_id);
$subjects = get_subjects_for_class($selected_class_id);

if (isset($_POST['submit'])) {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $class_id = isset($_POST['class']) ? (int)$_POST['class'] : 0;
    $section_id = isset($_POST['section']) ? (int)$_POST['section'] : 0;
    $subject_id = isset($_POST['subject']) ? (int)$_POST['subject'] : 0;

    if (empty($title) || $class_id <= 0 || $section_id <= 0 || $subject_id <= 0 || empty($_FILES['attachment']['name'])) {
        $_SESSION['error_msg'] = 'Please fill all required fields.';
        header('Location: study-materials.php?action=add-new&class_id=' . $class_id);
        exit;
    }

    $section_valid = false;
    foreach (get_sections_for_class($class_id) as $section_row) {
        if ((int)$section_row->id === $section_id) {
            $section_valid = true;
            break;
        }
    }

    if (!$section_valid) {
        $_SESSION['error_msg'] = 'Selected section does not belong to selected class.';
        header('Location: study-materials.php?action=add-new&class_id=' . $class_id);
        exit;
    }

    $subject_valid = false;
    foreach (get_subjects_for_class($class_id) as $subject_row) {
        if ((int)$subject_row->id === $subject_id) {
            $subject_valid = true;
            break;
        }
    }

    if (!$subject_valid) {
        $_SESSION['error_msg'] = 'Selected subject does not belong to selected class.';
        header('Location: study-materials.php?action=add-new&class_id=' . $class_id);
        exit;
    }

    $target_dir = "../dist/uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    $original_file = basename($_FILES['attachment']['name']);
    $ext = strtolower(pathinfo($original_file, PATHINFO_EXTENSION));
    $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($original_file, PATHINFO_FILENAME));
    $new_file = time() . '_' . $safe_name . ($ext ? '.' . $ext : '');
    $target_file = $target_dir . $new_file;

    if ($_FILES['attachment']['size'] > 5000000) {
        $_SESSION['error_msg'] = 'File is too large. Max size is 5MB.';
        header('Location: study-materials.php?action=add-new&class_id=' . $class_id);
        exit;
    }

    if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
        $_SESSION['error_msg'] = 'Error uploading file.';
        header('Location: study-materials.php?action=add-new&class_id=' . $class_id);
        exit;
    }

    $title_sql = mysqli_real_escape_string($db_conn, $title);
    $desc_sql = mysqli_real_escape_string($db_conn, $description);
    $teacher_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

    $query = mysqli_query($db_conn, "INSERT INTO posts (title, description, type, status, parent, author, publish_date) VALUES ('$title_sql', '$desc_sql', 'study-material', 'publish', 0, '$teacher_id', NOW())");
    if (!$query) {
        $_SESSION['error_msg'] = 'Failed to save study material.';
        header('Location: study-materials.php?action=add-new&class_id=' . $class_id);
        exit;
    }

    $item_id = mysqli_insert_id($db_conn);
    $metadata = [
        'class' => $class_id,
        'section' => $section_id,
        'subject' => $subject_id,
        'file_attachment' => $new_file,
    ];

    foreach ($metadata as $key => $value) {
        $key_sql = mysqli_real_escape_string($db_conn, $key);
        $value_sql = mysqli_real_escape_string($db_conn, (string)$value);
        mysqli_query($db_conn, "INSERT INTO metadata (item_id, meta_key, meta_value) VALUES ('$item_id', '$key_sql', '$value_sql')");
    }

    $_SESSION['success_msg'] = 'Study material uploaded successfully.';
    header('Location: study-materials.php');
    exit;
}
?>


<?php include('header.php') ?>
<?php include('sidebar.php') ?>

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Study Materials</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Teacher</a></li>
                    <li class="breadcrumb-item active">Study Materials</li>
                </ol>
            </div><!-- /.col -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div>
<!-- /.content-header -->
<!-- Main content -->
<section class="content">
    <div class="container-fluid">


        


        <?php if(isset($_GET['action']) && $_GET['action'] == 'add-new') {
        ?>
        <!-- Info boxes -->
        <div class="card">
            <div class="card-header py-2">
                <h3 class="card-title">
                    Add New Study-Material
                </h3>
            </div>
            <div class="card-body">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Title</label>
                        <input type="text" name="title" placeholder="enter the title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="name">Description</label>
                        <textarea name="description" id="description" cols="30" rows="5" class="form-control" placeholder="Description"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="name">Select Class</label>
                        <select required name="class" class="form-control" id="class" onchange="window.location='?action=add-new&class_id='+this.value">
                            <option value="">Select Class</option>
                            <?php
                            foreach ($classes as $class) {
                                $selected = ($selected_class_id === (int)$class->id) ? 'selected' : '';
                                echo '<option value="' . $class->id . '" ' . $selected . '>' . $class->title . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="section">Select Section</label>
                        <select required name="section" class="form-control" id="section">
                            <option value="">Select Section</option>
                            <?php
                            foreach ($sections as $section) {
                                echo '<option value="' . $section->id . '">' . $section->title . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="category">Select Your Subject</label>
                        <select required name="subject" class="form-control" id="subject">
                            <option value="">Select Your Subject</option>
                            <?php
                            foreach ($subjects as $subject) {
                                echo '<option value="' . $subject->id . '">' . $subject->title . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="file" name="attachment" id="attachment" required>
                    </div>
                    <button name="submit" class="btn btn-success">
                        Submit
                    </button>
                </form>
            </div>
        </div>
        <!-- /.row -->
        <?php }else{?>
        <!-- Info boxes -->
        <div class="card">
          <div class="card-header py-2">
            <h3 class="card-title">
              Study Materials
            </h3>
            <div class="card-tools">
              <a href="?action=add-new" class="btn btn-success btn-xs"><i class="fa fa-plus mr-2"></i>Add New</a>
            </div>
          </div>
          <div class="card-body">
                        <?php if(isset($_SESSION['success_msg'])) { ?>
                            <div class="alert alert-success"><?=$_SESSION['success_msg']?></div>
                        <?php unset($_SESSION['success_msg']); } ?>

                        <?php if(isset($_SESSION['error_msg'])) { ?>
                            <div class="alert alert-danger"><?=$_SESSION['error_msg']?></div>
                        <?php unset($_SESSION['error_msg']); } ?>

            <div class="table-responsive bg-white">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th>S.No.</th>
                    <th>Title</th>
                    <th>Attachment</th>
                    <th>Class</th>
                                        <th>Section</th>
                    <th>Subject</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                      <?php
                      $count = 1;
                      $teacher_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
                                            $query = mysqli_query($db_conn, "SELECT * FROM posts WHERE type = 'study-material' AND author = '$teacher_id' ORDER BY id DESC");
                      while ($att = mysqli_fetch_object($query)) {
                                                    $class_meta = get_metadata($att->id, 'class');
                                                    $class_id = !empty($class_meta) ? (int)$class_meta[0]->meta_value : 0;
                                                    $class = $class_id > 0 ? get_post(['id' => $class_id]) : (object)['title' => 'N/A'];

                                                    $section_meta = get_metadata($att->id, 'section');
                                                    $section_id = !empty($section_meta) ? (int)$section_meta[0]->meta_value : 0;
                                                    $section = $section_id > 0 ? get_post(['id' => $section_id]) : (object)['title' => 'All'];

                                                    $subject_meta = get_metadata($att->id, 'subject');
                                                    $subject_id = !empty($subject_meta) ? (int)$subject_meta[0]->meta_value : 0;
                                                    $subject = $subject_id > 0 ? get_post(['id' => $subject_id]) : (object)['title' => 'N/A'];

                                                    $file_meta = get_metadata($att->id, 'file_attachment');
                                                    $file_attachment = !empty($file_meta) ? $file_meta[0]->meta_value : '';

                          ?>
                      <tr>
                          <td><?=$count++?></td>
                                                    <td><?=htmlspecialchars($att->title)?></td>
                          <td><a href="<?="../dist/uploads/".$file_attachment; ?>">Download File</a></td>
                                                    <td><?=htmlspecialchars($class->title)?></td>
                                                    <td><?=htmlspecialchars($section->title)?></td>
                                                    <td><?=htmlspecialchars($subject->title)?></td>
                          <td><?=$att->publish_date?></td>
                          
                      </tr>

                      <?php } ?>
                                        </tbody>
              </table>
            </div>
          </div>
        </div>
        <!-- /.row -->
        <?php } ?>
    </div>
</section>


<?php include('footer.php') ?>