<?php include('../includes/config.php') ?>
<?php

if (isset($_POST['action']) && $_POST['action'] === 'get_class_subjects') {
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $count = 0;
    $options = '<option value="">-Select Subject-</option>';

    if($class_id > 0) {
        $class_id_sql = mysqli_real_escape_string($db_conn, (string)$class_id);
        $sql = "SELECT p.id, p.title
                FROM posts p
                INNER JOIN metadata m ON (m.item_id = p.id)
                WHERE p.type = 'subject'
                  AND p.status = 'publish'
                  AND m.meta_key = 'class'
                  AND m.meta_value = '$class_id_sql'
                ORDER BY p.title ASC";
        $query = mysqli_query($db_conn, $sql);
        while($subject = mysqli_fetch_object($query)) {
            $options .= '<option value="' . $subject->id . '">' . $subject->title . '</option>';
            $count++;
        }
    }

    echo json_encode([
        'count' => $count,
        'options' => $options,
    ]);
    die;
}

if (isset($_POST['class_id']) && $_POST['class_id']) {
    $class_id = (int)$_POST['class_id'];
    $class_meta = get_metadata($class_id, 'section');
    $count = 0;
    $options = '<option value="">-Select Section-</option>';
    foreach ($class_meta as $meta) {
        $section = get_post(array('id' => $meta->meta_value));
        if ($section && isset($section->id)) {
            $options .= '<option value="' . $section->id . '">' . $section->title . '</option>';
            $count++;
        }
    }
    $output['count'] = $count;
    $output['options'] = $options;
    echo json_encode($output);
    die;
}

if (isset($_POST['type']) && $_POST['type'] == 'teacher') {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    if (empty($name) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Name and email are required']);
        die;
    }

    $name_sql = mysqli_real_escape_string($db_conn, $name);
    $email_sql = mysqli_real_escape_string($db_conn, $email);

    if ($user_id > 0) {
        mysqli_query($db_conn, "UPDATE `accounts` SET `name` = '$name_sql', `email` = '$email_sql' WHERE `id` = '$user_id' AND `type` = 'teacher'") or die(mysqli_error($db_conn));
    } else {
        $check_query = mysqli_query($db_conn, "SELECT `id` FROM `accounts` WHERE `email` = '$email_sql' LIMIT 1");
        if (mysqli_num_rows($check_query) > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            die;
        }

        $password = md5(1234567890);
        $query = mysqli_query($db_conn, "INSERT INTO `accounts` (`name`,`email`,`password`,`type`) VALUES ('$name_sql','$email_sql','$password','teacher')") or die(mysqli_error($db_conn));
        if ($query) {
            $user_id = mysqli_insert_id($db_conn);
        }
    }

    if (!is_dir('../dist/uploads/teacher-docs/')) {
        mkdir('../dist/uploads/teacher-docs/', 0777, true);
    }

    $usermeta = [];
    if (!empty($_FILES['documention']) && !empty($_FILES['documention']['name']) && is_array($_FILES['documention']['name'])) {
        foreach ($_FILES['documention']['name'] as $key => $file_name) {
            if (empty($file_name)) {
                continue;
            }

            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $safe_key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            $new_file_name = 'teacher_' . $user_id . '_' . $safe_key . '_' . time() . '.' . $file_ext;
            $target_file = '../dist/uploads/teacher-docs/' . $new_file_name;

            if (move_uploaded_file($_FILES['documention']['tmp_name'][$key], $target_file)) {
                $usermeta[$key] = $new_file_name;
            }
        }
    }

    $skip_fields = ['submit', 'type', 'user_id', 'name', 'email'];
    foreach ($_POST as $key => $value) {
        if (in_array($key, $skip_fields, true)) {
            continue;
        }

        if (is_array($value)) {
            $usermeta[$key] = serialize($value);
        } else {
            $usermeta[$key] = trim((string)$value);
        }
    }

    if (empty($usermeta['emp_id'])) {
        $usermeta['emp_id'] = 'EMP' . str_pad((string)$user_id, 4, '0', STR_PAD_LEFT);
    }

    foreach ($usermeta as $key => $value) {
        $meta_key = mysqli_real_escape_string($db_conn, $key);
        $meta_value = mysqli_real_escape_string($db_conn, (string)$value);
        $check_query = mysqli_query($db_conn, "SELECT `id` FROM `usermeta` WHERE `user_id` = '$user_id' AND `meta_key` = '$meta_key' LIMIT 1");

        if (mysqli_num_rows($check_query) > 0) {
            mysqli_query($db_conn, "UPDATE `usermeta` SET `meta_value` = '$meta_value' WHERE `user_id` = '$user_id' AND `meta_key` = '$meta_key'") or die(mysqli_error($db_conn));
        } else {
            mysqli_query($db_conn, "INSERT INTO `usermeta` (`user_id`,`meta_key`,`meta_value`) VALUES ('$user_id','$meta_key','$meta_value')") or die(mysqli_error($db_conn));
        }
    }

    echo json_encode(['success' => true, 'user_id' => $user_id]);
    die;
}

if(!empty($_GET['action']) && 'get_users_details' == $_GET['action']){

    $limit = $_POST['length'];
    $offset = $_POST['start'];
    $column = $_POST['order'][0]['column'];
    $dir = $_POST['order'][0]['dir'];
    $s = $_POST['search']['value'];
    $order_by = ($column == 0)? 'id': $_POST['columns'][$column]['data'];

    $data = [
        "draw"=> $_POST['draw'],
        "recordsTotal"=> 0,
        "recordsFiltered"=> 0,
        "data" => []
    ];

    $type = $_GET['user'];

    switch ($type) {
        case 'student':
            $args = array('type' => 'student');
            $result = get_users($args,false);
            $enrs = substr($s,8);

            foreach ($result as $key => $value) {
                
                $usermeta = get_user_metadata($value['id']);
                $class = get_post(array('id'=> $usermeta['class']))->title;
                $section = get_post(array('id'=> intval($usermeta['section'])))->title;
                $class = $class.' ('.$section.')';
                $img = !empty($usermeta['photo']) ? '<img class="border" src="../dist/uploads/student-docs/'.$usermeta['photo'].'" width="40" height="40">':'<img class="border" src="../dist/img/AdminLTELogo.png" width="40" height="40">';
                $data['data'][] = [
                    'enroll' => isset($usermeta['enrollment_no']) ? $usermeta['enrollment_no'] : 0,
                    'class' => $class,
                    'photo' => $img,
                    'name' => $value['name'],
                    'dob' =>$usermeta['dob'],
                    'father_name' =>$usermeta['father_name'],
                    'mother_name' =>$usermeta['mother_name'],
                    'doa' =>$usermeta['doa'],
                    'address' =>$usermeta['address'],
                    'action' => 
                    '<a href="user-account.php?user=student&action=view&id='.$value['id'].'" class="btn btn-sm btn-success"><i class="fa fa-eye"></i></a>
                    <a href="user-account.php?user=student&action=edit&id='.$value['id'].'" class="btn btn-sm btn-info"><i class="fa fa-pencil-alt"></i></a>
                    <a href="user-account.php?user=student&action=trash&id='.$value['id'].'" class="btn btn-sm btn-danger trash-user"><i class="fa fa-trash"></i></a>',
                ];
            }
            break;
        case 'teacher':

            $args = array('type' => 'teacher');
            $result = get_users($args,false);
            foreach ($result as $key => $value) {
                $usermeta = get_user_metadata($value['id']);
                $subjects = [];
                if(isset($usermeta['subjects'])){
                    $parsed_subjects = @unserialize($usermeta['subjects']);
                    if(is_array($parsed_subjects)){
                        $subjects = $parsed_subjects;
                    }
                }
                $subject_data = '';
                if(is_array($subjects)){
                    foreach ($subjects as $subject) {
                        // $child = get_userdata($chid_id);
                        $result = mysqli_fetch_array(mysqli_query($db_conn, "SELECT `title` FROM `posts` WHERE id = $subject"), MYSQLI_ASSOC);
                        if($result){
                            $subject_data .= '<a class="btn btn-sm btn-default mr-2">'.$result['title'].'</a>';
                        }
    
                    }
                }

                $teaching_areas = [];
                if(isset($usermeta['teaching_area'])){
                    $parsed_areas = @unserialize($usermeta['teaching_area']);
                    if(is_array($parsed_areas)){
                        $teaching_areas = $parsed_areas;
                    }
                }
                $teaching_area_data = '';
                $zone_labels = [
                    'nursery' => 'Nursery (LKG, UKG)',
                    'primary' => 'Primary (1-5)',
                    'junior' => 'Junior (6-8)',
                    'sencondary' => 'Secondary (9-10)',
                    'higersencondary' => 'Higher Secondary (11-12)',
                ];
                if(is_array($teaching_areas)){
                    foreach ($teaching_areas as $teaching_area) {
                        $label = isset($zone_labels[$teaching_area]) ? $zone_labels[$teaching_area] : $teaching_area;
                        $teaching_area_data .= '<a class="btn btn-sm btn-default mr-2">'.$label.'</a>';
                    }
                }

                if(empty($subject_data)){
                    $subject_data = '-';
                }
                if(empty($teaching_area_data)){
                    $teaching_area_data = '-';
                }

                $img = !empty($usermeta['photo']) ? '<img class="border" src="../dist/uploads/teacher-docs/'.$usermeta['photo'].'" width="40" height="40">':'<img class="border" src="../dist/img/AdminLTELogo.png" width="40" height="40">';
                $data['data'][] = [
                    'emp_id' => (isset($usermeta['emp_id']) && !empty($usermeta['emp_id']) ? $usermeta['emp_id'] : ('EMP' . str_pad((string)$value['id'], 4, '0', STR_PAD_LEFT))),
                    'photo' => $img,
                    'name' => $value['name'],
                    'dob' => (isset($usermeta['dob']) && !empty($usermeta['dob']) ? date('d-m-Y', strtotime($usermeta['dob'])) : '-'),
                    'contact_number' => (isset($usermeta['mobile']) && !empty($usermeta['mobile']) ? $usermeta['mobile'] : '-'),
                    'subjects' => $subject_data,
                    'zone' => $teaching_area_data,
                    'doj' => (isset($usermeta['doj'])? date('d-m-Y', strtotime($usermeta['doj'])):''),
                    'address' => isset($usermeta['address'])?$usermeta['address']:'',
                    'action' => 
                    '<a href="user-account.php?user=teacher&action=view&id='.$value['id'].'" class="btn btn-sm btn-success"><i class="fa fa-eye"></i></a>
                    <a href="user-account.php?user=teacher&action=edit&id='.$value['id'].'" class="btn btn-sm btn-info"><i class="fa fa-pencil-alt"></i></a>
                    <a href="user-account.php?user=teacher&action=trash&id='.$value['id'].'" class="btn btn-sm btn-danger trash-user"><i class="fa fa-trash"></i></a>',
                ];
            }
            
            break;
        case 'parent':

            $sql = "SELECT * FROM `accounts` as a WHERE a.type = '$type' ";
            $query = mysqli_query($db_conn,$sql);

            $result = mysqli_fetch_all($query, MYSQLI_ASSOC);
            foreach ($result as $key => $value) {
                $usermeta = get_user_metadata($value['id']);
                $children = unserialize($usermeta['children']);
                $children_data = '';
                foreach ($children as $chid_id) {
                    // $child = get_userdata($chid_id);
                    $child = mysqli_fetch_array(mysqli_query($db_conn, "SELECT `name` FROM `accounts` WHERE id = $chid_id"), MYSQLI_ASSOC);
                    if($child){
                        $children_data .= '<a href="user-account.php?user=student&action=view&id='.$chid_id.'" class="btn btn-sm btn-default mr-2">'.$child['name'].'</a>';
                    }

                }
                $data['data'][] = [
                    'name' => $value['name'],
                    'children' => $children_data,
                    'address' =>isset($usermeta['address'])?$usermeta['address']:'',
                    'action' => 
                    '<a href="user-account.php?user=parent&action=view&id='.$value['id'].'" class="btn btn-sm btn-success"><i class="fa fa-eye"></i></a>
                    <a href="user-account.php?user=parent&action=trash&id='.$value['id'].'" class="btn btn-sm btn-danger trash-user"><i class="fa fa-trash"></i></a>',
                ];
            }
            
            break;
        default:
            # code...
            break;
    }
  
    echo json_encode($data);
    die;
}
