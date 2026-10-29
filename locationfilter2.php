<!DOCTYPE html>
<html>
<head>
  <title>Flexible Location Autocomplete</title>
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD7KZ3dujsZuhVn8E0uagRyQ65_YvpGJcM&libraries=places"></script>
</head>
<body>
  <h3>Search Address, City, State or Postal Code</h3>
  <input id="autocomplete" type="text" placeholder="Start typing..." style="width: 300px; padding: 8px;" />

  <pre id="output"></pre>

  <script>
    function initAutocomplete() {
      const input = document.getElementById('autocomplete');

      const autocomplete = new google.maps.places.Autocomplete(input, {
        types: ['(regions)'], // allows city, state, zip, etc.
        // Optional: restrict to a country
        // componentRestrictions: { country: "us" }
      });

      autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace();
        console.log(place);

        const components = place.address_components;
        const formatted = place.formatted_address;

        let postalCode = '', city = '', state = '', country = '';

        components.forEach(component => {
          if (component.types.includes('postal_code')) {
            postalCode = component.long_name;
          }
          if (component.types.includes('locality')) {
            city = component.long_name;
          }
          if (component.types.includes('administrative_area_level_1')) {
            state = component.long_name;
          }
          if (component.types.includes('country')) {
            country = component.long_name;
          }
        });

        document.getElementById('output').innerText =
          `Formatted: ${formatted}
City: ${city}
State: ${state}
Postal Code: ${postalCode}
Country: ${country}`;
      });
    }

    window.onload = initAutocomplete;
  </script>
</body>
</html>
