<?php
// Redirect to a specific page
function redirect($page) {
    header('location: ' . URLROOT . '/' . $page);
    exit;
}

// Get current URL
function currentURL() {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}

// Get base URL
function baseURL() {
    return URLROOT;
}

// Get asset URL
function assetURL($path) {
    return URLROOT . '/assets/' . $path;
}

// Sanitize URL
function sanitizeURL($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}

// Check if URL is valid
function isValidURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL);
}

// Get query string parameters
function getQueryParams() {
    return $_GET;
}

// Get specific query parameter
function getQueryParam($key, $default = null) {
    return isset($_GET[$key]) ? $_GET[$key] : $default;
}

// Build query string
function buildQueryString($params) {
    return http_build_query($params);
}

// Add query parameter to URL
function addQueryParam($url, $key, $value) {
    $parsed = parse_url($url);
    $query = [];
    if (isset($parsed['query'])) {
        parse_str($parsed['query'], $query);
    }
    $query[$key] = $value;
    $parsed['query'] = buildQueryString($query);
    return buildURL($parsed);
}

// Build URL from parts
function buildURL($parts) {
    $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
    $host = isset($parts['host']) ? $parts['host'] : '';
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    $path = isset($parts['path']) ? $parts['path'] : '';
    $query = isset($parts['query']) ? '?' . $parts['query'] : '';
    $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
    return $scheme . $host . $port . $path . $query . $fragment;
} 