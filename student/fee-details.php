<?php ob_start(); ?>
<?php include('../includes/config.php') ?>
<?php include('header.php') ?>
<?php include('sidebar.php') ?>


<?php
$success_msg =  false;
$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$std_id = $user_id;

function get_payment_record($payment_id = 0, $student_id = 0, $month = '')
{
    global $db_conn;

    $payment_id = (int) $payment_id;
    $student_id = (int) $student_id;
    $month = trim((string) $month);

    $record = [];

    if ($payment_id > 0) {
        $post_query = mysqli_query($db_conn, "SELECT * FROM posts WHERE id = '$payment_id' AND type = 'payment' LIMIT 1");
        $post = mysqli_fetch_assoc($post_query);

        if ($post) {
            $record['post'] = $post;
            $meta_query = mysqli_query($db_conn, "SELECT * FROM metadata WHERE item_id = '$payment_id'");
            while ($row = mysqli_fetch_assoc($meta_query)) {
                $record['meta'][$row['meta_key']] = $row['meta_value'];
            }
        }

        return $record;
    }

    if ($student_id > 0 && !empty($month)) {
        $safe_month = mysqli_real_escape_string($db_conn, strtolower($month));
        $post_query = mysqli_query($db_conn, "SELECT p.* FROM posts p INNER JOIN metadata m ON p.id = m.item_id WHERE p.type = 'payment' AND p.author = '$student_id' AND m.meta_key = 'month' AND LOWER(m.meta_value) = '$safe_month' ORDER BY p.id DESC LIMIT 1");
        $post = mysqli_fetch_assoc($post_query);

        if ($post) {
            $record['post'] = $post;
            $payment_id = (int) $post['id'];
            $meta_query = mysqli_query($db_conn, "SELECT * FROM metadata WHERE item_id = '$payment_id'");
            while ($row = mysqli_fetch_assoc($meta_query)) {
                $record['meta'][$row['meta_key']] = $row['meta_value'];
            }
        }
    }

    return $record;
}

if (isset($_POST['form_submitted'])) {

    $status = 'success';
    $firstname = isset($_POST["firstname"]) ? $_POST["firstname"] : '';
    $amount = isset($_POST["amount"]) ? $_POST["amount"] : '';
    $email = isset($_POST["email"]) ? $_POST["email"] : '';
    $month = isset($_POST["month"]) ? trim($_POST["month"]) : '';

    if ($user_id > 0 && !empty($month)) {
        $title = mysqli_real_escape_string($db_conn, $month . ' - Fee');
        $publish_date = date('Y-m-d H:i:s');
        $query = mysqli_query($db_conn, "INSERT INTO `posts` (`title`, `type`,`description`, `status`, `author`,`parent`,`publish_date`) VALUES ('$title', 'payment','','$status', $user_id,0,'$publish_date')") or die(mysqli_error($db_conn));

        if ($query) {
            $item_id = mysqli_insert_id($db_conn);
            $payment_data = array(
                'amount' => $amount,
                'status' => $status,
                'student_id' => $user_id,
                'month' => $month,
                'firstname' => $firstname,
                'email' => $email
            );

            foreach ($payment_data as $key => $value) {
                $meta_key = mysqli_real_escape_string($db_conn, $key);
                $meta_value = mysqli_real_escape_string($db_conn, $value);
                mysqli_query($db_conn, "INSERT INTO `metadata` (`item_id`, `meta_key`, `meta_value`) VALUES ('$item_id', '$meta_key', '$meta_value')");
            }

            header('Location: fee-details.php?action=view-invoice&payment_id=' . $item_id);
            exit;
        }
    }
}

if (isset( $_GET['action'] ) && $_GET['action'] == 'view-invoice') {
    $payment_id = isset($_GET['payment_id']) ? (int) $_GET['payment_id'] : 0;
    $month = isset($_GET['month']) ? $_GET['month'] : '';
    $payment = get_payment_record($payment_id, $user_id, $month);
    $payment_post = isset($payment['post']) ? $payment['post'] : [];
    $payment_meta = isset($payment['meta']) ? $payment['meta'] : [];
    $student = get_user_data($user_id);
    $student_meta = get_user_metadata($user_id);
    $class = isset($student_meta['class']) ? get_post(['id' => $student_meta['class']]) : null;
    $invoice_number = !empty($payment_post['id']) ? 'INV-' . str_pad((string) $payment_post['id'], 5, '0', STR_PAD_LEFT) : 'INV-00000';
    $invoice_date = !empty($payment_post['publish_date']) ? date('d M, Y', strtotime($payment_post['publish_date'])) : date('d M, Y');
    $invoice_month = !empty($payment_meta['month']) ? ucwords($payment_meta['month']) : ucwords($month);
    $invoice_amount = !empty($payment_meta['amount']) ? (float) $payment_meta['amount'] : 0;
    $invoice_status = !empty($payment_meta['status']) ? $payment_meta['status'] : 'success';
    ?>


    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="invoice-title">
                            <h4 class="float-end font-size-15">Invoice #<?php echo $invoice_number; ?> <span class="badge bg-success font-size-12 ms-2"><?php echo ($invoice_status === 'success') ? 'Paid' : ucwords($invoice_status); ?></span></h4>
                            <div class="mb-4">
                                <h2 class="mb-1 text-muted">Smart Education Management</h2>
                            </div>
                            <div class="text-muted">
                                <p class="mb-1">Bharatpur, Chitwan</p>
                                <p class="mb-1"><i class="uil uil-envelope-alt me-1"></i>sems@example.com</p>
                                <p><i class="uil uil-phone me-1"></i> n568975121</p>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            <div class="col-sm-6">
                                <div class="text-muted">
                                    <h5 class="font-size-16 mb-3">Billed To:</h5>
                                    <h5 class="font-size-15 mb-2"><?php echo !empty($student['name']) ? $student['name'] : '-'; ?></h5>
                                    <p class="mb-1">Class: <?php echo !empty($class) ? $class->title : '-'; ?></p>
                                    <p class="mb-1"><?php echo !empty($student['email']) ? $student['email'] : ''; ?></p>
                                    <p><?php echo !empty($student['mobile']) ? $student['mobile'] : ''; ?></p>
                                </div>
                            </div>
                            <!-- end col -->
                            <div class="col-sm-6">
                                <div class="text-muted text-sm-end">
                                    <div>
                                        <h5 class="font-size-15 mb-1">Invoice No:</h5>
                                        <p><?php echo $invoice_number; ?></p>
                                    </div>
                                    <div class="mt-4">
                                        <h5 class="font-size-15 mb-1">Invoice Date:</h5>
                                        <p><?php echo $invoice_date; ?></p>
                                    </div>
                                    <div class="mt-4">
                                        <h5 class="font-size-15 mb-1">Fee Month:</h5>
                                        <p><?php echo $invoice_month; ?></p>
                                    </div>
                                </div>
                            </div>
                            <!-- end col -->
                        </div>
                        <!-- end row -->

                        <div class="py-2">
                            <h5 class="font-size-15">Order Summary</h5>

                            <div class="table-responsive">
                                <table class="table align-middle table-nowrap table-centered mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 70px;">No.</th>
                                            <th>Fees</th>
                                            <th class="text-end" style="width: 120px;">Price</th>
                                        </tr>
                                    </thead><!-- end thead -->
                                    <tbody>
                                        <tr>
                                            <th scope="row">01</th>
                                            <td>
                                                <div>
                                                    <h5 class="text-truncate font-size-14 mb-1"><?php echo !empty($invoice_month) ? $invoice_month . ' Tuition Fee' : 'Tuition Fee'; ?></h5>
                                                    <!-- <p class="text-muted mb-0">Watch, Black</p> -->
                                                </div>
                                            </td>
                                            <td class="text-end">Rs. <?php echo number_format($invoice_amount, 2); ?></td>
                                        </tr>
                                        <!-- end tr -->
                                        <tr>
                                            <th scope="row" colspan="2" class="text-end">Sub Total</th>
                                            <td class="text-end">Rs. <?php echo number_format($invoice_amount, 2); ?></td>
                                        </tr>
                                        
                                        <!-- end tr -->
                                        <tr>
                                            <th scope="row" colspan="2" class="border-0 text-end">Total</th>
                                            <td class="border-0 text-end">
                                                <h4 class="m-0 fw-semibold">Rs. <?php echo number_format($invoice_amount, 2); ?></h4>
                                            </td>
                                        </tr>
                                        <!-- end tr -->
                                    </tbody><!-- end tbody -->
                                </table><!-- end table -->
                            </div><!-- end table responsive -->
                            <div class="d-print-none mt-4">
                                <div class="float-end">
                                    <a href="javascript:window.print()" class="btn btn-success me-1"><i class="fa fa-print"></i></a>
                                    <a href="#" class="btn btn-primary w-md">Send</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- end col -->
        </div>
    </div>
<?php
} else {

?>



    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Manage Student Fee Details</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Admin</a></li>
                        <li class="breadcrumb-item active">Student Fee Details</li>
                    </ol>
                </div><!-- /.col -->


            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <?php if ($success_msg) { ?>
                <div class="alert alert-success" role="alert">
                    Payment has been completed, Thank You!
                </div>
            <?php } ?>

            <?php
            $usermeta = get_user_metadata($std_id);
            $student = get_user_data($std_id);

            $class = get_post(['id' => $usermeta['class']]);
            ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Student Detail</h3>
                </div>
                <div class="card-body">
                    <strong>Name: </strong> <?php echo get_users(array('id' => $std_id))[0]->name ?> <br>
                    <strong>Class: </strong> <?php echo $class->title ?>

                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tution Fee</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Month</th>
                                <th>Fee Status</th>

                                
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php

                            $current_year = date('Y');
                            $sql = "SELECT m.meta_value as `month` FROM `posts` as p JOIN `metadata` as m ON p.id = m.item_id WHERE p.type = 'payment' AND p.author = $user_id AND m.meta_key = 'month' AND year(p.publish_date) = $current_year";

                            $query = mysqli_query($db_conn, $sql);
                            $paid_fees = [];
                            while ($row = mysqli_fetch_object($query)) {

                                $paid_fees[] = strtolower($row->month);
                            }
                            //  echo '<pre>';

                            //                    print_r($paid_fees);
                            //                    echo '</pre>'; 
                            $months = array('january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december');
                            foreach ($months as $key => $value) {
                                $highlight = '';

                                $paid = false;
                                if (in_array($value, $paid_fees)) {
                                    $paid = true;
                                    $highlight = 'class="bg-success"';
                                }
                                //   if(date('F') == ucwords($value))
                                //   {
                                //     $highlight = 'class="bg-success"';
                                //   }
                            ?>
                                <tr>
                                    <td><?php echo ++$key ?></td>
                                    <td><?php echo ucwords($value) ?></td>
                                    <td <?php echo $highlight ?>>
                                        <?php

                                        echo ($paid) ? "Paid" : "Pending";

                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($paid) { ?>
                                            <a href="?action=view-invoice&month=<?php echo $value ?>&std_id=<?php echo $std_id ?>" class="btn btn-sm btn-primary"><i class="fa fa-eye fa-fw"></i> View</a>
                                        <?php } else { ?>
                                            <a href="#" data-toggle="modal" data-month="<?php echo ucwords($value) ?>" data-target="#paynow-popup" class="btn btn-sm btn-warning paynow-btn"><i class="fa fa-money-check-alt fa-fw"></i> Pay Now</a>
                                        <?php } ?>


                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div><!--/. container-fluid -->
    </section>
    <!-- /.content -->


    <!-- Modal -->
    <div class="modal fade" id="paynow-popup" tabindex="-1" role="dialog" aria-labelledby="paynow-popupLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paynow-popupLabel">Paynow</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <input type="hidden" name="amount" readonly="readonly" value="500" />
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="">Full Name</label>
                                    <input type="text" name="firstname" readonly class="form-control" value="<?php echo isset($student['name']) ? $student['name'] : ''; ?>">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="">Email Address</label>
                                    <input type="email" name="email" readonly class="form-control" value="<?php echo isset($student['email']) ? $student['email'] : ''; ?>">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="">Phone</label>
                                    <input type="text" name="phone" readonly class="form-control" value="1234567890">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="">Months</label>
                                    <input type="text" name="month" readonly class="form-control" id="month" value="">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <h3><i class="fa fa-rupee-sign"></i> 500.00</h3>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <button type="submit" name="form_submitted" class="btn btn-success">Confirm & Pay</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <script>
        jQuery(document).on('click', '.paynow-btn', function() {
            var month = jQuery(this).data('month');

            jQuery('#month').val(month)
        })
    </script>

<?php
}
include('footer.php') ?>