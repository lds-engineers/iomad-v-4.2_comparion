<!DOCTYPE html>
<html>
<head>
  <title>Google Address Autocomplete</title>
  <script
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD7KZ3dujsZuhVn8E0uagRyQ65_YvpGJcM&libraries=places"
    async
    defer
  ></script>
</head>
<body>
  <input id="autocomplete" type="text" placeholder="Enter your address" style="width: 300px;" />

  <script>
    function initAutocomplete() {
      const input = document.getElementById('autocomplete');
      const autocomplete = new google.maps.places.Autocomplete(input, {
        types: ['geocode'], // You can use '(cities)' or '(regions)' or leave it
        componentRestrictions: { country: 'us' } // optional
      });

      autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace();
        console.log(place);
        // You can extract address components like:
        // place.address_components, place.geometry, place.formatted_address
      });
    }

    // Wait for Google script to load
    window.initAutocomplete = initAutocomplete;
    window.onload = () => {
      if (typeof google !== 'undefined') initAutocomplete();
    };
  </script>
</body>
</html>