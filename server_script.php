<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") { //peticion post, viene de arduino
  $voltaje = floatval( $_POST['v'] );
  $estado = intval( $_POST['e'] );
  $temp = floatval( $_POST['t'] );
  if($temp>25.0){
    sendData("temp_riego",$temp);
  }
  $sleep=-1;
  $estadoNuevo=0;
  updateDB($voltaje,$estado);//pedimos a la db valores a devolver de sleep y estado y guardamos lo que manda el arduino
  if ($voltaje < 8.0) {
    sendData("battery_low",$voltaje);
	  echo "0|".$sleep;
  } else {
	  echo $estadoNuevo."|".$sleep;
  }
} else {//peticion GET, lectura de la bd y control de estado/sleep
	try {
		$unix=False;//control sobre formato de fecha
		if(isset($_GET["unix"])) {
		   $unix=True;
		}
		$mng = new MongoDB\Driver\Manager("mongodb://blabla");
		if(isset($_GET["estado"])) { //control del estado del arduino
			if (null !== ($estado = filter_input(INPUT_GET, 'estado', FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE))) {
				$bulkEstado = new MongoDB\Driver\BulkWrite;
				$bulkEstado->update(['_id' => 0], ['$set' => ['state' => $estado]]);
				$mng->executeBulkWrite('db.state', $bulkEstado);
				echo "Estado cambiado a ".$estado;
				return;
			}
		}
		if(isset($_GET["sleep"])) { //control de sleep del arduino
			if (null !== ($sleep = filter_input(INPUT_GET, 'sleep', FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE))) {
				$bulkEstado = new MongoDB\Driver\BulkWrite;
				$bulkEstado->update(['_id' => 1], ['$set' => ['sleep' => $sleep]]);
				$mng->executeBulkWrite('db.state', $bulkEstado);
				echo "Sleep cambiado a ".$sleep;
				return;
			}
		}
		$query = new MongoDB\Driver\Query([]); //si no ha habido controles, leemos la informacion que ha subido el arduino.

		$rows = $mng->executeQuery("db.history", $query);
		header("Content-Type: text/plain; charset=utf-8");
		header("Pragma: no-cache");
		header("Expires: 0");
		echo "Time;Voltaje;Estado\n";
		foreach ($rows as $row) {
		  if(!$unix){
			  $datetime = $row->time->toDateTime();
			  $date = $datetime->format('Y/m/d-H:i:s');
			  echo $date .";$row->voltage;$row->state\n";
		  }else{
			  echo "$row->time;$row->voltage;$row->state\n";
		  }
		}
      
  } catch (MongoDB\Driver\Exception\Exception $e) {  }
}
function updateDB($v, $e)
{	
	global $sleep, $estadoNuevo; 
	try {
		$mng = new MongoDB\Driver\Manager("mongodb://blabla");
		$filter = [ '_id' => 0 ]; 
		$queryEstado = new MongoDB\Driver\Query($filter);     
		$estadoNuevo = $mng->executeQuery("db.state", $queryEstado)->toArray()[0]->state; //obtenemos el estado de la bd
		
		$querySleep = new MongoDB\Driver\Query([ '_id' => 1 ]);   
		$sleep = $mng->executeQuery("db.state", $querySleep)->toArray()[0]->sleep; //obtenemos el sleep de la bd  
		
		$bulk = new MongoDB\Driver\BulkWrite;
		$doc = ['_id' => new MongoDB\BSON\ObjectID, 'time' => new MongoDB\BSON\UTCDatetime, 'voltage' => $v, 'state' => $e];
		$bulk->insert($doc);			
		$mng->executeBulkWrite('db.history', $bulk); //escribimos los nuevos datos en la bd
			
	} catch (MongoDB\Driver\Exception\Exception $e) {}
}
function sendData($t,$d){
  $url = 'myurl';
  $options = array(
      'http' => array(
          'header'  => "Content-type: application/json\r\n",
          'method'  => 'POST',
          'content' => '{ "blabla" : "'.$d.'"}'
      )
  );
  $context  = stream_context_create($options);
  $result = file_get_contents($url, false, $context);
}
?>
