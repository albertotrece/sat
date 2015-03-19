Certificados Raiz de la autoridad certificadora

Sirven para validar que el certificado proporcionado fue emitido por el SAT

Pagina del SAT que lo menciona

http://www.sat.gob.mx/informacion_fiscal/factura_electronica/Paginas/certificado_sello_digital.aspx

Direccion de donde se obtienen los certificados

http://www.sat.gob.mx/informacion_fiscal/factura_electronica/Documents/solcedi/Cert_Prod.zip

Procedimiento para procesarlos

[dev@www raiz]$ unzip Cert_Prod.zip 
Archive:  Cert_Prod.zip
  inflating: Cert_Prod/AC0_SAT.cer   
  inflating: Cert_Prod/AC1_SAT.cer   
  inflating: Cert_Prod/AC2_SAT.cer   
  inflating: Cert_Prod/AC3_SAT.cer   
  inflating: Cert_Prod/ARC0_IES.cer  
  inflating: Cert_Prod/ARC1_IES.cer  
  inflating: Cert_Prod/ARC2_IES.crt  
  inflating: Cert_Prod/ARC3_IES.crt  
$ mv Cert_Prod/*.cer       .
$ mv Cert_Prod/*.crt       .
$ rmdir Cert_Prod           

El problema es que algunos certificados los dan en PEM y otros en DER

[dev@www raiz]$ file *         
AC0_SAT.cer:   data         
AC1_SAT.cer:   ASCII text         
AC2_SAT.cer:   data         
AC3_SAT.cer:   data         
ARC0_IES.cer:  data         
ARC1_IES.cer:  data         
ARC2_IES.crt:  ASCII text         
ARC3_IES.crt:  ASCII text         
Cert_Prod.zip: Zip archive data, at least v2.0 to extract    
README.md:     ASCII text    

Asi que primero renombramos todos los 'ASCII TEXT' que son PEM a su extension.

$ mv AC1_SAT.cer AC1_SAT.cer.pem    
$ mv ARC2_IES.crt ARC2_IES.cer.pem    
$ mv ARC3_IES.crt ARC3_IES.cer.pem    

Y despues convertimos todos los .cer a .cer.pem con un for ....    

[dev@www raiz]$ for f in *.cer    
> do    
>     openssl x509 -in $f -inform der -out $f.pem    
> done    

Y borramos los viejos archivos DER

$ rm *.cer    

Ahora si ya todos los certificados son PEM con extension .cer.pem

[dev@www raiz]$ file *
AC0_SAT.cer.pem:  ASCII text        
AC1_SAT.cer.pem:  ASCII text        
AC2_SAT.cer.pem:  ASCII text        
AC3_SAT.cer.pem:  ASCII text        
ARC0_IES.cer.pem: ASCII text        
ARC1_IES.cer.pem: ASCII text        
ARC2_IES.cer.pem: ASCII text        
ARC3_IES.cer.pem: ASCII text        
Cert_Prod.zip:    Zip archive data, at least v2.0 to extract        
README.md:        ASCII text        

Transcribo la primera parte del manual de la funcion 'verify' del openssl

<quote>
VERIFY(1)                           OpenSSL                          VERIFY(1)

NAME
       verify - Utility to verify certificates.

SYNOPSIS
       openssl verify [-CApath directory] [-CAfile file] [-purpose purpose]
       [-untrusted file] [-help] [-issuer_checks] [-verbose] [-] [certifi-
       cates]

DESCRIPTION
       The verify command verifies certificate chains.

COMMAND OPTIONS
       -CApath directory
           A directory of trusted certificates. The certificates should have
           names of the form: hash.0 or have symbolic links to them of this
           form ("hash" is the hashed certificate subject name: see the -hash
           option of the x509 utility). Under Unix the c_rehash script will
           automatically create symbolic links to a directory of certificates.
</quote>

Asi que buscamos ese comando c_rehash en centos linux y se llama ....

[dev@www raiz]$ locate rehash  
/usr/sbin/cacertdir_rehash

Veamos los hashes de los certificdos

[dev@www raiz]$ for f in *pem
> do
> echo $f
> openssl x509 -in $f -hash -noout
> done
AC0_SAT.cer.pem
d2001d98
AC1_SAT.cer.pem
36dd2020
AC2_SAT.cer.pem
1cac10c1
AC3_SAT.cer.pem
95ba44db
ARC0_IES.cer.pem
d34dcb52
ARC1_IES.cer.pem
bf9ec309
ARC2_IES.cer.pem
7e57939d
ARC3_IES.cer.pem
7e57939d

Ese comando hay que ejecutarlo como root

# cacertdir_rehash .

Y veamos como queda despues de ejecutar el comando

[root@www raiz]# ll
total 48
lrwxrwxrwx 1 root root    15 Mar 19 07:45 1cac10c1.0 -> AC2_SAT.cer.pem
lrwxrwxrwx 1 root root    15 Mar 19 07:45 36dd2020.0 -> AC1_SAT.cer.pem
lrwxrwxrwx 1 root root    16 Mar 19 07:45 7e57939d.0 -> ARC3_IES.cer.pem
lrwxrwxrwx 1 root root    15 Mar 19 07:45 95ba44db.0 -> AC3_SAT.cer.pem
-rw-rw-rw- 1 dev  dev   2045 Mar 19 07:39 AC0_SAT.cer.pem
-rw-rw-rw- 1 dev  dev   1834 Nov 14  2012 AC1_SAT.cer.pem
-rw-rw-rw- 1 dev  dev   2464 Mar 19 07:39 AC2_SAT.cer.pem
-rw-rw-rw- 1 dev  dev   2529 Mar 19 07:39 AC3_SAT.cer.pem
-rw-rw-rw- 1 dev  dev   1968 Mar 19 07:39 ARC0_IES.cer.pem
-rw-rw-rw- 1 dev  dev   1379 Mar 19 07:39 ARC1_IES.cer.pem
-rw-rw-rw- 1 dev  dev   1805 Sep 22  2010 ARC2_IES.cer.pem
-rw-rw-rw- 1 dev  dev   1805 Sep 22  2010 ARC3_IES.cer.pem
-rw-rw-rw- 1 dev  dev  10581 Jul 28  2014 Cert_Prod.zip
-rw-rw-rw- 1 dev  dev   3491 Mar 19 07:50 README.md
lrwxrwxrwx 1 root root    16 Mar 19 07:45 bf9ec309.0 -> ARC1_IES.cer.pem
lrwxrwxrwx 1 root root    15 Mar 19 07:45 d2001d98.0 -> AC0_SAT.cer.pem
lrwxrwxrwx 1 root root    16 Mar 19 07:45 d34dcb52.0 -> ARC0_IES.cer.pem

Ahora ya se puede ejecutar una validacion

$ openssl verify -verbose -CApath raiz/ /home/httpd/sat/00001000000201135463.cer.pem      
/home/httpd/sat/00001000000201135463.cer.pem: OK


