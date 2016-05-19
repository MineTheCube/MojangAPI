# MojangAPI
##### PHP class to use official Mojang API

## Download

You only need to download this file: [mojang-api.class.php](https://github.com/MineTheCube/MojangAPI/blob/master/mojang-api.class.php)

## Methods

To see all methods available, see the MojangAPI interface: [`mojang-api.interface.php`](https://github.com/MineTheCube/MojangAPI/blob/master/mojang-api.interface.php) (not needed in your project).

## Usage

```php
// Require API
// ======================
require 'mojang-api.class.php';

// Do stuff
// ======================

$uuid = MojangAPI::getUuid('MTC');
echo 'UUID: <b>' . $uuid . '</b><br>';

$history = MojangAPI::getNameHistory($uuid);
echo 'First username: <b>' . reset($history)['name'] . '</b><br>';

$img = '<img src="' . MojangAPI::embedImage(MojangAPI::getPlayerHead($uuid)) . '" alt="Head of MTC">';
echo 'Skin:<br>' . $img;
```

## Result

![Preview](http://i.imgur.com/0HV8thN.jpg)
