<?php
header("Content-Type: application/javascript");
// Since we are running via Docker Monolith, we expose the same public URL for both.
// However, the Python API is internal-only (127.0.0.1:5000).
// JS needs to talk to Python API?
// Wait, JS runs in the browser. It cannot access 127.0.0.1:5000 of the server.
// We need to Proxy the requests or Expose Python to the world?
// Since we are using Apache, we can ProxyPass /api/py/ to localhost:5000
// OR we can expose it on a different port? Railway only exposes one port.
//
// BEST SOLUTION: Use Apache ProxyPass to forward requests from /py-api/ to localhost:5000
// This requires mod_proxy and mod_proxy_http which are enabled in Dockerfile (need to verify).

$apiUrl = ""; // Relative path will be used if we set up Proxy
?>
// We will use a relative path that Apache proxies to the Python app
const FLASK_API_URL = "/py-api";
