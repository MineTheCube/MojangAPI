# MojangAPI
##### A easy-to-use PHP class for accessing official Mojang's API

## Installation

##### Add in composer.json

add in repositories list

```json

"repositories": [
    {
        "type": "git",
        "url": "git@github.com:qneyrat/MojangAPI.git"
    }
],
```

add in require list

```json

"require": {
    "mtc/mojang-api": "dev-master"
},
```

## Usage

use MojangApi\MojangAPI;

## Example

```php
use MojangApi\MojangAPI;

// Get UUID from username
$uuid = MojangAPI::getUuid('MTC');
echo 'UUID: <b>' . $uuid . '</b><br>';

// Get his name history
$history = MojangAPI::getNameHistory($uuid);
echo 'First username: <b>' . reset($history)['name'] . '</b><br>';

// Print player's head
$img = '<img src="' . MojangAPI::embedImage(MojangAPI::getPlayerHead($uuid)) . '" alt="Head of MTC">';
echo 'Skin:<br>' . $img . '<br>';

// Query a server
$query = MojangAPI::query('play.onecraft.fr', 25565);
if ($query) echo 'There is ' . $query['players'] . ' players online out of ' . $query['maxplayers'] . '<br>';
else echo 'Server is offline.<br>';
```

## Result

![Preview](http://i.imgur.com/LeCrUoe.png)
