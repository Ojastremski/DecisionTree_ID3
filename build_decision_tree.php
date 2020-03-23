<?php 

require("index.php");

$columns = read_data_coumn_by_column("test2.txt");

$data = cut_decision_column($columns);
echo search_max_gain__create_collection($columns,$data[0],$data[1])[1];

//first iteration
echo body($columns);
			
$collection = search_max_gain__create_collection($columns,$data[0],$data[1])[0];
$counter = 0;
//next iterations
echo decision_tree($collection,$counter);

function body ($columns) {	
	$data = cut_decision_column($columns);
	
	echo "<pre>";
	$occurrence = count_occurrence($data[0],$data[1]);
	print_r($occurrence);
	echo "</pre>";
	
	echo "<h3>Entropia rozkładu prawdopodobieństwa</h3>";
	print_r(entropy($data[1]));
	
	echo "<h3>Informacja atrybutu</h3>";
	echo "<pre>";
	print_r(information_for_each_column($data[0],$occurrence));
	echo "</pre>";
	
	echo "<h3>GAIN(X,T)</h3>";
	echo "<pre>";
	$gain = gain($data[1],$data[0],$occurrence);
	print_r(gain($data[1],$data[0],$occurrence));
	echo "</pre>";
	
	echo "<h3>GAIN RATIOS</h3>";
	echo "<pre>";
	print_r(gainRatios($data[1],$data[0],$occurrence));
	echo "</pre>";
}

function decision_tree($collection,&$counter) {
	$temp = array();
				
	$keys = array_keys($collection);
	for ($i = 0; $i < count($keys); $i++){					
		$data = cut_decision_column($collection[$keys[$i]]);
		$gain = gain($data[1],$data[0],count_occurrence($data[0],$data[1]));
					
		echo "<div class='card'>";
		echo "<div class='card-header' id='heading".$counter."'>";
		echo "<h5 class='mb-0'>";
		echo "<button class='btn btn-link collapsed' type='button' data-toggle='collapse' data-target='#collapse".$counter."' aria-expanded='false' aria-controls='collapse".$counter."'>";
		if (count(array_unique($gain)) !== 1){
			$single_collection = search_max_gain__create_collection($collection[$keys[$i]],$data[0],$data[1])[0];
			array_push($temp,$single_collection);
			echo search_max_gain__create_collection($collection[$keys[$i]],$data[0],$data[1])[1]."&nbsp;|&nbsp;Unikalna wartość ".$keys[$i]."</h4>";
		}
		else {
			echo "<h4>Brak podziału | GAIN = 0</h4>";
		}
		echo "</button>";
		echo "</h5>";
		echo "</div>";
		echo "<div id='collapse".$counter."' class='collapse' aria-labelledby='heading".$counter."' data-parent='#accordionExample'>";
		echo "<div class='card-body'>";
		body($collection[$keys[$i]]);
		echo "</div>";
		echo "</div>";
		echo "</div>";
								
		$counter++;
	}
	if (!empty($temp)){
		$collection = $temp;
		for ($j = 0; $j < count($collection); $j++) {
			decision_tree($collection[$j],$counter);
		}	
	}
}
?>