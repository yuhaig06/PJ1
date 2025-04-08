<?php

$router = new Router();

// ...existing code...

$router->get('/auth/google/callback', 'GoogleAuthController@callback');

// ...existing code...

?>