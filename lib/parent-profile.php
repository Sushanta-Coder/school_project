<div class="row">
    <div class="col-md-3">
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center">
                    <?php
                    $parent_photo = '../dist/img/AdminLTELogo.png';
                    echo '<img class="profile-user-img img-fluid img-circle" src="' . $parent_photo . '" alt="Parent profile picture">';
                    ?>
                </div>
                <h3 class="profile-username text-center"><?php echo !empty($user['name']) ? $user['name'] : (!empty($user['father_name']) ? $user['father_name'] : 'N/A'); ?></h3>
                <p class="text-muted text-center"><?php echo !empty($user['email']) ? $user['email'] : (!empty($user['father_mobile']) ? $user['father_mobile'] : 'Parent'); ?></p>
                <hr>
                <p>
                    <strong><i class="fa-fw fas fa-user-friends mr-1"></i> Children</strong>
                    <span class="text-muted float-right">
                        <?php
                        $children = [];
                        if (!empty($user['children'])) {
                            $parsed_children = @unserialize($user['children']);
                            if (is_array($parsed_children)) {
                                $children = $parsed_children;
                            }
                        }

                        if (!empty($children)) {
                            $names = [];
                            foreach ($children as $child_id) {
                                $child = get_user_data($child_id, false);
                                if (!empty($child['name'])) {
                                    $names[] = $child['name'];
                                }
                            }
                            echo !empty($names) ? implode(', ', $names) : 'N/A';
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </span>
                </p>
                <hr>
                <p>
                    <strong><i class="fa-fw fas fa-phone-square mr-1"></i> Mobile</strong>
                    <span class="text-muted float-right"><?php echo isset($user['mobile']) ? $user['mobile'] : 'N/A'; ?></span>
                </p>
            </div>
        </div>

        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">About Me</h3>
            </div>
            <div class="card-body">
                <strong><i class="fas fa-map-marker-alt mr-1"></i> Location</strong>
                <p class="text-muted"><?php echo !empty($user['parents_address']) ? $user['parents_address'] : (isset($user['address']) ? $user['address'] : ''); ?>, <?php echo !empty($user['parents_state']) ? $user['parents_state'] : (isset($user['state']) ? $user['state'] : ''); ?>, <?php echo !empty($user['parents_country']) ? $user['parents_country'] : (isset($user['country']) ? $user['country'] : ''); ?> (<?php echo !empty($user['parents_zip']) ? $user['parents_zip'] : (isset($user['zip']) ? $user['zip'] : ''); ?>)</p>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="card">
            <div class="card-header p-2">
                <ul class="nav nav-pills">
                    <li class="nav-item"><a class="nav-link active" href="#timeline" data-toggle="tab">Profile Details</a></li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane active" id="timeline">
                        <?php
                        $child_profile = [];
                        if (!empty($user['children'])) {
                            $parsed_children = @unserialize($user['children']);
                            if (is_array($parsed_children) && !empty($parsed_children)) {
                                $first_child_id = (int)$parsed_children[0];
                                if ($first_child_id > 0) {
                                    $child_profile = get_user_data($first_child_id, false);
                                }
                            }
                        }

                        $parent_name = !empty($user['father_name']) ? $user['father_name'] : (!empty($user['name']) ? $user['name'] : (!empty($child_profile['father_name']) ? $child_profile['father_name'] : 'N/A'));
                        $parent_mobile = !empty($user['father_mobile']) ? $user['father_mobile'] : (!empty($user['mobile']) ? $user['mobile'] : (!empty($child_profile['father_mobile']) ? $child_profile['father_mobile'] : 'N/A'));
                        $mother_name = !empty($user['mother_name']) ? $user['mother_name'] : (!empty($child_profile['mother_name']) ? $child_profile['mother_name'] : 'N/A');
                        $mother_mobile = !empty($user['mother_mobile']) ? $user['mother_mobile'] : (!empty($child_profile['mother_mobile']) ? $child_profile['mother_mobile'] : 'N/A');
                        $parent_address = !empty($user['parents_address']) ? $user['parents_address'] : (!empty($user['address']) ? $user['address'] : (!empty($child_profile['parents_address']) ? $child_profile['parents_address'] : 'N/A'));
                        $parent_country = !empty($user['parents_country']) ? $user['parents_country'] : (!empty($user['country']) ? $user['country'] : (!empty($child_profile['parents_country']) ? $child_profile['parents_country'] : 'N/A'));
                        $parent_state = !empty($user['parents_state']) ? $user['parents_state'] : (!empty($user['state']) ? $user['state'] : (!empty($child_profile['parents_state']) ? $child_profile['parents_state'] : 'N/A'));
                        $parent_zip = !empty($user['parents_zip']) ? $user['parents_zip'] : (!empty($user['zip']) ? $user['zip'] : (!empty($child_profile['parents_zip']) ? $child_profile['parents_zip'] : 'N/A'));
                        ?>
                        <div class="row">
                            <div class="col-lg-6">
                                <p><strong>Father's Name:</strong> <?php echo $parent_name; ?></p>
                                <p><strong>Father's Mobile:</strong> <?php echo $parent_mobile; ?></p>
                                <p><strong>Mother's Name:</strong> <?php echo $mother_name; ?></p>
                                <p><strong>Mother's Mobile:</strong> <?php echo $mother_mobile; ?></p>
                            </div>
                            <div class="col-lg-6">
                                <p><strong>Parent Address:</strong> <?php echo $parent_address; ?></p>
                                <p><strong>Parent Country:</strong> <?php echo $parent_country; ?></p>
                                <p><strong>Parent State:</strong> <?php echo $parent_state; ?></p>
                                <p><strong>Parent Zip:</strong> <?php echo $parent_zip; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>