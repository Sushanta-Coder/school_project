<?php include('../includes/config.php') ?>
<?php

  // Handle delete request - MUST BE BEFORE ANY OUTPUT
  if(isset($_REQUEST['delete_id']))
  {
    $delete_id = intval($_REQUEST['delete_id']);
    $delete_query = mysqli_query($db_conn, "DELETE FROM posts WHERE id = $delete_id AND type = 'section'");
    
    if($delete_query)
    {
      $_SESSION['success_msg'] = 'Section deleted successfully';
      header('Location: sections.php');
      exit;
    }
    else
    {
      $_SESSION['error_msg'] = 'Error deleting section';
    }
  }

  if(isset($_POST['submit']))
  {
    $title = $_POST['title'];
    $_SESSION['success_msg'] = 'Section added successfully';

    $query = mysqli_query($db_conn, "INSERT INTO `posts`(`author`, `title`, `description`, `type`, `status`,`parent`) VALUES ('1','$title','description','section','publish',0)") or die('DB error');
    header('Location: sections.php');
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
            <h1 class="m-0 text-dark">Manage Sections</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Admin</a></li>
              <li class="breadcrumb-item active">Sections</li>
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
          ?>
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class='col-lg-8'>
            
            <!-- Info boxes -->
            <div class="card">
              <div class="card-header py-2">
                <h3 class="card-title">
                  Sections
                </h3>
                <div class="card-tools">
                </div>
              </div>
              <div class="card-body">
                <div class="table-responsive bg-white">
                  <table class="table table-bordered">
                    <thead>
                      <tr>
                        <th>S.No.</th>
                        <th>Title</th>
                        <th>Action</th>
                      </tr>
                    </thead>

                    <tbody>
                      <?php
                      $count = 1;
                      $args = array(
                        'type' => 'section',
                        'status' => 'publish',
                      );
                      $sections = get_posts($args);
                      foreach($sections as $section) {?>
                      <tr>
                        <td><?=$count++?></td>
                        <td><?=$section->title?></td>
                        <td>
                          <a href="?delete_id=<?=$section->id?>" class="btn btn-danger btn-xs" onclick="return confirm('Are you sure you want to delete this section?')"><i class="fa fa-trash mr-2"></i>Delete</a>
                        </td>
                      </tr>

                      <?php } ?>

                    </tbody>


                  </table>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-4">
            <!-- Info boxes -->
            <div class="card">
              <div class="card-header py-2">
                <h3 class="card-title">
                  Add New Section
                </h3>
              </div>
              <div class="card-body">
                <form action="" method="POST">
                  <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" name="title" placeholder="Title" required class="form-control">
                  </div>
                  
                  <button name="submit" class="btn btn-success float-right">
                    Submit
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>   

      </div><!--/. container-fluid -->
    </section>
    <!-- /.content -->
<?php include('footer.php') ?>