<?php
header("Content-Type: application/javascript");
$apiUrl = getenv('FLASK_API_URL') ?: 'http://127.0.0.1:5000';
?>
const FLASK_API_URL = "<?php echo $apiUrl; ?>";
