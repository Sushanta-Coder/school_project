<?php include('../includes/config.php') ?>
<?php include('header.php') ?>
<?php include('sidebar.php') ?>
<?php
$days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

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

$classes = get_posts(array('type' => 'class', 'status' => 'publish'));
$selected_class_id = isset($_GET['class']) ? (int)$_GET['class'] : 0;
if($selected_class_id <= 0 && !empty($classes)) {
    $selected_class_id = (int)$classes[0]->id;
}

$sections_for_class = get_sections_for_class($selected_class_id);
$selected_section_id = isset($_GET['section']) ? (int)$_GET['section'] : 0;
$selected_section_valid = false;
foreach($sections_for_class as $section_item) {
    if((int)$section_item->id === $selected_section_id) {
        $selected_section_valid = true;
        break;
    }
}
if(!$selected_section_valid) {
    $selected_section_id = !empty($sections_for_class) ? (int)$sections_for_class[0]->id : 0;
}

$periods = get_periods_for_timetable();
$timetable_map = get_timetable_for_class_section($selected_class_id, $selected_section_id);

$teachers = get_users(array('type' => 'teacher'));
$teacher_cache = [];
foreach($teachers as $teacher_row) {
    $teacher_cache[(int)$teacher_row->id] = $teacher_row->name;
}

$subject_cache = [];
?>

    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-dark">Time Table</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Teacher</a></li>
              <li class="breadcrumb-item active">Time Table</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">

        <form action="" method="get" class="mb-3">
            <div class="row">
                <div class="col-auto">
                    <div class="form-group">
                        <select name="class" id="class" class="form-control" onchange="this.form.submit()">
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $class) {
                                $selected = ($selected_class_id === (int)$class->id) ? 'selected' : '';
                                echo '<option value="' . (int)$class->id . '" ' . $selected . '>' . $class->title . '</option>';
                            } ?>
                        </select>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="form-group">
                        <select name="section" id="section" class="form-control">
                            <option value="">Select Section</option>
                            <?php foreach ($sections_for_class as $section) {
                                $selected = ($selected_section_id === (int)$section->id) ? 'selected' : '';
                                echo '<option value="' . (int)$section->id . '" ' . $selected . '>' . $section->title . '</option>';
                            } ?>
                        </select>
                    </div>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" type="submit">Apply</button>
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
                        <?php foreach($periods as $period) {
                            $from = $period['period_from'];
                            $to = $period['period_to'];
                            $period_id = (int)$period['id'];
                        ?>
                        <tr>
                            <td>
                                <div><strong><?php echo $period['title']; ?></strong></div>
                                <div><?php echo !empty($from) ? date('h:i A',strtotime($from)) : '--'; ?> - <?php echo !empty($to) ? date('h:i A',strtotime($to)) : '--'; ?></div>
                            </td>
                            <?php foreach($days as $day) {
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
                                        <div><strong>Teacher:</strong> <?php echo $teacher_name; ?></div>
                                        <div><strong>Subject:</strong> <?php echo $subject_name; ?></div>
                                    </td>
                                <?php } else { ?>
                                    <td><span class="text-muted">Unscheduled</span></td>
                                <?php }
                            } ?>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

      </div>
    </section>

<?php include('footer.php') ?>
