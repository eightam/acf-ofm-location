# ACF OpenFreeMap Location Field

Contributors: 8am GmbH
Tags: acf, advanced custom fields, location, map, maplibre, openstreetmap, geocoding
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple, lightweight location field for Advanced Custom Fields using MapLibre GL and OpenFreeMap with geocoding support.

## Description

## Features

- **MapLibre GL** powered interactive maps
- **OpenFreeMap** tiles (customizable style URL)
- **Dual geocoding support**: Photon (photon.komoot.io) or Nominatim (nominatim.openstreetmap.org)
- **Multiple input methods**: Search, click on map, or drag marker
- **Comprehensive data storage**: Full address plus individual components
- **Global & per-field settings**: Configure defaults globally, override per field
- **WordPress standards compliant**: Proper sanitization, validation, and security

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Advanced Custom Fields (ACF) Pro or Free

## Installation

1. Upload the `acf-ofm-location` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure global settings at **Custom Fields > Location Settings**

## Configuration

### Global Settings

Navigate to **Custom Fields > Location Settings** to configure:

- **Geocoding API**: Choose between Photon or Nominatim
- **Map Style URL**: MapLibre GL style URL (default: OpenFreeMap Positron)
- **Default Latitude**: Center latitude for new maps (default: 50.0 for Europe)
- **Default Longitude**: Center longitude for new maps (default: 10.0 for Europe)
- **Default Zoom Level**: Initial zoom level (default: 6)

### Per-Field Settings

When creating a location field, you can override global settings:

1. Create a new field group
2. Add a field of type "Location (OpenFreeMap)"
3. Configure field-specific settings (optional overrides)

## Usage

### Setting a Location

Users can set a location in three ways:

1. **Search**: Type an address in the search box
2. **Click**: Click anywhere on the map to place a marker
3. **Drag**: Drag the marker to fine-tune the position

### Accessing Data in Templates

The field returns an array with the following keys:

```php
$location = get_field('location_field_name');

// Access full address
echo $location['full_address'];

// Access individual components
echo $location['street'];
echo $location['number'];
echo $location['city'];
echo $location['post_code'];
echo $location['country'];
echo $location['state'];
echo $location['lat'];
echo $location['lng'];
```

### Individual Component Access

Each component is automatically registered as a separate ACF field that's visible in theme builders (like Blocksy, Elementor, etc.):

```php
// If your field name is "address", individual fields are automatically available:

// Method 1: ACF's get_field (recommended - works with theme builders)
$street = get_field('address_street', $post_id);
$city = get_field('address_city', $post_id);
$lat = get_field('address_lat', $post_id);
$lng = get_field('address_lng', $post_id);

// Method 2: Direct meta access (also works)
$street = get_post_meta($post_id, 'address_street', true);
$city = get_post_meta($post_id, 'address_city', true);

// All available component fields:
// - {field_name}_full_address  (comma-separated complete address)
// - {field_name}_street        (street name)
// - {field_name}_number        (house/building number)
// - {field_name}_city          (city name)
// - {field_name}_post_code     (postal/ZIP code)
// - {field_name}_country       (country name)
// - {field_name}_state         (state/province/region)
// - {field_name}_lat           (latitude coordinate)
// - {field_name}_lng           (longitude coordinate)
```

**Note**: When you create a location field named "address", you'll see these fields in your theme builder's ACF field selector:
- Address - Full Address
- Address - Street
- Address - Number
- Address - City
- Address - Post Code
- Address - Country
- Address - State
- Address - Latitude
- Address - Longitude

### Display Example

```php
<?php
$location = get_field('location');

if ($location): ?>
    <div class="location-info">
        <h3><?php echo esc_html($location['full_address']); ?></h3>

        <?php if ($location['street'] && $location['number']): ?>
            <p><?php echo esc_html($location['street'] . ' ' . $location['number']); ?></p>
        <?php endif; ?>

        <?php if ($location['post_code'] && $location['city']): ?>
            <p><?php echo esc_html($location['post_code'] . ' ' . $location['city']); ?></p>
        <?php endif; ?>

        <?php if ($location['lat'] && $location['lng']): ?>
            <p>
                <a href="https://www.openstreetmap.org/?mlat=<?php echo esc_attr($location['lat']); ?>&mlon=<?php echo esc_attr($location['lng']); ?>#map=15/<?php echo esc_attr($location['lat']); ?>/<?php echo esc_attr($location['lng']); ?>" target="_blank">
                    View on OpenStreetMap
                </a>
            </p>
        <?php endif; ?>
    </div>
<?php endif; ?>
```

## Data Structure

The field stores the following data:

| Key           | Type   | Description                    |
|---------------|--------|--------------------------------|
| full_address  | string | Complete formatted address     |
| street        | string | Street name                    |
| number        | string | House/building number          |
| city          | string | City/town/village name         |
| post_code     | string | Postal/ZIP code                |
| country       | string | Country name                   |
| state         | string | State/province/region          |
| lat           | float  | Latitude coordinate            |
| lng           | float  | Longitude coordinate           |

## Geocoding Services

### Photon (Default)

- **URL**: https://photon.komoot.io
- **Rate Limit**: Fair use policy
- **Coverage**: Worldwide
- **Reverse Geocoding**: Falls back to Nominatim

### Nominatim

- **URL**: https://nominatim.openstreetmap.org
- **Rate Limit**: 1 request per second
- **Coverage**: Worldwide
- **Reverse Geocoding**: Supported

## Customization

### Custom Map Styles

You can use any MapLibre GL compatible style URL:

```
https://tiles.openfreemap.org/styles/positron (default)
https://tiles.openfreemap.org/styles/liberty
https://tiles.openfreemap.org/styles/bright
```

Or use your own custom style URL in the settings.

## Security

- All user inputs are sanitized using WordPress functions
- Coordinates are validated (lat: -90 to 90, lng: -180 to 180)
- URLs are sanitized with `esc_url_raw()`
- Text fields use `sanitize_text_field()`
- Output is escaped with `esc_html()`, `esc_attr()`

## License

This plugin is licensed under the GPLv2 or later.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

## Credits

- **MapLibre GL JS**: https://maplibre.org
- **OpenFreeMap**: https://openfreemap.org
- **Photon**: https://photon.komoot.io
- **Nominatim**: https://nominatim.openstreetmap.org

## Support

For issues, questions, or contributions, please visit the plugin repository.
