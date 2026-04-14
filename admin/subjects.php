<?php include('../includes/config.php') ?>
<?php
  $selected_class = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

  if (isset($_POST['delete_subject']) && isset($_POST['subject_id'])) {
    $subject_id = intval($_POST['subject_id']);
    $return_class_id = isset($_POST['return_class_id']) ? intval($_POST['return_class_id']) : 0;

    if ($subject_id > 0) {
      mysqli_query($db_conn, "DELETE FROM metadata WHERE item_id = '$subject_id'") or die(mysqli_error($db_conn));
      mysqli_query($db_conn, "DELETE FROM posts WHERE id = '$subject_id' AND type = 'subject'") or die(mysqli_error($db_conn));
      $_SESSION['success_msg'] = 'Subject deleted successfully';
    }

    header('Location: subjects.php?class_id=' . $return_class_id);
    exit;
  }

  if(isset($_POST['submit']))
  {
    if(isset($_POST['subject']) && !empty($_POST['subject']) && isset($_POST['class']) && !empty($_POST['class']))
    {
      $subject_name = mysqli_real_escape_string($db_conn, $_POST['subject']);
      $class_id = intval($_POST['class']);
      $today = date('Y-m-d H:i:s');

      $insert_query = "INSERT INTO posts (`title`, `type`, `status`, `publish_date`) 
                       VALUES ('$subject_name', 'subject', 'publish', '$today')";

      if(mysqli_query($db_conn, $insert_query))
      {
        $post_id = mysqli_insert_id($db_conn);
        mysqli_query($db_conn, "INSERT INTO metadata (item_id, meta_key, meta_value) VALUES ($post_id, 'class', '$class_id')");

        $_SESSION['success_msg'] = 'Subject has been added successfully';
        header('Location: subjects.php?class_id=' . $class_id);
        exit;
      }
      else
      {
        $_SESSION['error_msg'] = 'Error adding subject: ' . mysqli_error($db_conn);
      }
    }
    else
    {
      $_SESSION['error_msg'] = 'Please select a class and provide a subject name.';
      header('Location: subjects.php');
      exit;
    }
  }

  function get_subjects_by_class($class_id)
  {
    global $db_conn;
    $subjects = array();
    $class_id = intval($class_id);
    if($class_id > 0)
    {
      $sql = "SELECT p.* FROM posts p 
              JOIN metadata m ON p.id = m.item_id 
              WHERE p.type = 'subject' AND p.status = 'publish' 
                AND m.meta_key = 'class' AND m.meta_value = '$class_id' 
              ORDER BY p.publish_date DESC";
      $result = mysqli_query($db_conn, $sql);
      while($row = mysqli_fetch_object($result)) {
        $subjects[] = $row;
      }
    }
    return $subjects;
  }

  $classes = get_posts(array('type' => 'class', 'status' => 'publish'));
  $subjects = get_subjects_by_class($selected_class);
?>
<?php include('header.php') ?>
<?php include('sidebar.php') ?>

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-dark">Manage Subjects </h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Admin</a></li>
              <li class="breadcrumb-item active">Subjects</li>
            </ol>
          </div><!-- /.col -->
          <?php
           
            if(isset($_SESSION['success_msg']))
            {?>
              <div class="col-12">
                <small class="text-success" style="font-size:16px"><?=$_SESSION['success_msg']?></small>
              </div>
            <?php 
              unset($_SESSION['success_msg']);
            }
            if(isset($_SESSION['error_msg']))
            {?>
              <div class="col-12">
                <small class="text-danger" style="font-size:16px"><?=$_SESSION['error_msg']?></small>
              </div>
            <?php 
              unset($_SESSION['error_msg']);
            }
          ?>
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <!-- Info boxes -->
        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header py-2">
                        <h3 class="card-title">
                            Add New Subject
                        </h3>
                    </div>
                    <div class="card-body" >
                        <form action="" method="post">
                            <div class="form-group">
                                <label for="class">Select Class</label>
                                <select required name="class" id="class" class="form-control">
                                    <option value="">-Select Class-</option>
                                    <?php foreach ($classes as $class) { ?>
                                    <option value="<?php echo $class->id ?>" <?=($selected_class == $class->id ? 'selected' : '')?>><?php echo $class->title ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="subject">Subject Name</label>
                                <input required type="text" name="subject" id="subject" placeholder="Subject Name" class="form-control">
                            </div>
                            <div class="form-group">
                                <input type="submit" name="submit" id="submit" value="Submit" class="btn btn-primary">
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header py-2">
                        <h3 class="card-title">
                        Subjects
                        </h3>
                    </div>
                    <div class="card-body">
                        <form action="" method="get" class="form-inline mb-3">
                            <div class="form-group mr-2">
                                <label for="filter_class" class="mr-2">Select Class</label>
                                <select name="class_id" id="filter_class" class="form-control" onchange="this.form.submit()">
                                    <option value="">-Select Class-</option>
                                    <?php foreach ($classes as $class) { ?>
                                    <option value="<?=$class->id?>" <?=($selected_class == $class->id ? 'selected' : '')?>><?=$class->title?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </form>
                        <?php if($selected_class === 0) { ?>
                          <div class="alert alert-info">Select a class to view its subjects.</div>
                        <?php } else { ?>
                        <div class="table-responsive bg-white">
                            <table class="table table-bordered">
                                <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Name</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if(empty($subjects)) {
                                      echo '<tr><td colspan="4" class="text-center">No subjects found for this class.</td></tr>';
                                    } else {
                                      $count = 1;
                                      foreach($subjects as $subject){ ?>
                                        <tr>
                                          <td><?=$count++?></td>
                                          <td><?=$subject->title?></td>
                                          <td><?=$subject->publish_date?></td>
                                          <td>
                                            <form action="" method="post" onsubmit="return confirm('Are you sure you want to delete this subject?');" style="margin:0;">
                                              <input type="hidden" name="subject_id" value="<?=$subject->id?>">
                                              <input type="hidden" name="return_class_id" value="<?=$selected_class?>">
                                              <button type="submit" name="delete_subject" class="btn btn-sm btn-danger">
                                                <i class="fa fa-trash"></i>
                                              </button>
                                            </form>
                                          </td>
                                        </tr>
                                      <?php }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->
      </div><!--/. container-fluid -->
    </section>
    <!-- /.content -->
<?php include('footer.php') ?>