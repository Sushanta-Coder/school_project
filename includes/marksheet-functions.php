<?php

function exam_meta_delete_and_insert($item_id, $meta_key, $meta_value)
{
    global $db_conn;
    $item_id = (int)$item_id;
    $meta_key_sql = mysqli_real_escape_string($db_conn, (string)$meta_key);
    $meta_value_sql = mysqli_real_escape_string($db_conn, (string)$meta_value);

    mysqli_query($db_conn, "DELETE FROM metadata WHERE item_id = '$item_id' AND meta_key = '$meta_key_sql'");
    mysqli_query($db_conn, "INSERT INTO metadata (item_id, meta_key, meta_value) VALUES ('$item_id', '$meta_key_sql', '$meta_value_sql')");
}

function get_class_sections($class_id)
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

function get_class_subjects($class_id)
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

function get_class_students($class_id, $section_id = 0)
{
    global $db_conn;
    $class_id = (int)$class_id;
    $section_id = (int)$section_id;
    $students = [];

    if ($class_id <= 0) {
        return $students;
    }

    $sql = "SELECT a.id, a.name
            FROM accounts a
            INNER JOIN usermeta uc ON (uc.user_id = a.id AND uc.meta_key = 'class' AND uc.meta_value = '$class_id')
            WHERE a.type = 'student'";
    if ($section_id > 0) {
        $sql .= " AND EXISTS (SELECT 1 FROM usermeta us WHERE us.user_id = a.id AND us.meta_key = 'section' AND us.meta_value = '$section_id')";
    }
    $sql .= " ORDER BY a.name ASC";

    $query = mysqli_query($db_conn, $sql);
    while ($row = mysqli_fetch_object($query)) {
        $students[] = $row;
    }

    return $students;
}

function find_result_record($student_id, $class_id, $section_id, $exam_name)
{
    global $db_conn;
    $student_id = (int)$student_id;
    $class_id = (int)$class_id;
    $section_id = (int)$section_id;
    $exam_name_sql = mysqli_real_escape_string($db_conn, strtolower(trim((string)$exam_name)));

    $sql = "SELECT p.*
            FROM posts p
            INNER JOIN metadata ms ON (ms.item_id = p.id AND ms.meta_key = 'student_id' AND ms.meta_value = '$student_id')
            INNER JOIN metadata mc ON (mc.item_id = p.id AND mc.meta_key = 'class_id' AND mc.meta_value = '$class_id')
            INNER JOIN metadata me ON (me.item_id = p.id AND me.meta_key = 'exam_name' AND LOWER(me.meta_value) = '$exam_name_sql')
            WHERE p.type = 'result' AND p.status = 'publish'";

    if ($section_id > 0) {
        $sql .= " AND EXISTS (SELECT 1 FROM metadata msec WHERE msec.item_id = p.id AND msec.meta_key = 'section_id' AND msec.meta_value = '$section_id')";
    }

    $sql .= " ORDER BY p.id DESC LIMIT 1";

    $query = mysqli_query($db_conn, $sql);
    return mysqli_fetch_object($query);
}

function save_result_record($student_id, $class_id, $section_id, $exam_name, array $marks, $author_id = 0)
{
    global $db_conn;

    $student_id = (int)$student_id;
    $class_id = (int)$class_id;
    $section_id = (int)$section_id;
    $author_id = (int)$author_id;
    $exam_name = trim((string)$exam_name);

    if ($student_id <= 0 || $class_id <= 0 || $exam_name === '') {
        return 0;
    }

    $numeric_marks = [];
    foreach ($marks as $subject_id => $mark_value) {
        if ($mark_value === '' || $mark_value === null) {
            continue;
        }
        $numeric_marks[(int)$subject_id] = (float)$mark_value;
    }

    $record = find_result_record($student_id, $class_id, $section_id, $exam_name);
    $result_id = $record ? (int)$record->id : 0;
    $student = get_user_data($student_id);
    $student_name = !empty($student['name']) ? $student['name'] : 'Student';
    $marks_json = json_encode($numeric_marks);
    $total_obtained = array_sum($numeric_marks);
    $subject_count = count($marks);
    $total_marks = $subject_count * 100;
    $percentage = $total_marks > 0 ? round(($total_obtained / $total_marks) * 100, 2) : 0;
    $title = $exam_name . ' Marksheet - ' . $student_name;
    $description = 'Marks record for ' . $exam_name;
    $publish_date = date('Y-m-d H:i:s');

    if ($result_id > 0) {
        $title_sql = mysqli_real_escape_string($db_conn, $title);
        $desc_sql = mysqli_real_escape_string($db_conn, $description);
        mysqli_query($db_conn, "UPDATE posts SET title = '$title_sql', description = '$desc_sql', author = '$author_id', publish_date = '$publish_date' WHERE id = '$result_id' AND type = 'result'");
    } else {
        $title_sql = mysqli_real_escape_string($db_conn, $title);
        $desc_sql = mysqli_real_escape_string($db_conn, $description);
        mysqli_query($db_conn, "INSERT INTO posts (title, description, type, status, parent, author, publish_date) VALUES ('$title_sql', '$desc_sql', 'result', 'publish', 0, '$author_id', '$publish_date')") or die(mysqli_error($db_conn));
        $result_id = mysqli_insert_id($db_conn);
    }

    exam_meta_delete_and_insert($result_id, 'student_id', $student_id);
    exam_meta_delete_and_insert($result_id, 'class_id', $class_id);
    exam_meta_delete_and_insert($result_id, 'section_id', $section_id);
    exam_meta_delete_and_insert($result_id, 'exam_name', $exam_name);
    exam_meta_delete_and_insert($result_id, 'marks_json', $marks_json);
    exam_meta_delete_and_insert($result_id, 'total_obtained', $total_obtained);
    exam_meta_delete_and_insert($result_id, 'total_marks', $total_marks);
    exam_meta_delete_and_insert($result_id, 'percentage', $percentage);

    return $result_id;
}

function get_result_meta_map($result_id)
{
    $result_id = (int)$result_id;
    $meta = get_metadata($result_id);
    $map = [];
    foreach ($meta as $item) {
        $map[$item->meta_key] = $item->meta_value;
    }
    return $map;
}

function get_result_marks($result_id)
{
    $meta = get_result_meta_map($result_id);
    if (empty($meta['marks_json'])) {
        return [];
    }

    $decoded = json_decode($meta['marks_json'], true);
    return is_array($decoded) ? $decoded : [];
}

function get_student_latest_result($student_id, $class_id = 0, $section_id = 0)
{
    global $db_conn;
    $student_id = (int)$student_id;
    $class_id = (int)$class_id;
    $section_id = (int)$section_id;

    if ($student_id <= 0) {
        return null;
    }

    $sql = "SELECT p.* FROM posts p
            INNER JOIN metadata ms ON (ms.item_id = p.id AND ms.meta_key = 'student_id' AND ms.meta_value = '$student_id')
            WHERE p.type = 'result' AND p.status = 'publish'";

    if ($class_id > 0) {
        $sql .= " AND EXISTS (SELECT 1 FROM metadata mc WHERE mc.item_id = p.id AND mc.meta_key = 'class_id' AND mc.meta_value = '$class_id')";
    }

    if ($section_id > 0) {
        $sql .= " AND EXISTS (SELECT 1 FROM metadata msec WHERE msec.item_id = p.id AND msec.meta_key = 'section_id' AND msec.meta_value = '$section_id')";
    }

    $sql .= " ORDER BY p.publish_date DESC, p.id DESC LIMIT 1";

    $query = mysqli_query($db_conn, $sql);
    return mysqli_fetch_object($query);
}

function get_student_results($student_id)
{
    global $db_conn;
    $student_id = (int)$student_id;
    $results = [];

    if ($student_id <= 0) {
        return $results;
    }

    $sql = "SELECT p.* FROM posts p
            INNER JOIN metadata ms ON (ms.item_id = p.id AND ms.meta_key = 'student_id' AND ms.meta_value = '$student_id')
            WHERE p.type = 'result' AND p.status = 'publish'
            ORDER BY p.publish_date DESC, p.id DESC";

    $query = mysqli_query($db_conn, $sql);
    while ($row = mysqli_fetch_object($query)) {
        $results[] = $row;
    }

    return $results;
}
