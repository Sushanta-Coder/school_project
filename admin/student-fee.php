<?php include('../includes/config.php') ?>
<?php include('header.php') ?>
<?php include('sidebar.php') ?>


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

        <?php if (isset($_GET['action']) && $_GET['action'] == 'view') {
            $std_id = isset($_GET['std_id']) ? $_GET['std_id'] : '';
            $usermeta = get_user_metadata($std_id);
        ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Student Detail</h3>
                </div>
                <div class="card-body">
                    <strong>Name: </strong> <?php echo get_users(array('id' => $std_id))[0]->name ?> <br>
                    <strong>Class: </strong> <?php
                        $class_title = 'N/A';
                        if (!empty($usermeta['class'])) {
                            $class_post = get_post(['id' => $usermeta['class']]);
                            if ($class_post && isset($class_post->title)) {
                                $class_title = $class_post->title;
                            }
                        }
                        echo $class_title;
                    ?>

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
                            $sql = "SELECT p.id as payment_id, m.meta_value as `month` FROM `posts` as p JOIN `metadata` as m ON p.id = m.item_id WHERE p.type = 'payment' AND p.author = $std_id AND m.meta_key = 'month' AND year(p.publish_date) = '$current_year' ORDER BY p.id DESC";

                            $query = mysqli_query($db_conn, $sql);
                            $paid_fees = [];
                            while ($row = mysqli_fetch_object($query)) {
                                $month_key = strtolower(trim($row->month));
                                if (!isset($paid_fees[$month_key])) {
                                    $paid_fees[$month_key] = (int)$row->payment_id;
                                }
                            }
                            //  echo '<pre>';

                            //                    print_r($paid_fees);
                            //                    echo '</pre>'; 
                            $months = array('january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december');
                            foreach ($months as $key => $value) {
                                $highlight = '';

                                $paid = false;
                                $payment_id = 0;
                                if (isset($paid_fees[$value])) {
                                    $paid = true;
                                    $payment_id = (int)$paid_fees[$value];
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
                                            <a href="?action=view-invoice&payment_id=<?php echo $payment_id ?>&std_id=<?php echo $std_id ?>" class="btn btn-sm btn-primary"><i class="fa fa-eye fa-fw"></i> View</a>
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

        <?php } elseif( isset($_GET['action']) && $_GET['action'] == 'view-invoice') {
            $payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;
            $std_id = isset($_GET['std_id']) ? (int)$_GET['std_id'] : 0;

            $payment_post = [];
            $payment_meta = [];
            if($payment_id > 0){
                $payment_query = mysqli_query($db_conn, "SELECT * FROM posts WHERE id = '$payment_id' AND type = 'payment' LIMIT 1");
                $payment_post = mysqli_fetch_assoc($payment_query);
                if(!empty($payment_post)){
                    $meta_query = mysqli_query($db_conn, "SELECT * FROM metadata WHERE item_id = '$payment_id'");
                    while($meta = mysqli_fetch_assoc($meta_query)){
                        $payment_meta[$meta['meta_key']] = $meta['meta_value'];
                    }
                }
            }

            if(empty($payment_post)){
                echo '<div class="alert alert-warning">Invoice not found.</div>';
            }else{
                $student_id = !empty($payment_post['author']) ? (int)$payment_post['author'] : $std_id;
                $student_data = get_user_data($student_id);
                $student_meta = get_user_metadata($student_id);
                $class_post = !empty($student_meta['class']) ? get_post(['id' => $student_meta['class']]) : null;

                $invoice_number = 'INV-' . str_pad((string)$payment_post['id'], 5, '0', STR_PAD_LEFT);
                $invoice_date = !empty($payment_post['publish_date']) ? date('d M, Y', strtotime($payment_post['publish_date'])) : date('d M, Y');
                $invoice_month = !empty($payment_meta['month']) ? ucwords($payment_meta['month']) : '-';
                $invoice_amount = !empty($payment_meta['amount']) ? (float)$payment_meta['amount'] : 0;
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
                                </div>

                                <hr class="my-4">

                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="text-muted">
                                            <h5 class="font-size-16 mb-3">Billed To:</h5>
                                            <h5 class="font-size-15 mb-2"><?php echo isset($student_data['name']) ? $student_data['name'] : '-'; ?></h5>
                                            <p class="mb-1">Class: <?php echo (!empty($class_post) && isset($class_post->title)) ? $class_post->title : '-'; ?></p>
                                            <p class="mb-1"><?php echo isset($student_data['email']) ? $student_data['email'] : ''; ?></p>
                                        </div>
                                    </div>
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
                                </div>

                                <div class="py-2">
                                    <h5 class="font-size-15">Fee Summary</h5>
                                    <div class="table-responsive">
                                        <table class="table align-middle table-nowrap table-centered mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="width: 70px;">No.</th>
                                                    <th>Fees</th>
                                                    <th class="text-end" style="width: 120px;">Price</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <th scope="row">01</th>
                                                    <td><?php echo $invoice_month; ?> Tuition Fee</td>
                                                    <td class="text-end">Rs. <?php echo number_format($invoice_amount, 2); ?></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row" colspan="2" class="border-0 text-end">Total</th>
                                                    <td class="border-0 text-end"><h4 class="m-0 fw-semibold">Rs. <?php echo number_format($invoice_amount, 2); ?></h4></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-print-none mt-4">
                                        <div class="float-end">
                                            <a href="javascript:window.print()" class="btn btn-success me-1"><i class="fa fa-print"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php }
        ?>
        <?php } else { ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>S.no.</th>
                        <th>Student Name</th>
                        <th>Last Payment</th>
                        <th>Due Payment</th>
                        <th>Fee Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $students = get_users(array('type' => 'student'));
                    foreach ($students as $key => $student) { ?>
                        <tr>
                            <td><?php echo ++$key ?></td>
                            <td><?php echo $student->name ?></td>
                            <td>4/12</td>
                            <td></td>
                            <td></td>
                            <td>
                                <a href="?action=view&std_id=<?php echo $student->id ?>" class="btn btn-sm btn-info"><i class="fa fa-eye fa-fw"></i> View</a>
                                <!-- <a href="" class="btn btn-xs btn-dark"><i class="fa fa-pencil-alt fa-fw"></i></a> -->
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </div><!--/. container-fluid -->
</section>
<!-- /.content -->
<?php include('footer.php') ?>