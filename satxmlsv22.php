<?php
//
// +---------------------------------------------------------------------------+
// | satxmlsv22.php Procesa el arreglo asociativo de intercambio y genera un   |
// |               mensaje XML con los requisitos del SAT de la version 2.2    |
// |               publicada en el DOF del ? de Diciembre del 2011.            |
// |                                                                           |
// |               Si se incluye un texto en edidata se agrega como Addenda    |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2011  Fabrica de Jabon la Corona, SA de CV                  |
// +---------------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU General Public License               |
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// |                                                                           |
// | This program is distributed in the hope that it will be useful,           |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
// | GNU General Public License for more details.                              |
// |                                                                           |
// | You should have received a copy of the GNU General Public License         |
// | along with this program; if not, write to the Free Software               |
// | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA|
// +---------------------------------------------------------------------------|
// | Autor: Fernando Ortiz <fortiz@lacorona.com.mx>                            |
// +---------------------------------------------------------------------------+
// |19/dic/2011  Se toma como base el prograba de la version 2.0 se agregan los|
// |             nuevos nodos, pero se usa xslt para formar la cadena original |
// +---------------------------------------------------------------------------+
//

// {{{  satxmlsv22
function satxmlsv22($arr, $edidata=false, $dir="./tmp/",$nodo="",$addenda="") {
global $xml, $cadena_original, $conn, $sello, $texto, $ret;
error_reporting(E_ALL & ~(E_WARNING | E_NOTICE));
satxmlsv22_genera_xml($arr,$edidata,$dir,$nodo,$addenda);
satxmlsv22_genera_cadena_original();
satxmlsv22_sella($arr);
$ret = satxmlsv22_termina($arr,$dir);
return $ret;
}
// }}}
// {{{  satxmlsv22_genera_xml
function satxmlsv22_genera_xml($arr, $edidata, $dir,$nodo,$addenda) {
global $xml, $ret;
$xml = new DOMdocument("1.0","UTF-8");
satxmlsv22_generales($arr, $edidata, $dir,$nodo,$addenda);
satxmlsv22_emisor($arr, $edidata, $dir,$nodo,$addenda);
satxmlsv22_receptor($arr, $edidata, $dir,$nodo,$addenda);
satxmlsv22_conceptos($arr, $edidata, $dir,$nodo,$addenda);
satxmlsv22_impuestos($arr, $edidata, $dir,$nodo,$addenda);
satxmlsv22_complemento($arr, $edidata, $dir,$nodo,$addenda);
$ok = satxmlsv22_valida();
if (!$ok) {
    die("Error al validar XSD\n");
}
satxmlsv22_addenda($arr, $edidata, $dir,$nodo,$addenda);
}
// }}}
// {{{  Datos generales del Comprobante
function satxmlsv22_generales($arr, $edidata, $dir,$nodo,$addenda) {
global $root, $xml;
$root = $xml->createElement("Comprobante");
$root = $xml->appendChild($root);
if ($addenda=="detallista") {
    # 12/Mar/2009   Se agrega el namespace de detallista para futurama
    satxmlsv22_cargaAtt($root, array("xmlns"=>"http://www.sat.gob.mx/cfd/2",
                          "xmlns:xsi"=>"http://www.w3.org/2001/XMLSchema-instance",
                          "xmlns:detallista"=>"http://www.sat.gob.mx/detallista",
                          "xsi:schemaLocation"=>"http://www.sat.gob.mx/cfd/2 http://www.sat.gob.mx/sitio_internet/cfd/2/cfdv22.xsd http://www.sat.gob.mx/detallista http://www.sat.gob.mx/sitio_internet/cfd/detallista/detallista.xsd"
                         )
                );
} elseif ($addenda=="diconsa") {
    # 23/Oct/2009   Se agrega el namespace de Diconsa
    satxmlsv22_cargaAtt($root, array("xmlns"=>"http://www.sat.gob.mx/cfd/2",
                          "xmlns:xsi"=>"http://www.w3.org/2001/XMLSchema-instance",
                          "xmlns:Diconsa"=>"http://www.diconsa.gob.mx/cfd",
                          "xsi:schemaLocation"=>"http://www.sat.gob.mx/cfd/2 http://www.sat.gob.mx/sitio_internet/cfd/2/cfdv22.xsd http://www.diconsa.gob.mx/cfd http://www.diconsa.gob.mx/cfd/diconsa.xsd"
                      )
                  );
} elseif ($addenda=="superneto") {
    # 26/Ago/2010   Se agrega el namespace de SuperNeto
    satxmlsv22_cargaAtt($root, array("xmlns"=>"http://www.sat.gob.mx/cfd/2",
                          "xmlns:xsi"=>"http://www.w3.org/2001/XMLSchema-instance",
                          "xmlns:ap"=>"http://www.tiendasneto.com/ap",
                          "xsi:schemaLocation"=>"http://www.sat.gob.mx/cfd/2 http://www.sat.gob.mx/sitio_internet/cfd/2/cfdv22.xsd http://www.tiendasneto.com/ap addenda_prov.xsd"
                      )
                  );
} elseif ($addenda=="extra") {
    # 04/Ene/2012   Se agrega el namespace de Tiendas Extra
    satxmlsv22_cargaAtt($root, array ("xmlns"=>"http://www.sat.gob.mx/cfd/2",
                          "xmlns:xsi"=>"http://www.w3.org/2001/XMLSchema-instance",
                          //"xmlns:modelo"=>"http://www.pegasotecnologia.com/secfd/Schemas",
                          //"xsi:schemaLocation"=>"http://www.sat.gob.mx/cfd/2 http://www.sat.gob.mx/sitio_internet/cfd/2/cfdv22.xsd http://www.pegasotecnologia.com/secfd/Schemas http://www.pegasotecnologia.com/secfd/Schemas/ADDENDAMODELO.xsd" 
                          "xmlns:modelo"=>"http://www.gmodelo.com.mx/CFD/Addenda/Receptor",
                          "xsi:schemaLocation"=>"http://www.sat.gob.mx/cfd/2 http://www.sat.gob.mx/sitio_internet/cfd/2/cfdv22.xsd http://www.gmodelo.com.mx/CFD/Addenda/Receptor https://femodelo.gmodelo.com/Addenda/ADDENDAMODELO.xsd"
                      )
                  );
} else {
    satxmlsv22_cargaAtt($root, array("xmlns"=>"http://www.sat.gob.mx/cfd/2",
                          "xmlns:xsi"=>"http://www.w3.org/2001/XMLSchema-instance",
                          "xsi:schemaLocation"=>"http://www.sat.gob.mx/cfd/2  http://www.sat.gob.mx/sitio_internet/cfd/2/cfdv22.xsd"
                         )
                     );
}
                       
satxmlsv22_cargaAtt($root, array("version"=>"2.2",
                      "serie"=>$arr['serie'],
                      "folio"=>$arr['folio'],
                      "fecha"=>satxmlsv22_xml_fech($arr['fecha']),
                      "sello"=>"@",
                      "noAprobacion"=>$arr['noAprobacion'],
                      "anoAprobacion"=>$arr['anoAprobacion'],
                      "formaDePago"=>$arr['formaDePago'],
                      "noCertificado"=>$arr['noCertificado'],
                      "certificado"=>"@",
                      "subTotal"=>$arr['subTotal'],
                      "descuento"=>"0",
                      "total"=>$arr['total'],
                      "tipoDeComprobante"=>$arr['tipoDeComprobante'],
                      "metodoDePago"=>$arr['metodoDePago'],
                      "LugarExpedicion"=>$arr['LugarExpedicion'],
                      "NumCtaPago"=>$arr['NumCtaPago'],
                      "FolioFiscalOrig"=>$arr['FolioFiscalOrig'],
                      "SerieFolioFiscalOrig"=>$arr['SerieFolioFiscalOrig'],
                      "FechaFolioFiscalOrig"=>satxmlsv22_xml_fech($arr['FechaFolioFiscalOrig']),
                      "MontoFolioFiscalOrig"=>$arr['MontoFolioFiscalOrig']
                   )
                );
}
// }}}
// {{{ Datos del Emisor
function satxmlsv22_emisor($arr, $edidata, $dir,$nodo,$addenda) {
global $root, $xml;
$emisor = $xml->createElement("Emisor");
$emisor = $root->appendChild($emisor);
satxmlsv22_cargaAtt($emisor, array("rfc"=>$arr['Emisor']['rfc'],
                       "nombre"=>$arr['Emisor']['nombre']
                   )
                );
$domfis = $xml->createElement("DomicilioFiscal");
$domfis = $emisor->appendChild($domfis);
satxmlsv22_cargaAtt($domfis, array("calle"=>"CARLOS B. ZETINA",
                        "noExterior"=>"80",
                        "noInterior"=>"",
                        "colonia"=>"PARQUE INDUSTRIAL XALOSTOC",
                        "municipio"=>"ECATEPEC DE MORELOS",
                        "estado"=>"MEXICO",
                        "pais"=>"MEXICO",
                        "codigoPostal"=>"55348"
                   )
                );
$regimen = $xml->createElement("RegimenFiscal");
$expedido = $emisor->appendChild($regimen);
satxmlsv22_cargaAtt($regimen, array("Regimen"=>$arr['Emisor']['Regimen']
                   )
                );
}
// }}}
// {{{ Datos del Receptor
function satxmlsv22_receptor($arr, $edidata, $dir,$nodo,$addenda) {
global $root, $xml;
$receptor = $xml->createElement("Receptor");
$receptor = $root->appendChild($receptor);
satxmlsv22_cargaAtt($receptor, array("rfc"=>$arr['Receptor']['rfc'],
                          "nombre"=>$arr['Receptor']['nombre']
                      )
                  );
$domicilio = $xml->createElement("Domicilio");
$domicilio = $receptor->appendChild($domicilio);
satxmlsv22_cargaAtt($domicilio, array("calle"=>$arr['Receptor']['Domicilio']['calle'],
                        "noExterior"=>$arr['Receptor']['Domicilio']['noExterior'],
                        "noInterior"=>$arr['Receptor']['Domicilio']['noInterior'],
                       "colonia"=>$arr['Receptor']['Domicilio']['colonia'],
                       "municipio"=>$arr['Receptor']['Domicilio']['municipio'],
                       "estado"=>$arr['Receptor']['Domicilio']['estado'],
                       "pais"=>$arr['Receptor']['Domicilio']['pais'],
                       "codigoPostal"=>$arr['Receptor']['Domicilio']['codigoPostal'],
                   )
               );
}
// }}}
// {{{ Detalle de los conceptos/productos de la factura
function satxmlsv22_conceptos($arr, $edidata, $dir,$nodo,$addenda) {
global $root, $xml;
$conceptos = $xml->createElement("Conceptos");
$conceptos = $root->appendChild($conceptos);
for ($i=1; $i<=sizeof($arr['Conceptos']); $i++) {
    $concepto = $xml->createElement("Concepto");
    $concepto = $conceptos->appendChild($concepto);
    $prun = $arr['Conceptos'][$i]['valorUnitario'];
    satxmlsv22_cargaAtt($concepto, array("cantidad"=>$arr['Conceptos'][$i]['cantidad'],
                              "unidad"=>$arr['Conceptos'][$i]['unidad'],
                              "descripcion"=>$arr['Conceptos'][$i]['descripcion'],
                              "valorUnitario"=>$arr['Conceptos'][$i]['valorUnitario'],
                              "importe"=>$arr['Conceptos'][$i]['importe'],
                   )
                );
}
}
// }}}
// {{{ Impuesto (IVA)
function satxmlsv22_impuestos($arr, $edidata, $dir,$nodo,$addenda) {
global $root, $xml;
$impuestos = $xml->createElement("Impuestos");
$impuestos = $root->appendChild($impuestos);
if (isset($arr['Traslados']['importe'])) {
    $traslados = $xml->createElement("Traslados");
    $traslados = $impuestos->appendChild($traslados);
    $traslado = $xml->createElement("Traslado");
    $traslado = $traslados->appendChild($traslado);
    satxmlsv22_cargaAtt($traslado, array("impuesto"=>$arr['Traslados']['impuesto'],
                              "tasa"=>$arr['Traslados']['tasa'],
                              "importe"=>$arr['Traslados']['importe']
                             )
                         );
}
$impuestos->SetAttribute("totalImpuestosTrasladados",$arr['Traslados']['importe']);
}
// }}}
// {{{ Complemento si es detallista
function satxmlsv22_complemento($arr, $edidata, $dir,$nodo,$addenda) {
global $root, $xml;
if ($addenda=="detallista") {
    $Complemento = $xml->createElement("Complemento");
    $Complemento = $root->appendChild($Complemento);
    $detallista = $xml->createElement("detallista:detallista");
    $detallista->SetAttribute("type","SimpleInvoiceType");
    $detallista->SetAttribute("contentVersion","1.3.1");
    $detallista->SetAttribute("documentStructureVersion","AMC8.1"); 
    $detallista->SetAttribute("documentStatus","ORIGINAL");
       $requestForPaymentIdentification = $xml->createElement("detallista:requestForPaymentIdentification");
           $entityType = $xml->createElement("detallista:entityType","INVOICE");
           $entityType = $requestForPaymentIdentification->appendChild($entityType);
       $requestForPaymentIdentification = $detallista->appendChild($requestForPaymentIdentification);

       $orderIdentification = $xml->createElement("detallista:orderIdentification");
           $referenceIdentification = $xml->createElement("detallista:referenceIdentification",trim($arr['Complemento']['npec']));
           $referenceIdentification->SetAttribute("type","ON");
           $referenceIdentification = $orderIdentification->appendChild($referenceIdentification);
           $ReferenceDate = $xml->createElement("detallista:ReferenceDate",satxmlsv22_xml_fix_fech($arr['Complemento']['fpec']));
           $ReferenceDate = $orderIdentification->appendChild($ReferenceDate);
       $orderIdentification = $detallista->appendChild($orderIdentification);

       $AdditionalInformation = $xml->createElement("detallista:AdditionalInformation");
           $referenceIdentification = $xml->createElement("detallista:referenceIdentification",$arr['serie'].$arr['folio']);
           $referenceIdentification->SetAttribute("type","IV");
           $referenceIdentification = $AdditionalInformation->appendChild($referenceIdentification);
       $AdditionalInformation = $detallista->appendChild($AdditionalInformation);

       $buyer = $xml->createElement("detallista:buyer");
           $gln = $xml->createElement("detallista:gln",trim($arr['Complemento']['gln']));
           $gln = $buyer->appendChild($gln);
       $buyer = $detallista->appendChild($buyer);

       $seller = $xml->createElement("detallista:seller");
       $gln = $xml->createElement("detallista:gln", '0000000001867');
       $alternatePartyIdentification = $xml->createElement("detallista:alternatePartyIdentification", "01867");
       $alternatePartyIdentification->setAttribute("type","SELLER_ASSIGNED_IDENTIFIER_FOR_A_PARTY");
       $tmp = $seller->appendChild($gln);
       $tmp = $seller->appendChild($alternatePartyIdentification);
       $tmp = $detallista->appendChild($seller);

       for ($i=1; $i<=sizeof($arr['Conceptos']); $i++) {
           $lineItem = $xml->createElement("detallista:lineItem");
           $lineItem->SetAttribute("type","SimpleInvoiceLineItemType");
           $lineItem->SetAttribute("number",$i);

               $tradeItemIdentification = $xml->createElement("detallista:tradeItemIdentification");
                   $gtin = $xml->createElement("detallista:gtin",trim($arr['Conceptos'][$i]['gtin']));
                   $gtin = $tradeItemIdentification->appendChild($gtin);
               $tradeItemIdentification = $lineItem->appendChild($tradeItemIdentification);

               $tradeItemDescriptionInformation = $xml->createElement("detallista:tradeItemDescriptionInformation");
               $tradeItemDescriptionInformation->SetAttribute("language","ES");
                   $longText = $xml->createElement("detallista:longText",$arr['Conceptos'][$i]['descripcion']);
                   $longText = $tradeItemDescriptionInformation->appendChild($longText);
               $tradeItemDescriptionInformation = $lineItem->appendChild($tradeItemDescriptionInformation);

               $invoicedQuantity = $xml->createElement("detallista:invoicedQuantity",$arr['Conceptos'][$i]['cantidad']);
               $invoicedQuantity->SetAttribute("unitOfMeasure","CS");
               $invoicedQuantity = $lineItem->appendChild($invoicedQuantity);

               $grossPrice = $xml->createElement("detallista:grossPrice");
                   $Amount = $xml->createElement("detallista:Amount",$arr['Conceptos'][$i]['prun']);
                   $Amount = $grossPrice->appendChild($Amount);
               $grossPrice = $lineItem->appendChild($grossPrice);

               $netPrice = $xml->createElement("detallista:netPrice");
                   $Amount = $xml->createElement("detallista:Amount",$arr['Conceptos'][$i]['neto'] / $arr['Conceptos'][$i]['cantidad']);
                   $Amount = $netPrice->appendChild($Amount);
               $netPrice = $lineItem->appendChild($netPrice);

               $tradeItemTaxInformation = $xml->createElement("detallista:tradeItemTaxInformation");
                   $taxTypeDescription = $xml->createElement("detallista:taxTypeDescription","VAT");
                   $taxTypeDescription = $tradeItemTaxInformation->appendChild($taxTypeDescription);

                   $tradeItemTaxAmount = $xml->createElement("detallista:tradeItemTaxAmount");
                   $taxPercentage = $xml->createElement("detallista:taxPercentage",$arr['Conceptos'][$i]['poim']);
                       $taxPercentage = $tradeItemTaxAmount->appendChild($taxPercentage);

                       $taxAmount = $xml->createElement("detallista:taxAmount",$arr['Conceptos'][$i]['impu']);
                       $taxAmount = $tradeItemTaxAmount->appendChild($taxAmount);
                   $tradeItemTaxAmount = $tradeItemTaxInformation->appendChild($tradeItemTaxAmount);

                   $taxCategory = $xml->createElement("detallista:taxCategory","TRANSFERIDO");
                   $taxCategory = $tradeItemTaxInformation->appendChild($taxCategory);
               $tradeItemTaxInformation = $lineItem->appendChild($tradeItemTaxInformation);

               $totalLineAmount = $xml->createElement("detallista:totalLineAmount");
                   $netAmount = $xml->createElement("detallista:netAmount");
                       $Amount = $xml->createElement("detallista:Amount",$arr['Conceptos'][$i]['importe']);
                       $Amount = $netAmount->appendChild($Amount);
                   $netAmount = $totalLineAmount->appendChild($netAmount);
               $totalLineAmount = $lineItem->appendChild($totalLineAmount);

           $lineItem = $detallista->appendChild($lineItem);

       }

       $totalAmount = $xml->createElement("detallista:totalAmount");
           $Amount = $xml->createElement("detallista:Amount",$arr['total']);
           $Amount = $totalAmount->appendChild($Amount);
       $totalAmount = $detallista->appendChild($totalAmount);

    $detallista = $Complemento->appendChild($detallista);
}
}
// }}}
// {{{ Addenda si se requiere
function satxmlsv22_addenda($arr, $edidata, $dir,$nodo,$addenda) {
global $root, $xml;
if ($edidata || $addenda=="diconsa" || $addenda=="imss") {
    $Addenda = $xml->createElement("Addenda");
    if ($edidata!="") {
        if (substr($edidata,0,5) == "<?xml") {
            // Es XML por ejemplo Soriana
            $smp = simplexml_load_string($edidata);
            $Documento = dom_import_simplexml($smp);
            $Documento = $xml->importNode($Documento, true);
        } else {
            if ($nodo=="") {
                // Va el EDIDATA directo sin nodo adiconal. por ejemplo Corvi
                $Documento = $xml->createTextNode(utf8_encode($edidata));
            } else {
                // Va el EDIDATA dentro de un nodo. por ejemplo Walmart
                $Documento = $xml->createElement($nodo,utf8_encode($edidata));
            }
        }
        $Documento = $Addenda->appendChild($Documento);
    }
    if ($addenda=="diconsa") {
        $Agregado = $xml->createElement("Diconsa:Agregado");
        $Agregado->SetAttribute("nombre","PROVEEDOR");
        $Agregado->SetAttribute("valor",$arr['diconsa']['proveedor']);
        $Agregado = $Addenda->appendChild($Agregado);
    
        $AgregadoProv = $xml->createElement("Diconsa:AgregadoProv");
        $AgregadoProv->SetAttribute("almacen",$arr['diconsa']['almacen']);
        $AgregadoProv->SetAttribute("negociacion",$arr['diconsa']['negociacion']);
        $AgregadoProv->SetAttribute("pedido",$arr['diconsa']['pedido']);
        $AgregadoProv = $Addenda->appendChild($AgregadoProv);
    
    }
    if ($addenda=="imss") {
        $Proveedor_IMSS = $xml->createElement("Proveedor_IMSS");
          $Proveedor = $xml->createElement("Proveedor");
          $Proveedor->SetAttribute("noProveedor",$arr['imss']['proveedor']);
          $Proveedor = $Proveedor_IMSS->appendChild($Proveedor);
        $Proveedor_IMSS = $Addenda->appendChild($Proveedor_IMSS);
        $Delegacion = $xml->createElement("Delegacion");
          $UnidadNegocio = $xml->createElement("UnidadNegocio");
          $UnidadNegocio->SetAttribute("unidad",$arr['imss']['delegacion']);
          $UnidadNegocio = $Delegacion->appendChild($UnidadNegocio);
        $Delegacion = $Addenda->appendChild($Delegacion);

        $Concepto = $xml->createElement("Concepto");
          $NumeroConcepto = $xml->createElement("NumeroConcepto");
          $NumeroConcepto->SetAttribute("concepto",$arr['imss']['concepto']);
          $NumeroConcepto = $Concepto->appendChild($NumeroConcepto);
        $Concepto = $Addenda->appendChild($Concepto);

        $Pedido = $xml->createElement("Pedido");
          $NumeroPedido = $xml->createElement("NumeroPedido");
          $NumeroPedido->SetAttribute("pedido",$arr['imss']['pedido']);
          $NumeroPedido = $Pedido->appendChild($NumeroPedido);
        $Pedido = $Addenda->appendChild($Pedido);

        $Recepcion = $xml->createElement("Recepcion");
          $Recepcion1 = $xml->createElement("Recepcion1");
          $Recepcion1->SetAttribute("numero_recepcion",$arr['imss']['recepcion']);
          $Recepcion1 = $Recepcion->appendChild($Recepcion1);
        $Recepcion = $Addenda->appendChild($Recepcion);

    }

    $Addenda = $root->appendChild($Addenda);
}
}
// }}}
// {{{ genera_cadena_original
function satxmlsv22_genera_cadena_original() {
global $xml, $cadena_original;
$paso = new DOMDocument;
$paso->loadXML($xml->saveXML());
$xsl = new DOMDocument;
$maquina = trim(`uname -n`);
$ruta = ($maquina == "www.fjcorona.com.mx") ? "/home/httpd/htdocs/cfds/" : "./cfds/";
$file=$ruta."cadenaoriginal_2_2.xslt";      // Ruta al archivo
$xsl->load($file);
$proc = new XSLTProcessor;
$proc->importStyleSheet($xsl); 
$cadena_original = $proc->transformToXML($paso);
}
// }}}
// {{{ Calculo de sello
function satxmlsv22_sella($arr) {
global $root, $cadena_original, $sello;
$certificado = $arr['noCertificado'];
$maquina = trim(`uname -n`);
$ruta = ($maquina == "www.fjcorona.com.mx") ? "/home/httpd/sat/" : "./";
$file=$ruta.$certificado.".key.pem";      // Ruta al archivo
// Obtiene la llave privada del Certificado de Sello Digital (CSD),
//    Ojo , Nunca es la FIEL/FEA
$pkeyid = openssl_get_privatekey(file_get_contents($file));
openssl_sign($cadena_original, $crypttext, $pkeyid, OPENSSL_ALGO_SHA1);
openssl_free_key($pkeyid);

$sello = base64_encode($crypttext);      // lo codifica en formato base64
$root->setAttribute("sello",$sello);

$file=$ruta.$certificado.".cer.pem";      // Ruta al archivo de Llave publica
$datos = file($file);
$certificado = ""; $carga=false;
for ($i=0; $i<sizeof($datos); $i++) {
    if (strstr($datos[$i],"END CERTIFICATE")) $carga=false;
    if ($carga) $certificado .= trim($datos[$i]);
    if (strstr($datos[$i],"BEGIN CERTIFICATE")) $carga=true;
}
// El certificado como base64 lo agrega al XML para simplificar la validacion
$root->setAttribute("certificado",$certificado);
}
// }}}
// {{{ Termina, graba en edidata o genera archivo en el disco
function satxmlsv22_termina($arr,$dir) {
global $xml, $conn;
$xml->formatOutput = true;
$todo = $xml->saveXML();
$nufa = $arr['serie'].$arr['folio'];    // Junta el numero de factura   serie + folio
$paso = $todo;
if ($dir != "/dev/null") {
    //  Para cundo se envia a un sysoout privado  18/Julio/2012
    $xml->formatOutput = true;
    $xml->save($dir.$nufa.".xml");
}
$encfd = $conn->GetOne("Select count(*) from clcfdxml WHERE cfdxdocu = '$nufa'");
if ($encfd == 0) 
        $conn->replace("clcfdxml",array("cfdxdocu"=>$nufa,"cfdxcade"=>$cadena_original,"cfdxlxml"=>$paso),"cfdxdocu",true);
// }}}
return($todo);
}
// {{{ Funcion que carga los atributos a la etiqueta XML
function satxmlsv22_cargaAtt(&$nodo, $attr) {
$quitar = array('sello'=>1,'noCertificado'=>1,'certificado'=>1);
foreach ($attr as $key => $val) {
    $val = preg_replace('/\s\s+/', ' ', $val);   // Regla 5a y 5c
    $val = trim($val);                           // Regla 5b
    if (strlen($val)>0) {   // Regla 6
        $val = utf8_encode(str_replace("|","/",$val)); // Regla 1
        $nodo->setAttribute($key,$val);
    }
}
}
// }}}
// {{{ Formateo de la fecha en el formato XML requerido (ISO)
function satxmlsv22_xml_fech($fech) {
    $ano = substr($fech,0,4);
    $mes = substr($fech,4,2);
    $dia = substr($fech,6,2);
    $hor = substr($fech,8,2);
    $min = substr($fech,10,2);
    $seg = substr($fech,12,2);
    $aux = $ano."-".$mes."-".$dia."T".$hor.":".$min.":".$seg;
    if ($aux == "--T::")
        $aux = "";
    return ($aux);
}
// }}}
// {{{ Formateo de la fecha en el formato XML requerido (ISO)
function satxmlsv22_xml_fix_fech($fech) {
    $dia = substr($fech,0,2);
    $mes = substr($fech,3,2);
    $ano = substr($fech,6,4);
    $aux = $ano."-".$mes."-".$dia;
    return ($aux);
}
// }}}
// {{{ valida que el xml coincida con esquema XSD
function satxmlsv22_valida($docu) {
global $xml;
$xml->formatOutput=true;
$paso = new DOMDocument;
$texto = $xml->saveXML();
$paso->loadXML($texto);
libxml_use_internal_errors(false);
$maquina = trim(`uname -n`);
$ruta = ($maquina == "www.fjcorona.com.mx") ? "/home/httpd/htdocs/cfds/" : "./cfds/";
if (strpos($texto,"detallista:")===FALSE) {
    $file=$ruta."cfdv22.xsd";  
    $ok = $paso->schemaValidate($file);
} else {
    $file=$ruta."cfdv22detallista.xsd";  
    $ok = $paso->schemaValidate($file);
}
return $ok;
}
// }}}
?>
