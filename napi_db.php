<?php
/**
 * @author N.Wang
 * @copyright 2012-03-20
 * @version lite
 */
/*define area*/
define('CURRENT_DIR',dirname(__FILE__));
define('LOG_DIR_NAME','/log/');
define('LOG_DIR',CURRENT_DIR . LOG_DIR_NAME);
date_default_timezone_set('Asia/Taipei');
/*end define area*/
class ndb{		
	public function __construct(){	   
		$hostname_db = "mysql.event.topwin.com.tw";
		$database_db = "event_topwin_v3";
		$username_db = "topwincomtw";
		$password_db = "12958652";
		$db = mysql_pconnect($hostname_db, $username_db, $password_db) or trigger_error(mysql_error(),E_USER_ERROR); 
		mysql_query("set names 'utf8'");
		mysql_select_db($database_db);
	}

	public function select($sql=NULL,$sort=NULL,$term=NULL,$start_row=NULL,$per_rows=NULL,$debug=false){
	   #取回資料用
        #term part
        if($term!=NULL){
            $sql.=" where {$term}";
        }
        #sort part
        if($sort!=NULL){
            $sql.=" order by {$sort}";
        }
        #limit part
        if($start_row==NULL && $per_rows!=NULL){
            $sql.=" limit {$per_rows}";              
        }elseif($start_row!=NULL && $per_rows!=NULL){
             $sql.=" limit {$start_row},{$per_rows}";               
        }
        if($debug){
            $msg["sql"]=$sql;
            $this->debug_print($msg);
        }      
        try {
            $r=mysql_query($sql);
            $data=$this->gendata_arr($r);  	            
            return $data;
            $this->free($r);
        } catch (Exception $e) {
            $this->log(date("YmdHis").$e->getMessage());
            return false;            
        }
	}
    public function count_all($sql=NULL,$term=NULL,$debug=false){
	   #取回資料用
        #term part
        if($term!=NULL){
            $sql.=" where {$term}";
        }
        
        if($debug){
            $msg["sql"]=$sql;
            $this->debug_print($msg);
        }      
        try {
            $r=mysql_query($sql);
            $ra=mysql_num_rows($r);  	            
            return $ra;
            $this->free($r);
        } catch (Exception $e) {
            $this->log(date("YmdHis").$e->getMessage());
            return false;            
        }
	}
    public function query($sql){
        #直接執行指定語法,及回傳執行成功與否
        try {            
            $r=mysql_query($sql);
            return $r;
        } catch (Exception $e) {
            $this->log(date("YmdHis").$e->getMessage());
            return false;            
        }
	}
    
    public function insert2tbl($tbl,$data,$debug=false){
        #寫入記錄
        foreach($data as $key => $val ){
            $col_set.="`{$key}`,";
            $val_set.="{$val},";
        }
        $col_set=rtrim($col_set,",");
        $val_set=rtrim($val_set,",");
        $sql="insert into {$tbl} ({$col_set}) values ({$val_set});";
        if($debug){
            $msg["sql"]=$sql;
            $this->debug_print($msg);
        }      
        try {            
            $r=mysql_query($sql);            
            return $r;
        } catch (Exception $e) {
            $this->log(date("YmdHis").$e->getMessage());
            return false;            
        }
    }
    public function update2tbl($tbl,$data,$term,$debug=false){
        #更新記錄
        foreach($data as $key => $val ){
            $col_val_set.="`{$key}`={$val},";            
        }
        $col_val_set=rtrim($col_val_set,",");        
        $sql="update {$tbl} set {$col_val_set} where {$term};";
        if($debug){
            $msg["sql"]=$sql;
            $this->debug_print($msg);
        }      
        try {            
            $r=mysql_query($sql);            
            return $r;
        } catch (Exception $e) {
            $this->log(date("YmdHis").$e->getMessage());
            return false;            
        }
    }
    public function delete2tbl($tbl,$term,$debug=false){
        #刪除記錄
        $sql="delete from {$tbl} where {$term};";
        if($debug){
            $msg["sql"]=$sql;
            $this->debug_print($msg);
        }      
        try {            
            $r=mysql_query($sql);            
            return $r;
        } catch (Exception $e) {
            $this->log(date("YmdHis").$e->getMessage());
            return false;            
        }
    }
    public function free($obj){
        #釋放資源
        return mysql_free_result($obj);
    }
    public function gen_page_navi($config){
        //<li><a href="#">1</a></li>
        #echo "test";
        #echo $config['total_page'];
        $end=$config['total_page'];
        if($_GET['p'] >0){
			$i=$_GET['p']-1;
			echo"<li><a href=\"{$config['page_link']}?p={$i}&{$q_str}\" title=\"{$config['prev_page_text']}\">&laquo; {$config['prev_page_text']}</a></li>";
		}else{
			echo"<li><a href=\"{$config['page_link']}?p=0&{$q_str}\" title=\"{$config['prev_page_text']}\">&laquo; {$config['prev_page_text']}</a></li>";
		}
        for($i=0;$i<=$end;$i++){
            
            printf("<li><a href=\"%s?p=%s\">%s</a></li>",$config['page_link'],$i,$i+1);
        }
        if($_GET['p'] <=$end){
			$i=$_GET['p']+1;
			echo"<li><a href=\"{$config['page_link']}?p={$i}&{$q_str}\" title=\"{$config['next_page_text']}\"> {$config['next_page_text']}&raquo;</a></li>";
		}else{
			echo"<li><a href=\"{$config['page_link']}?p={$end}&{$q_str}\" title=\"{$config['next_page_text']}\"> {$config['next_page_text']}&raquo;</a></li>";
		}
    }
    private function gendata_arr($obj){
        #產生回傳的資料陣列
        $data['num_rows']=mysql_num_rows($obj);
        while ($row = mysql_fetch_array($obj, MYSQL_ASSOC)) {
            $data['results'][]=$row;
        }
        return $data;
    }
	private function log($msg=null){
        if (!mkdir(LOG_DIR, 0766, true)) {
           # die('Failed to create folders...');
        }
        $myFile = LOG_DIR.date("Y-m-d_H").".log";
		$fh = fopen($myFile, 'a') or die("can't open file");
        echo $myFile;
        echo $msg;
		fwrite($fh, $msg);
		fclose($fh);				
			
	}
    public function debug_print($msg){
        echo "<pre>";
        print_r($msg);
        echo "</pre>";
    }
}	
