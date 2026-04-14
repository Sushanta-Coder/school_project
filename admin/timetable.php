<?php include('../includes/config.php') ?>

<?php
$days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

function esc_sql($value)
{
    global $db_conn;
    return mysqli_real_escape_string($db_conn, (string)$value);
}

function get_meta_value($item_id, $meta_key, $default = '')
{
    $meta = get_metadata((int)$item_id, $meta_key);
    if(!empty($meta) && isset($meta[0]->meta_value)) {
        return $meta[0]->meta_value;
    }
    return $default;
}

function get_sections_for_class($class_id)
{
    $class_id = (int)$class_id;
    $sections = [];
    if($class_id <= 0) {
        return $sections;
    }

    $class_sections = get_metadata($class_id, 'section');
    foreach ($class_sections as $meta) {
        $section = get_post(array('id' => (int)$meta->meta_value));
        if ($section && isset($section->id)) {
            $sections[] = $section;
        }
    }

    return $sections;
}

function get_periods_for_timetable()
{
    global $db_conn;
    $output = [];
    $sql = "SELECT p.id, p.title,
            MAX(CASE WHEN m.meta_key = 'from' THEN m.meta_value END) AS period_from,
            MAX(CASE WHEN m.meta_key = 'to' THEN m.meta_value END) AS period_to
            FROM posts p
            LEFT JOIN metadata m ON (m.item_id = p.id AND m.meta_key IN ('from', 'to'))
            WHERE p.type = 'period' AND p.status = 'publish'
            GROUP BY p.id, p.title
            ORDER BY STR_TO_DATE(MAX(CASE WHEN m.meta_key = 'from' THEN m.meta_value END), '%H:%i') ASC, p.id ASC";

    $query = mysqli_query($db_conn, $sql);
    while($row = mysqli_fetch_assoc($query)) {
        $output[] = $row;
    }

    return $output;
}

function get_subjects_for_class($class_id)
{
    global $db_conn;
    $class_id = (int)$class_id;
    $subjects = [];
    if($class_id <= 0) {
        return $subjects;
    }

    $sql = "SELECT p.*
            FROM posts p
            INNER JOIN metadata m ON (m.item_id = p.id)
            WHERE p.type = 'subject'
              AND p.status = 'publish'
              AND m.meta_key = 'class'
              AND m.meta_value = '" . esc_sql($class_id) . "'
            ORDER BY p.title ASC";

    $query = mysqli_query($db_conn, $sql);
    while($row = mysqli_fetch_object($query)) {
        $subjects[] = $row;
    }

    return $subjects;
}

function upsert_item_metadata($item_id, $meta_key, $meta_value)
{
    global $db_conn;
    $item_id = (int)$item_id;
    $meta_key_sql = esc_sql($meta_key);
    $meta_value_sql = esc_sql($meta_value);

    $exists = mysqli_query($db_conn, "SELECT id FROM metadata WHERE item_id = '$item_id' AND meta_key = '$meta_key_sql' LIMIT 1");
    if(mysqli_num_rows($exists) > 0) {
        mysqli_query($db_conn, "UPDATE metadata SET meta_value = '$meta_value_sql' WHERE item_id = '$item_id' AND meta_key = '$meta_key_sql'");
    } else {
        mysqli_query($db_conn, "INSERT INTO metadata (item_id, meta_key, meta_value) VALUES ('$item_id', '$meta_key_sql', '$meta_value_sql')");
    }
}

function get_existing_slot_item($class_id, $section_id, $period_id, $day_name)
{
    global $db_conn;
    $class_id = (int)$class_id;
    $section_id = (int)$section_id;
    $period_id = (int)$period_id;
    $day_name_sql = esc_sql($day_name);

    $sql = "SELECT p.id
            FROM posts p
            INNER JOIN metadata mc ON (mc.item_id = p.id AND mc.meta_key = 'class_id' AND mc.meta_value = '$class_id')
            INNER JOIN metadata ms ON (ms.item_id = p.id AND ms.meta_key = 'section_id' AND ms.meta_value = '$section_id')
            INNER JOIN metadata mp ON (mp.item_id = p.id AND mp.meta_key = 'period_id' AND mp.meta_value = '$period_id')
            INNER JOIN metadata md ON (md.item_id = p.id AND md.meta_key = 'day_name' AND md.meta_value = '$day_name_sql')
            WHERE p.type = 'timetable' AND p.status = 'publish'
            LIMIT 1";

    $query = mysqli_query($db_conn, $sql);
    return mysqli_fetch_object($query);
}

function get_teacher_conflict_item($teacher_id, $period_id, $day_name, $exclude_item_id = 0)
{
    global $db_conn;
    $teacher_id = (int)$teacher_id;
    $period_id = (int)$period_id;
    $exclude_item_id = (int)$exclude_item_id;
    $day_name_sql = esc_sql($day_name);

    $exclude_sql = $exclude_item_id > 0 ? " AND p.id != '$exclude_item_id'" : '';
    $sql = "SELECT p.id
            FROM posts p
            INNER JOIN metadata mt ON (mt.item_id = p.id AND mt.meta_key = 'teacher_id' AND mt.meta_value = '$teacher_id')
            INNER JOIN metadata mp ON (mp.item_id = p.id AND mp.meta_key = 'period_id' AND mp.meta_value = '$period_id')
            INNER JOIN metadata md ON (md.item_id = p.id AND md.meta_key = 'day_name' AND md.meta_value = '$day_name_sql')
            WHERE p.type = 'timetable' AND p.status = 'publish' $exclude_sql
            LIMIT 1";

    $query = mysqli_query($db_conn, $sql);
    return mysqli_fetch_object($query);
}

function get_timetable_for_class_section($class_id, $section_id)
{
    global $db_conn;
    $class_id = (int)$class_id;
    $section_id = (int)$section_id;
    $result = [];

    if($class_id <= 0 || $section_id <= 0) {
        return $result;
    }

    $sql = "SELECT p.id,
            md.meta_value AS day_name,
            mp.meta_value AS period_id,
            mt.meta_value AS teacher_id,
            ms.meta_value AS subject_id
            FROM posts p
            INNER JOIN metadata mc ON (mc.item_id = p.id AND mc.meta_key = 'class_id' AND mc.meta_value = '$class_id')
            INNER JOIN metadata msec ON (msec.item_id = p.id AND msec.meta_key = 'section_id' AND msec.meta_value = '$section_id')
            INNER JOIN metadata md ON (md.item_id = p.id AND md.meta_key = 'day_name')
            INNER JOIN metadata mp ON (mp.item_id = p.id AND mp.meta_key = 'period_id')
            INNER JOIN metadata mt ON (mt.item_id = p.id AND mt.meta_key = 'teacher_id')
            INNER JOIN metadata ms ON (ms.item_id = p.id AND ms.meta_key = 'subject_id')
            WHERE p.type = 'timetable' AND p.status = 'publish'";

    $query = mysqli_query($db_conn, $sql);
    while($row = mysqli_fetch_assoc($query)) {
        $period_id = (int)$row['period_id'];
        $day_name = strtolower(trim($row['day_name']));
        $result[$period_id][$day_name] = $row;
    }

    return $result;
}

if(isset($_POST['delete_timetable_slot']))
{
    $slot_id = isset($_POST['slot_id']) ? (int)$_POST['slot_id'] : 0;
    $class_id = isset($_POST['class']) ? (int)$_POST['class'] : 0;
    $section_id = isset($_POST['section']) ? (int)$_POST['section'] : 0;

    if($slot_id <= 0) {
        $_SESSION['error_msg'] = 'Invalid timetable slot selected for delete.';
        header('Location: timetable.php?class=' . $class_id . '&section=' . $section_id);
        exit;
    }

    $slot_query = mysqli_query($db_conn, "SELECT id FROM posts WHERE id = '$slot_id' AND type = 'timetable' LIMIT 1");
    if(mysqli_num_rows($slot_query) === 0) {
        $_SESSION['error_msg'] = 'Timetable slot not found.';
        header('Location: timetable.php?class=' . $class_id . '&section=' . $section_id);
        exit;
    }

    mysqli_query($db_conn, "DELETE FROM metadata WHERE item_id = '$slot_id'");
    mysqli_query($db_conn, "DELETE FROM posts WHERE id = '$slot_id' AND type = 'timetable'");

    $_SESSION['success_msg'] = 'Timetable slot deleted. You can assign a new teacher now.';
    header('Location: timetable.php?class=' . $class_id . '&section=' . $section_id);
    exit;
}

if(isset($_POST['submit']))
{
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $section_id = isset($_POST['section_id']) ? (int)$_POST['section_id'] : 0;
    $teacher_id = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;
    $period_id = isset($_POST['period_id']) ? (int)$_POST['period_id'] : 0;
    $day_name = isset($_POST['day_name']) ? strtolower(trim($_POST['day_name'])) : '';
    $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;

    $form_redirect_url = 'timetable.php?action=add&class=' . $class_id . '&section=' . $section_id;
    $view_redirect_url = 'timetable.php?class=' . $class_id . '&section=' . $section_id;

    if($class_id <= 0 || $section_id <= 0 || $teacher_id <= 0 || $period_id <= 0 || $subject_id <= 0 || !in_array($day_name, $days, true)) {
        $_SESSION['error_msg'] = 'Please select valid class, section, period, day, teacher and subject.';
        header('Location: ' . $form_redirect_url);
        exit;
    }

    $teacher_query = mysqli_query($db_conn, "SELECT id FROM accounts WHERE id = '$teacher_id' AND type = 'teacher' LIMIT 1");
    if(mysqli_num_rows($teacher_query) === 0) {
        $_SESSION['error_msg'] = 'Selected teacher is invalid.';
        header('Location: ' . $form_redirect_url);
        exit;
    }

    $subject_class_query = mysqli_query($db_conn, "SELECT id FROM metadata WHERE item_id = '$subject_id' AND meta_key = 'class' AND meta_value = '" . esc_sql($class_id) . "' LIMIT 1");
    if(mysqli_num_rows($subject_class_query) === 0) {
        $_SESSION['error_msg'] = 'Please select a subject assigned to the selected class only.';
        header('Location: ' . $form_redirect_url);
        exit;
    }

    $section_valid = false;
    $class_sections = get_metadata($class_id, 'section');
    foreach($class_sections as $meta) {
        if((int)$meta->meta_value === $section_id) {
            $section_valid = true;
            break;
        }
    }

    if(!$section_valid) {
        $_SESSION['error_msg'] = 'Selected section does not belong to the chosen class.';
        header('Location: ' . $form_redirect_url);
        exit;
    }

    $existing_slot = get_existing_slot_item($class_id, $section_id, $period_id, $day_name);
    $existing_slot_id = $existing_slot ? (int)$existing_slot->id : 0;

    $teacher_conflict = get_teacher_conflict_item($teacher_id, $period_id, $day_name, $existing_slot_id);
    if($teacher_conflict) {
        $conflict_class_id = (int)get_meta_value($teacher_conflict->id, 'class_id', 0);
        $conflict_section_id = (int)get_meta_value($teacher_conflict->id, 'section_id', 0);
        $conflict_class = $conflict_class_id > 0 ? get_post(array('id' => $conflict_class_id)) : null;
        $conflict_section = $conflict_section_id > 0 ? get_post(array('id' => $conflict_section_id)) : null;
        $period = get_post(array('id' => $period_id));

        $class_label = ($conflict_class && isset($conflict_class->title)) ? $conflict_class->title : 'Unknown Class';
        $section_label = ($conflict_section && isset($conflict_section->title)) ? $conflict_section->title : 'Unknown Section';
        $period_label = ($period && isset($period->title)) ? $period->title : 'Selected Period';

        $_SESSION['error_msg'] = 'Teacher already assigned in ' . $class_label . ' - ' . $section_label . ' for ' . ucwords($day_name) . ' (' . $period_label . '). Please choose a different teacher.';
        header('Location: ' . $form_redirect_url);
        exit;
    }

    if($existing_slot_id > 0) {
        upsert_item_metadata($existing_slot_id, 'teacher_id', $teacher_id);
        upsert_item_metadata($existing_slot_id, 'subject_id', $subject_id);
        $_SESSION['success_msg'] = 'Timetable updated for selected slot.';
    } else {
        $now = date('Y-m-d H:i:s');
        $type = 'timetable';
        $query = mysqli_query($db_conn, "INSERT INTO posts (`author`, `title`, `description`, `type`, `status`, `publish_date`, `parent`) VALUES ('1', '$type', 'description', 'timetable', 'publish', '$now', 0)") or die('DB error');
        if($query) {
            $item_id = mysqli_insert_id($db_conn);
            $metadata = array(
                'class_id' => $class_id,
                'section_id' => $section_id,
                'teacher_id' => $teacher_id,
                'period_id' => $period_id,
                'day_name' => $day_name,
                'subject_id' => $subject_id,
            );

            foreach ($metadata as $key => $value) {
                upsert_item_metadata($item_id, $key, $value);
            }

            $_SESSION['success_msg'] = 'Timetable added successfully.';
        }
    }

    header('Location: ' . $view_redirect_url);
    exit;
}

$classes = get_posts(array('type' => 'class', 'status' => 'publish'));
$selected_class_id = isset($_GET['class']) ? (int)$_GET['class'] : 0;
if($selected_class_id <= 0 && !empty($classes)) {
    $selected_class_id = (int)$classes[0]->id;
}

$sections_for_class = get_sections_for_class($selected_class_id);
$selected_section_id = isset($_GET['section']) ? (int)$_GET['section'] : 0;
if($selected_section_id <= 0 && !empty($sections_for_class)) {
    $selected_section_id = (int)$sections_for_class[0]->id;
}

$periods = get_periods_for_timetable();
$subjects_for_selected_class = get_subjects_for_class($selected_class_id);
$timetable_map = get_timetable_for_class_section($selected_class_id, $selected_section_id);

$teachers = get_users(array('type' => 'teacher'));
$teacher_cache = [];
foreach($teachers as $teacher_row) {
    $teacher_cache[(int)$teacher_row->id] = $teacher_row->name;
}

$subject_cache = [];
foreach($subjects_for_selected_class as $subject_row) {
    $subject_cache[(int)$subject_row->id] = $subject_row->title;
}
?>

<?php include('header.php') ?>
<?php include('sidebar.php') ?>

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-dark">Manage Time Table 

            <a href="?action=add" class="btn btn-success btn-sm"> Add New</a>
            </h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Admin</a></li>
              <li class="breadcrumb-item active">Time Table</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">

        <?php if(isset($_SESSION['success_msg'])) { ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_msg']; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
        <?php unset($_SESSION['success_msg']); } ?>

        <?php if(isset($_SESSION['error_msg'])) { ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error_msg']; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
        <?php unset($_SESSION['error_msg']); } ?>

        <?php if(isset($_GET['action']) && $_GET['action'] == 'add') {?>
        
        <div class="card">
            <div class="card-header py-2">
                <h3 class="card-title">Assign Teacher and Subject to Period</h3>
            </div>
            <div class="card-body">
                <form action="" method="post">
                <div class="row">
                        <div class="col-lg">
                            <div class="form-group">
                                <label for="class_id">Select Class</label>
                                <select required name="class_id" id="class_id" class="form-control">
                                    <option value="">-Select Class-</option>
                                    <?php
                                    foreach ($classes as $key => $class) {
                                    $selected = ($selected_class_id === (int)$class->id) ? 'selected' : '';
                                    ?>
                                    <option value="<?php echo $class->id ?>" <?php echo $selected; ?>><?php echo $class->title ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg">
                            <div class="form-group" id="add-section-container">
                                <label for="section_id">Select Section</label>
                                <select required name="section_id" id="section_id" class="form-control">
                                    <option value="">-Select Section-</option>
                                    <?php foreach($sections_for_class as $section) {
                                        $selected = ($selected_section_id === (int)$section->id) ? 'selected' : '';
                                        echo '<option value="' . $section->id . '" ' . $selected . '>' . $section->title . '</option>';
                                    } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg">
                            <div class="form-group">
                                <label for="teacher_id">Select Teacher</label>
                                <select required name="teacher_id" id="teacher_id" class="form-control">
                                    <option value="">-Select Teacher-</option>
                                    <?php
                                    foreach ($teachers as $teacher) {
                                        echo '<option value="' . $teacher->id . '">' . $teacher->name . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg">
                            <div class="form-group">
                                <label for="period_id">Select Period</label>
                                <select required name="period_id" id="period_id" class="form-control">
                                    <option value="">-Select Period-</option>
                                    <?php
                                      foreach ($periods as $period) {
                                        $from_label = !empty($period['period_from']) ? date('h:i A', strtotime($period['period_from'])) : '--';
                                        $to_label = !empty($period['period_to']) ? date('h:i A', strtotime($period['period_to'])) : '--';
                                      ?>
                                      <option value="<?php echo $period['id'] ?>"><?php echo $period['title'] . ' (' . $from_label . ' - ' . $to_label . ')' ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg">
                            <div class="form-group">
                                <label for="day_name">Select Day</label>
                                <select required name="day_name" id="day_name" class="form-control">
                                    <option value="">-Select Day-</option>

                                    <?php
                                     foreach ($days as $key => $day) { ?>
                                        <option value="<?php echo $day ?>"><?php echo ucwords($day)?></option>
                                      <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg">
                            <div class="form-group" id="subject-container">
                                <label for="subject_id">Select Subject</label>
                                <select required name="subject_id" id="subject_id" class="form-control">
                                    <option value="">-Select Subject-</option>
                                    <?php
                                    foreach ($subjects_for_selected_class as $subject) {
                                        echo '<option value="' . $subject->id . '">' . $subject->title . '</option>';
                                    }
                                    ?>
                                </select>
                                
                            </div>
                        </div>
                        <div class="col-lg">
                            <div class="from-group">
                            <label for="">&nbsp;</label>
                            <input type="submit" value="Save Timetable" name="submit" class="btn btn-success form-control">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php } else {?>

        <form action="" method="get">
            <div class="row">
                <div class="col-auto">
                    <div class="form-group">
                        <select name="class" id="class" class="form-control">
                            <option value="">Select Class</option>
                            <?php
                            foreach ($classes as $class) {
                                $selected = ($selected_class_id ==  $class->id)? 'selected': '';
                                echo '<option value="' . $class->id . '" '.$selected.'>' . $class->title . '</option>';
                            } ?>
                        </select>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="form-group" id="list-section-container">
                        <select name="section" id="section" class="form-control" data-selected="<?php echo $selected_section_id; ?>">
                            <option value="">Select Section</option>
                            <?php
                            foreach ($sections_for_class as $section) {
                                $selected = ($selected_section_id ==  $section->id)? 'selected': '';
                                echo '<option value="' . $section->id . '" '.$selected.'>' . $section->title . '</option>';
                            } ?>
                        </select>
                    </div>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary">Apply</button>
                </div>
            </div>

        </form>

        <div class="card">
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Timing</th>
                            <th>Monday</th>
                            <th>Tuesday</th>
                            <th>Wednesday</th>
                            <th>Thursday</th>
                            <th>Friday</th>
                            <th>Saturday</th>
                            <th>Sunday</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach($periods as $period){
                            $from = $period['period_from'];
                            $to = $period['period_to'];
                            $period_id = (int)$period['id'];
                            ?>
                        <tr>
                            <td>
                                <div><strong><?php echo $period['title']; ?></strong></div>
                                <div><?php echo !empty($from) ? date('h:i A',strtotime($from)) : '--'; ?> - <?php echo !empty($to) ? date('h:i A',strtotime($to)) : '--'; ?></div>
                            </td>
                            <?php 

                            foreach($days as $day){
                                $slot = isset($timetable_map[$period_id][$day]) ? $timetable_map[$period_id][$day] : null;
                                if($slot) {
                                    $slot_teacher_id = (int)$slot['teacher_id'];
                                    $slot_subject_id = (int)$slot['subject_id'];
                                    $teacher_name = isset($teacher_cache[$slot_teacher_id]) ? $teacher_cache[$slot_teacher_id] : 'N/A';

                                    if(isset($subject_cache[$slot_subject_id])) {
                                        $subject_name = $subject_cache[$slot_subject_id];
                                    } else {
                                        $subject = get_post(array('id' => $slot_subject_id));
                                        $subject_name = ($subject && isset($subject->title)) ? $subject->title : 'N/A';
                                        $subject_cache[$slot_subject_id] = $subject_name;
                                    }
                                    ?>
                                    <td>
                                        <div class="badge badge-light border mb-2">Scheduled</div>
                                        <div><strong>Teacher:</strong> <?php echo $teacher_name; ?></div>
                                        <div><strong>Subject:</strong> <?php echo $subject_name; ?></div>
                                        <form action="" method="post" class="mt-2">
                                            <input type="hidden" name="slot_id" value="<?php echo (int)$slot['id']; ?>">
                                            <input type="hidden" name="class" value="<?php echo (int)$selected_class_id; ?>">
                                            <input type="hidden" name="section" value="<?php echo (int)$selected_section_id; ?>">
                                            <button type="submit" name="delete_timetable_slot" class="btn btn-danger btn-xs" onclick="return confirm('Delete this timetable slot?');">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                <?php } else { ?>
                                    <td><span class="text-muted">Unscheduled</span></td>
                                <?php }
                            }?>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php } ?>
      </div><!--/. container-fluid -->
    </section>
    <!-- /.content -->
    <!-- Subject -->
<script>
jQuery(document).ready(function(){

    function loadSections(classSelector, sectionSelector, selectedSection) {
        var classId = jQuery(classSelector).val();
        if(!classId) {
            jQuery(sectionSelector).html('<option value="">-Select Section-</option>');
            return;
        }

        jQuery.ajax({
            url:'ajax.php',
            type : 'POST',
            data  : {'class_id': classId},
            dataType : 'json',
            success: function(response){
                jQuery(sectionSelector).html(response.options);
                if(selectedSection) {
                    jQuery(sectionSelector).val(selectedSection);
                }
            }
        });
    }

    function loadSubjects(classSelector, subjectSelector, selectedSubject) {
        var classId = jQuery(classSelector).val();
        if(!classId) {
            jQuery(subjectSelector).html('<option value="">-Select Subject-</option>');
            return;
        }

        jQuery.ajax({
            url:'ajax.php',
            type : 'POST',
            data  : {'action': 'get_class_subjects', 'class_id': classId},
            dataType : 'json',
            success: function(response){
                jQuery(subjectSelector).html(response.options);
                if(selectedSubject) {
                    jQuery(subjectSelector).val(selectedSubject);
                }
            }
        });
    }

    jQuery('#class_id').on('change', function(){
        loadSections('#class_id', '#section_id', '');
        loadSubjects('#class_id', '#subject_id', '');
    });

    jQuery('#class').on('change', function(){
        loadSections('#class', '#section', '');
    });

    if(jQuery('#class').length) {
        var selectedSection = jQuery('#section').attr('data-selected');
        if(selectedSection) {
            loadSections('#class', '#section', selectedSection);
        }
    }

    if(jQuery('#class_id').length) {
        loadSubjects('#class_id', '#subject_id', '');
    }

})
</script>
<?php include('footer.php') ?>