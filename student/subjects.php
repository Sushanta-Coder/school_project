<?php include('../includes/config.php') ?>
<?php include('header.php') ?>
<?php include('sidebar.php') ?>
<?php
  $student_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
  $usermeta = $student_id > 0 ? get_user_metadata($student_id) : [];
  $student_class_id = isset($usermeta['class']) ? (int)$usermeta['class'] : 0;

  $subjects = [];
  if($student_class_id > 0)
  {
    $sql = "SELECT p.id, p.title
            FROM posts p
            JOIN metadata m ON p.id = m.item_id
            WHERE p.type = 'subject'
              AND p.status = 'publish'
              AND m.meta_key = 'class'
              AND m.meta_value = '$student_class_id'
            ORDER BY p.title ASC";
    $result = mysqli_query($db_conn, $sql);
    while($row = mysqli_fetch_object($result)) {
      $subjects[] = $row;
    }
  }
?>

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-dark">Subjects</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Student</a></li>
              <li class="breadcrumb-item active">Subjects</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="card">
          <div class="card-body">
            <div class="table-responsive bg-white">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th>S.No.</th>
                    <th>Subject</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    if(empty($subjects)) {
                      echo '<tr><td colspan="2" class="text-center">No subjects found.</td></tr>';
                    } else {
                      $count = 1;
                      foreach($subjects as $subject) {
                        echo '<tr>';
                        echo '<td>' . $count++ . '</td>';
                        echo '<td>' . htmlspecialchars($subject->title) . '</td>';
                        echo '</tr>';
                      }
                    }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </div><!--/. container-fluid -->
    </section>
    <!-- /.content -->
<?php include('footer.php') ?>