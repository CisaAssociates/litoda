<?php
header("Content-Type: application/javascript");

// Determine environment
// Railway usually sets 'RAILWAY_ENVIRONMENT' or similar, or we can check for 'litoda-production' in hostname
// But a simpler way is: 
// If we are on localhost/127.0.0.1, default to localhost:5000
// If we are on a domain (like railway.app), use /py-api proxy

// However, since we are using a monolith docker container in Railway, 
// we might have set up ProxyPass there, but NOT on XAMPP.

$serverName = $_SERVER['SERVER_NAME'];
$isLocalhost = ($serverName === 'localhost' || $serverName === '127.0.0.1');

// If running in Docker Monolith (detected via env var or just assumption for online), use relative proxy
// If running in XAMPP, we assume Python is running on port 5000 separately
// Let's allow an override via Environment Variable if possible, but PHP-FPM/Apache might not pass it easily to this script without config.

$apiUrl = "/py-api"; // Default for Production/Docker with Proxy

if ($isLocalhost) {
    // For XAMPP local development, unless you configured ProxyPass in httpd.conf,
    // you probably need to hit Python directly.
    $apiUrl = "http://127.0.0.1:5000";
}
?>
// Generated API Configuration
const FLASK_API_URL = "<?php echo $apiUrl; ?>";
console.log("API Config Loaded: " + FLASK_API_URL);