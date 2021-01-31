<!DOCTYPE html>
<html>
<head> 
<meta charset="UTF-8"> 
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CasaDeYam</title> 
<link rel="stylesheet" type="text/css" href="index.css">
<?php
//Hongxin Zhuang, 201377535, Final Year Project
//printing the restaurant information
function printRestaurant() {
	echo "<ul><li><p class=\"A\">Name: </p><p class=\"B\" id=\"2B\"></p></li></ul>\n";
	echo "<ul><li><p class=\"A\">Price level: </p><p class=\"B\" id=\"3B\"></p></li></ul>\n";
	echo "<ul><li><p class=\"A\">Rating (out of 5): </p><p class=\"B\" id=\"5B\"></p></li></ul>\n";
	echo "<ul><li><p class=\"A\">Address: </p><p class=\"B\" id=\"6B\"></p></li></ul>\n";
	echo "<ul><li><p class=\"A\">Link to Google Maps: </p><p class=\"B\" id=\"7B\"><button class = \"gogo\" onclick=window.open(url)>Go!</button></p></li></ul>\n";
}

//printing the login form
function printLogin() {
	if(isset($_POST['registerUserCode'])) {
		echo "<script>document.getElementById(\"1B\").innerHTML = \"Register successfully\";</script>";
	}
	echo "<form method=\"post\">\n";
	echo "<ul><li><p class=\"A\">Please enter your user code to login or register</p><p><input type=\"text\" name=\"userCode\" value=\"".$_POST['registerUserCode']."\"/></p></li></ul>\n";
	echo "<div class=\"gogoDiv\"><input class = \"gogo\" type=\"submit\" value=\"Register/Login\" /></div></br>\n";
}

//printing the registration form
function printRegister() {
	if(isset($GLOBALS['priceTrue']) AND (!$GLOBALS['priceTrue'] OR !$GLOBALS['rateTrue'])) {
		echo "<script>document.getElementById(\"1B\").innerHTML = \"Price level or rateing value is wrong, please try again\";</script>";
	}
	if(isset($_POST["registerUserCode"])) $_POST["userCode"] = $_POST["registerUserCode"];
	echo "<form name=\"register\" method=\"post\">\n";
	echo "<ul><li><p class=\"A\">User code</p><p><input type=\"text\" name=\"registerUserCode\" value=\"".$_POST['userCode']."\" /></p></li></ul>\n";
	echo "<ul><li><p class=\"A\">Please enter the keyword you would like to be associated with to generate the restaurant</p><p><input type=\"text\" name=\"keyword\" /></p></li></ul>\n";
	echo "<ul><li><p class=\"A\">Please enter the ideal price level of the restaurant (1-5)</p><p><input type=\"text\" name=\"price\" /></p></li></ul>\n";
	echo "<ul><li><p class=\"A\">Please enter the minimum rating of the restaurant (0.0-5.0)</p><p><input type=\"text\" name=\"rate\" /></p></li></ul>\n";
	echo "<div class=\"gogoDiv\"><input class = \"gogo\" type=\"submit\" value=\"Register\" /></div></br>\n";
}

//pringting loading...
function printLoading() {
	echo "<script>document.getElementById(\"1B\").innerHTML = \"Loading\";</script>";
}

//send a request to search nearby restaurants via Google Maps API, with user preferences from the database
function getRestaurant($sqlresult) {
	while($row = $sqlresult->fetch_assoc()) {
        $code = $row["code"];
        $keyword = $row["keyword"];
        $price = $row["price"];
        $rate = $row["minRate"];
    }
    if($price == '0') {
    	$minprice = '0';
    	$maxprice = '1';
    } else if($price == '5') {
    	$minprice = '4';
    	$maxprice = '5';
    } else {
    	$minprice = $price - 1;
    	$maxprice = $price + 1;
    }
    $notUsed = array("type","geometry","icon","reference","id","opening_hours","photo","user_ratings_total","scope","plus_code","place_id");
    $searchString = 'https://maps.googleapis.com/maps/api/place/nearbysearch/xml?location='. $_GET["location"] .'&radius=5000&type=restaurant&keyword='. $keyword .'&language=en&minprice='. $minprice .'&maxprice='. $maxprice .'&opennow=true&key=#Enter your Google Maps API key here#';
    $xmlIterator = new SimpleXMLIterator($searchString,null,true);
    $count = 0;
    for( $xmlIterator->rewind(); $xmlIterator->valid(); $xmlIterator->next() ) {
    	foreach($xmlIterator->getChildren() as $name => $data) {
    		if(!in_array($name, $notUsed)) $results[$count][$name] = (string)$data;
        }
        $count++;
    }
    $i = 0;
    while($i < count($results) - 1) {
    	if($results[$i]["rating"] < $minRate) {
    		array_splice($results, $i + 1, 1);
		} else {
			$i++;
		}
	}
    if(count($results) == 0) unset($results);
    return $results;
}

if(isset($_GET["userCode"])) {
	$_POST["userCode"] = $_GET["userCode"];
}

//checking if a user code is exist
if(isset($_POST['userCode'])) {
	$servername = "localhost";
	$username = "sa";
	$password = "19980709";
 
	$conn = mysqli_connect($servername, $username, $password);
	if (!$conn) {
    	die("Connection failed: " . mysqli_connect_error());
	}
	$sql = "SELECT * FROM yamgoUser.Sheet1 WHERE code = '".$_POST['userCode']."';";
	$result = $conn->query($sql);
 
	if ($result->num_rows > 0) {
    	$results = getRestaurant($result);
    	if(!isset($results)) $GLOBALS["noResult"] = true;
	} else {
		$GLOBALS["toRegister"] = 1;
	}
	mysqli_close($conn);
}

//check user preferences and insert them into the database
if(isset($_POST['registerUserCode'])) {
	$GLOBALS["priceTrue"] = preg_match("/^[1-5]$/", $_POST['price']);
	$GLOBALS["rateTrue"] = preg_match("/^[0-5]\.[0-9]$/", $_POST['rate']);

	if($GLOBALS["priceTrue"] AND $GLOBALS["rateTrue"]) {
		$servername = "localhost";
		$username = "sa";
		$password = "19980709";
		$conn = mysqli_connect($servername, $username, $password);
		if (!$conn) {
    		die("Connection failed: " . mysqli_connect_error());
		}

		$sql = "INSERT INTO yamgoUser.Sheet1 VALUES ('".$_POST['registerUserCode']."', '".$_POST['keyword']."', '".$_POST['price']."', '".$_POST['rate']."')";
 
		if ($conn->query($sql) === FALSE) {
    		echo "Error: " . $sql . "<br>" . $conn->error;
		}
 
		$conn->close();
	} else {
		$GLOBALS["toRegister"] = 1;
	}
}
?>
<script type="text/javascript">
//function for getting user location
function geoFindMe() {
	var output = document.getElementById("out");
  	if (!navigator.geolocation){
    	output.innerHTML = "<p>Your browser does not support getting the location</p>";
    	return;
  	}

  	function success(position) {
    	var latitude  = position.coords.latitude;
    	var longitude = position.coords.longitude;
    	var result = latitude + "," + longitude;
    	window.location='index.php?location='+result+'&userCode='+userCode;
  	};

  	function error() {
    	output.innerHTML = "Loading location failed, please refresh and try again";
  	};

  	navigator.geolocation.getCurrentPosition(success, error);
}

function firstLoad() {
	data = eval(<?php echo json_encode($results);?>);
	counting = 1;
    getNew();
}

function nextOne() {
	counting++;
	getNew();
}

//get a new restaurant detail from the array
function getNew() {
	if(Object.keys(data).length >= counting) {
		name = data[counting]["name"];
		rmb = data[counting]["price_level"];
		rating = data[counting]["rating"];
		address = data[counting]["vicinity"];
		message = "Restaurant generated successfully";
		document.getElementById("1B").innerHTML = message;
		document.getElementById("2B").innerHTML = name;
 		document.getElementById("3B").innerHTML = rmb;
 		document.getElementById("5B").innerHTML = rating;
 		document.getElementById("6B").innerHTML = address;
 		url = "https://www.google.com/maps/search/?api=1&query=" + name;
 		document.getElementById('toGogo').hidden = false;
	} else {
		message = "No more result could be generated";
		document.getElementById("1B").innerHTML = message;
	}
}

	var data, counting;

</script>
</head>
<body>
<div id="first">
    <image class = "yamGo" src = "yamGo.jpg">
</div>
<hr style=" height:2px;border:none;border-top:2px dotted #C0C0C0;" />
<div id="out"></div>
<div id="sencond">
    <div class="canDiv"><image class = "can" src = "can.jpg"></div>
    </br>
    <div class="gogoDiv" id="toGogo" hidden="true"><input class = "gogo" type=button value=Next onclick="nextOne()"></br></div>
    <div><h3 align="center" id = 1B></h3></div>
    </br>

<div class="tableDiv" id="table" hidden="true">
<?php
    //check which form to print in the center
	if((isset($_GET["userCode"]) OR isset($_POST['userCode'])) AND (!isset($GLOBALS["toRegister"]) OR $GLOBALS["toRegister"] == 0)) {
		if(!isset($_GET["location"])){
			printLoading();
			echo "<script type=\"text/javascript\">window.userCode=\"". $_POST['userCode'] ."\";\ngeoFindMe();</script>";
		} else {
			$_POST["userCode"] = $_GET["userCode"];
			if($GLOBALS["noResult"]) {
				echo "<script type=\"text/javascript\">\n";
    			echo "message = \"No restaurant is open now basing on your preference, please try at another time or modify your preference\";\n";
				echo "document.getElementById(\"1B\").innerHTML = message;\n";
    			echo "</script>\n";
			} else {
				printRestaurant();
				echo "<script type=\"text/javascript\">\n";
				echo "document.getElementById(\"table\").hidden = false;\n";
    			echo "firstLoad();\n";
    			echo "</script>\n";
			}
		}
	} else if($GLOBALS["toRegister"] == 1){
		echo "<script type=\"text/javascript\">\n";
		echo "document.getElementById(\"table\").hidden = false;\n";
    	echo "</script>\n";
		printRegister();
		$GLOBALS["toRegister"] = 0;
	} else {
		echo "<script type=\"text/javascript\">\n";
		echo "document.getElementById(\"table\").hidden = false;\n";
    	echo "</script>\n";
		printLogin();
	}
?>
</div>
 </br></br></br>
</div>
<hr style=" height:2px;border:none;border-top:2px dotted #C0C0C0;" />
<div id="third">
 </br></br>
 <image class = "casadeyam" src = "casadeyam.jpg">
</div>
</body>
</html>