<?php
// error_reporting(0);
$profile = 'am_212_murer';


$instagram = new instagram_locations($profile);
$instagram = json_encode($instagram);

echo('
    <script>var instagram ='.$instagram.'</script>
');




class instagram_locations
{
	public $full_name; //string
	public $posts; //string
	public $photo_array = array();
	public $lat_array = array();
	public $long_array = array();



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

		//Curl Request to get media based on user ID found previously
		$curl = curl_init();
			$url = 'https://api.instagram.com/v1/users/' . $user_id . '/media/recent/?client_id=055e35eca183461fa96a84f9ed5ab891';
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
			$result = curl_exec($curl);
		curl_close($curl);

		$result = json_decode($result);

        foreach($result->data as $post)
        {
            if($post->location != NULL)
            {
                array_push($this->photo_array, $post->images->standard_resolution->url);
                array_push($this->lat_array,$post->location->latitude);
                array_push($this->long_array,$post->location->longitude);
            }
        }
        return;

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


}
?>

<script>
    console.log(instagram.photo_array[0]);
</script>

<html>
  <body>


    <div style="height: 400px; width: 600px; background-color: gray;" id="map">
      Map here
    </div>


  </body>
</html>

<script src="https://maps.googleapis.com/maps/api/js?v=3"></script>
<script>

  (function(){
    initMap();
    function initMap() {

        var stylesArray = [
    {
        "featureType": "landscape.man_made",
        "elementType": "geometry",
        "stylers": [
            {
                "color": "#f7f1df"
            }
        ]
    },
    {
        "featureType": "landscape.natural",
        "elementType": "geometry",
        "stylers": [
            {
                "color": "#d0e3b4"
            }
        ]
    },
    {
        "featureType": "landscape.natural.terrain",
        "elementType": "geometry",
        "stylers": [
            {
                "visibility": "off"
            }
        ]
    },
    {
        "featureType": "poi",
        "elementType": "labels",
        "stylers": [
            {
                "visibility": "off"
            }
        ]
    },
    {
        "featureType": "poi.business",
        "elementType": "all",
        "stylers": [
            {
                "visibility": "off"
            }
        ]
    },
    {
        "featureType": "poi.medical",
        "elementType": "geometry",
        "stylers": [
            {
                "color": "#fbd3da"
            }
        ]
    },
    {
        "featureType": "poi.park",
        "elementType": "geometry",
        "stylers": [
            {
                "color": "#bde6ab"
            }
        ]
    },
    {
        "featureType": "road",
        "elementType": "geometry.stroke",
        "stylers": [
            {
                "visibility": "off"
            }
        ]
    },
    {
        "featureType": "road",
        "elementType": "labels",
        "stylers": [
            {
                "visibility": "off"
            }
        ]
    },
    {
        "featureType": "road.highway",
        "elementType": "geometry.fill",
        "stylers": [
            {
                "color": "#ffe15f"
            }
        ]
    },
    {
        "featureType": "road.highway",
        "elementType": "geometry.stroke",
        "stylers": [
            {
                "color": "#efd151"
            }
        ]
    },
    {
        "featureType": "road.arterial",
        "elementType": "geometry.fill",
        "stylers": [
            {
                "color": "#ffffff"
            }
        ]
    },
    {
        "featureType": "road.local",
        "elementType": "geometry.fill",
        "stylers": [
            {
                "color": "black"
            }
        ]
    },
    {
        "featureType": "transit.station.airport",
        "elementType": "geometry.fill",
        "stylers": [
            {
                "color": "#cfb2db"
            }
        ]
    },
    {
        "featureType": "water",
        "elementType": "geometry",
        "stylers": [
            {
                "color": "#a2daf2"
            }
        ]
    }
];

        var myLatLng = {lat: -25.363, lng: 131.044};
        var styledMap = new google.maps.StyledMapType(stylesArray,{name: "Styled Map"});

        var map = new google.maps.Map(document.getElementById('map'), {
          zoom: 4,
          center: myLatLng
        });
        map.mapTypes.set('map_style', styledMap);
 map.setMapTypeId('map_style');

        // new google.maps.Marker({
        //   position: myLatLng,
        //   map: map,
        //   title: 'Hello World!'
        // });

        var size = instagram.lat_array.length

        for(x = 0; x < size; x++)
        {
            var latlng = new google.maps.LatLng(instagram.lat_array[x], instagram.long_array[x]);
            console.log(latlng);
            new google.maps.Marker({
              position: latlng,
              map: map,
              title: 'Post'
            });
        }

      }
  })();



</script>
