<?php

/* Require API
---------------------------*/

require 'mojang-api.class.php';


/* Mojang Status
---------------------------*/

$status = MojangAPI::getStatus();
echo 'Minecraft.net: ' . $status['minecraft.net']; // Minecraft.net: green


/* UUID / Username
---------------------------*/

$username = MojangAPI::getUsername('069a79f444e94726a5befca90e38aaf5');
echo $username; // "Notch"

$uuid = MojangAPI::getUuid('Notch');
echo $uuid; // "069a79f444e94726a5befca90e38aaf5"

$full_uuid = MojangAPI::formatUuid('069a79f444e94726a5befca90e38aaf5');
echo $full_uuid; // "069a79f4-44e9-4726-a5be-fca90e38aaf5"

$uuid = MojangAPI::minifyUuid('069a79f4-44e9-4726-a5be-fca90e38aaf5');
echo $uuid; // "069a79f444e94726a5befca90e38aaf5"

$history = MojangAPI::getNameHistory('069a79f444e94726a5befca90e38aaf5');
var_dump($history); // Array with his username's history

$valid = MojangAPI::isValidUsername('=?2.;');
var_dump($valid); // false

$valid = MojangAPI::isValidUuid('069a79f444e94726a5befca90e38aaf5');
var_dump($valid); // true


/* Query / Ping
---------------------------*/

$query = MojangAPI::query('play.onecraft.fr', 25565);
if ($query) {
    echo 'There is ' . $query['players'] . ' players online out of ' . $query['maxplayers'];
} else {
    echo 'Server is offline.';
}
// Uncomment the line below to see what's returned
// var_dump($query);

$ping = MojangAPI::ping('play.onecraft.fr', 25565);
if ($ping) {
    echo 'There is ' . $ping['players']['online'] . ' players online out of ' . $ping['players']['max'];
    $img = '<img src="' . MojangAPI::embedImage($ping['favicon']) . '" alt="Favicon of server">';
    echo $img;
} else {
    echo 'Server is offline.';
}
// Uncomment the line below to see what's returned
// var_dump($ping);


/* Textures / Skins
---------------------------*/

$uuid = '069a79f4-44e9-4726-a5be-fca90e38aaf5';
$size = 80;

// We try to fetch player's head
$head = MojangAPI::getPlayerHead($uuid, $size);

// We can't get his head
if (empty($head)) {
    // So we get default skin
    if (MojangAPI::isAlex($uuid)) {
        $head = MojangAPI::getAlexHead($size);
    } else {
        $head = MojangAPI::getSteveHead($size);
    }
}

// And print it directly with <img> tag
$img = '<img src="' . MojangAPI::embedImage($head) . '" alt="Skin head">';
echo $img;


/* Authentication
---------------------------*/

$result = MojangAPI::authenticate('username@gmail.com', '123456');

if ($result) {
    echo 'Welcome ' . $result['name'] . ', your UUID is: ' . $result['id'];
} else {
    echo 'Sorry, authentication failed.. :(';
}
