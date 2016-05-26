<?php
/* Ldb3 - stand for Luthfie database 3rd Generation
 * Authored by 9r3i a.k.a Luthfie a.k.a. Abu Ayyub
 * Author-Email: luthfie@y7mail.com
 * Author-URI: https://github.com/9r3i
 *
 * Started on June 5th 2014 - Finished on June 12th 2014
 * Version: 3.0 Beta
 *
 * Continued on October 4th 2014 - Finished on October 6th 2014
 * Version: 3.0 Alpha
 *
 * Class file: Ldb3.php
 * API file: Ldb3-api.txt
 */

class Ldb3{
  public $database;
  public $access = 'not allowed yet';
  public $errors = array();
  public $error = false;
  private $start_time;
  /*** function __construct ***/
  function __construct($dir=null){
    $this->setting($dir);
    $this->start_time = microtime(true);
  }
  /*** public functions before connection ***/
  public function connect($database=null,$username=null,$password=null){
    if(defined('LDB_ACCESS')){
      $this->error = 'database has been connected';
      $this->errors[] = $this->error;
      return false;
    }elseif(isset($database)&&isset($username)&&isset($password)){
      $this->database = $database;
      $content = $this->get_raw_content();
      if($content){
        $decode =  $this->decode($content);
        if(isset($decode[$database]['access'][$username])&&!defined('LDB_ACCESS')){
          if($decode[$database]['access'][$username]==$this->hash($password,7)){
            $access_key = base64_encode($username.':'.$this->hash($password,7).'@'.$database);
            define('LDB_ACCESS',$access_key);
            $this->access = 'allowed';
            return $this;
          }else{
            $this->error = 'cannot connect into database';
            $this->access = 'denied';
            $this->errors[] = $this->error;
            return false;
          }
        }else{
          $this->error = 'cannot access database by '.$username.'@'.$database;
          $this->access = 'denied';
          $this->errors[] = $this->error;
          return false;
        }
      }else{
        $this->error = 'database does not exist';
        $this->access = 'denied';
        $this->errors[] = $this->error;
        return false;
      }
    }else{
      $this->error = 'cannot connect into database';
      $this->access = 'denied';
      $this->errors[] = $this->error;
      return false;
    }
  }
  public function create_database($database=null,$username=null,$password=null){
    if(isset($database)&&isset($username)&&isset($password)&&preg_match('/[a-z0-9_]+/i',$database)&&preg_match('/[a-z0-9_]+/i',$username)){
      $db_name = preg_replace('/[^a-z0-9_]+/i','',$database);
      $dir = $this->dir();
      if(!file_exists($dir.$db_name.'.ldb')){
        $db_user = preg_replace('/[^a-z0-9_]+/i','',$username);
        $db_pass = $this->hash($password,7);
        $data = array($db_name=>array('access'=>array($db_user=>$db_pass),'db_content'=>array()));
        $write = $this->write($dir.$db_name.'.ldb',$this->encode($data));
        if($write){
          return true;
        }else{
          $this->error = 'cannot create database';
          $this->errors[] = $this->error;
          return false;
        }
      }else{
        $this->error = 'database already exist';
        $this->errors[] = $this->error;
        return false;
      }
    }else{
      $this->error = 'empty parameter';
      $this->errors[] = $this->error;
      return false;
    }
  }
  /*** allowed functions after connection by this way: ($access=$this->allowed())!==false ***/
  public function show_database(){
    if(($access=$this->allowed())!==false){
      $database = array();
      $sdir = @scandir($this->dir());
      foreach($sdir as $sd){
        if(preg_match('/\.ldb/i',$sd)){
          $database[] = str_replace('.ldb','',$sd);
        }
      }
      return $database;
    }else{
      $this->error = 'access is required';
      $this->errors[] = $this->error;
      return false;
    }
  }
  /***# access required #***/ /*** table functions ***/
  public function create_table($name=null,$options=array()){
    if(($access=$this->allowed())!==false&&isset($name)&&is_array($options)){
      $name = preg_replace('/[^a-zA-Z0-9_]+/i','',trim($name));
      if(isset($access[$name])){
        $this->error = 'table already exists';
        $this->errors[] = $this->error;
        return false;
      }else{
        $reop = array();
        foreach($options as $key=>$value){
          $reop['column_content']['primary_tid'] = array();
          if(is_numeric($key)&&!in_array($value,$this->default_value())){
            $value = preg_replace('/[^a-zA-Z0-9_]+/i','',trim($value));
            $reop['column_name'][] = $value;
            $reop['column_default'][$value] = '';
            $reop['column_content'][$value] = array();
          }elseif(!is_numeric($key)){
            $key = preg_replace('/[^a-zA-Z0-9_]+/i','',trim($key));
            $reop['column_name'][] = $key;
            $value = (isset($value))?$value:'NULL';
            $value = (in_array($value,$this->default_value()))?$value:$value;
            $reop['column_default'][$key] = $value;
            $reop['column_content'][$key] = array();
          }
        }
        $new_table = array($name=>array(
          'table_option'=>array(
            'aid'=>0,
            'column_name'=>$reop['column_name'],
            'column_default'=>$reop['column_default'],
          ),
          'table_content'=>$reop['column_content']
        ));
        $access = (is_array($access))?$access:array();
        $merge = array_merge($access,$new_table);
        return $this->write_db_content($merge);
      }
    }else{
      $this->error = 'cannot create a table';
      $this->errors[] = $this->error;
      return false;
    }
  }
  public function alter_table($name=null,$options=array()){
    if(($access=$this->allowed())!==false&&isset($name)&&is_array($options)){
      if(isset($access[$name])){
        $old_table = $access[$name];
        $reop = array();
        foreach($options as $key=>$value){
          $reop['column_content']['primary_tid'] = $old_table['table_content']['primary_tid'];
          if(is_numeric($key)&&!in_array($value,$this->default_value())){
            $value = preg_replace('/[^a-zA-Z0-9_]+/i','',trim($value));
            $reop['column_name'][] = $value;
            $reop['column_default'][$value] = '';
            if(isset($old_table['table_content'][$value])){
              $reop['column_content'][$value] = $old_table['table_content'][$value];
            }else{
              $new_col = array();
              foreach($old_table['table_content']['primary_tid'] as $tid){$new_col[$tid] = '';}
              $reop['column_content'][$value] = $new_col;
            }
          }elseif(!is_numeric($key)){
            $key = preg_replace('/[^a-zA-Z0-9_]+/i','',trim($key));
            $reop['column_name'][] = $key;
            $value = (isset($value))?$value:'NULL';
            $value = (in_array($value,$this->default_value()))?$value:$value;
            $reop['column_default'][$key] = $value;
            $reop['column_content'][$key] = array();
            if(isset($old_table['table_content'][$key])){
              $new_col = array();
              foreach($old_table['table_content']['primary_tid'] as $tid){
                if($old_table['table_option']['column_default'][$key]!==$value){
                  $new_col[$tid] = $this->column_default($value,(($value=='AID')?$name:$old_table['table_content'][$key][$tid]));
                  if($value=='AID'){$old_table['table_option']['aid']++;}
                }else{
                  $new_col[$tid] = $old_table['table_content'][$key][$tid];
                }
              }
              $reop['column_content'][$key] = $new_col; //$old_table['table_content'][$key];
            }else{
              $new_col = array();
              foreach($old_table['table_content']['primary_tid'] as $tid){$new_col[$tid] = $value;}
              $reop['column_content'][$key] = $new_col;
            }
          }
        }
        $alter_table = array(
          'table_option'=>array(
            'aid'=>$old_table['table_option']['aid'],
            'column_name'=>$reop['column_name'],
            'column_default'=>$reop['column_default'],
          ),
          'table_content'=>$reop['column_content']
        );
        $access[$name] = $alter_table;
        return $this->write_db_content($access);
      }else{
        $this->error = 'table does not exist';
        $this->errors[] = $this->error;
        return false;
      }
    }else{
      $this->error = 'table options are required';
      $this->errors[] = $this->error;
      return false;
    }
  }
  public function drop_table($name=null){
    if(($access=$this->allowed())!==false&&isset($name)){
      if(isset($access[$name])){
        unset($access[$name]);
        return $this->write_db_content($access);
      }else{
        $this->error = 'table does not exist';
        $this->errors[] = $this->error;
        return false;
      }
    }else{
      $this->error = 'table name is required';
      $this->errors[] = $this->error;
      return false;
    }
  }
  public function show_tables(){
    if(($access=$this->allowed())!==false){
      $tables = array_keys($access);
      return $tables;
    }else{
      $this->error = 'access is required';
      $this->errors[] = $this->error;
      return false;
    }
  }
  public function show_columns($table_name=null){
    if(($access=$this->allowed())!==false&&isset($table_name)){
      if(isset($access[$table_name])){
        return $access[$table_name]['table_option']['column_name'];
      }else{
        $this->error = 'table does not exist';
        $this->errors[] = $this->error;
        return false;
      }
    }else{
      $this->error = 'access is required';
      $this->errors[] = $this->error;
      return false;
    }
  }
  /***# access required #***/ /*** field functions ***/
  public function insert($table=null,$content=array()){
    if(($access=$this->allowed())!==false&&isset($table,$access,$access[$table])&&is_array($content)){
      $primary_tid = $this->default_tid();
      $table_content = $access[$table]['table_content'];
      $table_content['primary_tid'][$primary_tid] = $primary_tid;
      $default = $access[$table]['table_option']['column_default'];
      foreach($default as $key=>$value){
        if(in_array($value,$this->default_value())){
          $table_content[$key][$primary_tid] = $this->column_default($value);
        }elseif(!isset($content[$key])||$content[$key]==''){
          $table_content[$key][$primary_tid] = $value;
        }else{
          $table_content[$key][$primary_tid] = $content[$key];
        }
      }
      return $this->write_table_content($table,$table_content,true);
    }else{
      $this->error = 'access is required';
      $this->errors[] = 'access is required';
      return false;
    }
  }
  public function delete($table=null,$where=null){
    if(!isset($table)){
      $this->error = 'table is not selected';
      $this->errors[] = $this->error;
      return false;
    }elseif(!isset($where)){
      $this->error = 'location is not selected';
      $this->errors[] = $this->error;
      return false;
    }elseif(($access=$this->allowed())==false){
      $this->error = 'access is required';
      $this->errors[] = $this->error;
      return false;
    }elseif(!isset($access[$table])){
      $this->error = 'table does not exist';
      $this->errors[] = $this->error;
      return false;
    }elseif(($parse=$this->parse_where($where))==false){
      $this->error = 'data content cannot be parsed';
      $this->errors[] = $this->error;
      return false;
    }elseif(isset($access)&&is_array($access)&&isset($parse)){
      $table_content = $access[$table]['table_content'];
      $tids = $this->tid_table_content($table_content,$parse);
      if(is_array($tids)&&count($tids)>0){
        foreach($table_content as $column=>$content_array){
          foreach($content_array as $tid=>$content){
            if(in_array($tid,$tids)){
              unset($table_content[$column][$tid]);
            }
          }
        }
        return $this->write_table_content($table,$table_content);
      }else{
        $this->error = 'cannot find the key';
        $this->errors[] = $this->error;
        return false;
      }
    }else{
      $this->error = 'unknown error';
      $this->errors[] = $this->error;
      return false;
    }
  }
  public function update($table=null,$content=array(),$where=null){
    if(!isset($table)){
      $this->error = 'table is not selected';
      $this->errors[] = $this->error;
      return false;
    }elseif(!isset($where)){
      $this->error = 'location is not selected';
      $this->errors[] = $this->error;
      return false;
    }elseif(($access=$this->allowed())==false){
      $this->error = 'access is required';
      $this->errors[] = $this->error;
      return false;
    }elseif(!isset($access[$table])){
      $this->error = 'table does not exist';
      $this->errors[] = $this->error;
      return false;
    }elseif(!is_array($content)){
      $this->error = 'data content cannot be parsed';
      $this->errors[] = $this->error;
      return false;
    }elseif(($parse=$this->parse_where($where))==false){
      $this->error = 'data content cannot be parsed';
      $this->errors[] = $this->error;
      return false;
    }elseif(isset($access)&&is_array($access)&&isset($parse)){
      $table_content = $access[$table]['table_content'];
      $tids = $this->tid_table_content($table_content,$parse);
      $default = $access[$table]['table_option']['column_default'];
      if(is_array($tids)&&count($tids)>0){
        foreach($table_content as $column=>$content_array){
          foreach($content_array as $tid=>$content_inside){
            if(in_array($tid,$tids)&&$column!=='primary_tid'&&!in_array($default[$column],$this->default_value())&&isset($content[$column])){
              $table_content[$column][$tid] = $content[$column];
            }
          }
        }
        return $this->write_table_content($table,$table_content);
      }else{
        $this->error = 'cannot find the key';
        $this->errors[] = $this->error;
        return false;
      }
    }else{
      $this->error = 'unknown error';
      $this->errors[] = $this->error;
      return false;
    }
  }
  public function select($table=null,$where=null,$option=null){
    if(!isset($table)){
      $this->error = 'table is not selected';
      $this->errors[] = $this->error;
      return false;
    }elseif(($access=$this->allowed())==false){
      $this->error = 'access is required';
      $this->errors[] = $this->error;
      return false;
    }elseif(!isset($access[$table])){
      $this->error = 'table does not exist';
      $this->errors[] = $this->error;
      return false;
    }elseif(($parse=$this->parse_where($where))==false&&$where!==null){
      $this->error = 'data content cannot be parsed';
      $this->errors[] = $this->error;
      return false;
    }elseif(isset($access)&&is_array($access)&&isset($parse)){
      $table_content = $access[$table]['table_content'];
      $table_option = $access[$table]['table_option'];
      $tids = ($parse)?$this->tid_table_content($table_content,$parse):array();
      $table_rows = count($table_content['primary_tid']);
      unset($table_content['primary_tid']); // primary_tid unset after parsed
      $option = $this->parse_option($option);
      $hasil = array();
      $order = (isset($option['order'])&&in_array($option['order'],$table_option['column_name']))?$option['order']:'';
      foreach($table_content as $column=>$content_array){
        foreach($content_array as $tid=>$content){
          $index_key = (isset($table_content[$order]))?$table_content[$order][$tid].'_'.$tid:$tid;
          if(in_array($tid,$tids)){
            $hasil[$index_key][$column] = $table_content[$column][$tid];
          }elseif($where==null){
            $hasil[$index_key][$column] = $table_content[$column][$tid];
          }
        }
      }
      $hasil = (isset($table_content[$order]))?$hasil:array_values($hasil);
      if(isset($option['sort'])){
        if($option['sort']=='ASC'){
          ksort($hasil);
        }elseif($option['sort']=='DESC'){
          ksort($hasil);
          $hasil = array_reverse($hasil,true);
        }
      }
      $start = (isset($option['start']))?$option['start']:0;
      $limit = (isset($option['limit']))?$option['limit']:25;
      $r = 0; $hasil_akhir = array();
      $key = (isset($option['key']))?$option['key']:'';
      foreach($hasil as $kunci=>$nilai){
        if($r>=$start){
          if(isset($nilai[$key])){
            $hasil_akhir[$nilai[$key]] = $nilai;
          }else{
            $hasil_akhir[$kunci] = $nilai;
          }
          $r++;
          if($r>=($limit+$start)){break;}
        }
      }
      $ldb_selected = new Ldb3_selected($hasil_akhir,$this->start_time,$table_rows);
      return $ldb_selected;
    }else{
      $this->error = 'unknown error';
      $this->errors[] = $this->error;
      return false;
    }
  }
  /***# access required #***/ /*** user functions ***/
  function create_user($username=null,$password=null){
    if(($access=$this->allowed(true))!==false){
      if(isset($username)&&isset($password)){
        $db_user = preg_replace('/[^a-z0-9_]+/i','',$username);
        $db_pass = $this->hash($password,7);
        if(isset($access[$this->database]['access'][$db_user])){
          $this->error = 'username has been taken';
          $this->errors[] = $this->error;
          return false;
        }else{
          $data = array($db_user=>$db_pass);
          $merge = array_merge($access[$this->database]['access'],$data);
          $access[$this->database]['access'] = $merge;
          if(!empty($db_user)){
            return $this->write($this->dir().$this->database.'.ldb',$this->encode($access));
          }else{
            $this->error = 'username is returned to empty';
            $this->errors[] = $this->error;
            return false;
          }
        }
      }else{
        $this->error = 'username and password are required';
        $this->errors[] = $this->error;
        return false;
      }
    }else{
      $this->error = 'access is required';
      $this->errors[] = $this->error;
      return false;
    }
  }
  function delete_user($username=null){
    if(($access=$this->allowed(true))!==false){
      if(isset($username)){
        if(isset($access[$this->database]['access'][$username])){
          unset($access[$this->database]['access'][$username]);
          return $this->write($this->dir().$this->database.'.ldb',$this->encode($access));
        }else{
          $this->error = 'username does not exist';
          $this->errors[] = $this->error;
          return false;
        }
      }else{
        $this->error = 'username is required';
        $this->errors[] = $this->error;
        return false;
      }
    }else{
      $this->error = 'access is required';
      $this->errors[] = $this->error;
      return false;
    }
  }
  /***# no access required #***/ /*** optional functions ***/
  function hash($password='',$algo=5,$raw=false){
    $algos = hash_algos();
    $algo = ($algo<count($algos))?$algo:5;
    $hash = @hash($algos[$algo],$password,$raw);
    return $hash;
  }
  /*** private functions ***/
  private function tid_table_content($table_content=array(),$where=array()){
    if(is_array($table_content)&&is_array($where)){
      if(isset($where['key'])){
        $hasil = array();
        foreach($table_content as $column=>$content){
          if($column==$where['key']){
            $hasil = array_keys($content,$where['value']);
          }
        }
        return $hasil;
      }elseif(isset($where[0]['key'])){
        $hasil = array();
        foreach($where as $wher){
          foreach($table_content as $column=>$content){
            if($column==$wher['key']){
              $has = array_keys($content,$wher['value']);
              $hasil = array_merge($hasil,$has);
            }
          }
        }
        $hasil = array_unique($hasil);
        return $hasil;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  private function parse_option($where=null){
    $default_keys = array('key','order','sort','start','limit');
    $hasil = array();
    if(isset($where)&&preg_match('/\=/i',$where)){
      $we = @explode('&',$where);
      foreach($we as $wo){
        $wi = @explode('=',$wo);
        if(isset($wi[0],$wi[1])&&in_array(strtolower($wi[0]),$default_keys)){
          $hasil[strtolower($wi[0])] = $wi[1];
        }
      }
    }
    return $hasil;
  }
  private function parse_where($where=null){
    if(!isset($where)){
      return false;
    }elseif(preg_match('/\=/i',$where)){
      $hasil = array();
      if(preg_match('/\&/i',$where)){
        $we = @explode('&',$where);
        $r=0;
        foreach($we as $wo){
          $wi = @explode('=',$wo);
          if(isset($wi[1])){
            $hasil[$r]['key'] = $wi[0];
            $hasil[$r]['value'] = $wi[1];
            $r++;
          }
        }
      }else{
        $wi = @explode('=',$where);
        if(isset($wi[1])){
          $hasil['key'] = $wi[0];
          $hasil['value'] = $wi[1];
        }
      }
      return $hasil;
    }else{
      return false;
    }
  }
  private function decode($data=null){
    if(isset($data)){
      return @json_decode(@base64_decode($this->Lo9($data,true)),true);
    }else{
      return false;
    }
  }
  private function encode($data=array()){
    if(is_array($data)){
      return $this->Lo9(@base64_encode(@json_encode($data,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)));
    }else{
      return false;
    }
  }
  private function write($filename=null,$content='',$type='wb'){
    if(isset($filename)){
      $fp = @fopen($filename,$type);
      if(flock($fp,LOCK_EX)){
        $write = @fwrite($fp,$this->strip_magic($content));
        flock($fp,LOCK_UN);
        if($write){
          return true;
        }else{
          return false;
        }
      }else{
        return false;
      }
      @fclose($fp);
    }else{
      return false;
    }
  }
  private function strip_magic($str){
    if(is_array($str)){
      $hasil = array();
	  foreach($str as $k=>$v){
        $hasil[$k] = (get_magic_quotes_gpc())?stripslashes($v):$v;
      }
      return $hasil;
	}else{
	  return (get_magic_quotes_gpc())?stripslashes($str):$str;
	}
  }
  private function Lo9($str=null,$reverse=false){
    if(isset($str)){
      if($reverse){
        return strtr($str,'Lo9JqHsF7uDwBy5AmOkQ3iSgUeW1cYa0ZbXdVf2ThRjP4lNzCx6EvGtI8rKpMn','0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
      }else{
        return strtr($str,'0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ','Lo9JqHsF7uDwBy5AmOkQ3iSgUeW1cYa0ZbXdVf2ThRjP4lNzCx6EvGtI8rKpMn');
      }
    }else{
      return false;
    }
  }
  private function default_bid(){
    $tid = $this->default_tid('.');
    $exp = @explode('.',$tid);
    $hasil = ($exp[0]-452373300).$exp[1];
    return $hasil;
  }
  private function default_cid(){
    $tid = $this->default_tid('.');
    $exp = @explode('.',$tid);
    $hasil = @dechex($exp[0]-452373300).@dechex($exp[1]);
    return $hasil;
  }
  private function default_tid($float='',$tri=''){
    return number_format(microtime(true),9,$float,$tri);
  }
  /*** protected functions ***/
  protected function get_access(){
    if(($access=$this->allowed(true))!==false){
      return $access;
    }else{
      return false;
    }
  }
  protected function get_raw_content(){
    $filename = $this->dir().$this->database.'.ldb';
    if(file_exists($filename)){
      return @file_get_contents($filename);
    }else{
      return false;
    }
  }
  protected function allowed($raw=false){
    if(defined('LDB_ACCESS')){
      $ldb_access = base64_decode(LDB_ACCESS);
      if(preg_match('/[a-z0-9_]+:/i',$ldb_access,$akur)&&preg_match('/:[0-9a-f]+@/i',$ldb_access,$akur2)&&preg_match('/@[a-z0-9_]+/i',$ldb_access,$akur3)&&preg_match('/[a-z0-9_]+:[0-9a-f]+@[a-z0-9_]+/i',$ldb_access)){
        $username = rtrim($akur[0],'\:');
        $password = substr($akur2[0],1,-1);
        $database = substr($akur3[0],1,strlen($akur3[0]));
        $content = $this->decode($this->get_raw_content());
        if(isset($content[$database]['access'][$username])&&$content[$database]['access'][$username]==$password){
          if($raw==true){
            return $content;
          }else{
            unset($content[$database]['access']);
            return $content[$database]['db_content'];
          }
        }else{
          $this->error = 'access denied';
          $this->access = 'denied';
          $this->errors[] = $this->error;
          return false;
        }
      }else{
        $this->error = 'access denied';
        $this->access = 'denied';
        $this->errors[] = $this->error;
        return false;
      }
    }else{
      $this->error = 'access denied';
      $this->access = 'denied';
      $this->errors[] = $this->error;
      return false;
    }
  }
  /***# access required #***/
  protected function write_db_content($db_content=array()){
    if(is_array($db_content)&&($access=$this->allowed(true))!==false){
      $access[$this->database]['db_content'] = $db_content;
      $write = $this->write($this->dir().$this->database.'.ldb',$this->encode($access));
      if($write){
        return true;
      }else{
        $this->error = 'cannot write database content';
        $this->errors[] = $this->error;
        return false;
      }
    }else{
      $this->error = 'access is required';
      $this->errors[] = $this->error;
      return false;
    }
  }
  protected function write_table_content($table,$table_content=array(),$aid=false){
    if(is_array($table_content)&&($access=$this->allowed(true))!==false){
      if(isset($access[$this->database]['db_content'][$table])){
        if($aid&&isset($access[$this->database]['db_content'][$table]['table_option']['aid'])){
          $aid = $access[$this->database]['db_content'][$table]['table_option']['aid'];
          $aid++;
          $access[$this->database]['db_content'][$table]['table_option']['aid'] = $aid;
        }
        $access[$this->database]['db_content'][$table]['table_content'] = $this->write_aid($table_content,$aid);
        $write = $this->write($this->dir().$this->database.'.ldb',$this->encode($access));
        if($write){
          return true;
        }else{
          $this->error = 'cannot write table content';
          $this->errors[] = $this->error;
          return false;
        }
      }else{
        $this->error = 'table does not exist';
        $this->errors[] = $this->error;
        return false;
      }
    }else{
      $this->error = 'access is required';
      $this->errors[] = $this->error;
      return false;
    }
  }
  protected function write_aid($table_content=array(),$aid=false){
    if($aid&&is_array($table_content)){
      foreach($table_content as $key=>$value){
        foreach($value as $kunci=>$nilai){
          if($nilai=='LDB_AID'){
            $table_content[$key][$kunci] = $aid;
            break;
          }
        }
      }
    }
    return $table_content;
  }
  protected function column_default($key=null){
    if(isset($key)){
      $default = array(
        'AID'=>'LDB_AID',
        'BID'=>$this->default_bid(),
        'CID'=>$this->default_cid(),
        'TID'=>$this->default_tid(),
        'TIME'=>time(),
        'TIMESTAMP'=>date('Y-m-d H:i:s'),
        'DATE'=>date('Y-m-d'),
        'NULL'=>null,
      );
      if(array_key_exists($key,$default)){
        return $default[$key];
      }
    }else{
      $this->error = 'key is required';
      $this->errors[] = $this->error;
      return false;
    }
  }
  protected function default_value(){
    return array('AID','BID','CID','TID','TIME','TIMESTAMP','DATE','NULL');
  }
  /***# no access required #***/ /*** directory function ***/
  protected function dir($db_dir=null){
    $default = '_Ldb3/';
    if(isset($_SERVER['DOCUMENT_ROOT'])){
      if(isset($db_dir)){
        $new_db_dir = preg_replace('/[^a-z0-9_]+/','',$db_dir);
        return $_SERVER['DOCUMENT_ROOT'].'/'.$new_db_dir.'/';
      }else{
        return $_SERVER['DOCUMENT_ROOT'].'/'.$default;
      }
    }else{
      return $default;
    }
  }
  /***# no access required #***/ /*** setting function ***/
  protected function setting($db_dir=null){
    $dir = $this->dir($db_dir);
    if(!is_dir($dir)){
	  @mkdir($dir,0700);
	}
	@chmod($dir,0700);
	if(!file_exists($dir.'.htaccess')){
	  $this->write($dir.'.htaccess','Options -Indexes'. PHP_EOL .'deny from all');
	}
  }
}

/* Ldb3_selected class
 * The next class to fetch and return selected data
 * This class is in alpha version only
 */

class Ldb3_selected{
  public $rows;
  public $error = false;
  private $store;
  private $debt = 0;
  private $start_time;
  private $finish_time;
  public $process_time;
  public $table_rows;
  function __construct($selected=array(),$start_time=null,$table_rows=null){
    if(is_array($selected)){
      $this->store['o'] = $selected;
      $this->store['i'] = array_values($selected);
      $this->rows = count($selected);
      if(isset($start_time)){
        $this->start_time = $start_time;
        $this->finish_time = microtime(true);
        $this->process_time = number_format(($this->finish_time-$this->start_time),4,'.',',');
      }
      if(isset($table_rows)){
        $this->table_rows = $table_rows;
      }
    }else{
      $this->error = 'return zero data';
    }
  }
  public function fetch_array(){
    if(isset($this->store['i'])){
      $stores = $this->store['i'];
      $result = array();
      foreach($stores as $id=>$store){
        if($id==$this->debt){
          $result = $store;
          $this->debt++;
          break;
        }
      }
      return $result;
    }else{
      return false;
    }
  }
  public function fetch_store(){
    if(isset($this->store['o'])){
      return $this->store['o'];
    }else{
      return false;
    }
  }
}
