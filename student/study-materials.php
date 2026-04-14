<?php include('../includes/config.php') ?>
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
                    <li class="breadcrumb-item"><a href="#">Student</a></li>
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
        <!-- Info boxes -->
        <div class="card">
          <div class="card-header py-2">
            <h3 class="card-title">
              Study Materials
            </h3>
          </div>
          <div class="card-body">
            <div class="table-responsive bg-white">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th>S.No.</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Attachment</th>
                    <th>Subject</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                      <?php
                      $usermeta = get_user_metadata($std_id);
                      $class = isset($usermeta['class']) ? $usermeta['class'] : '';
                      $section = isset($usermeta['section']) ? $usermeta['section'] : '';
                      $count = 1;
                      $query = mysqli_query($db_conn, "SELECT DISTINCT p.*
                        FROM posts AS p
                        INNER JOIN metadata AS mc ON p.id = mc.item_id AND mc.meta_key = 'class' AND mc.meta_value = '$class'
                        LEFT JOIN metadata AS ms ON p.id = ms.item_id AND ms.meta_key = 'section'
                        WHERE p.type = 'study-material' AND p.status = 'publish'
                          AND (ms.meta_value = '$section' OR ms.meta_value IS NULL OR ms.meta_value = '')
                        ORDER BY p.id DESC");
                      while ($att = mysqli_fetch_object($query)) {
                        
                        //   $class_id = get_metadata($att->id, 'class')[0]->meta_value;

                          $subject_meta = get_metadata($att->id, 'subject');
                          $subject_id = !empty($subject_meta) ? $subject_meta[0]->meta_value : '';
                          $subject = !empty($subject_id) ? get_post(['id' => $subject_id]) : (object)['title' => 'N/A'];

                          $file_meta = get_metadata($att->id, 'file_attachment');
                          $file_attachment = !empty($file_meta) ? $file_meta[0]->meta_value : '';


                          ?>
                      <tr>
                          <td><?=$count++?></td>
                          <td><?=htmlspecialchars($att->title)?></td>
                          <td><?=htmlspecialchars($att->description)?></td>
                          <td><a href="<?="../dist/uploads/".$file_attachment; ?>">Download File</a></td>
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
    </div>
</section>


<?php include('footer.php') ?>