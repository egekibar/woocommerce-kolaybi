<?php

if (isset($_GET['page']) && isset($_GET['action']) && $_GET['action'] === 'edit' && $_GET['page'] === 'wc-orders') {
	add_action ('admin_head', function () {
		add_thickbox();
		$order = wc_get_order($_GET['id']);
		$uuid = $order->get_meta("e_invoice_uuid", true);
		$doc = $order->get_meta("document_id", true);
        if ($doc && empty($meta)): ?>
		<script>
			document.addEventListener('DOMContentLoaded', function() {
                jQuery(document).ready(function($) {
                    <?php if (!$uuid): ?>
                        $('<li>').addClass('wide').html(
                            $('<a>').attr('href', 'admin.php?action=kolaybi_convert_e_invoice&id=<?= $_GET['id'] ?>&TB_iframe=true')
                            .addClass('button thickbox')
                            .css({ width: "100%" })
                            .text('E-fatura Oluştur')
                        ).insertAfter('#actions')

                        $(document).on('tb_unload', function() {
                            location.reload()
                        });
                    <?php else: ?>
                        $('<li>').addClass('wide').html(
                            $('<a>').attr('href', '/?e_invoice_uuid=<?= $uuid ?>').attr('target', '_blank')
                            .addClass('button')
                            .css({ width: "100%" })
                            .text("E-fatura'yı Görüntüle")
                        ).insertAfter('#actions')

                        //$('<li>').addClass('wide').html($('<a>').attr('href', 'admin.php?action=kolaybi_view_e_invoice&id=<?php //= $_GET['id'] ?>//&TB_iframe=true&width=900&height=800')
                        //    .addClass('button thickbox')
                        //    .css({ width: "100%" })
                        //    .text("E-fatura'yı Görüntüle"))
                        //    .insertAfter('#actions')
                    <?php endif; ?>
                });
			});
		</script>
		<?php endif;
	});
}

add_action("admin_action_kolaybi_convert_e_invoice", function (){
    if (isset($_GET['start'])){
	    $order_id = $_GET['id'];
	    if ($order_id){
		    \Plugin\Kolaybi\Authorization::login();
		    $order = wc_get_order($order_id);
		    $document_id = $order->get_meta("document_id", true);
		    $resp = \Plugin\Kolaybi\Order::convert_e_invoice([
			    "document_id" => $document_id
		    ]);
		    if ($resp->uuid){
			    $order->add_meta_data("e_invoice_uuid", $resp->uuid);
			    $order->add_meta_data("e_invoice_no", $resp->no);
			    $order->save();
		    }
		    dd($document_id, $resp);
	    }
    }
    view("modal", [
        "title" => "Bu faturadan e-fatura oluştur.",
        "ajax_action" => "convert_e_invoice&id={$_GET['id']}"
    ]);

});

//add_action("admin_action_kolaybi_view_e_invoice", function (){
//	$order_id = $_GET['id'];
//	if ($order_id){
//		\Plugin\Kolaybi\Authorization::login();
//		$order = wc_get_order($order_id);
//		$uuid = $order->get_meta("e_invoice_uuid", true);
//		$resp = \Plugin\Kolaybi\Order::view_e_invoice($uuid);
//
//        if ($resp->output_type == "pdf")
//		    echo "<img src='data:application/pdf;base64,{$resp->src}' style='width: 100%'>";
//	}
//});

add_action('woocommerce_order_status_changed', function ($order_id, $old_status, $new_status){
	if ($new_status == "completed") do_action('kolaybi_create_invoice', $order_id);
}, 10, 3);

add_action("kolaybi_create_invoice", function ($order_id){
	\Plugin\Kolaybi\Authorization::login();
	$order = wc_get_order($order_id);
	$associate_id = get_user_meta($order->get_user_id(), 'associate_id', true);
	$associate = \Plugin\Kolaybi\Associate::detail( $associate_id );

	$tax_rate = 0;
	foreach ($order->get_taxes() as $tax )
		$tax_rate += $tax->get_rate_percent();

	$items = [];
	foreach ( $order->get_items() as $item_id => $item ) {
		$product = [];
		$product['product_id'] = (int) get_post_meta($item->get_product_id(), "kolaybi_urun_id", true);
		$product['vat_rate'] = (int) $tax_rate;
		$product['quantity'] = $item->get_quantity();
		$product['unit_price'] = $item->get_subtotal() / $item->get_quantity();

		$items[] = $product;
	}

	$body = [
		"contact_id" => (int) $associate_id,
		"address_id" => $associate->address[0]->id,
		"order_date" => (string) $order->get_date_created(),
		"currency" => $order->get_currency(),
		"items" => $items,
	];

	$resp = \Plugin\Kolaybi\Order::create_invoice($body);

	$order->add_meta_data("document_id", $resp->document_id);
	$order->save();
});

add_filter('woocommerce_account_orders_columns', function ( $columns ){
	$order_actions  = $columns['order-actions'];
	unset($columns['order-actions']);
	$columns['e-fatura'] = "E-fatura";
	$columns['order-actions'] = $order_actions;
	return $columns;
}, 10, 1 );

add_action('woocommerce_my_account_my_orders_column_e-fatura', function ( $order ) {
	if ( $value = $order->get_meta( 'e_invoice_no', true ) ) {
        echo "<a target='_blank' href='/?e_invoice_uuid={$order->get_meta( 'e_invoice_uuid', true )}'>{$value}</a>";
	} else {
		printf( '<small>%s</small>', __("Oluşturulmadı") );
	}
});

//add_action('init', function () {
//	add_rewrite_endpoint( 'e-fatura', EP_PAGES );
//});
//
//add_action('wp', function () {
//	if (wc_get_page_id('myaccount') == get_queried_object_id())
//		flush_rewrite_rules();
//});
//
//add_action('woocommerce_account_e-fatura_endpoint', function () {
//	echo do_action('admin_action_kolaybi_view_e_invoice');
//});

add_action("wp", function (){
    if (isset($_GET['e_invoice_uuid'])) {
	    \Plugin\Kolaybi\Authorization::login();
	    $resp = \Plugin\Kolaybi\Order::view_e_invoice($_GET['e_invoice_uuid']);
	    if ($resp->output_type == "pdf")
		    header("Content-type:application/pdf");
        if ($resp->src)
	        die( base64_decode($resp->src) );
    }
});