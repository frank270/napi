<?php

/**
 * use to sync ali's all machine upload file
 * 
 */
define("LOG_DIR","/var/log");
include_once "/var/www/include/class/mysql.php";
include_once "/var/www/liteec/config.php";
$DB=new DB_MySQL;
$DB->servername = $cfg['servername'];
$DB->dbname = $cfg['dbname'];
$DB->dbusername = $cfg['dbusername'];
$DB->dbpassword = $cfg['dbpassword'];
$DB->debug = $cfg['debug'];
$DB->connect();
$DB->selectdb();

$local='ali1';

$r2 = $DB->query("select * from srv_mapping_tbl");
$srv = array();
$e2 = $DB->fetch_array($r2);
do {
    $srv[$e2['srv_name']] = $e2['srv_ip'];
} while ($e2=$DB->fetch_array($r2));

//print_r($e);
//print_r($srv);

$r = $DB->query("select a.*,b.srv_name,b.srv_ip from upload_log as a left join srv_mapping_tbl as b on a.srv_location = b.srv_name where a.sync_flag=
              'N'");
$rcc = mysql_num_rows($r);
if ($rcc > 0) {
    $e=$DB->fetch_array($r);
    do {
	$r2l=false;
        foreach ($srv as $key => $val) {
            if ($e['srv_name'] != $key) {
                $f = "/var/www/liteec/" . $e['path'];
                //echo $key." not sync \n";
		if ($e['srv_name']!=$local && $r2l==false){
		    echo date("Y-m-d H:i:s")."/var/www/include/fun/upload_sync_r2l.sh {$f} {$srv[$e['srv_name']]}\n";
		    dolog(date("Y-m-d H:i:s")."/var/www/include/fun/upload_sync_r2l.sh {$f} {$srv[$e['srv_name']]}\n");
            exec("/var/www/include/fun/upload_sync_r2l.sh {$f} {$srv[$e['srv_name']]}");
		    $r2l=true;
		}
                echo date("Y-m-d H:i:s")."/var/www/include/fun/upload_sync.sh {$f} {$val}\n";
				dolog(date("Y-m-d H:i:s")."/var/www/include/fun/upload_sync.sh {$f} {$val}\n");
                exec("/var/www/include/fun/upload_sync.sh {$f} {$val}");
                $DB->query("update upload_log set sync_flag = 'Y' where no = '{$e['no']}';");
            }
        }
    } while ($e = $DB->fetch_array($r));
}else{
	echo date("Y-m-d H:i:s")."===========> Good! No file need to sync to other server\n";
	dolog(date("Y-m-d H:i:s")."===========> Good! No file need to sync to other server\n");
}

function dolog($msg=null){
        /*if (!mkdir(LOG_DIR, 0766, true)) {
           # die('Failed to create folders...');
        }*/
        $myFile = LOG_DIR."/liteec_upload_sync.log";
		$fh = fopen($myFile, 'a') or die("can't open file");
        echo $myFile;
        echo $msg;
		fwrite($fh, $msg);
		fclose($fh);				
			
	}
die();
