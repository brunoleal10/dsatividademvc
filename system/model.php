<?php
class Model{
    private $db_host = '127.0.0.1'; 
    private $db_name = 'framework'; 
    private $db_user = 'root'; 
    private $db_pass = '';
    private $conn;

    public $id; 
    public $data;    
    protected $tabela; 

    private $select = '*';
    private $where = '';
    private $order_by ='';
    private $limit = '';
    private $offset ='';

    private $query; 
    private $result;

    protected $one_to_one;
    protected $one_to_many;
    protected $many_to_many;


	
    protected function __construct(){
        $dsn = 'mysql:host=' . $this->db_host . ';dbname=' . $this->db_name;
	$opcoes = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
	$this->conn = new PDO($dsn, $this->db_user, $this->db_pass, $opcoes);
	$this->setAttributes();
	}
        
    private function setAttributes(){
	$q = $this->conn->prepare("DESCRIBE {$this->tabela}");
	$q->execute();
	$attributes = $q->fetchAll(PDO::FETCH_COLUMN);
	foreach($attributes as $field){
            if($field =='id'){
		continue;
            }
            $this->data[$field] = null;
            $this->$field = &$this->data[$field];
	}
    }

    public function recursiveGet(){
	if($this->one_to_one){
            foreach($this->one_to_one as $name){
		$var = $name.'_id'; 
		$r_id = $this->$var;
		if (empty($r_id)) {
                    continue;
                }
                $obj = new $name();
                 $obj->getById($r_id);
                $obj->recursiveGet();
                $this->$name = $obj;		
            }
        }
        
        if($this->one_to_many){
            foreach($this->one_to_many as $name){
		$obj = new $name(); 
		$tmp = strtolower(get_class($this)); 
		$obj->where($tmp.'_id',$this->id); 
		$obj->get(); 
		$var = $name.'_list'; 
		$this->$var = $obj->all_to_array();
            }
	}
    }

    public function get(){
	if ($this->select != '*' && !preg_match('/^id,/', $this->select)) {
            $this->select = 'id,' . $this->select;
        }
        $sql = "SELECT {$this->select} FROM {$this->tabela} {$this->where} {$this->order_by} {$this->limit} {$this->offset}";  
	$this->query = $this->conn->query($sql);
	$this->result = $this->query->fetchAll(PDO::FETCH_ASSOC);
	$temp = $this->result[0];
	if(!is_null($temp)){
            $this->id = $temp['id'];
            unset($temp['id']);
            foreach ($temp as $k => $v) {
                $this->data[$k] = $v;
            }
        }
    }

	public function save(){
		if(is_int($this->id))
			return $this->update();
		$keys = '';
		$values = '';
		foreach($this->data as $k => $v){
			if(empty($v)) continue;
			$keys .= ','.$k;
			$values .= ",'{$v}'";
		}

		$keys = substr($keys, 1);
		$values = substr($values, 1);

		$sql = "INSERT INTO {$this->tabela}({$keys}) VALUES({$values})";
		$this->conn->query($sql);
		#echo $sql;
	}


    public function update(){
		if(empty($this->where))
			$this->where('id', $this->id);
		$set = '';
		foreach($this->data as $k=>$v){
			$set .=", {$k}='{$v}'";
		}
		$set = substr($set, 1);


		$sql = "UPDATE {$this->tabela} SET {$set} {$this->where}";
		$this->conn->query($sql);
		#echo $sql;
    }

    public function delete(){
		$sql = "DELETE FROM {$this->tabela} WHERE id='{$this->id}'";
		$this->conn->query($sql);
    }

    public function deleteById($id){
		$sql = "DELETE FROM {$this->tabela} WHERE id='{$id}'";
		$this->conn->query($sql);
    }

    public function getById($id){
		$this->where('id',$id);
		$this->get();
    }

    public function like($column, $value, $position = null){
		switch ($position) {
			case 'START':
				$this->where .= empty($this->where) ? "WHERE {$column} LIKE'{$value}%'" : " AND LIKE {$column}='{$value}%' ";
			break;
			case 'END':
				$this->where .= empty($this->where) ? "WHERE {$column} LIKE'%{$value}'" : " AND LIKE {$column}='%{$value}' ";
			break;
			default:
				$this->where .= empty($this->where) ? "WHERE {$column} LIKE'%{$value}%'" : " AND LIKE {$column}='%{$value}%' ";
			break;
		}

    }

    public function or_like($column, $value, $position = null){
		switch ($position) {
			case 'START':
				$this->where .= empty($this->where) ? "WHERE {$column} LIKE'{$value}%'" : " OR LIKE {$column}='{$value}%' ";
			break;
			case 'END':
				$this->where .= empty($this->where) ? "WHERE {$column} LIKE'%{$value}'" : " OR LIKE {$column}='%{$value}' ";
			break;
			default:
				$this->where .= empty($this->where) ? "WHERE {$column} LIKE'%{$value}%'" : " OR LIKE {$column}='%{$value}%' ";
			break;
		}

    }

    public function to_array(){
		return $this->result[0];
    }

    public function all_to_array(){
		return $this->result;
    }

    public function select(Array $params){
		$this->select = implode(',', $params);
    }

    public function where($column, $value){
		$this->where .= empty($this->where) ? "WHERE {$column}='{$value}'" : " and {$column}='{$value}' ";
    }

    public function order_by(Array $params, $ordem='ASC'){
		$this->order_by = 'ORDER BY ';
		foreach($params as $param){
			$this->order_by .= "{$param} {$ordem},";
		}

		$this->order_by = substr($this->order_by, 0, -1);
    }

    public function limit($value){
		$this->limit = "LIMIT ".$value;
    }

    public function offset($value){
		$this->offset = "OFFSET ".$value;
    }
}

?>