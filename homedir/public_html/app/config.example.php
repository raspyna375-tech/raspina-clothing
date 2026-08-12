<?php
return array(
    'site' => array(
        'name' => 'Raspina Clothing',
        'url' => 'https://raspinaclothing.com',
        'base_path' => '',
        'email' => 'info@raspinaclothing.com',
        'phone_display' => '+98 21 8880 0890',
        'phone_link' => '+982188800890',
        'order_mobile_display' => '+98 999 301 9987',
        'order_mobile_link' => '+989993019987',
        'instagram' => 'https://www.instagram.com/raspina.clothing/',
        'address' => 'No. 22, Ground Floor, Shahamati Alley, Valiasr Sq., Tehran, Iran',
    ),
    'database' => array(
        'enabled' => false,
        'host' => 'localhost',
        'port' => '3306',
        'name' => '',
        'user' => '',
        'password' => '',
        'prefix' => 'rc_',
    ),
    'mail' => array(
        'enabled' => false,
        'recipient' => 'info@raspinaclothing.com',
        'from' => 'website@raspinaclothing.com',
    ),
    'security' => array(
        'rate_limit_count' => 5,
        'rate_limit_window' => 600,
    ),
);
