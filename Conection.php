 $xml="<?xml version='1.0' encoding='utf-8'?>
    <Comprobante
    sistema='ECOPIPO'
    almacen='General'
    generar='NotaDeVenta'
    rfcEmisor='ECO151106ED3'
    sucursal='Matriz'  
    nomArchivoDescarga='Nota de venta'
    permiteAgregarProductosNoInv='1'
    folioAutofacturacion='".$folioauto."'
    formaDePago='01'
    observacion='Prueba POST'
    referencia='Desde POST'
    subtotal='".$sumaproductosl."'
    descuento='0'
    porcentajeIVA='16'
    montoIVA='".$sumaiva."'
    total='".$sumatotales."'
    monedaSinube='MXN'
    difZonaHoraria='-5'>
    <Receptor
        rfc='XAXX010101000'
        razonSocial='".$nombrecompleto."'
        esPersonaFisica='0'/>
    <Conceptos>    
    '".$conceptos."'
    </Conceptos>
    </Comprobante>";



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
