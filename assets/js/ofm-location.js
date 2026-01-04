(function($) {
	'use strict';

	/**
	 * Initialize ACF Location Field
	 */
	function initializeField(field) {
		// ACF passes a field object with $el property containing the jQuery element
		const $field = field.$el || $(field);

		// Find the container: .acf-field > .acf-input > .acf-ofm-location-field
		const $container = $field.find('.acf-ofm-location-field');

		if (!$container.length) {
			return;
		}

		if ($container.data('map-initialized')) {
			return;
		}

		const geocodingApi = $container.data('geocoding-api') || 'photon';
		const styleUrl = $container.data('style-url') || 'https://tiles.openfreemap.org/styles/positron';
		const defaultLat = parseFloat($container.data('default-lat')) || 50.0;
		const defaultLng = parseFloat($container.data('default-lng')) || 10.0;
		const defaultZoom = parseInt($container.data('default-zoom')) || 6;

		const $mapContainer = $container.find('.acf-ofm-location-map');
		const $searchInput = $container.find('.acf-ofm-location-search-input');
		const $searchResults = $container.find('.acf-ofm-location-search-results');
		const $display = $container.find('.acf-ofm-location-display');

		// Get current value
		const currentLat = $container.find('.acf-ofm-location-lat').val();
		const currentLng = $container.find('.acf-ofm-location-lng').val();

		// Initialize map
		const map = new maplibregl.Map({
			container: $mapContainer[0],
			style: styleUrl,
			center: currentLat && currentLng ? [parseFloat(currentLng), parseFloat(currentLat)] : [defaultLng, defaultLat],
			zoom: currentLat && currentLng ? 13 : defaultZoom
		});

		// Ensure map resizes properly when loaded
		map.on('load', function() {
			map.resize();
		});

		// Add navigation controls
		map.addControl(new maplibregl.NavigationControl(), 'top-right');

		let marker = null;

		// Add existing marker if location is set
		if (currentLat && currentLng) {
			marker = new maplibregl.Marker({ draggable: true })
				.setLngLat([parseFloat(currentLng), parseFloat(currentLat)])
				.addTo(map);

			// Handle marker drag
			marker.on('dragend', function() {
				const lngLat = marker.getLngLat();
				reverseGeocode(lngLat.lat, lngLat.lng);
			});
		}

		// Handle map click
		map.on('click', function(e) {
			const { lng, lat } = e.lngLat;

			// Create or move marker
			if (marker) {
				marker.setLngLat([lng, lat]);
			} else {
				marker = new maplibregl.Marker({ draggable: true })
					.setLngLat([lng, lat])
					.addTo(map);

				// Handle marker drag
				marker.on('dragend', function() {
					const lngLat = marker.getLngLat();
					reverseGeocode(lngLat.lat, lngLat.lng);
				});
			}

			// Reverse geocode to get address
			reverseGeocode(lat, lng);
		});

		// Debounce timer
		let searchTimeout = null;

		// Handle search input
		$searchInput.on('input', function() {
			const query = $(this).val().trim();

			clearTimeout(searchTimeout);

			if (query.length < 3) {
				$searchResults.hide().empty();
				return;
			}

			searchTimeout = setTimeout(function() {
				searchLocation(query);
			}, 300);
		});

		// Search for location
		function searchLocation(query) {
			const encodedQuery = encodeURIComponent(query);
			let url = '';

			if (geocodingApi === 'nominatim') {
				url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodedQuery}&addressdetails=1`;
			} else {
				// Default to Photon
				url = `https://photon.komoot.io/api/?q=${encodedQuery}`;
			}

			$.ajax({
				url: url,
				method: 'GET',
				dataType: 'json',
				success: function(data) {
					displaySearchResults(data);
				},
				error: function() {
					$searchResults.hide().empty();
				}
			});
		}

		// Display search results
		function displaySearchResults(data) {
			$searchResults.empty();

			if (!data || data.length === 0) {
				$searchResults.hide();
				return;
			}

			const results = geocodingApi === 'nominatim' ? parseNominatimResults(data) : parsePhotonResults(data);

			if (results.length === 0) {
				$searchResults.hide();
				return;
			}

			results.forEach(function(result) {
				const $item = $('<div class="acf-ofm-location-result-item"></div>')
					.text(result.full_address)
					.on('click', function() {
						selectLocation(result);
					});
				$searchResults.append($item);
			});

			$searchResults.show();
		}

		// Parse Photon results
		function parsePhotonResults(data) {
			const results = [];

			if (data.features && Array.isArray(data.features)) {
				data.features.slice(0, 5).forEach(function(feature) {
					const props = feature.properties || {};
					const coords = feature.geometry.coordinates;

					const street = props.street || '';
					const number = props.housenumber || '';
					const city = props.city || '';
					const postCode = props.postcode || '';
					const country = props.country || '';
					const state = props.state || '';

					// Build comma-separated full address
					const addressParts = [];
					if (street) {
						addressParts.push(number ? `${street} ${number}` : street);
					}
					if (city) addressParts.push(city);
					if (postCode) addressParts.push(postCode);
					if (state) addressParts.push(state);
					if (country) addressParts.push(country);

					results.push({
						full_address: addressParts.join(', '),
						street: street,
						number: number,
						city: city,
						post_code: postCode,
						country: country,
						state: state,
						lat: coords[1],
						lng: coords[0]
					});
				});
			}

			return results;
		}

		// Parse Nominatim results
		function parseNominatimResults(data) {
			const results = [];

			if (Array.isArray(data)) {
				data.slice(0, 5).forEach(function(item) {
					const address = item.address || {};

					const street = address.road || '';
					const number = address.house_number || '';
					const city = address.city || address.town || address.village || '';
					const postCode = address.postcode || '';
					const country = address.country || '';
					const state = address.state || '';

					// Build comma-separated full address
					const addressParts = [];
					if (street) {
						addressParts.push(number ? `${street} ${number}` : street);
					}
					if (city) addressParts.push(city);
					if (postCode) addressParts.push(postCode);
					if (state) addressParts.push(state);
					if (country) addressParts.push(country);

					results.push({
						full_address: addressParts.join(', '),
						street: street,
						number: number,
						city: city,
						post_code: postCode,
						country: country,
						state: state,
						lat: parseFloat(item.lat),
						lng: parseFloat(item.lon)
					});
				});
			}

			return results;
		}

		// Select location from search results
		function selectLocation(location) {
			// Update hidden inputs
			$container.find('.acf-ofm-location-full-address').val(location.full_address);
			$container.find('.acf-ofm-location-street').val(location.street);
			$container.find('.acf-ofm-location-number').val(location.number);
			$container.find('.acf-ofm-location-city').val(location.city);
			$container.find('.acf-ofm-location-post-code').val(location.post_code);
			$container.find('.acf-ofm-location-country').val(location.country);
			$container.find('.acf-ofm-location-state').val(location.state);
			$container.find('.acf-ofm-location-lat').val(location.lat);
			$container.find('.acf-ofm-location-lng').val(location.lng);

			// Update display
			$display.text(location.full_address || 'Location set');

			// Move map and marker
			map.flyTo({ center: [location.lng, location.lat], zoom: 13 });

			if (marker) {
				marker.setLngLat([location.lng, location.lat]);
			} else {
				marker = new maplibregl.Marker({ draggable: true })
					.setLngLat([location.lng, location.lat])
					.addTo(map);

				// Handle marker drag
				marker.on('dragend', function() {
					const lngLat = marker.getLngLat();
					reverseGeocode(lngLat.lat, lngLat.lng);
				});
			}

			// Hide search results and clear input
			$searchResults.hide().empty();
			$searchInput.val('');
		}

		// Reverse geocode coordinates to address
		function reverseGeocode(lat, lng) {
			let url = '';

			if (geocodingApi === 'nominatim') {
				url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`;
			} else {
				// Photon doesn't support reverse geocoding, use Nominatim as fallback
				url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`;
			}

			$.ajax({
				url: url,
				method: 'GET',
				dataType: 'json',
				success: function(data) {
					const location = parseReverseGeocodeResult(data, lat, lng);
					selectLocation(location);
				},
				error: function() {
					// If reverse geocoding fails, just set coordinates
					selectLocation({
						full_address: `${lat.toFixed(6)}, ${lng.toFixed(6)}`,
						street: '',
						number: '',
						city: '',
						post_code: '',
						country: '',
						state: '',
						lat: lat,
						lng: lng
					});
				}
			});
		}

		// Parse reverse geocode result (Nominatim format)
		function parseReverseGeocodeResult(data, lat, lng) {
			const address = data.address || {};

			const street = address.road || '';
			const number = address.house_number || '';
			const city = address.city || address.town || address.village || '';
			const postCode = address.postcode || '';
			const country = address.country || '';
			const state = address.state || '';

			// Build comma-separated full address
			const addressParts = [];
			if (street) {
				addressParts.push(number ? `${street} ${number}` : street);
			}
			if (city) addressParts.push(city);
			if (postCode) addressParts.push(postCode);
			if (state) addressParts.push(state);
			if (country) addressParts.push(country);

			return {
				full_address: addressParts.length > 0 ? addressParts.join(', ') : `${lat.toFixed(6)}, ${lng.toFixed(6)}`,
				street: street,
				number: number,
				city: city,
				post_code: postCode,
				country: country,
				state: state,
				lat: lat,
				lng: lng
			};
		}

		// Hide search results when clicking outside
		$(document).on('click', function(e) {
			if (!$(e.target).closest('.acf-ofm-location-search').length) {
				$searchResults.hide();
			}
		});

		// Mark as initialized
		$container.data('map-initialized', true);
	}

	/**
	 * ACF Integration
	 */
	if (typeof acf !== 'undefined') {
		acf.addAction('ready_field/type=ofm_location', function(field) {
			initializeField(field);
		});

		acf.addAction('append_field/type=ofm_location', function(field) {
			initializeField(field);
		});
	}

})(jQuery);
