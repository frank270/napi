<?php
/**
 * @author N.Wang
 * @copyright 2012-03-20
 * @version ms sql srv drive
 */
/*define area*/
define('CURRENT_DIR',dirname(__FILE__));
define('LOG_DIR_NAME','/log/');
define('LOG_DIR',CURRENT_DIR . LOG_DIR_NAME);
date_default_timezone_set('Asia/Taipei');
session_start();
/*end define area*/
class ndb_ms{       
    public $conn;
    private $serverName= "host"; 
    private $database = "db";
    private $uid = "uid";
    private $pwd = "pwd";
    
    function set_db($val=NULL){
          $this->database = $val;
          $this->set_conn();
    }
    function get_db(){
        return $this->database;
    }
    function set_conn(){
        try {
            $this->conn = new PDO( "sqlsrv:server={$this->serverName};Database = {$this->database}", $this->uid, $this->pwd); 
            $this->conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION ); 
        }catch( PDOException $e ) {
            $this->log(date("YmdHis").$e->getMessage());
            die( "Error connecting to SQL Server" ); 
        }
    } 
    
    public function __construct(){  
        try {
            $this->conn = new PDO( "sqlsrv:server={$this->serverName};Database = {$this->database}", $this->uid, $this->pwd); 
            $this->conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION ); 
        }catch( PDOException $e ) {
            $this->log(date("YmdHis").$e->getMessage());
            die( "Error connecting to SQL Server" ); 
        }
    }

    public function select($col=NULL,$tbl,$term=NULL,$sort=NULL,$start_row=NULL,$end_rows=NULL,$extra_arg=NULL,$debug=false){
       #取回資料用
                
        foreach($col as $val ){
            $col_set[]="{$val}";         
        }
        $rownum= "Row_Number() Over(order by {$sort}) as rno";
        $top = (isset($extra_arg['top']))?$extra_arg['top']:"";
        
        $sql = "select {$top} ".implode($col_set,",").",{$rownum} from {$tbl}";     
        $_SESSION['debug_last_query']=array();
        if($term!=NULL){
            $sql=$sql." where {$term}";
        }
        $_SESSION['debug_last_query']['type1']=$sql;
        #sort part
        if($sort!=NULL){
            $page_sql=$sql;
            $sql=$sql." order by {$sort}";          
        }
        $_SESSION['debug_last_query']['type2']=$sql;
        
        if($start_row!=NULL && $end_rows!=NULL){            
            $sql="SELECT * from ({$page_sql}) as new_table where rno between {$start_row} and {$end_rows} order by rno asc";
        }
        $_SESSION['debug_last_query']['type3']=$sql;
        
        if($debug){
            $msg["sql"]=$sql;
            $this->debug_print($msg);
        }      
        try {       
            $_SESSION['debug_last_query']['type4']=$sql;
            $r=$this->conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));                    
            $data=$this->gendata_arr($r);               
            return $data;           
        } catch (Exception $e) {
            $this->log(date("YmdHis").$e->getMessage()."\n sql=>{$sql}");
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
            $r=$this->conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));   
            $r->execute();         
            $data=$this->gendata_arr($r);               
            return $data;         
        } catch (Exception $e) {
            $this->log(date("YmdHis").$e->getMessage());
            return false;            
        }
    }


    /**
     * doQuery
     * 
     * @param mixed $sql   Description.
     * @param mixed $debug Description.
     *
     * @access public
     *
     * @return mixed Value.
     */
    public function doQuery($sql=NULL,$debug=false){
        if($debug){
            $msg["sql"]=$sql;
            $this->debug_print($msg);
        }      
        try {
            $r=$this->conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));   
            $r->execute();         
            $data=$this->gendata_arr($r);               
            return $data;         
        } catch (Exception $e) {
            $this->log(date("YmdHis").$e->getMessage());
            return false;            
        }
    }
    public function sql_str($str,$type="str"){
        #產成sql 用字串
        switch($type){
            case "str":
                $re_str="'{$str}'";
                break;
            case "int":
                $re_str="{$str}";
                break;
            case "bol":
                $re_str="{$str}";
                break;        
            break;
        }
        return $re_str;
    }
    public function insert2tbl($tbl,$data,$debug=false){
        #寫入記錄
        foreach($data as $key => $val ){
            $col_set[]="{$key}";
            $val_set[]="{$val}";
        }
        $col_set=implode(",",$col_set);
        $val_set=implode(",",$val_set);
        $sql="insert into {$tbl} ({$col_set}) values ({$val_set});";
        if($debug){
            $msg["sql"]=$sql;
            $this->debug_print($msg);
        }      
        try {            
            $r=$this->conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));       
            return $r->execute();   
        } catch (Exception $e) {
            $this->log(date("YmdHis").$e->getMessage());
            return false;            
        }
    }
    public function update2tbl($tbl,$data,$term,$debug=false){
        #更新記錄
        foreach($data as $key => $val ){
            $col_val_set[]="{$key}={$val}";            
        }
        $col_val_set=implode(",",$col_val_set);        
        $sql="update {$tbl} set {$col_val_set} where {$term};";
        if($debug){
            $msg["sql"]=$sql;
            $this->debug_print($msg);
        }      
        try {                        
            $r=$this->conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));                   
             return $r->execute();  
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
            $r=$this->conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));       
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
        $end=$config['total_page']; 
        if($config['display_pages']>=0){
            $display_page=$config['display_pages'];
        }else{
            $display_page=4;
        }
        $q=$_SERVER["QUERY_STRING"];
        $q_arr=(strpos($q,"&"))?explode("&",$q):$q;        
        if(is_array($q_arr)){
            foreach($q_arr as $e){              
                if (substr($e,0,2)!="p="){                    
                    $q_str.=$e;
                    }
            }
        }else{
            if (substr($q_arr,0,2)!="p="){
                $q_str=$q_arr;
                }
        }
        #echo $q_str;
        echo "{$config['div_open_tag']}
            <a href=\"{$config['page_link']}?p=0\" title=\"{$config['first_page_text']}\">&laquo; {$config['first_page_text']}</a>";
        if($_GET['p'] >0){
            $i=$_GET['p']-1;
            echo"<a href=\"{$config['page_link']}?p={$i}&{$q_str}\" title=\"{$config['prev_page_text']}\">&laquo; {$config['prev_page_text']}</a>";
        }else{
            echo"<a href=\"{$config['page_link']}?p=0&{$q_str}\" title=\"{$config['prev_page_text']}\">&laquo; {$config['prev_page_text']}</a>";
        }
        
        for($i=0;$i<=$end;$i++){
            $i1=$i+1;
            if(($i >= ($_GET['p']-$display_page)) and ($i <= ($_GET['p']+$display_page))){ 
            if($i==$_GET['p']){         
                echo "           
                <a href=\"{$config['page_link']}?p={$i}&{$q_str}\" class=\"{$config['num_link_css_class']} {$config['curr_num_link_css_class']}\" title=\"{$i1}\">{$i1}</a>
                ";
            }else{
                echo "           
                <a href=\"{$config['page_link']}?p={$i}&{$q_str}\" class=\"{$config['num_link_css_class']}\" title=\"{$i1}\">{$i1}</a>
                ";    
            }
            }
        }
        if($_GET['p'] <=$end){
            $i=$_GET['p']+1;
            echo"<a href=\"{$config['page_link']}?p={$i}&{$q_str}\" title=\"{$config['next_page_text']}\"> {$config['next_page_text']}&raquo;</a>";
        }else{
            echo"<a href=\"{$config['page_link']}?p={$end}&{$q_str}\" title=\"{$config['next_page_text']}\"> {$config['next_page_text']}&raquo;</a>";
        }
        
        echo "<a href=\"{$config['page_link']}?p={$config['total_page']}\" title=\"{$config['last_page_text']}\">{$config['last_page_text']} &raquo;</a>
            {$config['div_close_tag']}";
    }
    public function data_tbl($tbl,$type="mb"){        
        echo "<table border=\"1\">";
        echo "<thead><tr>";
        foreach($tbl[0] as $key => $val){
            if($type['type']=="iconv"){
                echo iconv($type["in"],$type['out'],"<th>{$key}</th>");
            }elseif($type['type']=="mb"){
               echo mb_convert_encoding("<th>{$key}</th>",$type['out'],$type['in']); 
            }else{
                echo "<th>{$key}</th>";
            }
        }
        echo "</tr></thead>";
        echo "<tbody>";
        foreach($tbl as $e =>$val){
            echo "<tr>";
            foreach($val as $v){
                 if($type['type']=="iconv"){
                    echo iconv($type["in"],$type['out'],"<td>{$v}</td>");
                }elseif($type['type']=="mb"){
                   echo mb_convert_encoding("<td>{$v}</td>",$type['out'],$type['in']); 
                }else{
                    echo "<td>{$v}</td>";
                }                
            }
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table >";
        
    }
    public function get_qs($str){
        
        $q=$_SERVER["QUERY_STRING"];
        $q_arr=(strpos($q,"&"))?explode("&",$q):$q;
        $str_arr=(strpos($str,"&"))?explode("&",$str):$str;
        
        if(is_array($q_arr)){
            foreach($q_arr as $val){
                $tmp_arr=explode("=",$val);
                $q_str_arr[$tmp_arr[0]]=$tmp_arr[1];#先把原有的query string 拆組成屬性值的陣列
            }
        }else{
            $tmp_arr=explode("=",$q_arr);
            $q_str_arr[$tmp_arr[0]]=$tmp_arr[1];#先把原有的query string 拆組成屬性值的陣列
        }
        if(is_array($str_arr)){
                foreach($str_arr as $val){
                    $tmp_arr1=explode("=",$val);
                    $q_str_arr[$tmp_arr1[0]]=$tmp_arr1[1];#此舉在於如果有重覆的query string 利用陣列特性,直接覆蓋掉
                }
        }else{
                $tmp_arr1=explode("=",$str_arr);
                $q_str_arr[$tmp_arr1[0]]=$tmp_arr1[1];#此舉在於如果有重覆的query string 利用陣列特性,直接覆蓋掉
        }
        foreach( $q_str_arr as $k => $v){
            $out_arr[]=$k."=".$v;
        }
        
        return implode("&", $out_arr);
    }
    private function gendata_arr($obj){
        #產生回傳的資料陣列
        $obj->execute();
        $data['num_rows']=$obj->rowCount();
        while ($row = $obj->fetch( PDO::FETCH_ASSOC )) {
            $data['results'][]=$row;
        }
        return $data;
    }
    private function log($msg=null){
        if (!@mkdir(LOG_DIR, 0766, true)) {
           # die('Failed to create folders...');
        }
        $myFile = LOG_DIR.date("Y-m-d_H").".log";
        $fh = @fopen($myFile, 'a') or die("can't open file");
        #echo $myFile;
        #echo $msg;
        fwrite($fh, "\n".$msg."\n");
        fclose($fh);                            
    }
    public function edmlog($msg=null){
        if (!@mkdir(LOG_DIR, 0766, true)) {
           # die('Failed to create folders...');
        }
        $myFile = LOG_DIR.date("Y-m-d_H")."_edm.log";
        $fh = @fopen($myFile, 'a') or die("can't open file");
        #echo $myFile;
        #echo $msg;
        fwrite($fh, "\n".$msg."\n");
        fclose($fh);                            
    }
    public function debug_print($msg){
        echo "<pre>";
        print_r($msg);
        echo "</pre>";
    }
}   
