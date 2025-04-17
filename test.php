<?php
// Configuration
define('DATA_FEED_ID', 'TowerEstAPI'); // Replace with your data feed ID
define('USERNAME', 'TE56938759'); // Replace with your API username
define('PASSWORD', 'zMn{_[eA8J'); // Replace with your API password
define('API_VERSION', '13');
define('BASE_URL', 'https://webservices.vebra.com/export/' . DATA_FEED_ID . '/v' . API_VERSION . '/');

// Function to get security token
function getSecurityToken() {
    $url = BASE_URL . 'branch';
    $usernamePassword = USERNAME . ':' . PASSWORD;
    $authHeader = 'Basic ' . base64_encode($usernamePassword);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . $authHeader,
        'Accept: application/xml'
    ]);
    curl_setopt($ch, CURLOPT_HEADER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode == 200) {
        // Extract token from headers
        preg_match('/Token: ([^\r\n]+)/', $response, $matches);
        $token = isset($matches[1]) ? $matches[1] : null;
        curl_close($ch);
        return $token;
    } else {
        curl_close($ch);
        error_log("Failed to get token. HTTP Code: $httpCode");
        return null;
    }
}

// Function to make authenticated API call
function makeApiCall($url, $token = null) {
    $authHeader = $token ? 'Basic ' . base64_encode($token) : 'Basic ' . base64_encode(USERNAME . ':' . PASSWORD);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . $authHeader,
        'Accept: application/xml'
    ]);
    
    // Add If-Modified-Since header for caching (example: last 24 hours)
    $lastModified = gmdate('D, d M Y H:i:s \G\M\T', strtotime('-24 hours'));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . $authHeader,
        'Accept: application/xml',
        'If-Modified-Since: ' . $lastModified
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode == 200) {
        curl_close($ch);
        return $response;
    } elseif ($httpCode == 304) {
        curl_close($ch);
        return null; // No new data
    } elseif ($httpCode == 401 && $token) {
        curl_close($ch);
        // Token expired, get new token and retry
        $newToken = getSecurityToken();
        if ($newToken) {
            return makeApiCall($url, $newToken);
        }
    } else {
        error_log("API call failed. URL: $url, HTTP Code: $httpCode");
        curl_close($ch);
        return null;
    }
}

// Function to fetch branches
function getBranches($token) {
    $url = BASE_URL . 'branch';
    $response = makeApiCall($url, $token);
    if ($response) {
        return simplexml_load_string($response);
    }
    return null;
}

// Function to fetch property list for a branch
function getPropertyList($branchId, $token) {
    $url = BASE_URL . 'branch/' . $branchId . '/property';
    $response = makeApiCall($url, $token);
    if ($response) {
        return simplexml_load_string($response);
    }
    return null;
}

// Main execution
$token = getSecurityToken();

if ($token) {
    // Get branches
    $branches = getBranches($token);
    if ($branches) {
        // For simplicity, process the first branch
        $firstBranch = $branches->branch[0];
        $branchId = (string)$firstBranch->branchid;

        // Get property list for the branch
        $properties = getPropertyList($branchId, $token);
        if ($properties) {
            echo '<h1>Property Listings</h1>';
            echo '<ul>';
            foreach ($properties->property as $property) {
                $propId = (string)$property->prop_id;
                $lastChanged = (string)$property->lastchanged;
                $propertyUrl = (string)$property->url;

                // Fetch full property details (optional, for more data)
                $propertyDetails = makeApiCall($propertyUrl, $token);
                if ($propertyDetails) {
                    $propertyXml = simplexml_load_string($propertyDetails);
                    $address = (string)$propertyXml->address->display;
                    $price = (string)$propertyXml->price->value;
                    $qualifier = (string)$propertyXml->price->qualifier;

                    // Decode HTML escape codes
                    $address = html_entity_decode($address, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $price = html_entity_decode($price, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $qualifier = html_entity_decode($qualifier, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                    echo "<li>";
                    echo "<strong>Property ID:</strong> $propId<br>";
                    echo "<strong>Address:</strong> $address<br>";
                    echo "<strong>Price:</strong> $qualifier $price<br>";
                    echo "<strong>Last Changed:</strong> $lastChanged<br>";
                    echo "</li>";
                }
            }
            echo '</ul>';
        } else {
            echo '<p>No properties found or API call failed.</p>';
        }
    } else {
        echo '<p>Failed to retrieve branches.</p>';
    }
} else {
    echo '<p>Failed to authenticate with the API.</p>';
}
?>