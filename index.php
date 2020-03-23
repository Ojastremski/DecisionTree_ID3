<?php 

// read data from a file and changing the output structure  
// split by row to split by column
function read_data_coumn_by_column($data){
	$columns = array();
	$split_by_line = explode("\n", file_get_contents($data));
	
	for ($i = 0; $i < count($split_by_line); $i++){
		$fromFile = explode(",",$split_by_line[$i]);
		foreach ($fromFile as $key => $sign) {
			$columns[$key][$i] = $sign;
		}
	}
	
	return $columns;
}

//separation of decision columns
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

// counting occurrences in individual columns 
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

// calculation of entropy for the decision column
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

// calculate information attributes for individual columns
function information_for_each_column($col,$res){
	$res_info = array();
	for($i = 0; $i < count($col); $i++){
		
		//the length of each column
		$len_each_column = count($col[$i]);
		//calculate individual elements
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

// calculating the GAIN value for individual columns
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

// calculating GAIN RATIOS values for individual columns
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

?>
