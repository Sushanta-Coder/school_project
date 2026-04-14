<?php include('../includes/config.php') ?>
<?php include('header.php') ?>
<?php include('sidebar.php') ?>
<?php
    $parent_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    $parent = $parent_id > 0 ? get_user_data($parent_id) : [];

    $children = [];
    if (!empty($parent['children'])) {
        $parsed_children = @unserialize($parent['children']);
        if (is_array($parsed_children)) {
            foreach ($parsed_children as $child_id) {
                $child_id = (int)$child_id;
                if ($child_id > 0 && !in_array($child_id, $children, true)) {
                    $children[] = $child_id;
                }
            }
        }
    }

    $selected_child_id = isset($_GET['child_id']) ? (int)$_GET['child_id'] : 0;
    if (!in_array($selected_child_id, $children, true)) {
        $selected_child_id = !empty($children) ? (int)$children[0] : 0;
    }

    $child_meta = $selected_child_id > 0 ? get_user_metadata($selected_child_id) : [];
    $student_class_id = isset($child_meta['class']) ? (int)$child_meta['class'] : 0;
    $student_section_id = isset($child_meta['section']) ? (int)$child_meta['section'] : 0;
?>

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-dark">Time Table</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Parent</a></li>
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
        <?php if($selected_child_id <= 0){ ?>
          <div class="alert alert-info">No child is linked with this parent account.</div>
        <?php } else { ?>
        <div class="card">
            <div class="card-body">
                <?php if(count($children) > 1){ ?>
                <form method="get" action="" class="mb-3">
                    <div class="form-row align-items-end">
                        <div class="col-md-4">
                            <label for="child_id">Select Child</label>
                            <select name="child_id" id="child_id" class="form-control">
                                <?php foreach($children as $child_id){
                                    $child = get_user_data($child_id);
                                ?>
                                    <option value="<?=$child_id?>" <?=$selected_child_id === $child_id ? 'selected' : ''?>>
                                        <?=!empty($child['name']) ? htmlspecialchars($child['name']) : ('Student #' . $child_id)?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">View</button>
                        </div>
                    </div>
                </form>
                <?php } ?>
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
                        $args = array(
                            'type' => 'period',
                            'status' => 'publish',
                        );
                        $periods = get_posts($args);

                        foreach($periods as $period){ 
                            $from = get_metadata($period->id, 'from')[0]->meta_value;

                            $to = get_metadata($period->id, 'to')[0]->meta_value;
                            ?>
                        <tr>
                            <td><?php echo date('h:i A',strtotime($from)) ?> - <?php echo date('h:i A',strtotime($to)) ?></td>
                            <?php 

                            $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
                            foreach($days as $day){ 
                                $query = mysqli_query($db_conn, "SELECT * FROM posts as p 
                                INNER JOIN metadata as mc ON (mc.item_id = p.id) 
                                INNER JOIN metadata as md ON (md.item_id = p.id) 
                                INNER JOIN metadata as mp ON (mp.item_id = p.id)
                                INNER JOIN metadata as ms ON (ms.item_id = p.id)
                                WHERE p.type = 'timetable' AND p.status = 'publish' 
                                AND md.meta_key = 'day_name' AND md.meta_value = '$day' 
                                AND mp.meta_key = 'period_id' AND mp.meta_value = $period->id 
                                AND mc.meta_key = 'class_id' AND mc.meta_value = $student_class_id
                                AND ms.meta_key = 'section_id' AND ms.meta_value = $student_section_id");

                                if(mysqli_num_rows($query) > 0)
                                {
                                    while($timetable = mysqli_fetch_object($query)) {
                                        
                                        
                                        ?>
                                        <td>
                                            <p>
                                                <b>Teacher: </b> 
                                                <?php 
                                                $teacher_id = get_metadata($timetable->item_id,'teacher_id',)[0]->meta_value;
                                                $teacher = get_user_data($teacher_id);
                                                echo isset($teacher['name']) ? $teacher['name'] : 'N/A';
                                                ?> 
                                                
                                                <br>
                                                <b>Subject: </b> 
                                                <?php 
                                                $subject_id = get_metadata($timetable->item_id,'subject_id',)[0]->meta_value;
                                                echo get_post(array('id'=>$subject_id))->title;
                                                ?>
                                            </p>
                                        </td>
                                    <?php } 
                                }else { ?>
                                    <td>
                                        Unscheduled 
                                    </td>     
                                
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
<?php include('footer.php') ?>