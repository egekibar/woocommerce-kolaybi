<?php

add_action("admin_action_kolaybi_sync_products", function (){
	if (isset($_GET['start'])){
		\Plugin\Kolaybi\Authorization::login();
		$products = \Plugin\Kolaybi\Product::list();

		foreach ($products as $data) {
			$product_id = wc_get_product_id_by_sku($data->code);

			if ($product_id) {
				$product = wc_get_product($product_id);
				$product->set_sku($data->code);
				$product->set_regular_price($data->sale_price);
				$product->set_tax_class('standard');
				$product->set_tax_status('taxable');
				$product->set_manage_stock(true);
				$product->set_stock_quantity($data->total_stock_quantity);
                $product->set_backorders("yes");

				$product_id = $product->save();
			} else {
				$product = new WC_Product_Simple();
				$product->set_name($data->name);
				$product->set_description($data->description ?? '');
				$product->set_sku($data->code);
				$product->set_regular_price($data->sale_price);
				$product->set_tax_class('standard');
				$product->set_tax_status('taxable');
				$product->set_manage_stock(true);
				$product->set_stock_quantity($data->total_stock_quantity);
				$product->set_backorders("yes");

				$product_id = $product->save();
			}

			if (is_wp_error($product_id)) {
				$error_message = $product_id->get_error_message();
				echo "Product update/insert failed: $error_message";
			}

			if (taxonomy_exists('product_group'))
                foreach ($data->tags as $tag)
                    wp_set_object_terms($product_id, $tag->name, 'product_group');

			update_post_meta($product_id, "kolaybi_urun_id", $data->id);
			update_post_meta($product_id, "kolaybi_vergi_orani", $data->vat_value);

//			dd($product_id, $product, $data);
		}

		die;
	}

	view("modal", [
		"title" => "Ürünler Kolaybi'den sisteme aktarılacak.",
		"ajax_action" => "sync_products"
	]);
});

// ThickBox Support
add_action ('admin_head-edit.php', function () {
    global $typenow;
    if ($typenow == "product"):
        add_thickbox();
    endif;
});

// Header Button
add_action('admin_footer-edit.php', function () {
    global $typenow;
    if ($typenow == "product"): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            jQuery(document).ready(function($) {
                $('<a>').attr('href', 'admin.php?action=kolaybi_sync_products&TB_iframe=true')
                    .addClass('page-title-action thickbox')
                    .text('Ürünleri Senkronize Et')
                    .insertBefore('.wp-header-end');

                $(document).on('tb_unload', function() {
                    location.reload()
                });
            });
        });
    </script>
    <?php endif;
});

//add_action('woocommerce_product_set_stock', function ($product) {
//    dd($product, $product->get_stock_quantity());
//}, 10, 3);
