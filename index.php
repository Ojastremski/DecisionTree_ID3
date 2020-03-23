<?php 

class Test
{
	public $r = array();
	public $new_id = "";
	public $last_elem = "";
	public $tt = array();
//echo body($columns);

// Funkcja << Main >> odpowiadająca za prezentację wyniku programu
function body ($columns) {	
	$data = $this->cut_decision_column($columns);
	
	echo "<pre>";
	$occurrence = $this->count_occurrence($data[0],$data[1]);
	print_r($occurrence);
	echo "</pre>";
	
	echo "<h3>Entropia rozkładu prawdopodobieństwa</h3>";
	print_r($this->entropy($data[1]));
	
	echo "<h3>Informacja atrybutu</h3>";
	echo "<pre>";
	print_r($this->information_for_each_column($data[0],$occurrence));
	echo "</pre>";
	
	echo "<h3>GAIN(X,T)</h3>";
	echo "<pre>";
	$gain = $this->gain($data[1],$data[0],$occurrence);
	print_r($this->gain($data[1],$data[0],$occurrence));
	echo "</pre>";
	
	echo "<h3>GAIN RATIOS</h3>";
	echo "<pre>";
	print_r($this->gainRatios($data[1],$data[0],$occurrence));
	echo "</pre>";
	
	/*
	echo "<pre>";
	print_r(search_max_gain__create_collection($columns,$data[0],$data[1]));
	echo "</pre>";
	*/
	
	/*$occurrence = $this->count_occurrence($data[0],$data[1]);
	$this->entropy($data[1]);
	$this->information_for_each_column($data[0],$occurrence);
	$this->gain($data[1],$data[0],$occurrence);
	$this->gainRatios($data[1],$data[0],$occurrence);*/
}
/*
function decision_tree($collection) {
	$temp = array();
	$keys = array_keys($collection);

	for ($i = 0; $i < count($keys); $i++){
		body($collection[$keys[$i]]);

		$data = cut_decision_column($collection[$keys[$i]]);
		$gain = gain($data[1],$data[0],count_occurrence($data[0],$data[1]));

		if (count(array_unique($gain)) !== 1){
			$single_collection = search_max_gain__create_collection($collection[$keys[$i]],$data[0],$data[1])[0];
			array_push($temp,$single_collection);
		}
		else {
			echo "<h4>Brak podziału. Gain = ".max($gain)."</h4>";
		}
	}
	
	if (!empty($temp)){
		$collection = $temp;
	
		for ($j = 0; $j < count($collection); $j++) {
			decision_tree($collection[$j]);
		}	
	}
	
}
*/
//wczytanie danych z pliku oraz zmiana strukrury wyjsciowej tablicy z split by row na  split by column
function read_data_coumn_by_column($data){
	$columns = array();
	
	$split_by_line = explode("\n", file_get_contents($data));
	
	for ($i = 0; $i < count($split_by_line); $i++){
		$fromFile = explode(",",$split_by_line[$i]);
		foreach ($fromFile as $key => $sign) {
			$columns[$key][$i] = $sign;
			//$rows[$i][$key] = $sign;
		}
	}
	
	return $columns;
}

//oddzielenie kolumny decyzyjnej
function cut_decision_column($columns){
	$columns_after_cut = array();
	$last_column = array();
	
	for($i = 0; $i < count($columns); $i++){
		for ($j = 0; $j < count($columns[$i]); $j++){
			if($i < count($columns)-1){
				$columns_after_cut[$i] = $columns[$i];
			} else {
				array_push($last_column,trim($columns[$i][$j]));
			}
		}
	}
	
	return array($columns_after_cut,$last_column);
}

// Funkcja odpowiadająca za zliczenie wystapien w poszczegolnych kolumnach 
function count_occurrence($columns_after_cut,$last_column) {
	$res_occurrence = array();
	$counter = 0;
	for ($i = 0; $i < count($columns_after_cut); $i++){
		$res_occurrence[$i] = array();
		for($j = 0; $j < count($last_column); $j++){
			$key = $columns_after_cut[$i][$j]."=>".$last_column[$j];
			if ($res_occurrence[$i] != null && array_key_exists($key,$res_occurrence[$i])){
				$res_occurrence[$i][$key] = $res_occurrence[$i][$key] + 1;
			}
			else{
				$counter++;
				$res_occurrence[$i][$key] = $counter;
				$counter = 0;
			}
		}
	}
	
	return $res_occurrence;
}

// Funkcja odpowiadająca za wyliczenie entropii dla kolumny decyzyjnej
function entropy($column,$len=false){
	$result = 0;

	if ($len != null){
		$keys = $column;
		$len_current_column = $len;
	}
	else{
		$calc_each_elem = array_count_values($column);
		$keys = array_keys($calc_each_elem);
		$len_current_column = count($column);
	}

	for($i = 0; $i < count($keys); $i++){
		if ($len != null){
			$current_p = $keys[$i] / $len_current_column;
		}
		else {
			$current_p = $calc_each_elem[$keys[$i]] / $len_current_column;
		}
		$result += $current_p * $this->log2($current_p);
			
		if ($i == count($keys)-1){
			$result *= -1;
		}
	}
	
	return $result;
}

// Funkcja odpowiadająca za wyliczenia informacji atrybuty dla poszczegolnych kolumn
function information_for_each_column($col,$res){
	$res_info = array();
	for($i = 0; $i < count($col); $i++){
		
		//długość poszczególnej kolumny
		$len_each_column = count($col[$i]);
		//zlicz poszczególne elementy
		$calc_current_elem = array_count_values($col[$i]);
		$keys = array_keys($calc_current_elem);
		$info = 0;
		
		for ($j = 0; $j < count($keys); $j++){
			$temp = array();
			$key1 = array_keys($res[$i]);
			
			for ($k = 0; $k < count($key1); $k++){
				if ($keys[$j] == explode('=>', $key1[$k])[0]){
					array_push($temp,$res[$i][$key1[$k]]);
				}
			}
			
			$info += $calc_current_elem[$keys[$j]]/$len_each_column * $this->entropy($temp,$calc_current_elem[$keys[$j]]);
		}
		array_push($res_info,$info);
	}
	
	return $res_info;
}

// Funkcja odpowiadająca za wyliczenia wartosci GAIN dla poszczegolnych kolumn
function gain($l_column,$other_column,$res){
	$res_gain = array();

	$information = $this->information_for_each_column($other_column,$res);
	$entropy_lastColumn = $this->entropy($l_column);
	
	for ($i = 0; $i < count($information); $i++){
		$temp = $entropy_lastColumn - $information[$i];
		array_push($res_gain, $temp);
	}
	return $res_gain;
}


function search_max_gain__create_collection($columns,$columns_after_cut,$last_column){
	$collection = array();
	$gain = $this->gain($last_column,$columns_after_cut,$this->count_occurrence($columns_after_cut,$last_column));
	
	$id_max_column = array_search(max($gain),$gain);
	//$draw = "<h4>Dzielimy wg kolumny nr ".$id_max_column."&nbsp;|&nbsp;MAX GAIN to: ".max($gain);
	$root = array_unique($columns_after_cut[$id_max_column]);
	$keys = array_keys($root);
	
	//$this->r[] = $id_max_column;
	//$this->r[] = $root;
	//$this->r[] = max($gain);
	
	//$this->r[$id_max_column] = array_values($root);
	
	for ($i = 0; $i < count($keys); $i++){
		$index = $root[$keys[$i]];
		$collection[$index] = array();
		for ($j = 0; $j < count($columns); $j++){
			$collection[$index][$j] = array();
			for ($p = 0; $p < count($columns[$j]); $p++){
				if ($columns[$id_max_column][$p] == $root[$keys[$i]]){
					array_push($collection[$index][$j],$columns[$j][$p]);
				}
			}
		}
	}
	
	return array($collection,$id_max_column,$root);
}

// Funkcja odpowiadająca za wyliczenia wartosci GAIN RATIOS dla poszczegolnych kolumn
function gainRatios($last_column,$columns_after_cut,$res) {
	$res_gainRatios = array();
	
	$gain = $this->gain($last_column,$columns_after_cut,$res);
	$splitInfo = $this->splitInfo($columns_after_cut);
	
	for ($i = 0; $i < count($gain); $i++){
		if ($splitInfo[$i] != 0){
			$temp = $gain[$i] / $splitInfo[$i];
		}
		else {
			$temp = "SplitInfo = 0";
		}
		array_push($res_gainRatios, $temp);
	}
	
	return $res_gainRatios;
}

function splitInfo($queueColumns){
	$result = 0;
	$res_splitInfo = array();
	
	for ($i = 0; $i < count($queueColumns); $i++){
		$calc_current_elem = array_count_values($queueColumns[$i]);
		$keys = array_keys($calc_current_elem);
		for ($j = 0; $j < count($keys); $j++){
			$current_T = $calc_current_elem[$keys[$j]] / count($queueColumns[$i]);
			$result += -1*$current_T * $this->log2($current_T);
		}
		array_push($res_splitInfo, $result);
		$result = 0;
	}
	return $res_splitInfo;
}
	
function log2($x){
	return (log10($x) / log10(2));
}
}
?>