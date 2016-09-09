<?php
class Controller{
	protected $load;
	public $data = array();

	function __construct(){
		$this->load = new Loader();
	}

	protected function view($file, Array $vars = null){
		if(count($vars) > 0)
			extract($vars);
		require_once ('application/views/'.$file.'.php');
	}

	protected function render($file){
		$this->view('base/head',$this->data);
		$this->view($file, $this->data);
		$this->view('base/foot', $this->data);
	}

	protected function post_to_array(Array $names){
		$arr = array();
		foreach($names as $name){
			$arr[$name] = strip_tags($_POST[$name]);
		}
	}

	protected function post_to_obj(Array $names, $obj){
		foreach($names as $name){
			$obj->$name = $_POST[$name];
		}

		return $obj;
	}
}

?>