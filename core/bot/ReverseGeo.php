<?php

class ReverseGeo {

  private $key = '';

  private $lang = 'en';
  
  private $lat;
  private $lon;
  
  private $street;
  private $number;
  private $zip;
  private district;

  public function __construct($lang = 'en', $key = '') {

    $this->lang = $lang;
    $this->key = $ley;
  }
  
  public function setLatLon($lat, $lon) {

    $this->lat = $lat;
    $this->lon = $lon;
  }
  
  public function setLat($lat) {

    $this->lat = $lat;
  }

  public function setLon($lon) {

    $this->lon = $lon;
  }
  
  public function street() {
    
    return $this->street;
  }

  public function number() {
    
    return $this->number;
  }
  
  public function zip() {
    
    return $this->zip;
  }
  
  public function district() {
    
    return $this->district;
  }

  public function parse() {
    
    $data = request();

    // Received valid data from Google.
    if (!empty($data) && !empty($data->status) && $data->status == 'OK' && !empty($data->results)) {

        // Init vars.
        $locality = '';
        $sublocalityLv2 = '';
        $sublocality = '';

        // Iterate each result.
        foreach ($data->results as $result) {

            // Check for address components.
            if (!empty($result->address_components)) {

                // Iterate each address component.
                foreach ($result->address_components as $address_component) {

                    // Street found.
                    if (in_array('route', $address_component->types) && !empty($address_component->long_name)) {
                        // Set street by first found.
                        $this->street = empty($this->street) ? $address_component->long_name : $this->street;
                    }

                    // Street number found.
                    if (in_array('street_number', $address_component->types) && !empty($address_component->long_name)) {
                        // Set street by first found.
                        $this->number = empty($this->number) ? $address_component->long_name : $this->number;
                    }

                    // Postal code found.
                    if (in_array('postal_code', $address_component->types) && !empty($address_component->long_name)) {
                        // Set street by first found.
                        $this->zip = empty($this->zip) ? $address_component->long_name : $this->zip;
                    }

                    // Sublocality level2 found.
                    if (in_array('sublocality_level_2', $address_component->types) && !empty($address_component->long_name)) {
                        // Set sublocality level 2 by first found.
                        $sublocalityLv2 = empty($sublocalityLv2) ? $address_component->long_name : $sublocalityLv2;
                    }

                    // Sublocality found.
                    if (in_array('sublocality', $address_component->types) && !empty($address_component->long_name)) {
                        // Set sublocality by first found.
                        $sublocality = empty($sublocality) ? $address_component->long_name : $sublocality;
                    }

                    // Locality found.
                    if (in_array('locality', $address_component->types) && !empty($address_component->long_name)) {
                        // Set sublocality by first found.
                        $locality = empty($sublocality) ? $address_component->long_name : $sublocality;
                    }
                }
            }
            break;
        }

        // Set district by priority.
        if (!empty($sublocalityLv2)) {

            $this->district = $sublocalityLv2;
        } else if ($sublocality) {

            $this->district = $sublocality;
        } else if ($locality) {

            $this->district = $locality;
        }
      }
  }

  private function request() {

    // Set maps geocode url.
    $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $this->lat . ',' . $this->lon . '&language=' . strtolower($this->lang);

    // Append google api key if exists.
    if (!empty($this->key)) {

        $url .= '&key=' . $this->key;
    }

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    // Use Proxyserver for curl if configured
    if (USEPROXY == true) {

        curl_setopt($curl, CURLOPT_PROXY, PROXY);
    }

    return curl_exec($curl);
  }
}
?>
