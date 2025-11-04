<?php
// Simple health check for App Engine
// No database, no sessions, no dependencies
http_response_code(200);
header('Content-Type: text/plain');
echo 'OK';
