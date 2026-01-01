<?php

use Illuminate\Support\Facades\Session;

if (!function_exists('currentUser')) {
    /**
     * Get current authenticated user from session
     * 
     * @return array|null
     */
    function currentUser()
    {
        return Session::get('user');
    }
}

if (!function_exists('authToken')) {
    /**
     * Get current auth token from session
     * 
     * @return string|null
     */
    function authToken()
    {
        return Session::get('auth_token');
    }
}

if (!function_exists('isAuthenticated')) {
    /**
     * Check if user is authenticated
     * 
     * @return bool
     */
    function isAuthenticated()
    {
        return Session::has('auth_token') && Session::has('user');
    }
}

if (!function_exists('userName')) {
    /**
     * Get current user name
     * 
     * @return string
     */
    function userName()
    {
        $user = currentUser();
        return $user['name'] ?? 'Guest';
    }
}

if (!function_exists('userEmail')) {
    /**
     * Get current user email
     * 
     * @return string
     */
    function userEmail()
    {
        $user = currentUser();
        return $user['email'] ?? '';
    }
}
