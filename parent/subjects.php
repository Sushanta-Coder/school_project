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

$selected_child = $selected_child_id > 0 ? get_user_data($selected_child_id) : [];
$child_meta = $selected_child_id > 0 ? get_user_metadata($selected_child_id) : [];
$student_class_id = isset($child_meta['class']) ? (int)$child_meta['class'] : 0;
$class_post = $student_class_id > 0 ? get_post(['id' => $student_class_id]) : null;

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

    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-dark">Subjects</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Parent</a></li>
              <li class="breadcrumb-item active">Subjects</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        <?php if (empty($children)) { ?>
          <div class="alert alert-info">No child is linked with this parent account.</div>
        <?php } else { ?>

        <?php if (count($children) > 1) { ?>
          <div class="card">
            <div class="card-body">
              <form method="get" action="" class="form-inline">
                <label class="mr-2" for="child_id">Select Child:</label>
                <select name="child_id" id="child_id" class="form-control mr-2">
                  <?php foreach($children as $child_id) {
                    $child = get_user_data($child_id);
                  ?>
                    <option value="<?=$child_id?>" <?=$selected_child_id === $child_id ? 'selected' : ''?>>
                      <?=!empty($child['name']) ? htmlspecialchars($child['name']) : ('Student #' . $child_id)?>
                    </option>
                  <?php } ?>
                </select>
                <button type="submit" class="btn btn-primary">View</button>
              </form>
            </div>
          </div>
        <?php } ?>

        <div class="card">
          <div class="card-body">
            <p class="mb-3">
              <strong>Child:</strong> <?=!empty($selected_child['name']) ? htmlspecialchars($selected_child['name']) : 'N/A'?>
              <span class="ml-3"><strong>Class:</strong> <?=!empty($class_post) ? htmlspecialchars($class_post->title) : 'N/A'?></span>
            </p>
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
                      echo '<tr><td colspan="2" class="text-center">No subjects found for this class.</td></tr>';
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

        <?php } ?>
      </div>
    </section>
<?php include('footer.php') ?>