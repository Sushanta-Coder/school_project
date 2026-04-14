<?php include('../includes/config.php') ?>

<?php

if(isset($_POST['submit']))
{
  $title = isset($_POST['title'])?$_POST['title']:'';
  $from = isset($_POST['from'])?$_POST['from']:'';
  $to = isset($_POST['to'])?$_POST['to']:'';
  $status = 'publish';
  $type = 'period';
  $date_add = date('Y-m-d g:i:s');

  $query = mysqli_query($db_conn, "INSERT INTO `posts` (`title`,`status`,`publish_date`,`type`) VALUES ('$title','$status','$date_add','$type') ");

  if($query)
  {
    $item_id = mysqli_insert_id($db_conn);
  }

  mysqli_query($db_conn, "INSERT INTO `metadata` (`meta_key`,`meta_value`,`item_id`) VALUES ('from','$from','$item_id') ");
  mysqli_query($db_conn, "INSERT INTO `metadata` (`meta_key`,`meta_value`,`item_id`) VALUES ('to','$to','$item_id') ");

  header('Location: periods.php');
}

if(isset($_POST['update_period']))
{
  $period_id = isset($_POST['period_id']) ? (int)$_POST['period_id'] : 0;
  $title = isset($_POST['title'])?$_POST['title']:'';
  $from = isset($_POST['from'])?$_POST['from']:'';
  $to = isset($_POST['to'])?$_POST['to']:'';

  if($period_id > 0)
  {
    mysqli_query($db_conn, "UPDATE `posts` SET `title` = '$title' WHERE `id` = '$period_id' AND `type` = 'period'");

    $from_meta = get_metadata($period_id, 'from');
    $to_meta = get_metadata($period_id, 'to');

    if(!empty($from_meta))
    {
      mysqli_query($db_conn, "UPDATE `metadata` SET `meta_value` = '$from' WHERE `item_id` = '$period_id' AND `meta_key` = 'from'");
    }
    else
    {
      mysqli_query($db_conn, "INSERT INTO `metadata` (`meta_key`,`meta_value`,`item_id`) VALUES ('from','$from','$period_id') ");
    }

    if(!empty($to_meta))
    {
      mysqli_query($db_conn, "UPDATE `metadata` SET `meta_value` = '$to' WHERE `item_id` = '$period_id' AND `meta_key` = 'to'");
    }
    else
    {
      mysqli_query($db_conn, "INSERT INTO `metadata` (`meta_key`,`meta_value`,`item_id`) VALUES ('to','$to','$period_id') ");
    }
  }

  header('Location: periods.php');
}

$edit_period = null;
$edit_from = '';
$edit_to = '';
if(isset($_GET['action']) && $_GET['action'] == 'edit' && !empty($_GET['id']))
{
  $edit_id = (int)$_GET['id'];
  $edit_period = get_post(array('id' => $edit_id, 'type' => 'period'));

  if($edit_period)
  {
    $edit_from_data = get_metadata($edit_id, 'from');
    $edit_to_data = get_metadata($edit_id, 'to');
    $edit_from = !empty($edit_from_data) ? $edit_from_data[0]->meta_value : '';
    $edit_to = !empty($edit_to_data) ? $edit_to_data[0]->meta_value : '';
  }
}

?>
<?php include('header.php') ?>
<?php include('sidebar.php') ?>

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-dark">Manage Periods</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Admin</a></li>
              <li class="breadcrumb-item active">Periods</li>
            </ol>
          </div><!-- /.col -->
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
                Periods
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
                        <th>From</th>
                        <th>To</th>
                        <th>Action</th>
                      </tr>
                    </thead>

                    <tbody>
                      <?php
                      $count = 1;
                      $args = array(
                        'type' => 'period',
                        'status' => 'publish',
                      );
                      $periods = get_posts($args);
                      foreach($periods as $period) {
                        $from = get_metadata($period->id, 'from')[0]->meta_value;
                        $to = get_metadata($period->id, 'to')[0]->meta_value;
                        ?>
                      <tr>
                        <td><?=$count++?></td>
                        <td><?=$period->title?></td>
                        <td><?php echo date('h:i A',strtotime($from)) ?></td>
                        <td><?php echo date('h:i A',strtotime($to)) ?></td>
                        <td>
                          <a href="periods.php?action=edit&id=<?=$period->id?>" class="btn btn-sm btn-info">
                            <i class="fa fa-edit"></i> Edit
                          </a>
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
                  <?php echo $edit_period ? 'Edit Period' : 'Add New Period'; ?>
                </h3>
              </div>
              <div class="card-body">
                <form action="" method="POST">
                  <?php if($edit_period) { ?>
                    <input type="hidden" name="period_id" value="<?php echo $edit_period->id; ?>">
                  <?php } ?>
                  <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" name="title" placeholder="Title" required class="form-control" value="<?php echo $edit_period ? $edit_period->title : ''; ?>">
                  </div>
                  <div class="form-group">
                    <label for="title">From</label>
                    <input type="time" name="from" placeholder="From" required class="form-control" value="<?php echo $edit_from; ?>">
                  </div>
                  <div class="form-group">
                    <label for="title">To</label>
                    <input type="time" name="to" placeholder="To" required class="form-control" value="<?php echo $edit_to; ?>">
                  </div>
                  <?php if($edit_period) { ?>
                    <a href="periods.php" class="btn btn-secondary">Cancel</a>
                    <button name="update_period" class="btn btn-success float-right">
                      Update
                    </button>
                  <?php } else { ?>
                    <button name="submit" class="btn btn-success float-right">
                      Submit
                    </button>
                  <?php } ?>
                </form>
              </div>
            </div>
          </div>
        </div>   

      </div><!--/. container-fluid -->
    </section>
    <!-- /.content -->
<?php include('footer.php') ?>