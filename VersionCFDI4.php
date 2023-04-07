<?php
/*
Plugin Name: 002-Exportador de pedidos a SiNube 4.4.
Version: 2.4.4
Plugin URI: http://metalsniperdesign.com/   
Description: Habilita la función de exportar a SiNube su pedido como una nota de venta, directamente desde la página de la orden.
Author: Metal Sniper
Author URI: http://metalsniperdesign.com/ 
*/

//Comenzamos con el codigo.

// WE CREATE THE ACTION FOR THE ORDER BUTTONS:
function sv_wc_add_order_meta_box_action( $actions ) {
	global $theorder;

	// add "mark printed" custom action
	$actions['wc_custom_order_action'] = __( 'Exportar pedido a SiNube 4.4', 'my-textdomain' );
	return $actions;
}
add_action( 'woocommerce_order_actions', 'sv_wc_add_order_meta_box_action' );


// MAIN FUNCTION
function obtenercfdi($order_id) {
    
    // Get the order object.
    $order = wc_get_order( $order_id );
    
    // Let's define the totals before.
    
    //TOTAL WITHOUT TAXES
    $sumaproductos = '';
    
    // TAXES
    $sumaiva = '';
    
    // TOTAL
    $sumatotales = '';
  
    // EMPTY VARIABLE FOR CONCEPTS
    $conceptos = '';
    
    //Let's get the shipping method & price :
    
    $shipping = $order->get_shipping_method();
    $shipping_price = $order->get_total_shipping();
     
    
    // Loop through each item in the order.
    foreach( $order->get_items() as $item_id => $item ) {
        // Get the product object for the item.
        $product = $item->get_product();
        $sku = $product->get_sku();
        $name = $product->get_name();
        $quantity = $item->get_quantity();
        $line_tax = $item->get_total_tax();
        $tax_type = $item->get_tax_class();
        $line_subtotal = number_format($item->get_total(), 2);
        $line_total = $line_subtotal + $line_tax;
        $product_price = $line_total / $quantity;
        
        // IF THE PRICE OF THE ARTICLE IS 0
        
        if ($line_total= 0 ){
            $line_total = 0.1;
        }
        
        // GET TAX TYPE FOR THE CONCEPT
        
        if($tax_type == 'tasa-cero'){
            $tasa = "IVA 0%";
        }else{
            $tasa = 'Causa IVA';
        }
        
        // DEFINING THE CONCEPT FOR EACH PRODUCT
        $conceptos .= '<Concepto 
        
            productoSinube="'.$sku.'" 
            descripcion="'.$name.'" 
            cantidad="'.$quantity.'" 
            unidadSinube="H87" 
            valorUnitario="'.$product_price.'" 
            descuento="0" 
            tipoIVA="'.$tasa.'" 
            montoBaseIVA="'.$line_subtotal.'" 
            montoIVA="'.$line_tax.'"
            importe="'.$line_subtotal.'" 
            subtotalDet="'.$line_subtotal.'"
            objetoImp="02" />';
            
        

        // Lets add everything to totals:
        $sumaproductos += $line_subtotal;
        $sumaiva += $line_tax;
        $sumatotales += $line_total;
        
        
    }
    
    
    // LETS SEE IF WE NEED TO ADD SHIPPING PRICE
    
    if ($shipping_price > 0){
        
        $preciodenevio = round($shipping_price / 1.16, 2);
	    $iva_envio = round($shipping_price - $preciodenevio, 2);
	
      // DEFINING THE SHIPPING CONCEPT TO ADD IT IN CASE WE CHARGE SHIPPING
      $conceptos .= '<Concepto
	        productoSinube="GUIA"
	        descripcion="GUIA DE ENVIO"
	        cantidad="1" 
	        unidadSinube="H87"
	        valorUnitario="'.$preciodenevio.'" 
	        descuento="0" tipoIVA="Causa IVA"
	        montoBaseIVA="'.$preciodenevio.'" 
	        montoIVA="'.$iva_envio.'"
	        importe="'.$preciodenevio.'"
	        subtotalDet="'.$preciodenevio.'"
	        objetoImp="02" />';
	        
	  // WE ADD SHIPPING PRICES TO THE TOTALS:
	  
	    $sumaproductos += $preciodenevio;
        $sumaiva += $iva_envio;
        $sumatotales += $shipping_price;
        
    } // WE CLOSE THE SHIPPING CONCEPT
    
    
    
    
    //DEFINING THE BILLING INFO:
    
    $folioauto = $order->get_order_number();
    $nombrepersona = $order->get_billing_first_name();
    $apellidopersona = $order->get_billing_last_name();
    $nombrecompleto = $nombrepersona .' '.$apellidopersona;
    
    
    // LETS DEFINE THE MAIN XML.
    $sumatotal=$sumaproductos+$sumaiva;
    $rawxml='<?xml version="1.0" encoding="utf-8"?>
    <Comprobante
    sistema="ECOPIPO"
    almacen="General"
    generar="NotaDeVenta"
    rfcEmisor="ECO151106ED3"
    sucursal="Matriz"  
    nomArchivoDescarga="Nota de venta"
    permiteAgregarProductosNoInv="1"
    folioAutofacturacion="'.$folioauto.'"
    formaDePago="01"
    observacion="Prueba POST"
    referencia="Desde POST"
    subtotal="'.$sumaproductos.'"
    descuento="0"
    porcentajeIVA="16"
    montoIVA="'.$sumaiva.'"
    total="'.$sumatotal.'"
    monedaSinube="MXN"
    difZonaHoraria="-5">
    <Receptor
        rfc="XAXX010101000"
        razonSocial="'.$nombrecompleto.'"
        esPersonaFisica="0"/>
    <Conceptos>    
    '.$conceptos.'
    </Conceptos>
    </Comprobante>';
    
    
    $xml = new SimpleXMLElement($rawxml);
    
    
    //LETS START WITH THE PARAMETERS FOR THE CONECTION:
    //primeros 5 parámetros
       $parametro1  = "tipo=7"."\n"; //Nota de venta
       $parametro2  = "emp=ECO151106ED3"."\n"; // RFC de la empresa	
       $parametro3  = "suc=Matriz"."\n"; // Sucursal 
       $parametro4  = "usu=contabilidadecopipo@gmail.com"."\n"; //Usuario
       $parametro5  = "pwd=ECOTEFILA16"."\n"; // contraseña de COMUNICACIONES
       $parametro6  = "zh=-6"."\n"; // Zona horaria de México
     
      //parámetros encriptados
        $parametros= base64_encode(utf8_encode($parametro1.$parametro2.$parametro3.$parametro4.$parametro5.$parametro6));
        
        $url='http://ep-dot-si-nube.appspot.com/blob?par='.$parametros; //url con parámetros encriptados
    
         //se configura el header tipo xml
           $headers = array(
            'Content-Type' => 'text/xml',
            'Accept' => 'application/xml;charset=UTF-8,application/x-www-form-urlencoded',
            'Content-length' => strlen($rawxml),
            );
        
         // Set the WP HTTP API request arguments
            $args = array(
                'body' => $rawxml,
                'headers' => $headers,
                'timeout' => 4000,
                'method' => 'POST',
            );
    
        
        
        // Make the HTTP request using the WP HTTP API
         $response = wp_remote_post( $url, $args );

        // Check for errors and return the response body
         if ( is_wp_error( $response ) ) {
        $response_body = wp_remote_retrieve_body( $response );
        $response_array = simplexml_load_string( $response_body );
        $folio = (string) $response_array->folio;
        $fechaNotaVenta = (string) $response_array->fechaNotaVenta;
        $errorwp = 'hubo error de wp';
        } else {
        $response_body = wp_remote_retrieve_body( $response );
        $response_array = simplexml_load_string( $response_body );
        $folio = (string) $response_array->folio;
        $fechaNotaVenta = (string) $response_array->fechaNotaVenta;
        }
        
        // VAMOS A COMPROBAR QUE TODO HAYA SALIDO BIEN, SI NO, MOSTRAMOS UN MENSAJE EN LA ORDEN Y PONEMOS COMO ERROR EL FOLIO
        
        if (!empty($folio)){
            
            $nota="NOTA CREADA EXITOSAMENTE<br>FOLIO: ".$folio."<br>SUBTOTAL: ".$sumaproductos."<br>IVA: ".$sumaiva."<br>TOTAL: ".$sumatotal;
            
            $note = __( $nota );
            $final = $folio;
            
        }else{
            
            $htmlxml = htmlentities($rawxml);
            $note = __( $response_body.$htmlxml  );
            
            $final = 'ERROR';
        }
        
          // THIS ADDS THE COMMENT  TO THE ORDER
            $message = sprintf( __( $genera_xml, 'my-textdomain' ), wp_get_current_user()->display_name );
            $order->add_order_note( $note );
            // add the flag so this action won't be shown again
	        update_post_meta( $order->id, '_wc_order_marked_printed_for_packaging', $final );
    
    
}

//AÑADIMOS LA ACCIÓN A LOS BOTONES DE LA ORDEN
add_action( 'woocommerce_order_action_wc_custom_order_action', 'obtenercfdi' );

//TAMBIEN LA AÑADIMOS AL CAMBIO AUTOMÁTICO AL PROCESAR:
add_action( 'woocommerce_order_status_processing', 'obtenercfdi', 10, 1); 




// ADDING 2 NEW COLUMNS WITH THEIR TITLES (keeping "Total" and "Actions" columns at the end)
add_filter( 'manage_edit-shop_order_columns', 'custom_shop_order_column', 20 );
function custom_shop_order_column($columns)
{
    $reordered_columns = array();

    // Inserting columns to a specific location
    foreach( $columns as $key => $column){
        $reordered_columns[$key] = $column;
        if( $key ==  'order_status' ){
            // Inserting after "Status" column
            $reordered_columns['my-column1'] = __( '# Nota SiNube','theme_domain');
        }
    }
    return $reordered_columns;
}

// Adding custom fields meta data for each new column (example)
add_action( 'manage_shop_order_posts_custom_column' , 'custom_orders_list_column_content', 20, 2 );
function custom_orders_list_column_content( $column, $post_id )
{
    switch ( $column )
    {
        case 'my-column1' :
            // Get custom post meta data
            $my_var_one = get_post_meta( $post_id, '_wc_order_marked_printed_for_packaging', true );
            if(!empty($my_var_one))
                echo $my_var_one;

            // Testing (to be removed) - Empty value case
            else
                echo '<span class=dashicons dashicons-minus> --- </span>';

            break;

    }
}

/*--------------------------------------------------------------------------------------------------------------------------------------------------------*/
/* NO AGREGE NINGUN CÓDIGO MAS ABAJO DE AQUI O LE DARA ERRORES */
/*---------------------------------------------------------------------------------------------------------------------------------------------------------*/
