<?php
/**
 * Barangay Coordinates Helper Class
 * 
 * Usage:
 * $coords = BarangayCoordinates::getCoordinates('Antipolo', 'Proper Antipolo');
 * $distance = BarangayCoordinates::calculateDistance($lat1, $lon1, $lat2, $lon2);
 */

class BarangayCoordinates {
    
    /**
     * All barangay coordinates organized by municipality
     */
    private static $barangayCoordinates = [
        'Angono' => [
            'Ampid' => [14.5245, 121.1412],
            'Bagumbayan' => [14.5334, 121.1567],
            'Binakayan' => [14.5156, 121.1478],
            'Bukal' => [14.5389, 121.1623],
            'Pag-asa' => [14.5301, 121.1712],
            'Pinagbuhatan' => [14.5423, 121.1534],
            'Pulo' => [14.5189, 121.1601],
            'San Luis' => [14.5267, 121.1645],
            'Santa Cruz' => [14.5345, 121.1423],
            'Santo Domingo' => [14.5478, 121.1589]
        ],
        'Antipolo' => [
            'Bagong Nayon' => [14.5954, 121.1798],
            'Bagong Sikat' => [14.5834, 121.1923],
            'Baras' => [14.5756, 121.1645],
            'Barong-Barong' => [14.5689, 121.1834],
            'Bintig' => [14.5612, 121.1756],
            'Cainta' => [14.5845, 121.1567],
            'Dela Paz' => [14.5823, 121.1634],
            'Paciano Rizal' => [14.5534, 121.1812],
            'Pittland' => [14.5723, 121.1723],
            'Proper Antipolo' => [14.5912, 121.1834],
            'San Jose' => [14.5834, 121.1889],
            'San Roque' => [14.5478, 121.1578],
            'Santa Cruz' => [14.5945, 121.1756],
            'Santo Domingo' => [14.5634, 121.1923],
            'Silangan' => [14.6012, 121.1645]
        ],
        'Baras' => [
            'Antipolo' => [14.5234, 121.2634],
            'Bangad' => [14.5312, 121.2723],
            'Casilag' => [14.5156, 121.2534],
            'Dulong Bayan' => [14.5289, 121.2589],
            'Guinhalinan' => [14.5401, 121.2645],
            'Kaligayahan' => [14.5123, 121.2712],
            'Libjo' => [14.5267, 121.2834],
            'Mahabang Parang' => [14.5378, 121.2756],
            'Pag-asa' => [14.5045, 121.2623],
            'Tagumpay' => [14.5189, 121.2845]
        ],
        'Binangonan' => [
            'Banaba' => [14.4578, 121.1834],
            'Binuclod' => [14.4534, 121.2034],
            'Blaan' => [14.4456, 121.1967],
            'Bugnay' => [14.4723, 121.1723],
            'Bukal' => [14.4834, 121.1945],
            'Caloocan' => [14.4612, 121.2123],
            'Halang' => [14.4701, 121.1889],
            'Harasan' => [14.4389, 121.1956],
            'Macabud' => [14.4823, 121.1734],
            'Mahabang Parang' => [14.4945, 121.1867],
            'Malapad' => [14.4667, 121.2234],
            'Maturog' => [14.4534, 121.2156],
            'Nagtahan' => [14.4756, 121.2045],
            'Pala-pala' => [14.4389, 121.2089],
            'Paraiso' => [14.4612, 121.1745],
            'Pasay' => [14.4345, 121.2178],
            'Putatan' => [14.4901, 121.2089],
            'Rosario' => [14.4478, 121.1834],
            'Tabing Ilog' => [14.4567, 121.2012]
        ],
        'Cainta' => [
            'Binantayan' => [14.3867, 121.2923],
            'Buenavista' => [14.3945, 121.3156],
            'Canlubang' => [14.4034, 121.3234],
            'Halaran' => [14.3834, 121.3089],
            'Hinulugang Taktak' => [14.4123, 121.2967],
            'Hollowville' => [14.3745, 121.3178],
            'Magdalo' => [14.4045, 121.3312],
            'Malaking Pulo' => [14.3656, 121.3045],
            'Paliparan' => [14.3934, 121.2834],
            'Putho' => [14.4089, 121.3045],
            'Rosario' => [14.3812, 121.3312],
            'Sampaloc' => [14.3978, 121.3178],
            'San Antonio' => [14.3712, 121.3089],
            'San Juan' => [14.4156, 121.3034],
            'Santo Niño' => [14.4012, 121.3412],
            'Sapang Kantin' => [14.3845, 121.3534],
            'Tagumpay' => [14.4234, 121.3089],
            'Tayabas' => [14.3589, 121.3156]
        ],
        'Cardona' => [
            'Ampid' => [14.4923, 121.2156],
            'Arado' => [14.4756, 121.2434],
            'Bakwayan' => [14.4634, 121.2378],
            'Binukling' => [14.5012, 121.2289],
            'Bukal' => [14.4923, 121.2512],
            'Burgos' => [14.4834, 121.2145],
            'Caliligawan' => [14.5089, 121.2423],
            'Dulongbayan' => [14.4745, 121.2234],
            'Dulong Bayan' => [14.4667, 121.2567],
            'Hulo' => [14.4923, 121.2067],
            'Kalinisan' => [14.5134, 121.2312],
            'Kasiglahan' => [14.4789, 121.2645],
            'Katipunan' => [14.5056, 121.2534],
            'Kay Buto' => [14.4934, 121.2278],
            'Palukahan' => [14.4612, 121.2289],
            'Palo-Talon' => [14.4823, 121.2089],
            'Rizal (Poblacion)' => [14.4834, 121.2234],
            'San Isidro' => [14.5012, 121.2156],
            'Talipapa' => [14.4745, 121.2423]
        ],
        'Jalajala' => [
            'Bangad' => [14.3634, 121.3178],
            'Batingan' => [14.3501, 121.3312],
            'Bilibiran' => [14.3723, 121.3089],
            'Binitagan' => [14.3789, 121.3401],
            'Bombong' => [14.3612, 121.3456],
            'Buhangin' => [14.3834, 121.3234],
            'Calumpang' => [14.3945, 121.3145],
            'Ginoong Sanay' => [14.3467, 121.3234],
            'Gulod' => [14.3712, 121.3389],
            'Habagatan' => [14.3389, 121.3467],
            'Ithan' => [14.3534, 121.3156],
            'Janosa' => [14.3678, 121.3278],
            'Kalawaan' => [14.3456, 121.3134],
            'Kalinawan' => [14.3812, 121.3156],
            'Kasile' => [14.3345, 121.3289],
            'Kaytome' => [14.3623, 121.3089],
            'Kinaboogan' => [14.3512, 121.3412],
            'Kinagatan' => [14.3745, 121.3234],
            'Layunan' => [14.3389, 121.3178],
            'Libid' => [14.3834, 121.3345],
            'Libis' => [14.3612, 121.3423],
            'Limbon-limbon' => [14.3501, 121.3267],
            'Lunsad' => [14.3723, 121.3145],
            'Macamot' => [14.3356, 121.3423],
            'Mahabang Parang' => [14.3689, 121.3234],
            'Malakaban' => [14.3534, 121.3089],
            'Mambog' => [14.3801, 121.3356],
            'Pag-asa' => [14.3412, 121.3134],
            'Palangoy' => [14.3623, 121.3512],
            'Pantok' => [14.3578, 121.3278],
            'Pila-Pila' => [14.3734, 121.3389],
            'Pinagdilawan' => [14.3389, 121.3212],
            'Pipindan' => [14.3856, 121.3267],
            'Rayap' => [14.3512, 121.3534],
            'San Carlos' => [14.3645, 121.3156],
            'Sapang' => [14.3378, 121.3345],
            'Tabon' => [14.3712, 121.3423],
            'Tagpos' => [14.3534, 121.3178],
            'Tatala' => [14.3823, 121.3234],
            'Tayuman' => [14.3456, 121.3289]
        ],
        'Morong' => [
            'Bagong Pook' => [14.5234, 121.2456],
            'Banasahan' => [14.5089, 121.2234],
            'Binangonan' => [14.5012, 121.2345],
            'Dalig' => [14.5178, 121.2289],
            'Kasiglahan' => [14.5301, 121.2534],
            'Laluan' => [14.5156, 121.2612],
            'Mataas Na Kahoy' => [14.5267, 121.2456],
            'Maybunga' => [14.5345, 121.2378]
        ],
        'Pililla' => [
            'Balibago' => [14.4934, 121.3145],
            'Boor' => [14.4756, 121.3245],
            'Calahan' => [14.4845, 121.3312],
            'Dalig' => [14.4712, 121.3078],
            'Del Remedio' => [14.4978, 121.3267],
            'Iglesia' => [14.5012, 121.3134],
            'Lambac' => [14.4889, 121.3423],
            'Looc' => [14.4756, 121.3389],
            'Malanggam-Calubacan' => [14.5023, 121.3245],
            'Nagsulo' => [14.4945, 121.3178],
            'Navotas' => [14.4834, 121.3312],
            'Patunhay' => [14.4678, 121.3234],
            'Real (Poblacion)' => [14.4789, 121.3156],
            'Sampad' => [14.4912, 121.3289],
            'San Roque (Poblacion)' => [14.5045, 121.3078],
            'Subay' => [14.4956, 121.3423],
            'Ticulio' => [14.4867, 121.3145],
            'Tuna' => [14.5134, 121.3312]
        ],
        'Rodriguez' => [
            'Bagumbong' => [14.7412, 121.1534],
            'Bayugo' => [14.7534, 121.1445],
            'Dulong Bayan' => [14.7156, 121.1678],
            'First District (Poblacion)' => [14.7223, 121.1512],
            'Hagdan' => [14.7389, 121.1356],
            'Hinulid' => [14.7245, 121.1723],
            'Jordanville' => [14.7089, 121.1634],
            'Karangalan' => [14.7145, 121.1278],
            'Kasiglahan' => [14.7423, 121.1234],
            'Lubo' => [14.7345, 121.1078],
            'Paalaman' => [14.7278, 121.1367],
            'Pagkalinawan' => [14.7512, 121.1234],
            'Palaypalay' => [14.7189, 121.1423],
            'Punta' => [14.7156, 121.1389],
            'Samahan' => [14.7234, 121.1456],
            'Second District (Poblacion)' => [14.7323, 121.1345],
            'Sipsipin' => [14.7345, 121.1178],
            'Third District (Poblacion)' => [14.7234, 121.1512],
            'Trinidad' => [14.7412, 121.1634],
            'Tubig' => [14.7089, 121.1512],
            'Wawa' => [14.7456, 121.1723]
        ],
        'San Mateo' => [
            'Ampid 1' => [14.6912, 121.1334],
            'Ampid 2' => [14.6834, 121.1345],
            'Ansiolin' => [14.7012, 121.1089],
            'Banaba' => [14.6812, 121.1512],
            'Banay-banay' => [14.7145, 121.1234],
            'Bangkusay' => [14.6923, 121.1156],
            'Buting' => [14.7234, 121.1045],
            'Dulong Bayan 1' => [14.6945, 121.1278],
            'Dulong Bayan 2' => [14.7089, 121.1167],
            'Galas' => [14.7312, 121.1423],
            'Guinayang' => [14.6889, 121.1523],
            'Guitnang Bayan 1' => [14.6845, 121.1312],
            'Guitnang Bayan 2' => [14.6978, 121.1434],
            'Gulod Malaya' => [14.7012, 121.1645],
            'Halap' => [14.7178, 121.1678],
            'Kaypian' => [14.6756, 121.1267],
            'Malanday' => [14.6856, 121.1178],
            'Maly' => [14.6934, 121.1523],
            'Nakabaao' => [14.7089, 121.1834],
            'Paliguan' => [14.7234, 121.1534],
            'Pikong Bukawc' => [14.6778, 121.1234],
            'Pittland' => [14.6945, 121.1089],
            'Riversville' => [14.7123, 121.1156],
            'Sakal' => [14.7045, 121.1412],
            'Salambao' => [14.7189, 121.1089],
            'Santa Ana' => [14.6923, 121.1367],
            'Santa Isabel' => [14.6834, 121.1456],
            'Santo Niño' => [14.6912, 121.1245],
            'Sapang' => [14.7267, 121.1334],
            'Silangan' => [14.6867, 121.1389],
            'Tumpahan' => [14.7312, 121.1267],
            'Upland' => [14.6945, 121.1612]
        ],
        'Tanay' => [
            'Ambangeg' => [14.5067, 121.3756],
            'Andres Bonifacio' => [14.4834, 121.3645],
            'Anuran' => [14.4945, 121.3534],
            'Bagumbayan' => [14.5123, 121.3234],
            'Banawe' => [14.4812, 121.3267],
            'Baras' => [14.5234, 121.3389],
            'Batangan' => [14.5012, 121.3812],
            'Binuangan' => [14.4923, 121.3912],
            'Bukal' => [14.5178, 121.3534],
            'Bungad' => [14.5045, 121.3745],
            'Comadre' => [14.5312, 121.3456],
            'Daang Bukid' => [14.5089, 121.3623],
            'Halayhayin' => [14.5245, 121.3445],
            'Hulo' => [14.5056, 121.3312],
            'Imatong' => [14.5189, 121.3078],
            'Kalinisan' => [14.5334, 121.3812],
            'Kalusugan' => [14.4923, 121.3178],
            'Kaybigan' => [14.5267, 121.3634],
            'Kumulot' => [14.5156, 121.3945],
            'Lakad' => [14.5378, 121.3723]
        ],
        'Taytay' => [
            'Bagong Kahoy' => [14.5745, 121.1412],
            'Balagtas' => [14.5834, 121.1267],
            'Balsahan' => [14.5612, 121.1534],
            'Batuhan' => [14.5901, 121.1156],
            'Bigaan' => [14.5534, 121.1478],
            'Buhangin' => [14.5723, 121.1089],
            'Bukal' => [14.5834, 121.1234],
            'Caloocan' => [14.5645, 121.1345],
            'Capitangan' => [14.5956, 121.1389],
            'Cayao-cayao' => [14.5612, 121.1156],
            'Daangbayan' => [14.5734, 121.1534],
            'Dolores' => [14.5467, 121.1267],
            'Greenvale' => [14.5578, 121.1623],
            'Karangalan' => [14.5845, 121.1412],
            'Kayquit' => [14.5789, 121.1789]
        ],
        'Teresa' => [
            'Balite' => [14.5834, 121.2234],
            'Bangkal' => [14.5612, 121.1934],
            'Burgos' => [14.5956, 121.2145],
            'Divisoria' => [14.5745, 121.2056],
            'Geronimo' => [14.5712, 121.2312],
            'Kasiglahan' => [14.5534, 121.1923],
            'Macabud' => [14.5589, 121.1923],
            'Manggahan' => [14.5723, 121.2078],
            'Mascap' => [14.5645, 121.1834],
            'Puray' => [14.5512, 121.2145],
            'Rosario' => [14.5867, 121.2312],
            'San Isidro' => [14.5745, 121.1912],
            'San Jose' => [14.5634, 121.2234],
            'San Rafael' => [14.5523, 121.2089],
            'Santa Maria' => [14.5678, 121.2045]
        ],
        'Tetuan' => [
            'Bagumbayan' => [14.2821, 121.3925],
            'Kalayaan' => [14.2834, 121.3715],
            'Mahabang Parang' => [14.2899, 121.3801],
            'Poblacion Ibaba' => [14.2801, 121.3748],
            'Poblacion Itaas' => [14.2821, 121.3725],
            'San Isidro' => [14.2687, 121.3764],
            'San Pedro' => [14.2625, 121.3845],
            'San Roque' => [14.2756, 121.3842],
            'San Vicente' => [14.2695, 121.3915],
            'Santo Niño' => [14.2933, 121.3768]
        ]
    ];
    
    /**
     * Get coordinates for a specific barangay
     * 
     * @param string $municipality Municipality name
     * @param string $barangay Barangay name
     * @return array|null [latitude, longitude] or null if not found
     */
    public static function getCoordinates($municipality, $barangay) {
        if (isset(self::$barangayCoordinates[$municipality][$barangay])) {
            return self::$barangayCoordinates[$municipality][$barangay];
        }
        return null;
    }
    
    /**
     * Get all barangays in a municipality
     * 
     * @param string $municipality Municipality name
     * @return array Associative array of barangays with coordinates
     */
    public static function getBarangaysByMunicipality($municipality) {
        return self::$barangayCoordinates[$municipality] ?? [];
    }
    
    /**
     * Get all municipalities
     * 
     * @return array List of all municipalities
     */
    public static function getMunicipalities() {
        return array_keys(self::$barangayCoordinates);
    }
    
    /**
     * Calculate distance between two coordinates using Haversine formula
     * 
     * @param float $lat1 Starting latitude
     * @param float $lon1 Starting longitude
     * @param float $lat2 Ending latitude
     * @param float $lon2 Ending longitude
     * @return float Distance in kilometers
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // Kilometers
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;
        
        return $distance;
    }
    
    /**
     * Get center coordinates of a municipality
     * 
     * @param string $municipality Municipality name
     * @return array|null [latitude, longitude] or null if not found
     */
    public static function getMunicipalityCenter($municipality) {
        $barangays = self::getBarangaysByMunicipality($municipality);
        
        if (empty($barangays)) {
            return null;
        }
        
        $totalLat = 0;
        $totalLon = 0;
        $count = count($barangays);
        
        foreach ($barangays as $coords) {
            $totalLat += $coords[0];
            $totalLon += $coords[1];
        }
        
        return [
            $totalLat / $count,
            $totalLon / $count
        ];
    }
    
    /**
     * Find nearest barangay to given coordinates
     * 
     * @param float $latitude User latitude
     * @param float $longitude User longitude
     * @param string|null $municipality Optional: restrict search to municipality
     * @return array ['municipality' => string, 'barangay' => string, 'distance' => float]
     */
    public static function findNearestBarangay($latitude, $longitude, $municipality = null) {
        $nearest = null;
        $minDistance = PHP_FLOAT_MAX;
        
        $municipalitiesToSearch = $municipality 
            ? [$municipality] 
            : array_keys(self::$barangayCoordinates);
        
        foreach ($municipalitiesToSearch as $mun) {
            $barangays = self::getBarangaysByMunicipality($mun);
            
            foreach ($barangays as $barangay => $coords) {
                $distance = self::calculateDistance(
                    $latitude, 
                    $longitude, 
                    $coords[0], 
                    $coords[1]
                );
                
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $nearest = [
                        'municipality' => $mun,
                        'barangay' => $barangay,
                        'distance' => round($distance, 2),
                        'coordinates' => $coords
                    ];
                }
            }
        }
        
        return $nearest;
    }
    
    /**
     * Validate if barangay exists
     * 
     * @param string $municipality Municipality name
     * @param string $barangay Barangay name
     * @return bool True if barangay exists
     */
    public static function barangayExists($municipality, $barangay) {
        return isset(self::$barangayCoordinates[$municipality][$barangay]);
    }
}

// Example usage:
/*
// Get coordinates for a specific barangay
$coords = BarangayCoordinates::getCoordinates('Antipolo', 'Proper Antipolo');
echo "Coordinates: " . implode(', ', $coords);

// Get all barangays in a municipality
$barangays = BarangayCoordinates::getBarangaysByMunicipality('Antipolo');

// Calculate distance
$distance = BarangayCoordinates::calculateDistance(14.5912, 121.1834, 14.5780, 121.1824);
echo "Distance: " . $distance . " km";

// Get municipality center
$center = BarangayCoordinates::getMunicipalityCenter('Antipolo');

// Find nearest barangay
$nearest = BarangayCoordinates::findNearestBarangay(14.5, 121.2);
*/
?>
