<?php
error_reporting(0);
// $time_start = microtime(true);
$profile = $_POST['profile'];




$instagram = new instagram_locations($profile);
$instagram->googleMapsResults();
$instagram->getFacebookLinks();
$instagram->normalize_states();
$instagram->getAggregate();



class instagram_locations
{
	public $full_name; //string
	public $posts; //string
	public $followed_by; //string
	public $following; //string
	public $name_array = array();
	public $name_from_instagram_array = array(); //Decides which variables to use to determine location
	public $lat_array = array();
	public $long_array = array();
	public $date_array = array();
	public $time_array = array();
	public $post_array = array();
	public $url_array = array();
	public $city_array = array();
	public $state_array = array();
	public $country_array = array();
	public $facebook_array = array();
	public $num_of_cities; //int
	public $num_of_states; //int
	public $num_of_countries; //int


	function __construct($profile)
	{
		$curl = curl_init();
			$url = 'https://api.instagram.com/v1/users/search?q=' . $profile . '&client_id=055e35eca183461fa96a84f9ed5ab891';
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
			$result = curl_exec($curl);
		curl_close($curl);

		//Pulls user ID out from data - Note this will pull out the first ID which is generally the exact match of the username entered.
		$result = json_decode($result);
		$user_id = $result->data[0]->id;

		//Get Name of Instagram Profile
		$this->full_name = $result->data[0]->full_name;

		//Curl Request to get info on user based on ID
		$curl = curl_init();
			$url = 'https://api.instagram.com/v1/users/' . $user_id . '?client_id=055e35eca183461fa96a84f9ed5ab891';
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
			$result = curl_exec($curl);
		curl_close($curl);
		$result = json_decode($result);

		//Set Counts Data
		$this->posts = $result->data->counts->media;
		$this->followed_by = $result->data->counts->followed_by;
		$this->following = $result->data->counts->follows;

		//Curl Request to get media based on user ID found previously
		$curl = curl_init();
			$url = 'https://api.instagram.com/v1/users/' . $user_id . '/media/recent/?client_id=055e35eca183461fa96a84f9ed5ab891';
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
			$result = curl_exec($curl);
		curl_close($curl);

		$result = json_decode($result);

		$this->getInstagramNames($result);

		if(isset($result->pagination->next_url)) //Checks to see if there is more images than the initial pull
		{
			$url = $result->pagination->next_url; //Url holds the instagram API url call for the next batch of images
			$pagination = true;
		}
		else
		{
			$pagination = false;
		}

		while ($pagination == true) //Executes while there is still images to pull
		{
			//Pulls images based on new URL
			$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
				$result = curl_exec($curl);
			curl_close($curl);
			$result = json_decode($result);

			$this->getInstagramNames($result);
			if(!isset($result->pagination->next_url)) //If there is no more images there will be no url here
			{
				$pagination = false;
			}
			else
			{
			$url = $result->pagination->next_url;
			}
		}
	}

	private function getInstagramNames($result)
	{
		$i=0;
		$array_count = sizeof($result->data);
		while($i < $array_count)
		{
			if(isset($result->data[$i]->location->name) && $result->data[$i]->location->name != NULL )
			{
				array_push($this->name_array, $result->data[$i]->location->name);
				array_push($this->name_from_instagram_array, TRUE);
				$this->setInstagramVars($result, $i, TRUE); //Sets data associated with instagram API
			}
			else
			 {

			 	array_push($this->name_from_instagram_array, FALSE);
			 	$this->setInstagramVars($result, $i, FALSE); //Sets data associated with instagram API
			 }
			$i++;
		}
	}

	private function setInstagramVars($result, $i, $getName)
	{
		if(isset($result->data[$i]->created_time)) //Date
		{
			array_push($this->date_array, date('M j, Y', $result->data[$i]->created_time));
		}
		else
		{
			array_push($this->date_array, NULL);
		}

		if(isset($result->data[$i]->created_time)) //Post Time
		{
			array_push($this->time_array, date('g:i', $result->data[$i]->created_time));
		}
		else
		{
			array_push($this->time_array, NULL);
		}

		if(isset($result->data[$i]->caption->text)) //Caption
		{
			array_push($this->post_array, $result->data[$i]->caption->text);
		}
		else
		{
			array_push($this->post_array, NULL);
		}

		if(isset($result->data[$i]->link)) //URL
		{
			array_push($this->url_array, $result->data[$i]->link);
		}
		else
		{
			array_push($this->url_array, NULL);
		}

		if(isset($result->data[$i]->location->latitude)) //Latitude
		{
			array_push($this->lat_array, $result->data[$i]->location->latitude);
		}
		else
		{
			array_push($this->lat_array, NULL);
		}

		if(isset($result->data[$i]->location->longitude))
		{
			array_push($this->long_array, $result->data[$i]->location->longitude);
		}
		else
		{
			array_push($this->long_array, NULL);
		}

		if($getName != True)
		{
			$curl = curl_init();
			$url = 'https://api.instagram.com/v1/locations/search?lat=' . $this->lat_array[$i] . '&lng=' . $this->long_array[$i] . '&client_id=055e35eca183461fa96a84f9ed5ab891';
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
			$result = curl_exec($curl);
			curl_close($curl);
			$result = json_decode($result);
			if(isset($result->data[0]->name))
			{
				array_push($this->name_array, $result->data[0]->name);
			}
			else
			{
				array_push($this->name_array, NULL);
			}
		}


	}

	public function googleMapsResults()
	{
		$i = 0;
		$array_count = count($this->name_array);
			while($i < $array_count)
			{
				if($this->name_from_instagram_array[$i] == true) // If a location is tagged in instagram
				{
					$final = preg_replace('#[ -]+#', '+', $this->name_array[$i]);
					$curl = curl_init();
						$url = 'https://maps.googleapis.com/maps/api/place/textsearch/json?query=' . $final . 'i&key=AIzaSyBkZSFpHFVevzCUzdofNnH2nWyOo0ix5Jk';
						curl_setopt($curl, CURLOPT_URL, $url);
						curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
						$result = curl_exec($curl);
					curl_close($curl);
					$result = json_decode($result);

					if(isset($result->results[0]->formatted_address))
					{
						$address = ($result->results[0]->formatted_address);
					}
					else
					{
						$address = NULL;
					}


					if($address != NULL)
					{
						$address_array = explode(',', $address);

						//If there is no address returned then the array will only be 4 elements and need to be set differently
						if(count($address_array) == 5)
						{
							$this->city_array[$i] = $address_array[2];
							$this->state_array[$i] = $address_array[3];
							$this->country_array[$i] = $address_array[4];
						}
						elseif(count($address_array) == 4)
						{
							$this->city_array[$i] = $address_array[1];
							$this->state_array[$i] = $address_array[2];
							$this->country_array[$i] = $address_array[3];
						}
						else // If only 3 are returned it's likely a name and address is missing so assign arrays accordingly
						{
							$this->city_array[$i] = $address_array[0];
							$this->state_array[$i] = $address_array[1];
							$this->country_array[$i] = $address_array[2];
						}
					}
					else
					{
						$this->city_array[$i] = NULL;
						$this->state_array[$i] = NULL;
						$this->country_array[$i] = NULL;
					}
				}


				else //If no location is tagged fallback to use coordinates to find location
				{
					if($this->lat_array[$i] != NULL && $this->long_array[$i] !=NULL)
					{
						$curl = curl_init();
						  $url = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=' . $this->lat_array[$i] . ',' . $this->long_array[$i] . '&radius=10&key=AIzaSyBkZSFpHFVevzCUzdofNnH2nWyOo0ix5Jk';
						  curl_setopt($curl, CURLOPT_URL, $url);
						  curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
						  $result = curl_exec($curl);
						curl_close($curl);

						$result = json_decode($result);
						if(isset($result->results[0]->place_id))
						{
							$id = $result->results[0]->place_id;
						}
						else
						{
							$id=NULL;
						}

						//CURL request to query additional information based on id
						$curl = curl_init();
						  $url = 'https://maps.googleapis.com/maps/api/place/details/json?placeid=' . $id . '&key=AIzaSyBkZSFpHFVevzCUzdofNnH2nWyOo0ix5Jk';
						  curl_setopt($curl, CURLOPT_URL, $url);
						  curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
						  $result = curl_exec($curl);
						curl_close($curl);

						$result = json_decode($result);
							//City Loop
							$x = 0;

						if(isset($result->result->address_components))
						{
							$in_array_count = sizeof($result->result->address_components);
							while( $x < $in_array_count)
							{
								if($result->result->address_components[$x]->types[0] == "locality")
								{
									if(isset($result->result->address_components[$x]->long_name))
									{
										$this->city_array[$i] =  $result->result->address_components[$x]->long_name;
									}
									else
									{
										$this->city_array[$i] =  NULL;
									}
								}
								$x++;
							}

							//country loop
							$x = 0;
							$in_array_count = sizeof($result->result->address_components);
							while( $x < $in_array_count)
							{
								if($result->result->address_components[$x]->types[0] == "country")
								{
									if(isset($result->result->address_components[$x]->long_name))
									{
										$this->country_array[$i] = $result->result->address_components[$x]->long_name;
									}
									else
									{
										$this->country_array[$i] = NULL;
									}
								}
								$x++;
							}

							//State Loop
							$x = 0;
							$in_array_count = sizeof($result->result->address_components);
							while( $x < $in_array_count )
							{
								if($result->result->address_components[$x]->types[0] == "administrative_area_level_1")
								{
									if(isset($result->result->address_components[$x]->long_name))
									{
										$this->state_array[$i] = $result->result->address_components[$x]->long_name;
									}
									else
									{
										$this->state_array[$i] = NULL;
									}
								}
								$x++;
							}
						}
						else
						{
							$this->state_array[$i] = NULL;
							$this->country_array[$i] = NULL;
							$this->city_array[$i] =  NULL;
						}
					}
					else
					{
						$this->state_array[$i] = NULL;
						$this->country_array[$i] = NULL;
						$this->city_array[$i] =  NULL;
					}
					}
				$i++;
			}

	}

	public function getFacebookLinks()
	{
		$i=0;
		while($i < count($this->name_array))
		{
			if(isset($this->name_array[$i]))
			{
				$file = @file_get_contents('http://graph.facebook.com/' . preg_replace('/\s+/', '', $this->name_array[$i]));
				if(strpos($file,'id') == true)
				{
					$file = '<a href = https://www.facebook.com/' . preg_replace('/\s+/', '', $this->name_array[$i]) . '>' .$this->name_array[$i] . '</a>' ;
				}
				else
				{
					$file = "N/A";
				}
			}
			else
			{
				$file = "N/A";
			}
			array_push($this->facebook_array, $file);
			$i++;
		}
	}

	public function getAggregate() //Gets Values for unique enteries for cities, states, and countries
	{
		$this->num_of_countries = count(array_unique(array_filter($this->country_array))); //Filter out null, get unique elements, and count result
		$this->num_of_cities = count(array_unique(array_filter($this->city_array)));
		$this->num_of_states = count(array_unique(array_filter($this->state_array)));
		return;
	}

	public function normalize_states()
	{
		$j=0;
		while($j < sizeof($this->state_array))
		{
			$this->state_array[$j] = trim(str_replace(range(0,9),'',$this->state_array[$j]));
			//Change this array so it's re-defined in each loop
			$states_full_normalized = array(
				'Alabama'=>'AL',
				'Alaska'=>'AK',
				'Arizona'=>'AZ',
				'Arkansas'=>'AR',
				'California'=>'CA',
				'Colorado'=>'CO',
				'Connecticut'=>'CT',
				'Delaware'=>'DE',
				'District of Columbia'=>'DC',
				'Florida'=>'FL',
				'Georgia'=>'GA',
				'Hawaii'=>'HI',
				'Idaho'=>'ID',
				'Illinois'=>'IL',
				'Indiana'=>'IN',
				'Iowa'=>'IA',
				'Kansas'=>'KS',
				'Kentucky'=>'KY',
				'Louisiana'=>'LA',
				'Maine'=>'ME',
				'Maryland'=>'MD',
				'Massachusetts'=>'MA',
				'Michigan'=>'MI',
				'Minnesota'=>'MN',
				'Mississippi'=>'MS',
				'Missouri'=>'MO',
				'Montana'=>'MT',
				'Nebraska'=>'NE',
				'Nevada'=>'NV',
				'New Hampshire'=>'NH',
				'New Jersey'=>'NJ',
				'New Mexico'=>'NM',
				'New York'=>'NY',
				'North Carolina'=>'NC',
				'North Dakota'=>'ND',
				'Ohio'=>'OH',
				'Oklahoma'=>'OK',
				'Oregon'=>'OR',
				'Pennsylvania'=>'PA',
				'Rhode Island'=>'RI',
				'South Carolina'=>'SC',
				'South Dakota'=>'SD',
				'Tennessee'=>'TN',
				'Texas'=>'TX',
				'Utah'=>'UT',
				'Vermont'=>'VT',
				'Virginia'=>'VA',
				'Washington'=>'WA',
				'West Virginia'=>'WV',
				'Wisconsin'=>'WI',
				'Wyoming'=>'WY',
				);

				$states_short_normalized = array(
					'AL'=>'Alabama',
				    'AK'=>'Alaska',
				    'AZ'=>'Arizona',
				    'AR'=>'Arkansas',
				    'CA'=>'California',
				    'CO'=>'Colorado',
				    'CT'=>'Connecticut',
				    'DE'=>'Delaware',
				    'DC'=>'District of Columbia',
				    'FL'=>'Florida',
				    'GA'=>'Georgia',
				    'HI'=>'Hawaii',
				    'ID'=>'Idaho',
				    'IL'=>'Illinois',
				    'IN'=>'Indiana',
				    'IA'=>'Iowa',
				    'KS'=>'Kansas',
				    'KY'=>'Kentucky',
				    'LA'=>'Louisiana',
				    'ME'=>'Maine',
				    'MD'=>'Maryland',
				    'MA'=>'Massachusetts',
				    'MI'=>'Michigan',
				    'MN'=>'Minnesota',
				    'MS'=>'Mississippi',
				    'MO'=>'Missouri',
				    'MT'=>'Montana',
				    'NE'=>'Nebraska',
				    'NV'=>'Nevada',
				    'NH'=>'New Hampshire',
				    'NJ'=>'New Jersey',
				    'NM'=>'New Mexico',
				    'NY'=>'New York',
				    'NC'=>'North Carolina',
				    'ND'=>'North Dakota',
				    'OH'=>'Ohio',
				    'OK'=>'Oklahoma',
				    'OR'=>'Oregon',
				    'PA'=>'Pennsylvania',
				    'RI'=>'Rhode Island',
				    'SC'=>'South Carolina',
				    'SD'=>'South Dakota',
				    'TN'=>'Tennessee',
				    'TX'=>'Texas',
				    'UT'=>'Utah',
				    'VT'=>'Vermont',
				    'VA'=>'Virginia',
				    'WA'=>'Washington',
				    'WV'=>'West Virginia',
				    'WI'=>'Wisconsin',
				    'WY'=>'Wyoming'
				);

			if(!isset($states_full_normalized[$this->state_array[$j]]))
			{
				if(!isset($states_short_normalized[$this->state_array[$j]]))
				{
					$this->state_array[$j] = NULL;
				}
				else
				{
					$this->state_array[$j] = $states_short_normalized[$this->state_array[$j]];
				}
			}

			$this->normalize_country($j);
			$j++;
		}
	}
	private function normalize_country($j)
	{
		if(isset($this->country_array[$j]))
		{
			if($this->country_array[$j] == 'USA' || $this->country_array[$j] == 'US' || $this->country_array[$j] == 'usa' || $this->country_array[$j] == 'us' || $this->country_array[$j] == ' USA' || $this->country_array[$j] == ' United States')
			{
				$this->country_array[$j] = "United States";
			}
		}
	}
}



?>



<html>
<link href="css/bootstrap.min.css" rel="stylesheet">

<style>
ul
{
	list-style: none;
}
ul li
{
	display: inline;
	padding-left: 5px;
	padding-right: 5px;
}
tr
{
	border: 1px solid black;
}
td
{
	border: 1px solid black;
}
th
{
	border: 1px solid black;
}
.row
{
	padding-left: 10px;
	padding-right: 10px;
}
</style>
<title>Instagram API Test </title>
<body>

	<div class="counts_wrap">
		<ul>
			<li>Name: <?php echo($instagram->full_name); ?></li>
			<li>Posts: <?php echo($instagram->posts); ?></li>
			<li>Followed: <?php echo($instagram->followed_by); ?></li>
			<li>Following: <?php echo($instagram->following); ?></li>
		</ul>
	</div>
	<table class="table table-striped">
  <tr>
    <th>Date</th>
    <th>Time</th>
    <th>Post</th>
    <th>URL</th>
    <th>Country</th>
    <th>State</th>
    <th>City</th>
    <th>Name</th>
    <th>Facebook Link</th>
  </tr>
	<?php
	$i = 0;
		while($i < sizeof($instagram->date_array))
		{
			echo('<tr>
					<td>' . $instagram->date_array[$i] .
					'</td>
					<td>' . $instagram->time_array[$i] .
					'</td>
					<td>' . $instagram->post_array[$i] .
					'</td>
					<td>' . $instagram->url_array[$i] .
					'</td>
					<td>' . $instagram->country_array[$i] .
					'</td>
					<td>' . $instagram->state_array[$i] .
					'</td>
					<td>' . $instagram->city_array[$i] .
					'</td>
					<td>' . $instagram->name_array[$i] .
					'</td>
					<td>' . $instagram->facebook_array[$i] .
					'</td>
					</tr>');
			$i++;
		}
	?>
</table>

<div class="row">

	<div class="col-sm-4">
		<!-- city tabel -->
		<?php echo($instagram->num_of_cities . " Citie(s)");?>
		<table class="table small_table table-striped">
			<tr>
				<th>Date</th>
				<th>City</th>
			</tr>
			<?php
			$i = 0;
				while($i < sizeof($instagram->date_array))
				{
					if($instagram->city_array[$i] != NULL)
					{
						echo('<tr>
								<td>' . $instagram->date_array[$i] .
								'<td>' . $instagram->city_array[$i] .
								'</tr>');
					}
					$i++;
				}
			?>
		</table>
	</div>

	<div class="col-sm-4">
		<!-- state table -->
		<?php echo($instagram->num_of_states . " State(s)");?>
		<table class="table small_table table-striped">
			<tr>
				<th>Date</th>
				<th>State</th>
			</tr>
			<?php
			$i = 0;
				while($i < sizeof($instagram->date_array))
				{
					if($instagram->state_array[$i] != NULL)
					{
						echo('<tr>
								<td>' . $instagram->date_array[$i] .
								'<td>' . $instagram->state_array[$i] .
								'</tr>');
					}
					$i++;
				}
			?>
		</table>
			</div>


	<div class="col-sm-4">
		<!-- country tabel  -->
		<?php echo($instagram->num_of_countries . " Countrie(s)");?>
		<table class="table small_table table-striped">
			<tr>
				<th>Date</th>
				<th>Country</th>
			</tr>
			<?php
			$i = 0;
				while($i < sizeof($instagram->date_array))
				{
					if($instagram->country_array[$i] != NULL)
					{
						echo('<tr>
								<td>' . $instagram->date_array[$i] .
								'<td>' . $instagram->country_array[$i] .
								'</tr>');
					}
					$i++;
				}
			?>
		</table>
	</div>


</div>
<?php
// $time_end = microtime(true);
// $time = $time_end - $time_start;
// var_dump($time);

?>

</body>
</html>
