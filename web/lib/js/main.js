    $(document).ready(function ()
    {
        $.initGeolocalisation = function (){
        var debug = true;

        var options = {
          enableHighAccuracy: true,
          timeout: 5000,
          maximumAge: 0
        };

        function success(pos) {
          var crd = pos.coords;
          if(debug){
              console.log('Votre position actuelle est :');
              console.log(`Latitude : ${crd.latitude}`);
              console.log(`Longitude: ${crd.longitude}`);
              console.log(`Plus ou moins ${crd.accuracy} mètres.`);
          }

          $("#photo_lat").val(crd.latitude);
          $("#photo_lon").val(crd.longitude);
        };

        function error(err) {
          console.warn(`ERROR(${err.code}): ${err.message}`);
        };

        navigator.geolocation.getCurrentPosition(success, error, options);
    };

        $.initMap = function () {
        if ($('#map').length) {
            var lat = 48.8593829;
            var lon = 2.347227;
            var zoom = 0;
            if ($('#map').attr('data-lat') && $('#map').attr('data-lon')) {
                lat = $('#map').data('lat');
                lon = $('#map').data('lon');
            }
            if($('#map').attr('data-zoom')){
                zoom = $('#map').data('zoom');
            }

            var map = L.map('map').setView([lat, lon], zoom);

            L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            var geojson = JSON.parse($('#map').attr('data-geojson'));
            var markers = [];

            var ascenseurIcon = L.icon({
                iconUrl: '../../elevator_inv_32px.svg',
                iconSize: [32, 32]
            });
            L.geoJson(geojson,
                    {
                        pointToLayer: function (feature, latlng) {
                            var marker = L.marker(latlng, {icon: ascenseurIcon});
                            markers[feature.properties._id] = marker;
                            return marker;
                        }
                    }
            ).addTo(map);

            }
        };

        $.initGeolocalisation();
        $.initMap();

      });
