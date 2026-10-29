<!DOCTYPE html>
<html>
<head>
  <title>Google Address Autocomplete</title>
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD7KZ3dujsZuhVn8E0uagRyQ65_YvpGJcM&libraries=places"></script>
</head>
<body>

<input id="autocomplete" type="text" placeholder="Enter your address" style="width: 300px; padding: 8px;" />

<script>
  function initAutocomplete() {
    const input = document.getElementById('autocomplete');
    const options = {
      types: ['address'], 
      // componentRestrictions: { country: 'us' } 
    };

    const autocomplete = new google.maps.places.Autocomplete(input, options);

    autocomplete.addListener('place_changed', function () {
      const place = autocomplete.getPlace();
      console.log('Selected place:', place);
    });
  }

  google.maps.event.addDomListener(window, 'load', initAutocomplete);
</script>

</body>
</html>
