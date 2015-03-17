<?php
//
// +-------------------------------------------------------------------------------+
// | satxmls.php : Procesa el arreglo asociativo de intercambio y genera un        |
// |               mensaje XML con los requisitos del SAT                          |
// |                                                                               |
// |               Si se incluye un texto en edidata se agrega como Addenda        |
// +-------------------------------------------------------------------------------+
// | Copyright (c) 2005  Fabrica de Jabon la Corona, SA de CV                      |
// +-------------------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or                 |
// | modify it under the terms of the GNU General Public License                   |
// | as published by the Free Software Foundation; either version 2                |
// | of the License, or (at your option) any later version.                        |
// |                                                                               |
// | This program is distributed in the hope that it will be useful,               |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of                |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                 |
// | GNU General Public License for more details.                                  |
// |                                                                               |
// | You should have received a copy of the GNU General Public License             |
// | along with this program; if not, write to the Free Software                   |
// | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.   |
// +-------------------------------------------------------------------------------+
// | Autor: Fernando Ortiz <fortiz@lacorona.com.mx>                                |
// +-------------------------------------------------------------------------------+
// | 2005/Jun/17   Se agrega que tome los certificados de sellos digitales segun   |
// |               la bodega que emitio (2 letras de la serie)                     |
// |                                                                               |
// | 2005/Ago/3    Se corrige la rutina del sello digital, se usa openssl en lugar |
// |               de las funciones de PHP                                         |
// |                                                                               |
// | 2005/Sep/22   Se cambia el comando openssl para que calcule el digesto md5    |
// |               al mismo tiempo                                                 |
// +-------------------------------------------------------------------------------+
//

function satxmls($arr, $edidata=false, $dir="./tmp/",$nodo="") {
// {{{  Parametros generales
global $xml, $cadena_original, $conn;
error_reporting(E_ALL);
$cadena_original='||';
$noatt=  array();
$nufa = $arr['serie'].$arr['folio'];    // Junta el numero de factura   serie + folio
// }}}
// {{{  Datos generales del Comprobante
$xml = new DOMdocument("1.0","UTF-8");
$root = $xml->createElement("Comprobante");
$root = $xml->appendChild($root);
cargaAtt($root, array("version"=>"1.0",
                      "serie"=>$arr['serie'],
                      "folio"=>$arr['folio'],
                      "fecha"=>xml_fech($arr['fecha']),
                      "sello"=>"@",
                      "noAprobacion"=>$arr['noAprobacion']
                   )
                );
// }}}
// {{{ Datos del Emisor
$emisor = $xml->createElement("Emisor");
$emisor = $root->appendChild($emisor);
cargaAtt($emisor, array("rfc"=>$arr['Emisor']['rfc'],
                       "nombre"=>$arr['Emisor']['nombre']
                   )
                );
$domfis = $xml->createElement("DomicilioFiscal");
$domfis = $emisor->appendChild($domfis);
cargaAtt($domfis, array("calle"=>$arr['Emisor']['DomicilioFiscal']['calle'],
                        "noExterior"=>$arr['Emisor']['DomicilioFiscal']['noExterior'],
                        "municipio"=>$arr['Emisor']['DomicilioFiscal']['municipio'],
                        "estado"=>$arr['Emisor']['DomicilioFiscal']['estado'],
                        "pais"=>$arr['Emisor']['DomicilioFiscal']['pais'],
                        "codigoPostal"=>$arr['Emisor']['DomicilioFiscal']['codigoPostal']
                   )
                );
// }}}
// {{{ Datos del Receptor
$receptor = $xml->createElement("Receptor");
$receptor = $root->appendChild($receptor);
cargaAtt($receptor, array("rfc"=>$arr['Receptor']['rfc'],
                          "nombre"=>$arr['Receptor']['nombre']
                      )
                  );
$domicilio = $xml->createElement("Domicilio");
$domicilio = $receptor->appendChild($domicilio);
cargaAtt($domicilio, array("calle"=>$arr['Receptor']['Domicilio']['calle'],
                       "colonia"=>$arr['Receptor']['Domicilio']['colonia'],
                       "municipio"=>$arr['Receptor']['Domicilio']['municipio'],
                       "estado"=>$arr['Receptor']['Domicilio']['estado'],
                       "pais"=>$arr['Receptor']['Domicilio']['pais'],
                   )
               );
// }}}
// {{{ Detalle de los conceptos/produtos de la factura
$conceptos = $xml->createElement("Conceptos");
$conceptos = $root->appendChild($conceptos);
for ($i=1; $i<=sizeof($arr['Conceptos']); $i++) {
    $concepto = $xml->createElement("Concepto");
    $concepto = $conceptos->appendChild($concepto);
    cargaAtt($concepto, array("cantidad"=>$arr['Conceptos'][$i]['cantidad'],
                              "descripcion"=>$arr['Conceptos'][$i]['descripcion'],
                              "valorUnitario"=>$arr['Conceptos'][$i]['valorUnitario'],
                              "importe"=>$arr['Conceptos'][$i]['importe'],
                   )
                );
}
// }}}
// {{{ Impuesto (IVA)
$impuestos = $xml->createElement("Impuestos");
$impuestos = $root->appendChild($impuestos);
if ($arr['Traslados']['importe']<>0) {
    $traslados = $xml->createElement("Traslados");
    $traslados = $impuestos->appendChild($traslados);
    $traslado = $xml->createElement("Traslado");
    $traslado = $traslados->appendChild($traslado);
    cargaAtt($traslado, array("impuesto"=>$arr['Traslados']['impuesto'],
                              "importe"=>$arr['Traslados']['importe']
                             )
                         );
}
// }}}
// {{{ Calculo de sello
$cadena_original .= "|";                 // termina la cadena original con el doble ||
$utf8 = utf8_encode($cadena_original);   // la codififica en utf8
// +-------------------------------------------------------------------------------+
// | Por lo pronto uso una llave de pruebas generada con OpenSSL sera              |
// |   proporcionada por el SAT al pedir los folios                                |
// +-------------------------------------------------------------------------------+
$sellos = array("XA"=>array("certificado"=>"00001000000000255445", "aprobacion"=>107, "nombre"=>"xal"),
                "AC"=>array("certificado"=>"00001000000000255444", "aprobacion"=>107, "nombre"=>"aca"),
                "AG"=>array("certificado"=>"00001000000000255444", "aprobacion"=>107, "nombre"=>"ags"),
                "CH"=>array("certificado"=>"00001000000000255444", "aprobacion"=>107, "nombre"=>"chi"),
                "CO"=>array("certificado"=>"00001000000000255444", "aprobacion"=>107, "nombre"=>"cor"),
                "CU"=>array("certificado"=>"00001000000000255444", "aprobacion"=>107, "nombre"=>"cul"),
                "DG"=>array("certificado"=>"00001000000000255444", "aprobacion"=>107, "nombre"=>"dgo"),
                "GD"=>array("certificado"=>"00001000000000255444", "aprobacion"=>107, "nombre"=>"gda"),
                "HE"=>array("certificado"=>"00001000000000255444", "aprobacion"=>107, "nombre"=>"her"),
                "IR"=>array("certificado"=>"00001000000000255444", "aprobacion"=>107, "nombre"=>"ira"),
                "ME"=>array("certificado"=>"00001000000000255444", "aprobacion"=>107, "nombre"=>"mer"),
                "MX"=>array("certificado"=>"00001000000000255444", "aprobacion"=>107, "nombre"=>"mxi"),
                "MY"=>array("certificado"=>"00001000000000255444", "aprobacion"=>107, "nombre"=>"mty"),
                "MO"=>array("certificado"=>"00001000000000255444", "aprobacion"=>107, "nombre"=>"mor"),
                "OA"=>array("certificado"=>"00001000000000255444", "aprobacion"=>107, "nombre"=>"oax"),
                "TA"=>array("certificado"=>"00001000000000255444", "aprobacion"=>107, "nombre"=>"tap"),
                "TU"=>array("certificado"=>"00001000000000255444", "aprobacion"=>107, "nombre"=>"tux"),
                "AL"=>array("certificado"=>"00001000000000255444", "aprobacion"=>107, "nombre"=>"tam"),
                "VI"=>array("certificado"=>"00001000000000255444", "aprobacion"=>107, "nombre"=>"vil")
                );
$indi = substr($arr['serie'],0,2);
$certificado = $sellos[$indi]['certificado'];
$aprobacion = $sellos[$indi]['aprobacion'];
$bode = $sellos[$indi]['nombre'];
$bode = "AAA010101AAA";                     // Ahora que use 'siempre' el certificado de pruebas del SAT
$certificado = "00001000000000000114";       // POr lo pronto el numero de certificado es el de pruebas
// Otros certificados de pruebas
$bode = "13";                     // Ahora que use 'siempre' el certificado de pruebas del SAT
$certificado = "00001000000000000113";       // POr lo pronto el numero de certificado es el de pruebas

$file="/home/httpd/sat/$bode.key.pem";      // Hay una llave (sello) por cada localidad

$descriptorspec = array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => array("file", "/tmp/open.log", "a"));
$cmd = "openssl dgst -md5 -sign $file";
$process = proc_open($cmd, $descriptorspec, $pipes);
if (is_resource($process)) {
      fwrite($pipes[0], $utf8);    // Mande la cadena original en utf-8 al comando openssl
      fclose($pipes[0]);
      $crypttext = stream_get_contents($pipes[1]);   // Le el sello
      fclose($pipes[1]);
      proc_close($process);
}
$sello = base64_encode($crypttext);             // lo codifica en formato base64
$root->setAttribute("sello",$sello);
$root->setAttribute("noCertificado",$certificado);
// }}}
// {{{ Addenda si se requiere
if ($edidata) {
    $Addenda = $xml->createElement("Addenda");
    $Addenda = $root->appendChild($Addenda);
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
// }}}
// {{{ Genera un archivo de texto con el mensaje XML + EDI
$xml->formatOutput = false;
$xml->save($dir.$nufa.".xml");
// }}}
// {{{ Guarda el mensaje XML y la cadena original en una tabla del ERP para su procesamiento  posterior
$conn->replace("cfdsello",array("selldocu"=>$nufa),"selldocu",true);
$where=" selldocu = '$nufa'";
$conn->UpdateBlob("cfdsello","sellcade",$utf8,$where,'TEXT');
$todo = $xml->saveXML();
$conn->UpdateBlob("cfdsello","sellxml",$todo,$where,'TEXT');
//$conn->execute("update cfdsello set sellcade = '$utf8', sellxml = '$todo' where $where");
// }}}
return($todo);
}
// {{{ Funcion que carga los atributos a la etiqueta XML
function cargaAtt(&$nodo, $attr) {
// +-------------------------------------------------------------------------------+
// | Ademas le concatena a la variable global los valores para la cadena origianl  |
// +-------------------------------------------------------------------------------+
global $xml, $cadena_original;
$quitar = array('version'=>1,'sello'=>1,'noCertificado'=>1,'certificado'=>1);
foreach ($attr as $key => $val) {
    $val = trim($val);
    if (strlen($val)>0) {
        $val = utf8_encode(str_replace("|","/",$val));
        $nodo->setAttribute($key,$val);
        if (!isset($quitar[$key])) $cadena_original .= $val . "|";
    }
}
}
// }}}
// {{{ Formateo de la fecha en el formato XML requerido (ISO)
function xml_fech($fech) {
    $ano = substr($fech,0,4);
    $mes = substr($fech,4,2);
    $dia = substr($fech,6,2);
    $hor = substr($fech,8,2);
    $min = substr($fech,10,2);
    $seg = substr($fech,12,2);
    $aux = $ano."-".$mes."-".$dia."T".$hor.":".$min.":".$seg;
    return ($aux);
}
?>
