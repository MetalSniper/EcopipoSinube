<?php
/*
Plugin Name: 001-Exportador de pedidos a SiNube.
Version: 1
Plugin URI: http://www.pruebasjinsa.com.mx/   
Description: Habilita la funci&oacuten de exportar a SiNube su pedido como una nota de venta, directamente desde la p&aacutegina de la orden.
Author: Metal Sniper
Author URI: http://www.pruebasjinsa.com.mx/ 
*/


/** AHORA SI EMPEZAMOS CON LO BUENO, SINUBE */


   /**
 * Add a custom action to order actions select box on edit order page
 * Only added for paid orders that haven't fired this action yet
 *
 * @param array $actions order actions array to display
 * @return array - updated actions
 */
function sv_wc_add_order_meta_box_action( $actions ) {
	global $theorder;

	// add "mark printed" custom action
	$actions['wc_custom_order_action'] = __( 'Exportar pedido a SiNube', 'my-textdomain' );
	return $actions;
}
add_action( 'woocommerce_order_actions', 'sv_wc_add_order_meta_box_action' );
/**
 * Add an order note when custom action is clicked
 * Add a flag on the order to show it's been run
 *
 * @param \WC_Order $order
 */
function sv_wc_process_order_meta_box_action( $order ) {
	
	 $dp = (isset($filter['dp'])) ? intval($filter['dp']) : 2;
		
		$total_orden_sucio = preg_replace('/[^0-9.]*/','',$order->get_total());
		$total_orden = round($total_orden_sucio / 1.16, 1);
		$monto_iva_total = round($total_orden *0.16, 1);
		$folioauto = $order->get_order_number();
		$autofacturacion = $folioauto;
		
		//AÑADIMOS EL CAMBIO DE FOLIO SI SE QUIERE FACTURAR
		
			
		$iddelpedido = $order->get_id();
		
		
		$deseofacturar = get_post_meta( $iddelpedido, '_billing_wooccm12', true ); //GET THE SAT CODE
		
		$rfc_cliente ='ECO151106ED3';
		
		
		if($deseofacturar !== 'Si'){
		
		$autofacturacion = $folioauto.'-REPO';
		
		$rfc_cliente = 'ECO151106ED3';
		
		}
		

    $autofacturacion = $folioauto.'-REPO';
		// Hasta aqui termina el campo condicional
		
		
		
		$datos=array(
                'rfcEmisor'=>'ECO151106ED3',
                'codigoReporte'=>'prueba',
                'nomArchivoDescarga'=>'Nota de venta',
                'folioAutofacturacion'=>$autofacturacion,
                'formaDePago'=>'01',
                'observacion'=>'Prueba POST',
                'referencia'=>'Desde POST',
                'subtotal'=>$total_orden,
                'descuento'=>'0',
                'porcentajeIVA'=>'16',
                'montoIVA'=>$monto_iva_total, 
                'total'=>$total_orden_sucio,
                'monedaSinube'=>'MXN',
                'difZonaHoraria'=>'-5',
                'rfc'=>'CUNS890508K72',
                'nombre_persona'=> $order->get_billing_first_name(),
                'apellido_persona'=> $order->get_billing_last_name(),
                'esPersonaFisica'=>'0',
                'productoSinube'=>'SEGUNDAPRUEBA',
                'descripcion'=>  $order->get_billing_first_name(),
                'cantidad'=>'1',
                'unidadSinube'=>'H87',
                'valorUnitario'=>preg_replace('/[^0-9.]*/','',$order->get_total()),
                'descuentoProducto'=>'0',
                'tipoIVA'=>'Causa IVA',
                'montoBaseIVA'=>preg_replace('/[^0-9.]*/','',$order->get_total()),
                'montoIVAProducto'=>'0',
                'importe'=>preg_replace('/[^0-9.]*/','',$order->get_total()),
                'subtotalDet'=>preg_replace('/[^0-9.]*/','',$order->get_total()),
                'metodoDeEnvio' => $order->get_shipping_method(),

                );
                
                
    // OBTENER INFORMACION PARA EL RFC 
    
    // $rfc_cliente = get_post_meta( $order->id, '_billing_myfield5', true );
     
    // if(empty($rfc_cliente)){
     
     	
    // }
    // if($rfc_cliente === 'Sara821139tz5'){
    // 	$rfc_cliente ='ECO151106ED3'; 
     	
    // }    
     
    // if (strlen($rfc_cliente) < 12){
     //	$rfc_cliente ='ECO151106ED3';
     //	}
            
	// Definimos variables para nombre completo
	$nombrecompleto = $datos['nombre_persona']. ' ' .$datos['apellido_persona'];
	//Importante definir primero precio de envio
	
	$precio_envio_sucio = preg_replace('/[^0-9.]*/','',$order->get_total_shipping());
	
if ($precio_envio_sucio > 0){	
	$preciodenevio = round($precio_envio_sucio / 1.16, 2);
	$iva_envio = round($precio_envio_sucio - $preciodenevio, 2);
	
	//Definimos el concepto de envio por si es necesario aplicarlo
	$conceptoenvio = '<Concepto productoSinube="GUIA" descripcion="'.$datos['metodoDeEnvio'].'" cantidad="1" unidadSinube="H87"
    valorUnitario="'.$preciodenevio.'" descuento="0" tipoIVA="'.$datos['tipoIVA'].'" montoBaseIVA="'.$preciodenevio.'" montoIVA="'.$iva_envio.'"
    importe="'.$preciodenevio.'" subtotalDet="'.$preciodenevio.'"/>'; 
}	
	  
	$sumaImportes = 0;
	$sumaIva= 0;
	// Buscar cada articulo en la orden
	foreach ($order->get_items() as $item_id => $item_data) {


    // Tomar una instancia correspondiente al objeto WC_Product
    $product = $item_data->get_product();
    $product_nombre = strtoupper($product->get_name()); // Nombre del producto
    $product_name = preg_replace('/[^\w]/', '', $product_nombre); // NOMBRE LIMPIO
    $productsku = substr( $product->get_sku(),0,20);

    $item_quantity = $item_data->get_quantity(); // Cantidad de productos
	
    $item_total_sucio = $item_data->get_total(); // Total de precio
	$item_total = round($item_total_sucio / 1.16, 2);
		
    $pieza = 'H87';
	
	$precio_pieza_sucio = round($item_total / $item_quantity,2);
    $precio_unitario = round ($precio_pieza_sucio / 1.16, 2); // Precio unitario
	$iva = 'Causa IVA';
	$descuentoenproducto = "0";
	$skudeprueba = "PruebaSKU";
	$monto_iva_producto_sucio = round($item_total *0.16,2);
	$monto_iva_producto = round($item_total_sucio - $item_total, 2);
	$gratis = "100%";
	
	
	//SI EL ARTICULO NO TIENE SKU:
	
	if (empty($productsku)){
		$productsku = 'NO-SKU';
		}
	
    // Mostrando la información de cada producto
    $conceptos .= '<Concepto productoSinube="'.$productsku.'" descripcion="'.$product_name.'" cantidad="'.$item_quantity.'" unidadSinube="'.$pieza.'"
    valorUnitario="'.($precio_pieza_sucio == 0 ? $precio_pieza_sucio= 0.01 : $precio_pieza_sucio).'" descuento="'.$descuentoenproducto.'" tipoIVA="'.$iva.'" montoBaseIVA="'.($item_total == 0 ? $item_total= 0.01*$item_quantity : $item_total).'" montoIVA="'.$monto_iva_producto.'"
    importe="'.($item_total == 0 ? $item_total= 0.01*$item_quantity : $item_total).'" subtotalDet="'.($item_total == 0 ? $item_total= 0.1 : $item_total).'"/>';
    
    
    
    $sumaIva+= $monto_iva_producto;	
	$sumaImportes+=$item_total;
    
}



//MOSTRAMOS EL METODO DE PAGO:
$metodo_de_pago = $order->get_payment_method();
$total_real_pedido = preg_replace('/[^0-9.]*/','',$order->get_total()) - $precio_envio_sucio;
$quitarcomision = round($total_real_pedido /1.035, 2);
$subtotal_sin_paypal=preg_replace('/[^0-9.]*/','',$order->get_total());

if ($metodo_de_pago =='paypal' OR $metodo_de_pago =='paypal_plus')

{
$paypal_sucia= round($quitarcomision*0.035, 2);
$transaccion_paypal= round($paypal_sucia / 1.16, 2);
$iva_paypal_sucio= round($paypal_sucia *0.16 ,2);
$iva_paypal = round($paypal_sucia - $transaccion_paypal, 2);
//Definimos el concepto de pago con paypal por si es necesario aplicarlo
	$conceptopaypal = '<Concepto productoSinube="PAYPAL3.5%" descripcion="COMISION PAYPAL 3.5%" cantidad="1" unidadSinube="H87"
    valorUnitario="'.$transaccion_paypal.'" descuento="0" tipoIVA="'.$datos['tipoIVA'].'" montoBaseIVA="'.$transaccion_paypal.'" montoIVA="'.$iva_paypal.'"
    importe="'.$transaccion_paypal.'" subtotalDet="'.$transaccion_paypal.'"/>';
}

	
//DEFINIMOS VALORES ABSOLUTOS



	$ivaCompleto = $sumaIva + $iva_envio;
	$subtotal = round($sumaImportes + $preciodenevio ,2) ;
	$total_amedias = $subtotal + $ivaCompleto;
	
	//VOY A VER SI HAY DESCUENTO:
	
	$obtenerdescuento = $order->get_total();
	
	$arraycredito = $total_amedias - $obtenerdescuento;
	
	$descuentos = $arraycredito;
	
	$total_total = $total_amedias - $descuentos;
	
	
	
	
	
	
	
	
//Ahora definiremos las variables que vamos a mandar al POST de SiNube
	
// Primero vemos si hay un precio de envio para definir un IF
	if ($preciodenevio > 0){


// Formato a enviar cuando el envio es mayor a 0, osea que hay gastos de envio
$xml="<?xml version='1.0' encoding='utf-8'?>
    <Comprobante sistema='ECOPIPO' almacen='General' generar='NotaDeVenta' rfcEmisor='".$datos['rfcEmisor']."' sucursal='Matriz'  
    nomArchivoDescarga='".$datos['nomArchivoDescarga']."'
    permiteAgregarProductosNoInv='1' folioAutofacturacion='".$datos['folioAutofacturacion']."' formaDePago='".$datos['formaDePago']."'
    observacion='".$datos['observacion']."' referencia='".$datos['referencia']."' subtotal='".$subtotal."' descuento='".$datos['descuento']."' porcentajeIVA='".$datos['porcentajeIVA']."'
    montoIVA='".$ivaCompleto."' total='".$total_total."' monedaSinube='".$datos['monedaSinube']."' difZonaHoraria='".$datos['difZonaHoraria']."'>
    <Receptor rfc='".$rfc_cliente."' razonSocial='".$nombrecompleto."' esPersonaFisica='".$datos['esPersonaFisica']."'/>
    <Conceptos>    
    '".$conceptos.$conceptoenvio."'
    </Conceptos>
    </Comprobante>";
    
    }

   
    else {
    
$xml="<?xml version='1.0' encoding='utf-8'?>
    <Comprobante sistema='ECOPIPO' almacen='General' generar='NotaDeVenta' rfcEmisor='".$datos['rfcEmisor']."' sucursal='Matriz'  
    nomArchivoDescarga='".$datos['nomArchivoDescarga']."'
    permiteAgregarProductosNoInv='1' folioAutofacturacion='".$datos['folioAutofacturacion']."' formaDePago='".$datos['formaDePago']."'
    observacion='".$datos['observacion']."' referencia='".$datos['referencia']."' subtotal='".$subtotal."' descuento='".$datos['descuento']."' porcentajeIVA='".$datos['porcentajeIVA']."'
    montoIVA='".$ivaCompleto."' total='".$total_total."' monedaSinube='".$datos['monedaSinube']."' difZonaHoraria='".$datos['difZonaHoraria']."'>
    <Receptor rfc='".$rfc_cliente."' razonSocial='".$nombrecompleto."' esPersonaFisica='".$datos['esPersonaFisica']."'/>
    <Conceptos>    
    '".$conceptos. "'
    </Conceptos>
    </Comprobante>";
    }
 
	
	
	$genera_xml = htmlentities($xml);
	
 // Ahora vamos a definir los parametros para la conexion con SinNube   
    
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
  $header = array('Content-Type: text/xml','application/xml;charset=UTF-8','application/x-www-form-urlencoded','Content-length: ' . strlen($xml));
  $connection = curl_init();
  curl_setopt($connection, CURLOPT_URL, $url);
  curl_setopt($connection, CURLOPT_HTTPHEADER, $header);
  curl_setopt($connection, CURLOPT_POST, true);
  curl_setopt($connection, CURLOPT_POSTFIELDS,$xml);//le mando el xml
  curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($connection, CURLOPT_CONNECTTIMEOUT, 40000);
  curl_exec($connection);
  $output = curl_exec($connection);
  $cleanoutput = preg_replace('/\D/', '', $output);
	$otheroutput = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $output);
	$respuestas = simplexml_load_string($output);
	
	if (extension_loaded('simplexml')) {

    //$consulta =  (int)$otheroutput->Respuesta->folio;
    $consulta = (string)$respuestas->folio;
    $fechanota = $respuestas->fechaNotaVenta;
    

} else{     
	
	$consulta = "snip snap! no cigar";
	
} 
	
  if(curl_errno($connection)){
        print curl_error($connection);
    }
  
//GRANDE GRANDE IF PARA CUANDO SE CREÉ EXITOSAMENTE LA NOTA
  
  if(!empty($consulta)){
  
  $nota_adding = '<strong>¡NOTA CREADA EXITÓSAMENTE!</strong><br> NOTA DE VENTA NO.: <strong>'.$consulta.'</strong><br>DESCUENTO:'.$arraycredito.'<br><strong>ID PEDIDO: '.$iddelpedido;
    
           //Añadir la nota a la Orden con, ya sea el error de parte de sinube o la Consulta satisfactoria
	$message = sprintf( __( $genera_xml, 'my-textdomain' ), wp_get_current_user()->display_name );
	$order->add_order_note( $nota_adding . "<br> Total de la nota:<br>$" . number_format($total_total,2) ."<br> FOLIO DE AUTOFACTURACION:<br>".$autofacturacion.'<br> ¡RECUERDA CAMBIAR EL CLIENTE!<br><br>' .$ivaCompleto.'<br> SUBTOTAL : '.$subtotal.'<br> TOTAL: '.$total_total.'<br> PAYPAL SUCIA: '.$paypal_sucia ); 
	
	// add the flag so this action won't be shown again
	update_post_meta( $order->id, '_wc_order_marked_printed_for_packaging', $consulta );
	
	}
	
	else{
	
	$erroradding = '¡¡¡¡ALGO SALIÓ MAL!!!!<br>¡AVÍSALE A CHAVA!<br>PAYPAL: '.$transaccion_paypal.'<br> IVA TOTAL :'.$ivaCompleto.'<br> SUBTOTAL : '.$subtotal.'<br> TOTAL: '.$total_total.'<br> PAYPAL SUCIA: '.$paypal_sucia;
	
	//Añadir la nota a la Orden con, ya sea el error de parte de sinube o la Consulta satisfactoria
	$message = sprintf( __( $genera_xml, 'my-textdomain' ), wp_get_current_user()->display_name );
	$order->add_order_note( $xml . '<br>' . $output); 
	
	// add the flag so this action won't be shown again
	update_post_meta( $order->id, '_wc_order_marked_printed_for_packaging', 'ERROR' );
	
	}
	
//TERMINA EL IF PARA LOS ERRORES DE SINUBE
          
	curl_close($connection);
   //return $output; //imprimo la nota de venta
   
   
}
add_action( 'woocommerce_order_action_wc_custom_order_action', 'sv_wc_process_order_meta_box_action' );

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
//-------------------------------------------------------
//A ENSUCIARNOS LAS MANOS CON BULK ACTIONS //
//-------------------------------------------------------

// Adding to admin order list bulk dropdown a custom action 'custom_downloads'
add_filter( 'bulk_actions-edit-shop_order', 'downloads_bulk_actions_edit_product', 20, 1 );
function downloads_bulk_actions_edit_product( $actions ) {
    $actions['write_downloads'] = __( 'Exportar pedido SINUBE ', 'woocommerce' );
    return $actions;
}

// Make the action from selected orders
add_filter( 'handle_bulk_actions-edit-shop_order', 'downloads_handle_bulk_action_edit_shop_order', 10, 3 );
function downloads_handle_bulk_action_edit_shop_order( $redirect_to, $action, $post_ids ) {
    if ( $action !== 'write_downloads' )
        return $redirect_to; // Exit

    global $attach_download_dir, $attach_download_file; // ???

    $processed_ids = array();

    foreach ( $post_ids as $post_id ) {
        $order = wc_get_order( $post_id );
        $order_data = $order->get_data();
        
        		// AQUI EMPIEZA LO MERO BUENO
        		
		$total_orden_sucio = preg_replace('/[^0-9.]*/','',$order->get_total());
		$total_orden = round($total_orden_sucio / 1.16, 1);
		$monto_iva_total = round($total_orden *0.16, 1);
		$folioauto = $order->get_order_number();
		$autofacturacion = $folioauto;
		
		
		//AÑADIMOS EL CAMBIO DE FOLIO SI SE QUIERE FACTURAR
		
			
		$iddelpedido = $order->get_id();
		
		
		$deseofacturar = get_post_meta( $iddelpedido, '_billing_wooccm12', true ); //GET THE SAT CODE
		
		$rfc_cliente ='ECO151106ED3';
		
		
		if($deseofacturar !== 'Si'){
		
		$autofacturacion = $folioauto;
		
		$rfc_cliente = 'ECO151106ED3';
		
		}
		
		// Hasta aqui termina el campo condicional
		
		
		
		$datos=array(
                'rfcEmisor'=>'ECO151106ED3',
                'codigoReporte'=>'prueba',
                'nomArchivoDescarga'=>'Nota de venta',
                'folioAutofacturacion'=>$autofacturacion,
                'formaDePago'=>'01',
                'observacion'=>'Prueba POST',
                'referencia'=>'Desde POST',
                'subtotal'=>$total_orden,
                'descuento'=>'0',
                'porcentajeIVA'=>'16',
                'montoIVA'=>$monto_iva_total, 
                'total'=>$total_orden_sucio,
                'monedaSinube'=>'MXN',
                'difZonaHoraria'=>'-5',
                'rfc'=>'CUNS890508K72',
                'nombre_persona'=> $order->get_billing_first_name(),
                'apellido_persona'=> $order->get_billing_last_name(),
                'esPersonaFisica'=>'0',
                'productoSinube'=>'SEGUNDAPRUEBA',
                'descripcion'=>  $order->get_billing_first_name(),
                'cantidad'=>'1',
                'unidadSinube'=>'H87',
                'valorUnitario'=>preg_replace('/[^0-9.]*/','',$order->get_total()),
                'descuentoProducto'=>'0',
                'tipoIVA'=>'Causa IVA',
                'montoBaseIVA'=>preg_replace('/[^0-9.]*/','',$order->get_total()),
                'montoIVAProducto'=>'0',
                'importe'=>preg_replace('/[^0-9.]*/','',$order->get_total()),
                'subtotalDet'=>preg_replace('/[^0-9.]*/','',$order->get_total()),
                'metodoDeEnvio' => $order->get_shipping_method(),

                );
                
                

     
            
	// Definimos variables para nombre completo
	$nombrecompleto = $datos['nombre_persona']. ' ' .$datos['apellido_persona'];
	//Importante definir primero precio de envio
	
	$precio_envio_sucio = preg_replace('/[^0-9.]*/','',$order->get_total_shipping());
	
if ($precio_envio_sucio > 0){	
	$preciodenevio = round($precio_envio_sucio / 1.16, 2);
	$iva_envio = round($precio_envio_sucio - $preciodenevio, 2);
	
	//Definimos el concepto de envio por si es necesario aplicarlo
	$conceptoenvio = '<Concepto productoSinube="GUIA" descripcion="'.$datos['metodoDeEnvio'].'" cantidad="1" unidadSinube="H87"
    valorUnitario="'.$preciodenevio.'" descuento="0" tipoIVA="'.$datos['tipoIVA'].'" montoBaseIVA="'.$preciodenevio.'" montoIVA="'.$iva_envio.'"
    importe="'.$preciodenevio.'" subtotalDet="'.$preciodenevio.'"/>'; 
}	
	  
	$sumaImportes = 0;
	$sumaIva= 0;
	// Buscar cada articulo en la orden
	foreach ($order->get_items() as $item_id => $item_data) {


    // Tomar una instancia correspondiente al objeto WC_Product
    $product = $item_data->get_product();
    $product_nombre = strtoupper($product->get_name()); // Nombre del producto
    $product_name = preg_replace('/[^\w]/', '', $product_nombre); // NOMBRE LIMPIO
    $productsku = substr( $product->get_sku(),0,20);

    $item_quantity = $item_data->get_quantity(); // Cantidad de productos
	
    $item_total_sucio = $item_data->get_total(); // Total de precio
	$item_total = round($item_total_sucio / 1.16, 2);
		
    $pieza = 'H87';
	
	$precio_pieza_sucio = round($item_total / $item_quantity,2);
    $precio_unitario = round ($precio_pieza_sucio / 1.16, 2); // Precio unitario
	$iva = 'Causa IVA';
	$descuentoenproducto = "0";
	$skudeprueba = "PruebaSKU";
	$monto_iva_producto_sucio = round($item_total *0.16,2);
	$monto_iva_producto = round($item_total_sucio - $item_total, 2);
	$gratis = "100%";
	
	
	//SI EL ARTICULO NO TIENE SKU:
	if (empty($productsku)){
		$productsku = 'NO-SKU';
		}
	
    // Mostrando la información de cada producto
    $conceptos .= '<Concepto productoSinube="'.$productsku.'" descripcion="'.$product_name.'" cantidad="'.$item_quantity.'" unidadSinube="'.$pieza.'"
    valorUnitario="'.($precio_pieza_sucio == 0 ? $precio_pieza_sucio= 0.01 : $precio_pieza_sucio).'" descuento="'.$descuentoenproducto.'" tipoIVA="'.$iva.'" montoBaseIVA="'.($item_total == 0 ? $item_total= 0.01*$item_quantity : $item_total).'" montoIVA="'.$monto_iva_producto.'"
    importe="'.($item_total == 0 ? $item_total= 0.01*$item_quantity : $item_total).'" subtotalDet="'.($item_total == 0 ? $item_total= 0.1 : $item_total).'"/>';
    
    
    
    $sumaIva+= $monto_iva_producto;	
	$sumaImportes+=$item_total;
    
}



//MOSTRAMOS EL METODO DE PAGO:
$metodo_de_pago = $order->get_payment_method();
$total_real_pedido = preg_replace('/[^0-9.]*/','',$order->get_total()) - $precio_envio_sucio;
$quitarcomision = round($total_real_pedido /1.035, 2);
$subtotal_sin_paypal=preg_replace('/[^0-9.]*/','',$order->get_total());

if ($metodo_de_pago =='paypal' OR $metodo_de_pago =='paypal_plus')

{
$paypal_sucia= round($quitarcomision*0.035, 2);
$transaccion_paypal= round($paypal_sucia / 1.16, 2);
$iva_paypal_sucio= round($paypal_sucia *0.16 ,2);
$iva_paypal = round($paypal_sucia - $transaccion_paypal, 2);
//Definimos el concepto de pago con paypal por si es necesario aplicarlo
	$conceptopaypal = '<Concepto productoSinube="PAYPAL3.5%" descripcion="COMISION PAYPAL 3.5%" cantidad="1" unidadSinube="H87"
    valorUnitario="'.$transaccion_paypal.'" descuento="0" tipoIVA="'.$datos['tipoIVA'].'" montoBaseIVA="'.$transaccion_paypal.'" montoIVA="'.$iva_paypal.'"
    importe="'.$transaccion_paypal.'" subtotalDet="'.$transaccion_paypal.'"/>';
}


	
//DEFINIMOS VALORES ABSOLUTOS
	$ivaCompleto = $sumaIva + $iva_envio + $iva_paypal;
	$subtotal = round($sumaImportes + $transaccion_paypal + $preciodenevio ,2) ;
	$total_total = $subtotal + $ivaCompleto;
//Ahora definiremos las variables que vamos a mandar al POST de SiNube
	
// Primero vemos si hay un precio de envio para definir un IF
	if ($preciodenevio > 0){


// Formato a enviar cuando el envio es mayor a 0, osea que hay gastos de envio
$xml="<?xml version='1.0' encoding='utf-8'?>
    <Comprobante sistema='ECOPIPO' almacen='General' generar='NotaDeVenta' rfcEmisor='".$datos['rfcEmisor']."' sucursal='Matriz'  
    nomArchivoDescarga='".$datos['nomArchivoDescarga']."'
    permiteAgregarProductosNoInv='1' folioAutofacturacion='".$datos['folioAutofacturacion']."' formaDePago='".$datos['formaDePago']."'
    observacion='".$datos['observacion']."' referencia='".$datos['referencia']."' subtotal='".$subtotal."' descuento='".$datos['descuento']."' porcentajeIVA='".$datos['porcentajeIVA']."'
    montoIVA='".$ivaCompleto."' total='".$total_total."' monedaSinube='".$datos['monedaSinube']."' difZonaHoraria='".$datos['difZonaHoraria']."'>
    <Receptor rfc='".$rfc_cliente."' razonSocial='".$nombrecompleto."' esPersonaFisica='".$datos['esPersonaFisica']."'/>
    <Conceptos>    
    '".$conceptos.$conceptoenvio."'
    </Conceptos>
    </Comprobante>";
    
    }

   
    else {
    
$xml="<?xml version='1.0' encoding='utf-8'?>
    <Comprobante sistema='ECOPIPO' almacen='General' generar='NotaDeVenta' rfcEmisor='".$datos['rfcEmisor']."' sucursal='Matriz'  
    nomArchivoDescarga='".$datos['nomArchivoDescarga']."'
    permiteAgregarProductosNoInv='1' folioAutofacturacion='".$datos['folioAutofacturacion']."' formaDePago='".$datos['formaDePago']."'
    observacion='".$datos['observacion']."' referencia='".$datos['referencia']."' subtotal='".$subtotal."' descuento='".$datos['descuento']."' porcentajeIVA='".$datos['porcentajeIVA']."'
    montoIVA='".$ivaCompleto."' total='".$total_total."' monedaSinube='".$datos['monedaSinube']."' difZonaHoraria='".$datos['difZonaHoraria']."'>
    <Receptor rfc='".$rfc_cliente."' razonSocial='".$nombrecompleto."' esPersonaFisica='".$datos['esPersonaFisica']."'/>
    <Conceptos>    
    '".$conceptos."'
    </Conceptos>
    </Comprobante>";
    }
 
	
	
	$genera_xml = htmlentities($xml);
	
 // Ahora vamos a definir los parametros para la conexion con SinNube   
    
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
  $header = array('Content-Type: text/xml','application/xml;charset=UTF-8','application/x-www-form-urlencoded','Content-length: ' . strlen($xml));
  $connection = curl_init();
  curl_setopt($connection, CURLOPT_URL, $url);
  curl_setopt($connection, CURLOPT_HTTPHEADER, $header);
  curl_setopt($connection, CURLOPT_POST, true);
  curl_setopt($connection, CURLOPT_POSTFIELDS,$xml);//le mando el xml
  curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($connection, CURLOPT_CONNECTTIMEOUT, 40000);
  curl_exec($connection);
  $output = curl_exec($connection);
  $cleanoutput = preg_replace('/\D/', '', $output);
	$otheroutput = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $output);
	$respuestas = simplexml_load_string($output);
	
	if (extension_loaded('simplexml')) {

    //$consulta =  (int)$otheroutput->Respuesta->folio;
    $consulta = (string)$respuestas->folio;
    $fechanota = $respuestas->fechaNotaVenta;
    

} else{     
	
	$consulta = "snip snap! no cigar";
	
} 
	
  if(curl_errno($connection)){
        print curl_error($connection);
    }
    
    curl_close($connection);
   
  
//GRANDE GRANDE IF PARA CUANDO SE CREÉ EXITOSAMENTE LA NOTA
  
  if(!empty($consulta)){
  
  $nota_adding = '<strong>¡NOTA CREADA EXITÓSAMENTE!</strong><br> NOTA DE VENTA NO.: <strong>'.$consulta.'</strong><br<strong>>FOLIO DE AUTOFACTURACION:<br></strong> '.$autofacturacion;
    
           //Añadir la nota a la Orden con, ya sea el error de parte de sinube o la Consulta satisfactoria
	$message = sprintf( __( $genera_xml, 'my-textdomain' ), wp_get_current_user()->display_name );
	$order->add_order_note( $nota_adding . "<br> Total de la nota:<br>$" . number_format($total_total,2) ."<br> ¡RECUERDA CAMBIAR EL CLIENTE!<br><br>"); 
	
	// add the flag so this action won't be shown again
	update_post_meta( $order->id, '_wc_order_marked_printed_for_packaging', $consulta );
	
	}
	
	else{
	
	$erroradding = '¡¡¡¡ALGO SALIÓ MAL!!!!<br>¡AVÍSALE A CHAVA!';
	
	//Añadir la nota a la Orden con, ya sea el error de parte de sinube o la Consulta satisfactoria
	$message = sprintf( __( $genera_xml, 'my-textdomain' ), wp_get_current_user()->display_name );
	$order->add_order_note( $erroradding . '<br>' . $output); 
	
	// add the flag so this action won't be shown again
	update_post_meta( $order->id, '_wc_order_marked_printed_for_packaging', 'ERROR' );
	
	}
	
//TERMINA EL IF PARA LOS ERRORES DE SINUBE
          
	
   
   


        
    } //TERMINA EL FOREACH

    return $redirect_to = add_query_arg( array(
        'write_downloads' => '1',
        'processed_count' => count( $processed_ids ),
        'processed_ids' => implode( ',', $processed_ids ),
    ), $redirect_to );
}

// The results notice from bulk action on orders
add_action( 'admin_notices', 'downloads_bulk_action_admin_notice' );
function downloads_bulk_action_admin_notice() {
    if ( empty( $_REQUEST['write_downloads'] ) ) return; // Exit

    $count = intval( $_REQUEST['processed_count'] );

    printf( '<div id="message" class="updated fade"><p>' .
        _n( 'Pedidos Exportados.',
        'Pedidos Exportados a Sinube.',
        $count,
        'write_downloads'
    ) . '</p></div>', $count );
}




//------------------------------------------------------
// AÑADIR EL CAMBIO AUTOMATICO PARA EXPORTAR:
//------------------------------------------------------
function mysite_processing($order_id) {
	$order = wc_get_order( $order_id );
	
	$order_data = $order->get_data();
        
        		// AQUI EMPIEZA LO MERO BUENO
        		
		$total_orden_sucio = preg_replace('/[^0-9.]*/','',$order->get_total());
		$total_orden = round($total_orden_sucio / 1.16, 1);
		$monto_iva_total = round($total_orden *0.16, 1);
		$folioauto = $order->get_order_number();
		$autofacturacion = $folioauto;
		
		
		//AÑADIMOS EL CAMBIO DE FOLIO SI SE QUIERE FACTURAR
		
			
		$iddelpedido = $order->get_id();
		
		
		$deseofacturar = get_post_meta( $iddelpedido, '_billing_wooccm12', true ); //GET THE SAT CODE
		
		$rfc_cliente ='ECO151106ED3';	
		
		// Hasta aqui termina el campo condicional
		
		
		$datos=array(
                'rfcEmisor'=>'ECO151106ED3',
                'codigoReporte'=>'prueba',
                'nomArchivoDescarga'=>'Nota de venta',
                'folioAutofacturacion'=>$autofacturacion,
                'formaDePago'=>'01',
                'observacion'=>'Prueba POST',
                'referencia'=>'Desde POST',
                'subtotal'=>$total_orden,
                'descuento'=>'0',
                'porcentajeIVA'=>'16',
                'montoIVA'=>$monto_iva_total, 
                'total'=>$total_orden_sucio,
                'monedaSinube'=>'MXN',
                'difZonaHoraria'=>'-5',
                'rfc'=>'CUNS890508K72',
                'nombre_persona'=> $order->get_billing_first_name(),
                'apellido_persona'=> $order->get_billing_last_name(),
                'esPersonaFisica'=>'0',
                'productoSinube'=>'SEGUNDAPRUEBA',
                'descripcion'=>  $order->get_billing_first_name(),
                'cantidad'=>'1',
                'unidadSinube'=>'H87',
                'valorUnitario'=>preg_replace('/[^0-9.]*/','',$order->get_total()),
                'descuentoProducto'=>'0',
                'tipoIVA'=>'Causa IVA',
                'montoBaseIVA'=>preg_replace('/[^0-9.]*/','',$order->get_total()),
                'montoIVAProducto'=>'0',
                'importe'=>preg_replace('/[^0-9.]*/','',$order->get_total()),
                'subtotalDet'=>preg_replace('/[^0-9.]*/','',$order->get_total()),
                'metodoDeEnvio' => $order->get_shipping_method(),

                );
                
                
    // OBTENER INFORMACION PARA EL RFC 



            
	// Definimos variables para nombre completo
	$nombrecompleto = $datos['nombre_persona']. ' ' .$datos['apellido_persona'];
	//Importante definir primero precio de envio
	
	$precio_envio_sucio = preg_replace('/[^0-9.]*/','',$order->get_total_shipping());
	
if ($precio_envio_sucio > 0){	
	$preciodenevio = round($precio_envio_sucio / 1.16, 2);
	$iva_envio = round($precio_envio_sucio - $preciodenevio, 2);
	
	//Definimos el concepto de envio por si es necesario aplicarlo
	$conceptoenvio = '<Concepto productoSinube="GUIA" descripcion="'.$datos['metodoDeEnvio'].'" cantidad="1" unidadSinube="H87"
    valorUnitario="'.$preciodenevio.'" descuento="0" tipoIVA="'.$datos['tipoIVA'].'" montoBaseIVA="'.$preciodenevio.'" montoIVA="'.$iva_envio.'"
    importe="'.$preciodenevio.'" subtotalDet="'.$preciodenevio.'"/>'; 
}	
	  
	$sumaImportes = 0;
	$sumaIva= 0;
	// Buscar cada articulo en la orden
	foreach ($order->get_items() as $item_id => $item_data) {


    // Tomar una instancia correspondiente al objeto WC_Product
    $product = $item_data->get_product();
    $product_nombre = strtoupper($product->get_name()); // Nombre del producto
    $product_name = preg_replace('/[^\w]/', '', $product_nombre); // NOMBRE LIMPIO
    $productsku = substr( $product->get_sku(),0,20);

    $item_quantity = $item_data->get_quantity(); // Cantidad de productos
	
    $item_total_sucio = $item_data->get_total(); // Total de precio
	$item_total = round($item_total_sucio / 1.16, 2);
		
    $pieza = 'H87';
	
	$precio_pieza_sucio = round($item_total / $item_quantity,2);
    $precio_unitario = round ($precio_pieza_sucio / 1.16, 2); // Precio unitario
	$iva = 'Causa IVA';
	$descuentoenproducto = "0";
	$skudeprueba = "PruebaSKU";
	$monto_iva_producto_sucio = round($item_total *0.16,2);
	$monto_iva_producto = round($item_total_sucio - $item_total, 2);
	$gratis = "100%";
	
	
	//SI EL ARTICULO NO TIENE SKU:
	if (empty($productsku)){
		$productsku = 'NO-SKU';
		}
	
    // Mostrando la información de cada producto
    $conceptos .= '<Concepto productoSinube="'.$productsku.'" descripcion="'.$product_name.'" cantidad="'.$item_quantity.'" unidadSinube="'.$pieza.'"
    valorUnitario="'.($precio_pieza_sucio == 0 ? $precio_pieza_sucio= 0.01 : $precio_pieza_sucio).'" descuento="'.$descuentoenproducto.'" tipoIVA="'.$iva.'" montoBaseIVA="'.($item_total == 0 ? $item_total= 0.01*$item_quantity : $item_total).'" montoIVA="'.$monto_iva_producto.'"
    importe="'.($item_total == 0 ? $item_total= 0.01*$item_quantity : $item_total).'" subtotalDet="'.($item_total == 0 ? $item_total= 0.1 : $item_total).'"/>';
    
    
    
    $sumaIva+= $monto_iva_producto;	
	$sumaImportes+=$item_total;
    
}



//MOSTRAMOS EL METODO DE PAGO:
$metodo_de_pago = $order->get_payment_method();
$total_real_pedido = preg_replace('/[^0-9.]*/','',$order->get_total()) - $precio_envio_sucio;
$quitarcomision = round($total_real_pedido /1.035, 2);
$subtotal_sin_paypal=preg_replace('/[^0-9.]*/','',$order->get_total());

if ($metodo_de_pago =='paypal' OR $metodo_de_pago =='paypal_plus')

{
$paypal_sucia= round($quitarcomision*0.035, 2);
$transaccion_paypal= round($paypal_sucia / 1.16, 2);
$iva_paypal_sucio= round($paypal_sucia *0.16 ,2);
$iva_paypal = round($paypal_sucia - $transaccion_paypal, 2);
//Definimos el concepto de pago con paypal por si es necesario aplicarlo
	$conceptopaypal = '<Concepto productoSinube="PAYPAL3.5%" descripcion="COMISION PAYPAL 3.5%" cantidad="1" unidadSinube="H87"
    valorUnitario="'.$transaccion_paypal.'" descuento="0" tipoIVA="'.$datos['tipoIVA'].'" montoBaseIVA="'.$transaccion_paypal.'" montoIVA="'.$iva_paypal.'"
    importe="'.$transaccion_paypal.'" subtotalDet="'.$transaccion_paypal.'"/>';
}
	
	
	
//DEFINIMOS VALORES ABSOLUTOS
	$ivaCompleto = $sumaIva + $iva_envio;
	$subtotal = round($sumaImportes + $preciodenevio ,2) ;
	$total_total = $subtotal + $ivaCompleto;
//Ahora definiremos las variables que vamos a mandar al POST de SiNube
	
// Primero vemos si hay un precio de envio para definir un IF
	if ($preciodenevio > 0){


// Formato a enviar cuando el envio es mayor a 0, osea que hay gastos de envio
$xml="<?xml version='1.0' encoding='utf-8'?>
    <Comprobante sistema='ECOPIPO' almacen='General' generar='NotaDeVenta' rfcEmisor='".$datos['rfcEmisor']."' sucursal='Matriz'  
    nomArchivoDescarga='".$datos['nomArchivoDescarga']."'
    permiteAgregarProductosNoInv='1' folioAutofacturacion='".$datos['folioAutofacturacion']."' formaDePago='".$datos['formaDePago']."'
    observacion='".$datos['observacion']."' referencia='".$datos['referencia']."' subtotal='".$subtotal."' descuento='".$datos['descuento']."' porcentajeIVA='".$datos['porcentajeIVA']."'
    montoIVA='".$ivaCompleto."' total='".$total_total."' monedaSinube='".$datos['monedaSinube']."' difZonaHoraria='".$datos['difZonaHoraria']."'>
    <Receptor rfc='".$rfc_cliente."' razonSocial='".$nombrecompleto."' esPersonaFisica='".$datos['esPersonaFisica']."'/>
    <Conceptos>    
    '".$conceptos.$conceptoenvio."'
    </Conceptos>
    </Comprobante>";
    
    }

   
    else {
    
$xml="<?xml version='1.0' encoding='utf-8'?>
    <Comprobante sistema='ECOPIPO' almacen='General' generar='NotaDeVenta' rfcEmisor='".$datos['rfcEmisor']."' sucursal='Matriz'  
    nomArchivoDescarga='".$datos['nomArchivoDescarga']."'
    permiteAgregarProductosNoInv='1' folioAutofacturacion='".$datos['folioAutofacturacion']."' formaDePago='".$datos['formaDePago']."'
    observacion='".$datos['observacion']."' referencia='".$datos['referencia']."' subtotal='".$subtotal."' descuento='".$datos['descuento']."' porcentajeIVA='".$datos['porcentajeIVA']."'
    montoIVA='".$ivaCompleto."' total='".$total_total."' monedaSinube='".$datos['monedaSinube']."' difZonaHoraria='".$datos['difZonaHoraria']."'>
    <Receptor rfc='".$rfc_cliente."' razonSocial='".$nombrecompleto."' esPersonaFisica='".$datos['esPersonaFisica']."'/>
    <Conceptos>    
    '".$conceptos."'
    </Conceptos>
    </Comprobante>";
    }
 
	
	
	$genera_xml = htmlentities($xml);
	
 // Ahora vamos a definir los parametros para la conexion con SinNube   
    
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
  $header = array('Content-Type: text/xml','application/xml;charset=UTF-8','application/x-www-form-urlencoded','Content-length: ' . strlen($xml));
  $connection = curl_init();
  curl_setopt($connection, CURLOPT_URL, $url);
  curl_setopt($connection, CURLOPT_HTTPHEADER, $header);
  curl_setopt($connection, CURLOPT_POST, true);
  curl_setopt($connection, CURLOPT_POSTFIELDS,$xml);//le mando el xml
  curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($connection, CURLOPT_CONNECTTIMEOUT, 40000);
  curl_exec($connection);
  $output = curl_exec($connection);
  $cleanoutput = preg_replace('/\D/', '', $output);
	$otheroutput = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $output);
	$respuestas = simplexml_load_string($output);
	
	if (extension_loaded('simplexml')) {

    //$consulta =  (int)$otheroutput->Respuesta->folio;
    $consulta = (string)$respuestas->folio;
    $fechanota = $respuestas->fechaNotaVenta;
    

} else{     
	
	$consulta = "snip snap! no cigar";
	
} 
	
  if(curl_errno($connection)){
        print curl_error($connection);
    }
    
    curl_close($connection);
   
  
//GRANDE GRANDE IF PARA CUANDO SE CREÉ EXITOSAMENTE LA NOTA
  
  if(!empty($consulta)){
  
  $nota_adding = '<strong>¡NOTA CREADA EXITÓSAMENTE!</strong><br> NOTA DE VENTA NO.: <strong>'.$consulta.'</strong><br><strong>FOLIO DE AUTOFACTURACION:</strong><br> '.$autofacturacion;
    
           //Añadir la nota a la Orden con, ya sea el error de parte de sinube o la Consulta satisfactoria
	$message = sprintf( __( $genera_xml, 'my-textdomain' ), wp_get_current_user()->display_name );
	$order->add_order_note( $nota_adding . "<br> Total de la nota:<br>$" . number_format($total_total,2) ."<br> ¡RECUERDA CAMBIAR EL CLIENTE!<br><br>"); 
	
	// add the flag so this action won't be shown again
	update_post_meta( $order->id, '_wc_order_marked_printed_for_packaging', $consulta );
	
	}
	
	else{
	
	$erroradding = '¡¡¡¡ALGO SALIÓ MAL!!!!<br>¡AVÍSALE A CHAVA!';
	
	//Añadir la nota a la Orden con, ya sea el error de parte de sinube o la Consulta satisfactoria
	$message = sprintf( __( $genera_xml, 'my-textdomain' ), wp_get_current_user()->display_name );
	$order->add_order_note( $erroradding . '<br>' . $output); 
	
	// add the flag so this action won't be shown again
	update_post_meta( $order->id, '_wc_order_marked_printed_for_packaging', 'ERROR' );
	
	}
	
//TERMINA EL IF PARA LOS ERRORES DE SINUBE
	
    
    }

add_action( 'woocommerce_order_status_processing', 'mysite_processing', 10, 1); 








//AÑADIR EL IFRAME DE FACTURACION

add_action( 'woocommerce_account_content', 'sinube_autofacturacion' );
function sinube_autofacturacion(){
	echo '
	<style>
	.woocommerce-page table.shop_table.my_account_orders tbody td, .woocommerce table.shop_table.order_details tfoot tr:last-child th, .woocommerce table.shop_table.order_details tfoot tr:last-child td{
padding:0px 0px !important;
}

	tr:nth-child(even) {background: #ebfdf3;}
tr:nth-child(odd) {background: #FFF;}


	</style>
	<div style="width:90%; margin-bottom:30px; margin-left:auto; margin-right:auto; text-align:center; background-color:#FFF; border: 3px solid #a2c531; padding:20px;">
	<h1 style="text-align:center; margin-top:0px; margin-bottom:30px;"> AUTOFACTURACIÓN </h1><br>
	<p> NOTA: Hay un cambio momentaneo en la manera de facturar, si tu numero de pedido te da un error, entonces por favor agrega -NF
	a tu numero de pedido, por ejemplo: si tu pedido es el número 123456 y éste te da un error, agregalo como NF-123456.</p>
	<p> Este cambio es momentaneo y pronto se resolverá, agradecemos mucho su atención y comprensión.</p>
	<div class="col" style="float:left; width:48%;">
	<a href="#" onclick="abrirvideo()" class="button" style="
	margin-left: auto !important;
    margin-right: auto !important;
    float: unset;"> ¿CÓMO AUTOFACTURAR? </a>
    </div>
    <div class="col" style="float:left; width:48%;">
	<a href="https://kiosco-dot-si-nube.appspot.com/?mprs=RUNPMTUxMTA2RUQz" target="_blank" class="button" style="
	margin-left: auto !important;
    margin-right: auto !important;
    float: unset;"> IR AL KIOSKO DE AUTOFACTURACIÓN </a>
    </div>
    
	<br style="clear:both;"><br>
	
	<div style="width:100%; display:none;" id="videoautofacturacion">
	<iframe width="100%" height="315" src="https://www.youtube.com/embed/7XCM_SLQ5zU" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
	</div>	
	</div>
	
	
	<script>
	function abrirvideo() {
    var x = document.getElementById("videoautofacturacion");
    if (x.style.display === "none") {
        x.style.display = "block";
    } else {
        x.style.display = "none";
    }
}
 	</script>
	
	';
}

//PRUEBA DE CUSTOM SALE TAG
//
function customoferta(){

  $room = get_post_meta( get_the_id(), '_yith_wcbm_product_meta', true );
	$rooms = unserialize($room);
	$id_ofer = $room['id_badge'];
	
	if ($id_ofer == 29470){
		echo '<div style="
		margin: 0;
    padding: 5px;
    background-color: rgba(121,80,224,.63);
    border-style: solid;
    border-width: 1px;
    border-color: rgba(63,0,224,.63);
    border-radius: 0 0 0 10px;
	position:absolute;
	top:0px;
	right:0px;
	color:#fff;
	z-index:500;
	font-family: Short Stack;">
	¡PAÑAL CON CAUSA!<br>
	Entrega 17 de Enero</div>';
	}
	
	if ($id_ofer == 32920){
		echo '<div style="
		margin: 0;
    padding: 5px;
    background-color: rgba(121,80,224,.63);
    border-style: solid;
    border-width: 1px;
    border-color: rgba(63,0,224,.63);
    border-radius: 0 0 0 10px;
	position:absolute;
	top:0px;
	right:0px;
	color:#fff;
	z-index:500;
	font-family: Short Stack;">
	-15%</div>';
	}
	
	
   		
}

add_shortcode( 'customoferta', 'customoferta' );


//AÑADIR SEGUROS:

add_action('woocommerce_cart_totals_after_shipping', 'wc_shipping_insurance_note_after_cart');
function wc_shipping_insurance_note_after_cart() {

wp_enqueue_script( 'modal', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-modal/2.2.6/js/bootstrap-modal.min.js', array(), null, true );


global $woocommerce;
    $product_id = Array( 44404, 44406, 44408, 44412, 44414);
foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
    $_product = $values['data'];
    $id_prodcomparar = $_product->id;
    if (in_array($id_prodcomparar , $product_id, true))
        $found = true;
    }
    // if product not found, add it
if ( ! $found ):

	$totaldecarrito = $woocommerce->cart->cart_contents_total;
	
	if ($totaldecarrito >1){
    
    ?>
    
    
    
    
    <tr class="shipping">
        <th><?php _e( 'Seguro de envíos', 'woocommerce' ); ?></th>
        <td>
        <span class="btn boton btn-info envios" style="width:100% !important;"><?php _e( 'Añadir seguro' ); ?> </span> </td>
    </tr>
    <?php }
   
 endif;  
    
 
 

 
 
    
  
}

/*--------------------------------------------------------------------------------------------------------------------------------------------------------*/
/* NO AGREGE NING⁄N C”DIGO M¡S ABAJO DE AQUÕ O LE DAR¡ ERRORES */
/*---------------------------------------------------------------------------------------------------------------------------------------------------------*/