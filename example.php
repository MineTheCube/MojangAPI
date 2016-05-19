<?php

// Require API
// ======================

require 'mojang-api.class.php';


// Fetch stuff
// ======================

$username = 'MTC';

$uuid = MojangAPI::getUuid($username);

$full_uuid = MojangAPI::formatUuid($uuid);

$history = MojangAPI::getNameHistory($uuid);

$img = '<img src="' . MojangAPI::embedImage(MojangAPI::getPlayerHead($uuid)) . '" alt="Head of ' . $username . '">';


// And print it
// ======================

echo '<h2>' . $username . '</h2>';

echo 'UUID: <b>' . $full_uuid . '</b><br>';

echo 'First username: <b>' . reset($history)['name'] . '</b><br>';

echo 'Skin:<br>';
echo $img;
