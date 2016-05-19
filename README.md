# MojangAPI
##### PHP class to use official Mojang API

## Methods

To see all methods available, open the file `mojang-api.class.php`.

## Usage

```php
// Require API
// ======================
require 'mojang-api.php';

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
