<?php
//
// +---------------------------------------------------------------------------+
// | ce_vali.php : En base a los archivos XML de balanza y catalogo de cuentas |
// |               efectua unas validaciones y determina que sean              |
// |               razonables, no se puede saber si es correcto porque no lee  |
// |               la contabilidad de la empresa ....                          |
// +---------------------------------------------------------------------------+
// | Copyright (c) 2011  Fabrica de Jabon la Corona, SA de CV                  |
// +---------------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU General Public License               |
// | of the License, or (at your option) any later version.                    |
// |                                                                           |
// | This program is distributed in the hope that it will be useful,           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
// | GNU General Public License for more details.                              |
// |                                                                           |
// | You should have received a copy of the GNU General Public License         |
// | along with this program; if not, write to the Free Software               |
// | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA|
// +---------------------------------------------------------------------------|
// | Autor: Fernando Ortiz <fortiz@lacorona.com.mx>                            |
// +---------------------------------------------------------------------------+
// | 06/Ago/2014 Prueba de concepto, version 1.0                               |
// |                                                                           |
// | 29/oct/2014 Como el esquema no lo ha definido el SAT, busca de dos XSD    |
// |                                                                           |
// | 11/dic/2014 Esquemas version 1.1                                          |
// |                                                                           |
// | 16/feb/2015 Validacion de sello, si incorrecto muestra cadena original    |
// |                                                                           |
// | 26/feb/2015 Valida que el certificado tenga el numero de serie reportado  |
// +---------------------------------------------------------------------------+
//
?>
<HTML>
<HEAD>
<meta http-equiv="Expires" content="Mon, 26 Jul 1997 05:00:00 GMT">
<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
<TITLE>Validacion de Contabilidad Electronica</TITLE>
<link rel="STYLESHEET" href="../fortiz.css" media="screen" type="text/css">
<?php
// Es solo para llevar mi estadistica en Google Analytics, ustedes quitenlo ...
@include("urchin/corona.html");
?>
</HEAD>
<BODY>
<div align=center>
<H1>Validacion de Contabilidad Electronica XML (Balanza/Catalogo)</H1>
<h2>Version 1.1</h2>
<form method='post' enctype='multipart/form-data'>
<table border=1>
<tr><th>Minimo este arcchivo<br>Balanza de Comprobacion mes actual 
    <td> <input type='file' name='bala_act' size='60'>
    <td><ul><li>Estructura del archivo balanza .. contra xsd
            <li>Suma de debe igual que suma de haber.
          </ul>
<tr><th>Si tambien me das este archivo<br>Catalogo de cuentas
    <td> <input type='file' name='cata_act' size='60'>
    <td><ul><li>Estructura del archivo catalogo .. contra xsd
            <li>Las cuentas en la balanza esten registradas en el catalogo.
            <li>Suma de saldo inicial (suma de deudoras = suma de acreedoras)
            <li>Suma de saldo final: (suma de deudoras = suma de acreedoras)
          </ul>
<tr><th>y si das este tercer archivo<br> Balanza de Comprobacion mes anterior 
    <td> <input type='file' name='bala_ant' size='60'>
    <td><ul><li>Estructura del archivo balanza anterior .. contra xsd
            <li>El saldo final del mes anterior sea igual al saldo inicial del mes actual.
            <li>Se reporten en el mes actual todas las cuentas que se reportaron con saldo el mes anterior.
            <li>Si se reporta una cuenta nueva tenga saldo inicial en cero.
       </ul>
<tr><th>Muestra las cuentas en ceros<br>aunque no esten reportadas en la balanza
    <td><input type="radio" name="cuales" value="balanza" checked>Solo cuentas en balanza<br>
        <input type="radio" name="cuales" value="catalogo">Todas las cuentas del catalogo<br><td>&nbsp;
<tr><th>Las muestra en base al numero de cuenta<br>del contribuyente o las<br>agrupa por codigo del SAT
    <td><input type="radio" name="agrupa" value="numcta" checked>Por cuenta contribuyente<br>
        <input type="radio" name="agrupa" value="codagru">Por codigo agrupador SAT<br><td>&nbsp;
<tr><td>&nbsp;<td align=center><INPUT TYPE="submit" VALUE="Valida" ><td>&nbsp;
</table>
</FORM>
<a href=ce_vali.phps>Codigo Fuente</a>
 <br><br><hr>
<?php
if (trim($_FILES['bala_act']['name'])=="") die("");
if ($_FILES['bala_act']['error']==1 || $_FILES['bala_act']['size']==0) {
    echo "<h1><red>NO SUBIO archivo, demasiado grande</red></h1>";
    die();
} 
$arch = $_FILES['bala_act']['tmp_name'];
$bala_act = file_get_contents($arch);
unlink($arch);
$cata_act="";
if ($_FILES['cata_act']['name']!=="") {
    $arch = $_FILES['cata_act']['tmp_name'];
    $cata_act = file_get_contents($arch);
    unlink($arch);
}
$bala_ant="";
if ($_FILES['bala_ant']['name']!=="") {
    $arch = $_FILES['bala_ant']['tmp_name'];
    $bala_ant = file_get_contents($arch);
    unlink($arch);
}



 /*
     * Todos los archivos que se requieren para hacer la validacion
     * fueron descargados del portal del SAT pero los tengo localmente
     * almacenados en mi maquina para que las validaciones sean mas rapidas.
     *
     * La version de mi maquina los pueden obtener de la misma URL
     *
     * http://www.lacorona.com.mx/fortiz/sat/ce/BalanzaComprobacion_1_1.xsd
     * http://www.lacorona.com.mx/fortiz/sat/ce/CatalogoCuentas_1_1.xsd
     * http://www.lacorona.com.mx/fortiz/sat/ce/...
     *
     * [dev@www ce]$ ls *xsd
     * AuxiliarCtas_1_1.xsd          CatalogosParaEsqContE.xsd
     * AuxiliarFolios_1_1.xsd        PolizasPeriodo_1_1.xsd
     * BalanzaComprobacion_1_1.xsd   SelloDigitalContElec.xsd
     * CatalogoCuentas_1_1.xsd
     *
*/
echo "<h3>Valida esquema Balanza mes actual</h3>";
$xsd = "BalanzaComprobacion_1_1.xsd";
$xslt = "BalanzaComprobacion_1_1.xslt";
$nodo = "Balanza";
$xml_act = valida_xsd($bala_act,$xsd, $xslt, $nodo);
if (!is_object($xml_act)) {
    die("<h3>Balanza actual con esquema incorrecto, no continua</h3>");
}
if ($cata_act!="") {
    echo "<h3>Valida esquema catalogo de cuentas</h3>";
    $nodo = "Catalogo";
    $xsd = "CatalogoCuentas_1_1.xsd";
    $xslt = "CatalogoCuentas_1_1.xslt";
    $xml_cata = valida_xsd($cata_act,$xsd, $xslt, $nodo);
    if (!is_object($xml_cata)) {
        echo "<h3>Catalogo de cuentas incorrecto, ignorando</h3>";
        unset($xml_cata);
    }
}
if ($bala_ant!="") {
    echo "<h3>Valida esquema Balanza mes anterior</h3>";
    $xsd = "BalanzaComprobacion_1_1.xsd";
    $xslt = "BalanzaComprobacion_1_1.xslt";
    $nodo = "Balanza";
    $xml_ant = valida_xsd($bala_ant,$xsd, $xslt, $nodo);
    if (!is_object($xml_ant)) {
        echo "<h3>Balanza anterior con esquema incorrecto, ignorando</h3>";
        unset($xml_ant);
    }
}

$data = valida_bala_act($xml_act);
if (is_object($xml_cata)) {
    $data = valida_catalogo($data, $xml_cata);
}
if (is_object($xml_ant)) {
    $data = valida_bala_ant($data, $xml_ant);
}
muestra_tabla($data);
die();

// {{{ valida_xsd
function valida_xsd($texto, $xsd, $xslt, $nodo) {
libxml_use_internal_errors(true); 
$xml = new DOMDocument();
$ok = $xml->loadXML($texto);
if (!$ok) {
   display_xml_errors(); 
   die();
}
$ok = $xml->schemaValidate($xsd);
if ($ok) {
    echo "<h3>Esquema valido</h3>";
    checa_sello($texto, $xslt, $nodo);
} else {
    echo "<h3>Estructura contra esquema incorrecta</h3>";
    display_xml_errors(); 
    $xml=false;
}
echo "<hr>";
return $xml;
}
// }}}
// {{{ checa_sello
function checa_sello($texto, $xslt, $nodo) {
    $xml = new DOMDocument("1.0","UTF-8");
    $xml->loadXML($texto);
    $root = $xml->getElementsByTagName($nodo)->item(0);
    $sello = $root->getAttribute("Sello");
    $no_cert = $root->getAttribute("noCertificado");
    $cert = $root->getAttribute("Certificado");
    if ($sello=="" && $no_cert=="" && $cert=="") {
        echo "No tiene datos de sello, NO valida<br>";
        return;
    }
    if ($sello=="" || $no_cert=="" && $cert=="") {
        echo "<h3>No tiene datos de sello completo, ignorando</h3>";
        return;
    }
    $paso = new DOMDocument("1.0","UTF-8");
    $paso->loadXML($texto);
    $xsl = new DOMDocument("1.0","UTF-8");
    $xsl->load($xslt);
    $proc = new XSLTProcessor;
    $proc->importStyleSheet($xsl);
    $cadena_original = $proc->transformToXML($paso);

    $pem = "-----BEGIN CERTIFICATE-----\n".chunk_split($cert,64)."-----END CERTIFICATE-----\n";
    $paso = openssl_x509_parse($pem);
    $serial = convierte($paso['serialNumber']);
    if ($serial!=$no_cert) {
        echo "Numero de certificado reportado $no_cert serie del certificado $serial<br>";
    }
    $pubkeyid = openssl_get_publickey(openssl_x509_read($pem));
    if ($pubkeyid===FALSE) {
        echo "<h3>El atributo 'Certificado' no contiene un certificado x509 valido </h3>";
        return;
    }
    $algo = OPENSSL_ALGO_SHA1;
    $ok = openssl_verify($cadena_original, 
                         base64_decode($sello), 
                         $pubkeyid, 
                         $algo);
    if ($ok == 1) {
        echo "<h3>Sello ok, sha1</h3>";
    } else {
        $algo = OPENSSL_ALGO_SHA256;
        $ok = openssl_verify($cadena_original, 
                             base64_decode($sello), 
                             $pubkeyid, 
                             $algo);
        if ($ok == 1) {
            echo "<h3>Sello ok, sha256</h3>";
            echo "<h3>OJO el SAT no lo acepta, usa SHA1</h3>";
        } else {
            echo "<h3>Sello incorrecto, ni sha1 ni sha256</h3>";
            echo "$cadena_original<br>";
            while ($msg = openssl_error_string())
                echo $msg. "\n";
        }
    }
    openssl_free_key($pubkeyid);
}
// }}}
// {{{ valida_bala_act
function valida_bala_act($xml) {
    $data=array();
    $Balanza = $xml->getElementsByTagName('Balanza');
    foreach ($Balanza as $balan) {
        $RFC=$balan->getAttribute('RFC');
        $Mes=(int)$balan->getAttribute('Mes');
        $Anio=(int)$balan->getAttribute('Anio');
        $data[0] = array("RFC"=>$RFC,
                         "Mes"=>$Mes,
                         "Anio"=>$Anio,
                         "Error"=>"");
        $cant=0;
        $Ctas = $balan->getElementsByTagName('Ctas');
        foreach ($Ctas as $cuen) {
            $cant++;
            $NumCta=$cuen->getAttribute('NumCta');
            $SaldoIni=(double)$cuen->getAttribute('SaldoIni');
            $Debe=(double)$cuen->getAttribute('Debe');
            $Haber=(double)$cuen->getAttribute('Haber');
            $SaldoFin=(double)$cuen->getAttribute('SaldoFin');
            $Error="";
            $calcD = $SaldoIni + $Debe - $Haber;
            $calcA = $SaldoIni - $Debe + $Haber;
            if (round($SaldoFin,2) != round($calcD,2) &&
                round($SaldoFin,2) != round($calcA,2)) $Error .= "SaldoFin incorrecto ($calc)";
            if (($SaldoFin<0 && $SaldoIni>0) ||
                ($SaldoFin>0 && $SaldoIni<0)) $Error .= "Cambio de Signo";
            $data[$NumCta] = array("Desc"=>"",
                                   "Natur"=>"",
                                   "SaldoIni"=>$SaldoIni,
                                   "Debe"=>$Debe,
                                   "Haber"=>$Haber,
                                   "SaldoFin"=>$SaldoFin,
                                   "Error"=>$Error);
        }
    }
    return $data;
}
// }}}
// {{{ valida_catalogo
function valida_catalogo($data,$xml) {
    // Carga el catalogo de cuentas
    $cata = array();
    $Catalogo = $xml->getElementsByTagName('Catalogo');
    foreach ($Catalogo as $cat) {
        $RFC=$cat->getAttribute('RFC');
        $Mes = (int)$cat->getAttribute('Mes');
        $Anio = (int)$cat->getAttribute('Anio');
        $cant=0;
        $Ctas = $cat->getElementsByTagName('Ctas');
        $cata[0]=array("RFC"=>$RFC,"Mes"=>$Mes,"Anio"=>$Anio);
        foreach ($Ctas as $cuen) {
            $cant++;
            $Error="";
            $Natur=$cuen->getAttribute('Natur');
            $Nivel=$cuen->getAttribute('Nivel');
            $CodAgrup=$cuen->getAttribute('CodAgrup');
            $SubCtaDe=$cuen->getAttribute('SubCtaDe');
            $NumCta=$cuen->getAttribute('NumCta');
            $Desc=$cuen->getAttribute('Desc');
            if ($Nivel==1 && $SubCtaDe) 
                $Error .= "Nivel 1 de cuenta no puede ser subcuenta de otra cuenta. ";
            if ($Nivel>1) {
                if (!$SubCtaDe) $Error .= "Nivel inferior de cuenta de subcuenta de otra cuenta. ";
                if (!array_key_exists($SubCtaDe,$cata)) $Error .= "No existe la cuenta padre referenciada. ";
            }
            $Desc = str_repeat("&nbsp;",($Nivel-1)*4).$Desc;
            $cata[$NumCta] = array("Desc"=>$Desc,"Natur"=>$Natur,"Nivel"=>$Nivel,"SubCtaDe"=>$SubCtaDe,"Error"=>$Error,"CodAgrup"=>$CodAgrup);
            if ($_POST['cuales']=="catalogo" && !array_key_exists($NumCta,$data)) { // Agrega descripcion y Natur
                $data[$NumCta]["Desc"] = $Desc;
                $data[$NumCta]["Natur"] = $Natur;
                $data[$NumCta]["SaldoIni"]=0;
                $data[$NumCta]["Debe"]=0;
                $data[$NumCta]["Haber"]=0;
                $data[$NumCta]["SaldoFin"]=0;
                $data[$NumCta]["Error"]=$Error;
            }
        }
    }
    // echo "<div align=left>";
    //  echo "<pre>";
    // print_r($_POST);
    // print_r($cata);
    //  echo "</pre>";
    //  echo "</div>";
    // Procesa la balanza y busca cada cuenta en el catalogo
    if ($_POST['agrupa']=="codagru") {
        $agru = array();
        // El codigo fuente de esta funcion lo pueden 
        // obtener de la siguiente URL
        //
        //   http://www.lacorona.com.mx/fortiz/sat/ce/codigo_agrupador.phps
        //  
        require_once "codigo_agrupador.php";
    }
    foreach ($data as $ncta => $row) {
        if ($ncta === 0) { // Valida encabezados
            $agru[0]=$row;
            if ($cata[0]["RFC"] !== $data[0]["RFC"]) {
                $data[0]["Error"] .= "El RFC del catalogo es otro (".
                                    $cata[0]["RFC"].").";
            }
            $f_cata = $cata[0]["Anio"]*100 + $cata[0]["Mes"];
            $f_bala = $data[0]["Anio"]*100 + $data[0]["Mes"];
            if ($f_cata > $f_bala) {
                $data[0]["Error"] .= "El catalogo no vigente ($f_cata)".
                                     " para la fecha de balanza ($f_bala).";
            }
        } else { // Valida cuenta
            if (array_key_exists($ncta,$cata)) { // Agrega descripcion y Natur
                $data[$ncta]["Desc"] = $cata[$ncta]["Desc"];
                $data[$ncta]["Natur"] = $cata[$ncta]["Natur"];
                $data[$ncta]["Error"] .= $cata[$ncta]["Error"];
                $data[$ncta]["Text"] = $cata[$ncta]["CodAgrup"];
            }
        }
        if ($_POST['agrupa']=="codagru" && $ncta > 0) {
            $indi = $cata[$ncta]["CodAgrup"];
            if (!array_key_exists($indi,$agru)) {
                $agru[$indi]["SaldoIni"]= 0;
                $agru[$indi]["Debe"] = 0;
                $agru[$indi]["Haber"] = 0;
                $agru[$indi]["SaldoFin"] = 0;
                $agru[$indi]["Error"] = "";
                $agru[$indi]["Text"] = "";
                $agru[$indi]["Desc"] = codigo_agrupador::desc($indi);
                $agru[$indi]["Natur"] = $cata[$ncta]["Natur"];
            }
            $agru[$indi]["SaldoIni"]+= $row["SaldoIni"];
            $agru[$indi]["Debe"] += $row["Debe"];
            $agru[$indi]["Haber"] += $row["Haber"];
            $agru[$indi]["SaldoFin"] += $row["SaldoFin"];
            $agru[$indi]["Error"] .= $row["Error"];
            $agru[$indi]["Text"] .= "$ncta ";
        }
    }
    if ($_POST['agrupa']=="codagru") $data = $agru;
    // echo "<div align=left>";
    // echo "<pre>";
    // print_r($data);
    // print_r($cata);
    // echo "</pre>";
    // echo "</div>";
    return $data;
}
// }}}
// {{{ valida_bala_ant
function valida_bala_ant($data,$xml) {
    // $ant=array();
    $Balanza = $xml->getElementsByTagName('Balanza');
    foreach ($Balanza as $balan) {
        $RFC=$balan->getAttribute('RFC');
        $Mes=(int)$balan->getAttribute('Mes');
        $Anio=(int)$balan->getAttribute('Anio');
        $ant[0] = array("RFC"=>$RFC,
                         "Mes"=>$Mes,
                         "Anio"=>$Anio,
                         "Error"=>"");
        $cant=0;
        $Ctas = $balan->getElementsByTagName('Ctas');
        foreach ($Ctas as $cuen) {
            $cant++;
            $NumCta=$cuen->getAttribute('NumCta');
            $SaldoFin=(double)$cuen->getAttribute('SaldoFin');
            $ant[$NumCta] = array("SaldoFin"=>$SaldoFin);
            if ($SaldoFin != 0) { // Si se quedo con saldo debe de estar 
                                  // En el siguiente mes
                if (!array_key_exists($NumCta,$data)) { 
                    $data[0]["Error"] .= "Cuenta $NumCta falta en mes actual ($SaldoFin).";
                }
            }
        }
    }
    // echo "<div align=left><pre>"; print_r($ant); echo "</pre></div>";
    // Procesa la balanza y busca cada cuenta en el mes anterior
    foreach ($data as $ncta => $row) {
        if ($ncta === 0) { // Valida encabezados
            if ($ant[0]["RFC"] !== $data[0]["RFC"]) {
                $data[0]["Error"] .= "El RFC del mes anterior es otro (".
                                    $ant[0]["RFC"].").";
            }
        } else { // Valida cuenta
            if (array_key_exists($ncta,$ant)) {  // Si esta mismo saldo
                if (round($data[$ncta]["SaldoIni"],2)!=
                    round($ant[$ncta]["SaldoFin"],2)) {
                    $data[$ncta]["Error"] .= "Saldo anterior diferente (".
                        $ant[$ncta]["SaldoFin"].").";
                    }
            } else { // Si no estaba, el inicial debe de ser cero
                if (round($data[$ncta]["SaldoIni"],2)!=0) {
                    $data[$ncta]["Error"] .= "Cuenta nueva, inicial debe de ser cero";
                }
            }
        }
    }
    return $data;
}
// }}}
// {{{ muestra_tabla
function muestra_tabla($data) {
    // echo "<div align=left>"; echo "<pre>"; print_r($data); echo "</pre>"; echo "</div>";
    ksort($data, SORT_STRING );
    echo "<h2>RFC:".$data[0]["RFC"]."A&ntilde;o ".$data[0]["Anio"].
        " mes:".$data[0]["Mes"]."</h2>";
    echo "<br>";
    echo "<h3 class=error>".$data[0]["Error"]."</h3>";
    // echo "<pre>"; print_r($data); echo "</pre>";
    echo "<table width=100% border=1>";
    echo "<tr><th>Cuenta<th>Nombre<th>Natur<th>Inicial".
             "<th>Debe<th>Haber<th>Final<th>Error";
    $t_debe=0; $t_haber=0;
    $t_i_acre=0;$t_i_deud=0;$t_f_acre=0;$t_f_deud=0;
    $t_i_nega=0;$t_i_posi=0;$t_f_nega=0;$t_f_posi=0;
    $error="";
    foreach ($data as $ncta => $row) {
        if ($ncta !== 0) {
            echo "<tr><td title=\"".$row["Text"]."\">".$ncta;
            echo "<td>".$row["Desc"];
            echo "<td align=center>".$row["Natur"];
            $t_debe += $row["Debe"];
            $t_haber += $row["Haber"];
            if ($row["SaldoIni"]<0) {
                $t_i_nega += $row["SaldoIni"];
            } else {
                $t_i_posi += $row["SaldoIni"];
            }
            if ($row["SaldoFin"]<0) {
                $t_f_nega += $row["SaldoFin"];
            } else {
                $t_f_posi += $row["SaldoFin"];
            }
            if ($row["Natur"]=="D") {
                $t_i_deud += $row["SaldoIni"];
                $t_f_deud += $row["SaldoFin"];
            }
            if ($row["Natur"]=="A") {
                $t_i_acre += $row["SaldoIni"];
                $t_f_acre += $row["SaldoFin"];
            }
            echo "<td align=right>".number_format($row["SaldoIni"],2);
            echo "<td align=right>".number_format($row["Debe"],2);
            echo "<td align=right>".number_format($row["Haber"],2);
            echo "<td align=right>".number_format($row["SaldoFin"],2);
            echo ($row["Error"]=="") ? "<td>" : "<td class=error>";
            echo $row["Error"];
         }
    }
    echo "<tr><th rowspan=2>".
        "<th align=right rowspan=2>Suma<th>A<br>D".
        "<th align=right>";
    if ($t_i_acre!=0 || $t_i_deud!=0) {
        if (abs(round($t_i_acre,2))!=abs(round($t_i_deud,2))) {
            $error .= "Suma de saldo inicial no cuadra.";
        }
        $t_i_acre=number_format($t_i_acre,2);
        $t_i_deud=number_format($t_i_deud,2);
        echo "$t_i_acre<br>$t_i_deud";
    }
    if (round($t_debe,2)!=round($t_haber,2)) {
        $error .= "Suma de debe no es igual a suma de haber.";
    }
    $t_debe=number_format($t_debe,2);
    $t_haber=number_format($t_haber,2);
    echo "<th align=right rowspan=2>$t_debe<th align=right rowspan=2>$t_haber<th align=right>";
    if ($t_f_acre!=0 || $t_f_deud!=0) {
        if (abs(round($t_f_acre,2))!=abs(round($t_f_deud,2))) {
            $error .= "Suma de saldo final no cuadra.";
        }
        $t_f_acre=number_format($t_f_acre,2);
        $t_f_deud=number_format($t_f_deud,2);
        echo "$t_f_acre<br>$t_f_deud";
    }
    echo "<th class=error>$error";
    $error="";
    echo "<tr><th>-<br>+<th align=right>";
    if ($t_i_nega!=0 || $t_i_posi!=0) {
        if (abs(round($t_i_nega,2))!=abs(round($t_i_posi,2))) {
            $error .= "Suma de saldo inicial no cuadra.";
        }
        $t_i_nega=number_format($t_i_nega,2);
        $t_i_posi=number_format($t_i_posi,2);
        echo "$t_i_nega<br>$t_i_posi";
    }
    echo "<th align=right>";
    if ($t_f_nega!=0 || $t_f_posi!=0) {
        if (abs(round($t_f_nega,2))!=abs(round($t_f_posi,2))) {
            $error .= "Suma de saldo final no cuadra.";
        }
        $t_f_nega=number_format($t_f_nega,2);
        $t_f_posi=number_format($t_f_posi,2);
        echo "$t_f_nega<br>$t_f_posi";
    }
    echo "<th class=error>$error";
    echo "</table>";
}
// }}}
// {{{ Convierte EL numero de serie del SAT a formato humano
function convierte($dec) {
    $hex=bcdechex($dec);
    $ser="";
    for ($i=1; $i<strlen($hex); $i=$i+2) {
        $ser.=substr($hex,$i,1);
    }
    return $ser;
}
// }}} Convierte
// {{{ bcdechex   :  como dechex pero para numeros de precision ilimitada
function bcdechex($dec) {
    $last = bcmod($dec, 16);
    $remain = bcdiv(bcsub($dec, $last), 16);
    if($remain == 0) {
        return dechex($last);
    } else {
        return bcdechex($remain).dechex($last);
    }
}
// }}} bcdechex
// {{{ display_xml_errors
function display_xml_errors() {
    global $texto;
    $lineas = explode("\n", $texto);
    $errors = libxml_get_errors();

    echo "<pre>";
    foreach ($errors as $error) {
        echo display_xml_error($error, $lineas);
    }
    echo "</pre>";

    libxml_clear_errors();
}
/// }}}}
// {{{ display_xml_error
function display_xml_error($error, $lineas) {
    $return  = htmlspecialchars($lineas[$error->line - 1]) . "\n";
    $return .= str_repeat('-', $error->column) . "^\n";

    switch ($error->level) {
        case LIBXML_ERR_WARNING:
            $return .= "Warning $error->code: ";
            break;
         case LIBXML_ERR_ERROR:
            $return .= "Error $error->code: ";
            break;
        case LIBXML_ERR_FATAL:
            $return .= "Fatal Error $error->code: ";
            break;
    }

    $return .= trim($error->message) .
               "\n  Linea: $error->line" .
               "\n  Columna: $error->column";
    echo "$return\n\n--------------------------------------------\n\n";
}
/// }}}}
?>
</div>
</BODY>
</HTML>
