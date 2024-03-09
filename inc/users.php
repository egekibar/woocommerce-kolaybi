<?php

// Action
add_action("admin_action_kolaybi_sync_users", function (){
    if (isset($_GET['start'])){
	    \Plugin\Kolaybi\Authorization::login();
	    $customers = \Plugin\Kolaybi\Associate::list();

        foreach ($customers as $customer) {
            $customer_data = [
			    'user_login' => $customer->code,
			    'display_name' => $customer->code,
			    'user_pass' => $customer->code,
			    'user_email' => $customer->email ?? $customer->code."@".$_SERVER['HTTP_HOST'],
			    'role' => 'customer',
			    'first_name' => $customer->name,
			    'last_name' => $customer->surname ?? "Firması",
		    ];

		    $country_code = array_search($customer->address[0]->country_name, WC()->countries->get_countries());
		    $state_code = array_search($customer->address[0]->city, WC()->countries->get_states($country_code));

		    $additional_meta_fields = [
                'billing_phone' => $customer->phone,
			    'billing_first_name' => $customer->name,
			    'billing_last_name' => $customer->surname ?? "Firması",
			    'billing_email' => $customer->email ?? $customer->code."@".$_SERVER['HTTP_HOST'],
			    'billing_address_1' => $customer->address[0]->address,
			    'billing_city' => $customer->address[0]->district,
			    'billing_state' => $state_code,
			    'billing_postcode' => $customer->address[0]->postal_code,
			    'billing_country' => $country_code,
			    'shipping_address_1' => $customer->address[0]->address,
			    'shipping_city' => $customer->address[0]->city,
			    'shipping_postcode' => $customer->address[0]->postal_code,
			    'shipping_country' => $customer->address[0]->country_name,
			    'identity_no' => $customer->identity_no,
			    'tax_office' => $customer->tax_office,
                'associate_type' => $customer->associate_type,
                'associate_id' => $customer->id
		    ];

            if (empty($customer->surname))
	            $additional_meta_fields['billing_company'] = $customer->name;

		    $existing_customer = get_users([
			    'login' => $customer->code,
			    'number' => 1
		    ]);

		    if (!empty($existing_customer)) {
			    $user_id = $existing_customer[0]->ID;
			    $customer_data['ID'] = $user_id;
                if (get_user_meta($user_id, 'no_sync', true))
			        $report['skipped'][$customer->code] = "Kullanıcı atlandı. ($user_id, {$customer->code})";
			    else $user_id = wp_update_user($customer_data);
			    if (is_wp_error($user_id))
				    $report['error'][] = "Kullanıcı güncellenemedi. ($user_id, {$customer->code})";
		    } else {
			    $user_id = wp_insert_user($customer_data);
			    if (is_wp_error($user_id))
				    $report['error'][] = "Kullanıcı eklenemedi. ({$customer->code})";
		    }

		    if (!is_wp_error($user_id))
                foreach ( $additional_meta_fields as $key => $value )
                    update_user_meta( $user_id, $key, $value );

	    }

        if ($report)
	        dd("Rapor tespit edili.", $report);

        die;
    }

	view("modal", [
		"title" => "Müşteriler Kolaybi'den sisteme aktarılacak.",
		"ajax_action" => "sync_users"
	]);
});

// ThickBox Support
add_action ('admin_head-users.php', function () {
    add_thickbox();
});

// Header Button
add_action('admin_footer-users.php', function () {
	?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            jQuery(document).ready(function($) {
                $('<a>').attr('href', 'admin.php?action=kolaybi_sync_users&TB_iframe=true')
                    .addClass('page-title-action thickbox')
                    .text('Kullanıcıları Senkronize Et')
                    .insertBefore('.wp-header-end');

                $(document).on('tb_unload', function() {
                    location.reload()
                });
            });
        });
    </script>
	<?php
});

function custom_user_meta_fields($user) {
	?>
    <h3>Kolaybi Entegrasyonu</h3>
    <table class="form-table">
        <tr>
            <th><label for="no_sync">Senkronizasyon</label></th>
            <td>
                <label for="no_sync">
                    <input type="checkbox" name="no_sync" id="no_sync" value="1" <?php checked( get_user_meta( $user->ID, 'no_sync', true ), 1 ); ?>>
                    Bu kullanıcıyı senkronize etme!
                </label>
            </td>
        </tr>
        <tr>
            <th><label for="identity_no">VKN / TCKN</label></th>
            <td>
                <input type="text" name="identity_no" id="identity_no" value="<?php echo esc_attr(get_user_meta($user->ID, 'identity_no', true)); ?>" class="regular-text" /><br />
            </td>
        </tr>
        <tr>
            <th><label for="tax_office">Vergi Dairesi</label></th>
            <td>
                <input type="text" name="tax_office" id="tax_office" value="<?php echo esc_attr(get_user_meta($user->ID, 'tax_office', true)); ?>" class="regular-text" /><br />
            </td>
        </tr>
    </table>
	<?php
}
add_action('show_user_profile', 'custom_user_meta_fields');
add_action('edit_user_profile', 'custom_user_meta_fields');

function save_custom_user_meta_fields($user_id) {
	if (current_user_can('edit_user', $user_id)) {
		update_user_meta($user_id, 'no_sync', $_POST['no_sync']);
		update_user_meta($user_id, 'identity_no', $_POST['identity_no']);
		update_user_meta($user_id, 'tax_office', $_POST['tax_office']);
	}
}
add_action('personal_options_update', 'save_custom_user_meta_fields');
add_action('edit_user_profile_update', 'save_custom_user_meta_fields');


//add_filter('bulk_actions-users', function($bulk_actions) {
//	$bulk_actions["no_sync"] = "Kolaybi'den Müşterileri Getir";
//	return $bulk_actions;
//});
//add_filter('handle_bulk_actions-edit-post', function($redirect_url, $action, $user) {
//	if ($action == 'no_sync') {
//		foreach ($user as $post_id) {
//			wp_update_post([
//				'ID' => $post_id,
//				'post_status' => 'publish'
//			]);
//		}
//		$redirect_url = add_query_arg('no_sync', count($user), $redirect_url);
//	}
//	return $redirect_url;
//}, 10, 3);