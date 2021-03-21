<?php
/*
 Plugin name: get-invoice
 plugin uri: www.localhost.com
 Description: Short Code para obtener una invoice de pedido
 Author: Luis Castillo
 Version: 0.1
 Author URI:
 */

add_shortcode("get_invoice","get_invoice");

function get_invoice(){
    global $wpdb;
    $id = get_current_user_id();
    $id_pedido = $_POST["order_id"];
    $subtotal = 0;
    $sql = "SELECT ID, post_author, post_date, post_status, post_type 
            FROM wp_posts
            WHERE post_type = 'shop_order' AND ID = '$id_pedido' ";
    $order = $wpdb->get_results($sql);
    if( count($order) > 0){
        $sql = "SELECT * FROM wp_postmeta 
                WHERE post_id = $id_pedido
                AND meta_key IN ('_billing_first_name', '_billing_last_name', '_billing_email', '_billing_phone', '_billing_city', '_payment_method_title','_order_currency','_order_shipping','_order_total')";

        //we obtain the basic data of the order
        $data_order = $wpdb->get_results($sql);
        foreach($data_order as $indice => $data ){
            switch($data->meta_key){
                case "_billing_first_name":
                    $billing_first_name = $data->meta_value;
                    break;
                case "_billing_last_name":
                    $billing_last_name = $data->meta_value;
                    break;
                case "_billing_email":
                    $billing_email = $data->meta_value;
                    break;
                case "_billing_phone":
                    $billing_phone = $data->meta_value;
                    break;
                case "_billing_city":
                    $billing_city = $data->meta_value;
                    break;
                case "_payment_method_title":
                    $payment_method_title = $data->meta_value;
                    break;
                case "_order_currency":
                    $order_currency = $data->meta_value;
                    break;
                case "_order_shipping"://Cost of the ship
                    $order_shipping = $data->meta_value;
                    break;
                case "_order_total":
                    $order_total = $data->meta_value;
                    break;
            }
        }


        $sql = "SELECT *
                FROM wp_woocommerce_order_items oi
                WHERE oi.order_id = $id_pedido ";

        $arr_item = array();
        $arr_ship = array();
        //we obtain the items of the order
        $data_items = $wpdb->get_results($sql);
        foreach($data_items as $indice_items => $item){
            switch ($item->order_item_type){
                case "line_item":
                    $item_id = $item->order_item_id;
                    $arr_item[$item_id]["id"] = $item_id;
                    $arr_item[$item_id]["name"] = $item->order_item_name;
                    $sql = "SELECT *
                            FROM  wp_woocommerce_order_itemmeta oim 
                            WHERE oim.order_item_id = {$item_id} ";
                    $data_items_info = $wpdb->get_results($sql);
                    foreach($data_items_info as $item_info) {
                        switch ($item_info->meta_key) {
                            case "_qty":
                                $arr_item[$item_id]["qty"] = $item_info->meta_value;
                                break;
                            case "_line_total":
                                $arr_item[$item_id]["line_total"] = $item_info->meta_value;
                                break;
                        }
                    }
                    break;

                case "shipping":
                    $item_id = $item->order_item_id;
                    $arr_ship["id"] = $item_id;
                    $arr_ship["name"] = $item->order_item_name;
                    $sql = "SELECT *
                            FROM  wp_woocommerce_order_itemmeta oim 
                            WHERE oim.order_item_id = {$item_id} ";
                    $data_items_info = $wpdb->get_results($sql);
                    foreach($data_items_info as  $item_info) {
                        switch ($item_info->meta_key) {
                            case "cost":
                                $arr_ship["cost"] = $item_info->meta_value;
                                break;
                        }
                    }
                    break;
            }
        }
        ob_start();
        ?>
        <h2>Detalles Del Cliente</h2>
        <table>
            <tr>
                <th>Nombre</th>
                <td><?php echo $billing_first_name ?></td>
                <th>Apellido</th>
                <td><?php echo $billing_last_name ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?php echo $billing_email ?></td>
                <th>Telefono</th>
                <td><?php echo $billing_phone ?></td>
            </tr>
        </table>
        <h2>Detalles Del Pedido</h2>
        <table>
            <tr>
                <th>Producto</th>
                <th>Total</th>
            </tr>
            <?php foreach($arr_item as $item){?>
                <tr>
                    <td><?php echo $item["name"] ." x ".$item["qty"] ?></td>
                    <td><?php echo $item["line_total"]; $subtotal += $item["line_total"] ?>€</td>
                </tr>
            <?php } ?>
            <tr>
                <th>Subtotal</th>
                <td><?php echo $subtotal ?>€</td>
            </tr>
            <tr>
                <th>Envío</th>
                <td><?php echo $order_shipping ?>€  <?php echo $arr_ship["name"] ?></td>
            </tr>
            <tr>
                <th>Metodo de pago</th>
                <td><?php echo $payment_method_title ?></td>
            </tr>
            <tr>
                <th>Total</th>
                <td><?php echo $order_total ?>€</td>
            </tr>
        </table>
        <script>
            (function() {
                window.print();
            })();
        </script>
        <?php
    }

    return ob_get_clean();

}

