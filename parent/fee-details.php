<?php ob_start(); ?>
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
            if ($child_id > 0) {
                $children[] = $child_id;
            }
        }
    }
}

$selected_child_id = isset($_GET['child_id']) ? (int)$_GET['child_id'] : 0;
if (!in_array($selected_child_id, $children, true)) {
    $selected_child_id = !empty($children) ? (int)$children[0] : 0;
}

$all_months = array('january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december');

$selected_student = $selected_child_id > 0 ? get_user_data($selected_child_id) : [];
$selected_student_meta = $selected_child_id > 0 ? get_user_metadata($selected_child_id) : [];
$class = (!empty($selected_student_meta['class'])) ? get_post(['id' => $selected_student_meta['class']]) : null;

function get_payment_record_for_student($payment_id = 0, $student_id = 0, $month = '')
{
    global $db_conn;

    $payment_id = (int)$payment_id;
    $student_id = (int)$student_id;
    $month = trim((string)$month);

    $record = [];

    if ($student_id <= 0) {
        return $record;
    }

    if ($payment_id > 0) {
        $post_query = mysqli_query($db_conn, "SELECT * FROM posts WHERE id = '$payment_id' AND type = 'payment' AND author = '$student_id' LIMIT 1");
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

    if (!empty($month)) {
        $safe_month = mysqli_real_escape_string($db_conn, strtolower($month));
        $post_query = mysqli_query($db_conn, "SELECT p.* FROM posts p INNER JOIN metadata m ON p.id = m.item_id WHERE p.type = 'payment' AND p.author = '$student_id' AND m.meta_key = 'month' AND LOWER(m.meta_value) = '$safe_month' ORDER BY p.id DESC LIMIT 1");
        $post = mysqli_fetch_assoc($post_query);

        if ($post) {
            $record['post'] = $post;
            $payment_id = (int)$post['id'];
            $meta_query = mysqli_query($db_conn, "SELECT * FROM metadata WHERE item_id = '$payment_id'");
            while ($row = mysqli_fetch_assoc($meta_query)) {
                $record['meta'][$row['meta_key']] = $row['meta_value'];
            }
        }
    }

    return $record;
}

if (isset($_POST['form_submitted'])) {
    $posted_child_id = isset($_POST['child_id']) ? (int)$_POST['child_id'] : 0;
    $month = isset($_POST['month']) ? strtolower(trim((string)$_POST['month'])) : '';
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 500;

    if (in_array($posted_child_id, $children, true) && in_array($month, $all_months, true)) {
        $existing_payment = get_payment_record_for_student(0, $posted_child_id, $month);
        if (!empty($existing_payment['post']['id'])) {
            $existing_payment_id = (int)$existing_payment['post']['id'];
            header('Location: fee-details.php?action=view-invoice&payment_id=' . $existing_payment_id . '&child_id=' . $posted_child_id);
            exit;
        }

        $student_for_payment = get_user_data($posted_child_id);
        $status = 'success';
        $title = mysqli_real_escape_string($db_conn, ucwords($month) . ' - Fee');
        $publish_date = date('Y-m-d H:i:s');

        $payment_query = mysqli_query($db_conn, "INSERT INTO posts (title, type, description, status, author, parent, publish_date) VALUES ('$title', 'payment', '', '$status', '$posted_child_id', '$parent_id', '$publish_date')");
        if ($payment_query) {
            $payment_id = mysqli_insert_id($db_conn);
            $payment_data = array(
                'amount' => $amount > 0 ? $amount : 500,
                'status' => $status,
                'student_id' => $posted_child_id,
                'month' => $month,
                'firstname' => !empty($student_for_payment['name']) ? $student_for_payment['name'] : '',
                'email' => !empty($student_for_payment['email']) ? $student_for_payment['email'] : ''
            );

            foreach ($payment_data as $key => $value) {
                $meta_key = mysqli_real_escape_string($db_conn, (string)$key);
                $meta_value = mysqli_real_escape_string($db_conn, (string)$value);
                mysqli_query($db_conn, "INSERT INTO metadata (item_id, meta_key, meta_value) VALUES ('$payment_id', '$meta_key', '$meta_value')");
            }

            header('Location: fee-details.php?action=view-invoice&payment_id=' . $payment_id . '&child_id=' . $posted_child_id);
            exit;
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'view-invoice') {
    $payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;
    $month = isset($_GET['month']) ? $_GET['month'] : '';

    $payment = get_payment_record_for_student($payment_id, $selected_child_id, $month);
    $payment_post = isset($payment['post']) ? $payment['post'] : [];
    $payment_meta = isset($payment['meta']) ? $payment['meta'] : [];

    if (empty($payment_post)) {
        echo '<div class="content"><div class="container-fluid"><div class="alert alert-warning mt-3 mb-0">Invoice not found for the selected child.</div></div></div>';
    } else {
        $invoice_number = 'INV-' . str_pad((string)$payment_post['id'], 5, '0', STR_PAD_LEFT);
        $invoice_date = !empty($payment_post['publish_date']) ? date('d M, Y', strtotime($payment_post['publish_date'])) : date('d M, Y');
        $invoice_month = !empty($payment_meta['month']) ? ucwords($payment_meta['month']) : ucwords($month);
        $invoice_amount = !empty($payment_meta['amount']) ? (float)$payment_meta['amount'] : 0;
        $invoice_status = !empty($payment_meta['status']) ? $payment_meta['status'] : 'success';
        ?>

        <section class="content pt-3">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                        <div class="invoice-title">
                            <h4 class="float-right">Invoice #<?php echo $invoice_number; ?>
                                <span class="badge badge-success ml-2"><?php echo ($invoice_status === 'success') ? 'Paid' : ucwords($invoice_status); ?></span>
                            </h4>
                            <h2 class="mb-1 text-muted">Smart Education Management</h2>
                            <div class="text-muted">
                                <p class="mb-1">Bharatpur, Chitwan</p>
                                <p class="mb-1">sems@example.com</p>
                                <p>568975121</p>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            <div class="col-sm-6">
                                <h5 class="mb-3">Billed To:</h5>
                                <h5 class="mb-2"><?php echo !empty($selected_student['name']) ? $selected_student['name'] : '-'; ?></h5>
                                <p class="mb-1">Class: <?php echo !empty($class) ? $class->title : '-'; ?></p>
                                <p class="mb-1"><?php echo !empty($selected_student['email']) ? $selected_student['email'] : ''; ?></p>
                                <p><?php echo !empty($selected_student['mobile']) ? $selected_student['mobile'] : ''; ?></p>
                            </div>
                            <div class="col-sm-6 text-sm-right">
                                <div>
                                    <h6 class="mb-1">Invoice No:</h6>
                                    <p><?php echo $invoice_number; ?></p>
                                </div>
                                <div class="mt-3">
                                    <h6 class="mb-1">Invoice Date:</h6>
                                    <p><?php echo $invoice_date; ?></p>
                                </div>
                                <div class="mt-3">
                                    <h6 class="mb-1">Fee Month:</h6>
                                    <p><?php echo $invoice_month; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="py-2">
                            <h5>Fee Summary</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 70px;">No.</th>
                                            <th>Fees</th>
                                            <th class="text-right" style="width: 120px;">Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>01</td>
                                            <td><?php echo !empty($invoice_month) ? $invoice_month . ' Tuition Fee' : 'Tuition Fee'; ?></td>
                                            <td class="text-right">Rs. <?php echo number_format($invoice_amount, 2); ?></td>
                                        </tr>
                                        <tr>
                                            <th colspan="2" class="text-right">Sub Total</th>
                                            <th class="text-right">Rs. <?php echo number_format($invoice_amount, 2); ?></th>
                                        </tr>
                                        <tr>
                                            <th colspan="2" class="text-right">Total</th>
                                            <th class="text-right">Rs. <?php echo number_format($invoice_amount, 2); ?></th>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3 text-right">
                                <a href="?child_id=<?php echo $selected_child_id; ?>" class="btn btn-default">Back</a>
                                <a href="javascript:window.print()" class="btn btn-success"><i class="fa fa-print"></i> Print</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
    }
} else {
    ?>

    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Manage Child Fee Details</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Parent</a></li>
                        <li class="breadcrumb-item active">Fee Details</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if ($selected_child_id <= 0) { ?>
                <div class="alert alert-info">No child is linked with this parent account.</div>
            <?php } else { ?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Child Detail</h3>
                    </div>
                    <div class="card-body">
                        <strong>Name:</strong> <?php echo !empty($selected_student['name']) ? $selected_student['name'] : '-'; ?><br>
                        <strong>Class:</strong> <?php echo !empty($class) ? $class->title : '-'; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Tuition Fee</h3>
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
                                $sql = "SELECT p.id AS payment_id, m.meta_value AS month FROM posts AS p JOIN metadata AS m ON p.id = m.item_id WHERE p.type = 'payment' AND p.author = '$selected_child_id' AND m.meta_key = 'month' AND YEAR(p.publish_date) = '$current_year' ORDER BY p.id DESC";
                                $query = mysqli_query($db_conn, $sql);

                                $paid_fees = [];
                                while ($row = mysqli_fetch_object($query)) {
                                    $month_key = strtolower((string)$row->month);
                                    if (!isset($paid_fees[$month_key])) {
                                        $paid_fees[$month_key] = (int)$row->payment_id;
                                    }
                                }

                                foreach ($all_months as $key => $value) {
                                    $paid = false;
                                    $payment_id = 0;
                                    if (isset($paid_fees[$value])) {
                                        $paid = true;
                                        $payment_id = (int)$paid_fees[$value];
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $key + 1; ?></td>
                                        <td><?php echo ucwords($value); ?></td>
                                        <td class="<?php echo $paid ? 'bg-success' : ''; ?>"><?php echo $paid ? 'Paid' : 'Pending'; ?></td>
                                        <td>
                                            <?php if ($paid) { ?>
                                                <a href="?action=view-invoice&payment_id=<?php echo $payment_id; ?>&child_id=<?php echo $selected_child_id; ?>" class="btn btn-sm btn-primary"><i class="fa fa-eye fa-fw"></i> View</a>
                                            <?php } else { ?>
                                                <a href="#" data-toggle="modal" data-target="#paynow-popup" data-month="<?php echo ucwords($value); ?>" class="btn btn-sm btn-warning paynow-btn"><i class="fa fa-money-check-alt fa-fw"></i> Pay Now</a>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal fade" id="paynow-popup" tabindex="-1" role="dialog" aria-labelledby="paynow-popupLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="paynow-popupLabel">Pay Child Fee</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form action="" method="post">
                                    <input type="hidden" name="child_id" value="<?php echo $selected_child_id; ?>">
                                    <input type="hidden" name="amount" value="500">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label>Child Name</label>
                                                <input type="text" readonly class="form-control" value="<?php echo !empty($selected_student['name']) ? htmlspecialchars($selected_student['name']) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label>Email Address</label>
                                                <input type="email" readonly class="form-control" value="<?php echo !empty($selected_student['email']) ? htmlspecialchars($selected_student['email']) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label>Month</label>
                                                <input type="text" name="month" readonly class="form-control" id="month" value="">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label>Amount</label>
                                                <input type="text" readonly class="form-control" value="Rs. 500.00">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" name="form_submitted" class="btn btn-success">Confirm and Pay</button>
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
                        jQuery('#month').val(month);
                    });
                </script>
            <?php } ?>
        </div>
    </section>
<?php
}
include('footer.php')
?>