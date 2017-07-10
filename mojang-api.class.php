<?php

/**
 * Fast and easy way to access Mojang API
 *
 * Can be used to get Mojang status, UUID and username conversions, names history, and fetch skin.
 * Usually, if NULL is returned, it means empty, and FALSE means failure
 * Also, UUIDs returned are minified (without dashes)
 *
 * @author MineTheCube
 * @link https://github.com/MineTheCube/MojangAPI
 * @see http://wiki.vg/Mojang_API
 */
class MojangAPI
{
    /**
     * Get Mojang status
     *
     * @return array|bool  Array with status, FALSE on failure
     */
    public static function getStatus()
    {
        $json = static::fetchJson('https://status.mojang.com/check');
        if (is_array($json)) {
            $status = array();
            foreach ($json as $array) {
                if (!empty($array)) {
                    list($key, $value) = each($array);
                    $status[$key] = $value;
                }
            }
            return $status;
        }
        return false;
    }

    /**
     * Get UUID from username, an optional time can be provided
     *
     * @param  string       $username
     * @param  int          $time optional
     * @return string|bool  UUID (without dashes) on success, FALSE on failure
     */
    public static function getUuid($username, $time = 0)
    {
        $profile = static::getProfile($username, $time);
        if (is_array($profile) and isset($profile['id'])) {
            return $profile['id'];
        }
        return false;
    }

    /**
     * Get username from UUID
     *
     * @param  string       $uuid
     * @return string|bool  Username on success, FALSE on failure
     */
    public static function getUsername($uuid)
    {
        $history = static::getNameHistory($uuid);
        if (is_array($history)) {
            $last = array_pop($history);
            if (is_array($last) and array_key_exists('name', $last)) {
                return $last['name'];
            }
        }
        return false;
    }

    /**
     * Get profile (username and UUID) from username, an optional time can be provided
     *
     * @param  string      $username
     * @param  int         $time optional
     * @return array|bool  Array with id and name, FALSE on failure
     */
    public static function getProfile($username, $time = 0)
    {
        if (static::isValidUsername($username) and is_numeric($time)) {
            return static::fetchJson(
                'https://api.mojang.com/users/profiles/minecraft/'
                . $username
                . ($time > 0 ? '?at=' . $time : '')
            );
        }
        return false;
    }

    /**
     * Get name history from UUID
     *
     * @param  string      $uuid
     * @return array|bool  Array with his username's history, FALSE on failure
     */
    public static function getNameHistory($uuid)
    {
        if (static::isValidUuid($uuid)) {
            return static::fetchJson('https://api.mojang.com/user/profiles/' . static::minifyUuid($uuid) . '/names');
        }
        return false;
    }

    /**
     * Check if string is a valid Minecraft username
     *
     * @param  string  $string to check
     * @return bool    Whether username is valid or not
     */
    public static function isValidUsername($string)
    {
        return is_string($string)
            and strlen($string) >= 2
            and strlen($string) <= 16
            and ctype_alnum(str_replace('_', '', $string));
    }

    /**
     * Check if string is a valid UUID, with or without dashes
     *
     * @param  string  $string to check
     * @return bool    Whether UUID is valid or not
     */
    public static function isValidUuid($string)
    {
        return is_string(static::minifyUuid($string));
    }

    /**
     * Remove dashes from UUID
     *
     * @param  string       $uuid
     * @return string|bool  UUID without dashes (32 chars), FALSE on failure
     */
    public static function minifyUuid($uuid)
    {
        if (is_string($uuid)) {
            $minified = str_replace('-', '', $uuid);
            if (strlen($minified) === 32) {
                return $minified;
            }
        }
        return false;
    }

    /**
     * Add dashes to an UUID
     *
     * @param  string       $uuid
     * @return string|bool  UUID with dashes (36 chars), FALSE on failure
     */
    public static function formatUuid($uuid)
    {
        $uuid = static::minifyUuid($uuid);
        if (is_string($uuid)) {
            return substr($uuid, 0, 8)
                . '-' . substr($uuid, 8, 4)
                . '-' . substr($uuid, 12, 4)
                . '-' . substr($uuid, 16, 4)
                . '-' . substr($uuid, 20, 12);
        }
        return false;
    }

    /**
     * Check if username is Alex or Steve is a valid UUID, with or without dashes
     *
     * @param  string     $uuid
     * @return bool|null  TRUE if Alex, FALSE if Steve, NULL on error
     */
    public static function isAlex($uuid)
    {
        $uuid = static::minifyUuid($uuid);
        if (is_string($uuid)) {
            $sub = array();
            for ($i = 0; $i < 4; $i++) {
                $sub[$i] = intval('0x' . substr($uuid, $i * 8, 8) + 0, 16);
            }
            return (bool) ((($sub[0] ^ $sub[1]) ^ ($sub[2] ^ $sub[3])) % 2);
        }
        return null;
    }

    /**
     * Get profile (username and UUID) from UUID
     *
     * This has a rate limit to 1 per minute per profile
     *
     * @param  string      $uuid
     * @return array|bool  Array with profile and properties, FALSE on failure
     */
    public static function getSessionProfile($uuid)
    {
        if (static::isValidUuid($uuid)) {
            return static::fetchJson('https://sessionserver.mojang.com/session/minecraft/profile/'
                . static::minifyUuid($uuid));
        }
        return false;
    }

    /**
     * Get textures (usually skin and cape, or empty array) from UUID
     *
     * This has a rate limit to 1 per minute per profile
     * @see getSessionProfile($uuid)
     *
     * @param  string      $uuid
     * @return array|bool  Array with profile and properties, FALSE on failure
     */
    public static function getTextures($uuid)
    {
        $profile = static::getSessionProfile($uuid);
        if (is_array($profile)
            and array_key_exists('properties', $profile)
            and is_array($profile['properties'])
            and !empty($profile['properties'])
            and array_key_exists('value', $profile['properties'][0])
        ) {
            $json = base64_decode($profile['properties'][0]['value']);
            if (!empty($json)) {
                $textures = static::parseJson($json);
                if (array_key_exists('textures', $textures) and is_array($textures['textures'])) {
                    return $textures['textures'];
                }
            }
        }
        return false;
    }

    /**
     * Get skin url of player
     *
     * This has a rate limit to 1 per minute per profile
     * @see getSessionProfile($uuid)
     *
     * @param  string            $uuid
     * @return string|null|bool  Skin url, NULL if he hasn't a skin, FALSE on failure
     */
    public static function getSkinUrl($uuid)
    {
        $textures = static::getTextures($uuid);
        if (is_array($textures)) {
            if (array_key_exists('SKIN', $textures)
                and array_key_exists('url', $textures['SKIN'])
                and !empty($textures['SKIN']['url'])
                and is_string($textures['SKIN']['url'])
            ) {
                return $textures['SKIN']['url'];
            }
            return null;
        }
        return false;
    }

    /**
     * Get skin (in raw png) of player
     *
     * This has a rate limit to 1 per minute per profile
     * @see getSessionProfile($uuid)
     *
     * @param  string            $uuid
     * @return string|null|bool  Skin picture, NULL if he hasn't a skin, FALSE on failure
     */
    public static function getSkin($uuid)
    {
        $skinUrl = static::getSkinUrl($uuid);
        if (is_string($skinUrl)) {
            return static::fetch($skinUrl);
        }
        return $skinUrl;
    }

    /**
     * Get player head (in raw png) of player
     *
     * This has a rate limit to 1 per minute per profile
     * @see getSessionProfile($uuid)
     *
     * @param  string            $uuid
     * @param  int               $size in pixels
     * @return string|null|bool  Player head, NULL if he hasn't a skin, FALSE on failure
     */
    public static function getPlayerHead($uuid, $size = 100)
    {
        $skin = static::getSkin($uuid);
        if (is_string($skin)) {
            return static::getPlayerHeadFromSkin($skin, $size);
        }
        return $skin;
    }

    /**
     * Get Steve skin (in raw png)
     *
     * @return string  Steve skin
     */
    public static function getSteveSkin()
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAFDUlEQVR42u2a20sUURzH97G0LKMotPuWbVpslj1olJXdjCgyisow'
            .'sSjzgrB0gSKyC5UF1ZNQWEEQSBQ9dHsIe+zJ/+nXfM/sb/rN4ZwZ96LOrnPgyxzP/M7Z+X7OZc96JpEISfWrFhK0YcU8knlozeJK'
            .'unE4HahEqSc2nF6zSEkCgGCyb+82enyqybtCZQWAzdfVVFgBJJNJn1BWFgC49/VpwGVlD0CaxQiA5HSYEwBM5sMAdKTqygcAG9+8'
            .'coHKY/XXAZhUNgDYuBSPjJL/GkzVVhAEU5tqK5XZ7cnFtHWtq/TahdSw2l0HUisr1UKIWJQBAMehDuqiDdzndsP2EZECAG1ZXaWM'
            .'wOCODdXqysLf++uXUGv9MhUHIByDOijjdiSAoH3ErANQD73C7TXXuGOsFj1d4YH4OTJAEy8y9Hd0mCaeZ5z8dfp88zw1bVyiYhCL'
            .'Og1ZeAqC0ybaDttHRGME1DhDeVWV26u17lRAPr2+mj7dvULfHw2q65fhQRrLXKDfIxkau3ZMCTGIRR3URR5toU38HbaPiMwUcKfB'
            .'Akoun09PzrbQ2KWD1JJaqswjdeweoR93rirzyCMBCmIQizqoizZkm2H7iOgAcHrMHbbV9KijkUYv7qOn55sdc4fo250e+vUg4329'
            .'/Xk6QB/6DtOws+dHDGJRB3XRBve+XARt+4hIrAF4UAzbnrY0ve07QW8uHfB+0LzqanMM7qVb+3f69LJrD90/1axiEIs6qIs21BTI'
            .'ToewfcSsA+Bfb2x67OoR1aPPzu2i60fSNHRwCw221Suz0O3jO+jh6V1KyCMGse9721XdN5ePutdsewxS30cwuMjtC860T5JUKpXy'
            .'KbSByUn7psi5l+juDlZYGh9324GcPKbkycaN3jUSAGxb46IAYPNZzW0AzgiQ5tVnzLUpUDCAbakMQXXrOtX1UMtHn+Q9/X5L4wgl'
            .'7t37r85OSrx+TYl379SCia9KXjxRpiTjIZTBFOvrV1f8ty2eY/T7XJ81FQAwmA8ASH1ob68r5PnBsxA88/xAMh6SpqW4HRnLBrkO'
            .'A9Xv5wPAZjAUgOkB+SHxgBgR0qSMh0zmZRsmwDJm1gFg2PMDIC8/nAHIMls8x8GgzOsG5WiaqREgYzDvpTwjLDy8NM15LpexDEA3'
            .'LepjU8Z64my+8PtDCmUyRr+fFwA2J0eAFYA0AxgSgMmYBMZTwFQnO9RNAEaHOj2DXF5UADmvAToA2ftyxZYA5BqgmZZApDkdAK4m'
            .'AKo8GzPlr8G8AehzMAyA/i1girUA0HtYB2CaIkUBEHQ/cBHSvwF0AKZFS5M0ZwMQtEaEAmhtbSUoDADH9ff3++QZ4o0I957e+zYA'
            .'Mt6wHkhzpjkuAcgpwNcpA7AZDLsvpwiuOkBvxygA6Bsvb0HlaeKIF2EbADZpGiGzBsA0gnwQHGOhW2snRpbpPexbAB2Z1oicAMQp'
            .'TnGKU5ziFKc4xSlOcYpTnOIUpzgVmgo+XC324WfJAdDO/+ceADkCpuMFiFKbApEHkOv7BfzfXt+5gpT8V7rpfYJcDz+jAsB233r6'
            .'yyBsJ0mlBCDofuBJkel4vOwBFPv8fyYAFPJ+wbSf/88UANNRVy4Awo6+Ig2gkCmgA5DHWjoA+X7AlM//owLANkX0w0359od++pvX'
            .'8fdMAcj3/QJ9iJsAFPQCxHSnQt8vMJ3v2wCYpkhkAOR7vG7q4aCXoMoSgG8hFAuc/grMdAD4B/kHl9da7Ne9AAAAAElFTkSuQmCC'
        );
    }

    /**
     * Get Alex skin (in raw png)
     *
     * @return string  Alex skin
     */
    public static function getAlexSkin()
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAAAXNSR0IArs4c6QAAAAZiS0dEAIwAuACKS3UjegAAAAlwSFlzAAAL'
            .'EwAACxMBAJqcGAAAAAd0SU1FB94IFA0kCPwApjEAAAAWaVRYdENvbW1lbnQAAAAAAG1vZGVsPXNsaW1TpLy5AAAIAElEQVR42uWa'
            .'728URRjH968wanznj5DoC9BASIopJFKgHFIbCUmlNQVLGhoDoqRa0rQaAgJFDRoSEuGNJmITrVrPKIgmGo0EXlg1/CYkBIwoUOUt'
            .'L9b7Pt3v9NnnZq/X2722XDf5ZmZndu/2+5lnZnd2Nggm2K4dXBaK9tWHRflCerb/8ZIKUm6XD+bGNLC0OF9IR/oWllTqTZslgIvv'
            .'LpL0r0M5Z3Rwd1tMmQFQZgng9P4lkl451OyMfrS7PabsAESGYZYgBEAEhObf394UEyFkEQEwDLMEIQAiIDR/sKc5JkLIJAJiAFQ3'
            .'QHnVAUSGHQDVDVA+JQAgAaC6A/YB4PLPH1QdACQAVHfAPgBc+unDKgMwg57u/1DVAZhBT/d/qOoAXH/XdwEVBQTgUxYAXH/XdwEV'
            .'BQTgUyYA9ODHVrddoJTS/r8e/NjqtguUUtkhTqM0afu8Sz23Rd9xGpwv5e9rczq0bZ93qee26DtOg/Ol/P0g0UjChXMMsGaSoLly'
            .'TxShLNFIwoVzDLBmkqC5ck8UoSwodeHWKMz/8/uwKNbSBGZa3oLRUcbfLHXh1ijMX/8tL4q1NIGZlrdgdJQ5ALZV2bdZxjzN3z5/'
            .'NLx68mORHhTdb+hWNpB83cK2qr0D6A3G/z33bcljYq1sIPm6RRALadPKyMPonTt3wgfXPyTmb1/6QfIoQ51+Kiw1JmjAOh8L6cgM'
            .'Wxnby40PBIX/CuZvX+BMIo8y1OnHZd9t044XOrKQd7c5try0csGkmC3o1umvwtFzx5x5SS8cD2+d+UbyBOU16ukWsf2CeJvjqC6t'
            .'fPFHaWnb2iy3UeEgWqOebhHbly5g+jzDXMzScJSOHvtcRDiE5SAkjPR6ULVji+3zDHMxSxBRevP4sIhwCMtBSBjp9aBqx5bYkx5D'
            .'X0eAky5TcIrMT3Rb5HjBByl1wQx9HQFOukzBKTI/0W2RY05UVrS9sOSxUGvOnDkxTfRc0ZPvDnfuai96L0AAnUvnltSEvz/UHVJn'
            .'j74nDYD0mXkPB1DqLSsAWgDA8M8EQP4uAcDHYeSrAYDmkW5aPDeAZgwAN5gWpMeGLLtAJgAc0YgqLuK1NU+G/S2Lwxcbn5B9gGA6'
            .'8N0eUe8XPSJ3bnQ+6iwA+zTonXeY2+jr+b5w79E3Q93i/H2Uy3+ba0c564OhoUBUTovzx2Fy69MLBACkzTO/OTdf4EA4Vl8AL3DP'
            .'3o0xAIwCfSfg8XbuAeM07wBQ+W5nHv9j/5vnoG5SALRRGiSMpHoAEgCeFpIL3Nc5Pm9QzwDWEA1TbMEYABVlupVtBLB80gAo3Rdp'
            .'cLIAuoe2uW4CCBDNx7pMdJ6+aG0cxyLtf6Ml3LV7vQh5dK+33+mStFRdRQAY4jvbGkTI23o7aNkW0gAYqmL+yx5vSPsAOIAFXT1x'
            .'JCZGFVSqbrB3YwBNuNXV1YUUDb767CIRW14f09DQEFMsAgopTWtT2lwMwFC8T2sATK05/RiuQbCOadkAVqxYEVIw2NLSEra2toqQ'
            .'R5k+ZtWqVeHatWtFyFsAtvWtIdtldIszz3MJQJslgOu/Dsn+jT++LsxKByXVj+oVAYDa29vDrq4uEfK2vghAPt4FeOHsCtpMDIA5'
            .'Xh+rz4ExGkV6fWQcwui541J+7eQn4ej578fhjAxPHgBb2gdAR4EFcO/Ce8L76+9z6er+XAyAHhNQh2N8x8uxn70SOwd1MKglAKLJ'
            .'GPZhnHVukjaZCIAJqLm52XWBDRs2iNgFUMfjGhsbw6amJhHyOvyRwhilzXJfD4CQrddwIJqDUWllmrz8SzGAQhnq/i7kD2x+LoAm'
            .'BWDdunVFEYCyyQCwfZ63s9gY4Dnejhssp3GaFZORUdaxnuVQ2QDq6+tDCmbR8lu2bBEhjzJ9zPLly8NcLidC3vc4irvHtqaFMaFM'
            .'7gL5ZABWqBPjUV93AFQX0FFQEYDp2jo6Oio7cWAgCAYHJcXkh9PgzKbDM36D+VOnJOUMcHYCKEQATRNEJu8DZvymuoA1X5sA2tqC'
            .'YNOmce3YMQYB0uXQ/v1j6ukZ112/rVwZBJ2dY+rrG5vhHTkSBIcPj+1Dug4iCKgmIkADgHkanZUA0PIwOVsiYOtTjwYHnl8i2rWm'
            .'LuAzPoR9iPUsf6tzjVNNAsATnqSqvGYBdNQ94gUgmm0AtEkLQHePmgRgW1kDsONDTY4BFoAeA2x09LbmnGoaQNJdoCYBUDCF0Eb6'
            .'0rJ5Qe/qBU4sn1YAaRdXZfXIs/xd9mSHk6Voujzl7wvSAtBLaBUBgPkIxLS8L8gCANYS7fJ32QYIoJBOy/uC1ABg3vMBRNkApvp9'
            .'gf1+AN8UcPFUL6vr5XW98KFfkLL1Sy2j4Rwc4yY+0/2+wK4ec2XZri4nfl+gDPL7gaKl8VLr/3hfwI0zQkYCZ4u6LuvZogUAU0hh'
            .'kKvLZS2vm/V/3xcg3vV/RIAGgPcF2PR0udoA9NI5vzDxhb8PQOr1fwsAxrHpFyZTBaCS7wvSrv/jQYkb5wOMAD4pYqvabDHt9wVp'
            .'1/+TAOi5QlUBpP2+IGn9n2b18rhv/R+zxRkDoJLvC7RBrAHq9X+WY/0/CcCVM58GN/88IUL+6oXh4L8bI5JyX+dZlzmASr8vmGj9'
            .'P6nOFwFsZcmr9wU6OjKPgLTfFxSt8EYGYTapzkZAUiuXqststpj2+wK99u9MqvV/DcHVFeQbBHUE6PcFdgzIdLqc9vsCGGNft2v8'
            .'3k9gPAB87wtQV877gv8BjY2wPg7jcKEAAAAASUVORK5CYII='
        );
    }

    /**
     * Get Steve head (in raw png)
     *
     * @return string  Steve head
     */
    public static function getSteveHead($size = 100)
    {
        return static::getPlayerHeadFromSkin(static::getSteveSkin(), $size);
    }

    /**
     * Get Alex head (in raw png)
     *
     * @return string  Alex head
     */
    public static function getAlexHead($size = 100)
    {
        return static::getPlayerHeadFromSkin(static::getAlexSkin(), $size);
    }

    /**
     * Get player head (in raw png) from skin
     *
     * @param  string       $skin returned by getSkin($uuid)
     * @param  int          $size in pixels
     * @return string|bool  Player head, FALSE on failure
     */
    public static function getPlayerHeadFromSkin($skin, $size = 100)
    {
        if (is_string($skin)) {
            $im = @imagecreatefromstring($skin);

            if (is_resource($im)) {
                $av = imagecreatetruecolor($size, $size);

                imagecopyresized($av, $im, 0, 0, 8, 8, $size, $size, 8, 8);         // Face
                imagecolortransparent($im, imagecolorat($im, 63, 0));               // Black Hat Issue
                imagecopyresized($av, $im, 0, 0, 8 + 32, 8, $size, $size, 8, 8);    // Accessories

                ob_start();
                imagepng($av);
                $img = ob_get_clean();

                imagedestroy($im);
                imagedestroy($av);

                return $img;
            }
        }

        return false;
    }

    /**
     * Print image from raw png
     *
     * Nothing should be displayed on the page other than this image
     *
     * @param  string  $img
     * @param  int     $cache in seconds, 0 to disable
     */
    public static function printImage($img, $cache = 86400)
    {
        header('Content-type: image/png');
        header('Pragma: public');
        header('Cache-Control: max-age=' . $cache);
        header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + $cache));
        echo $img;
    }

    /**
     * Embed image for <img> tag
     *
     * @param  string  $img
     * @return string  embed image
     */
    public static function embedImage($img)
    {
        return substr($img, 0, strlen('data:image')) === 'data:image'
            ? $img
            : 'data:image/png;base64,' . base64_encode($img);
    }

    /**
     * Authenticate with a Minecraft account
     *
     * After a few fails, Mojang server will deny all requests !
     *
     * @param  string      $id Minecraft username or Mojang email
     * @param  string      $password Account's password
     * @return array|bool  Array with id and name, FALSE if authentication failed
     */
    public static function authenticate($id, $password)
    {
        if (!function_exists('curl_init') or !extension_loaded('curl')) {
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://authserver.mojang.com/authenticate');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
            'agent' => array(
                'name' => 'Minecraft',
                'version' => 1,
            ),
            'username' => $id,
            'password' => $password
        )));

        $output = curl_exec($ch);
        curl_close($ch);
        $json = static::parseJson($output);

        if (is_array($json)
            and array_key_exists('selectedProfile', $json)
            and is_array($json['selectedProfile'])
            and array_key_exists('id', $json['selectedProfile'])
            and array_key_exists('name', $json['selectedProfile'])
        ) {
            return array(
                'id' => $json['selectedProfile']['id'],
                'name' => $json['selectedProfile']['name']
            );
        }

        return false;
    }

    /**
     * Query a Minecraft server
     *
     * @see https://github.com/xPaw/PHP-Minecraft-Query/
     *
     * @param  string      $address Server's address
     * @param  int         $port    Server's port, default is 25565
     * @param  int         $timeout Timeout (in seconds), default is 2
     * @return array|bool  Array with query result, FALSE if query failed
     */
    public static function query($address, $port = 25565, $timeout = 2)
    {

        // Check arguments and if functions exists
        if (!is_numeric($timeout) or $timeout < 0 or !function_exists('fsockopen')) {
            return false;
        }

        // Try to connect
        $start = microtime(true);
        $socket = @fsockopen('udp://' . $address, (int) $port, $errNo, $errStr, $timeout);
        if ($errNo or $socket === false) {
            return false;
        }

        // Set read/write timeout
        stream_set_timeout($socket, $timeout);
        stream_set_blocking($socket, true);

        // Send handshake
        $data = static::queryWriteData($socket, 0x09);
        if ($data === false) {
            return false;
        }

        // And query
        $challenge = pack('N', $data);
        $data = static::queryWriteData($socket, 0x00, $challenge . pack('c*', 0x00, 0x00, 0x00, 0x00));

        if (!$data) {
            return false;
        }

        $last = '';
        $info = array();

        // Extract data
        $data = substr($data, 11);
        $data = explode("\x00\x00\x01player_\x00\x00", $data);
        if (count($data) !== 2) {
            return false;
        }

        $players = substr($data[1], 0, -2);
        $data = explode("\x00", $data[0]);

        // Array with known keys in order to validate the result
        $keys = array(
            'hostname'   => 'motd',
            'gametype'   => 'gametype',
            'version'    => 'version',
            'plugins'    => 'plugins',
            'map'        => 'map',
            'numplayers' => 'players',
            'maxplayers' => 'maxplayers',
            'hostport'   => 'hostport',
            'hostip'     => 'hostip',
            'game_id'    => 'gamename'
        );

        $mb_convert_encoding = function_exists('mb_convert_encoding');
        foreach ($data as $key => $value) {
            if (~$key & 1) {
                if (!array_key_exists($value, $keys)) {
                    $last = false;
                    continue;
                }

                $last = $keys[$value];
                $info[$last] = '';
            } elseif ($last !== false) {
                if (strlen($value)) {
                    $info[$last] = $mb_convert_encoding ? mb_convert_encoding($value, 'UTF-8') : $value;
                } else {
                    $info[$last] = null;
                }
            }
        }

        // Ints
        $info['players']    = intval($info['players']);
        $info['maxplayers'] = intval($info['maxplayers']);
        $info['hostport']   = intval($info['hostport']);

        // Parse "plugins" if any
        if ($info['plugins']) {
            $data = explode(": ", $info['plugins'], 2);
            $info['rawplugins'] = $info['plugins'];
            $info['software']   = $data[0];
            if (count($data) == 2) {
                $info['plugins'] = explode("; ", $data[1]);
            }
        }

        if (empty($players)) {
            $info['playerlist'] = null;
        } else {
            $info['playerlist'] = explode("\x00", $players);
        }

        $info['timeout'] = microtime(true) - $start;

        fclose($socket);
        return $info;
    }

    /**
     * Ping a Minecraft server
     *
     * @see https://github.com/xPaw/PHP-Minecraft-Query/
     *
     * @param  string      $address Server's address
     * @param  int         $port    Server's port, default is 25565
     * @param  int         $timeout Timeout (in seconds), default is 2
     * @return array|bool  Array with query result, FALSE if query failed
     */
    public static function ping($address, $port = 25565, $timeout = 2)
    {

        // Check arguments and if functions exists
        if (!is_numeric($timeout) or $timeout < 0 or !function_exists('fsockopen')) {
            return false;
        }

        // Try to connect
        $start = microtime(true);
        $socket = @fsockopen($address, (int) $port, $errNo, $errStr, $timeout);
        if ($errNo or $socket === false) {
            return false;
        }

        // Set read/write timeout
        stream_set_timeout($socket, $timeout);

        // See http://wiki.vg/Protocol (Status Ping)
        $packet = "\x00\x04" . pack('c', strlen($address)) . $address . pack('n', $port) . "\x01";
        $data = pack('c', strlen($packet)) . $packet;

        // Send handshake and ping
        fwrite($socket, $data);
        fwrite($socket, "\x01\x00");

        // Read response
        $packetLength = static::pingReadVarInt($socket);
        if ($packetLength === false or $packetLength < 10) {
            return false;
        }

        fgetc($socket);
        $length = static::pingReadVarInt($socket);
        if ($length === false) {
            return false;
        }

        $data = '';
        do {
            if (microtime(true) - $start > $timeout) {
                return false;
            }

            $remainder = $length - strlen($data);
            $block = fread($socket, $remainder);
            if (!$block) {
                return false;
            }

            $data .= $block;
        } while (strlen($data) < $length);

        $data = static::parseJson($data);
        if (empty($data)
            or json_last_error() !== JSON_ERROR_NONE
            or !array_key_exists('players', $data)
            or !is_array($data['players'])
            or !array_key_exists('online', $data['players'])
        ) {
            return false;
        }

        if (array_key_exists('description', $data)
            and is_array($data['description'])
            and array_key_exists('extra', $data['description'])
            and empty($data['description']['text'])
        ) {
            $motd = '';
            foreach ($data['description']['extra'] as $key => $value) {
                $motd .= $value['text'];
            }

            $data['description']['text'] = $motd;
        }

        $data['timeout'] = microtime(true) - $start;

        return $data;
    }

    /**
     * Parse JSON
     *
     * @param  string       $json
     * @return string|bool  json data, FALSE on failure
     */
    private static function parseJson($json)
    {
        $data = json_decode($json, true);
        return empty($data) ? false : $data;
    }

    /**
     * Fetch url content
     *
     * @param  string        $url
     * @return string|false  content, FALSE on failure
     */
    private static function fetch($url)
    {
        if (function_exists('curl_init') and extension_loaded('curl')) {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            $output = curl_exec($ch);
            curl_close($ch);

            return $output;
        } else {
            return @file_get_contents($url);
        }
    }

    /**
     * Fetch url content as JSON
     *
     * @param  string       $url
     * @return array|false  json data, FALSE on failure
     */
    private static function fetchJson($url)
    {
        $output = static::fetch($url);

        if (!empty($output)) {
            $json = static::parseJson($output);
            if (is_array($json) and !array_key_exists('error', $json)) {
                return $json;
            }
        }

        return false;
    }

    /**
     * Write data in a socket for query
     *
     * @param  socket        $socket
     * @param  string        $command
     * @param  string        $append, default is ''
     * @return string|false  data, FALSE on failure
     */
    private static function queryWriteData($socket, $command, $append = '')
    {
        $command = pack('c*', 0xFE, 0xFD, $command, 0x01, 0x02, 0x03, 0x04) . $append;
        $length  = strlen($command);

        if ($length !== fwrite($socket, $command, $length)) {
            return false;
        }

        $data = fread($socket, 4096);
        if ($data === false or strlen($data) < 5 or $data[0] != $command[2]) {
            return false;
        }

        return substr($data, 5);
    }

    /**
     * Read var int in a socket for ping
     *
     * @param  socket    $socket
     * @return int|false var int, FALSE on failure
     */
    private static function pingReadVarInt($socket)
    {
        $i = $j = 0;

        while (true) {
            $k = @fgetc($socket);
            if ($k === false) {
                return false;
            }

            $k = ord($k);
            $i |= ($k & 0x7F) << $j++ * 7;
            if ($j > 5) {
                return false;
            }

            if (($k & 0x80) != 128) {
                break;
            }
        }

        return $i;
    }
}
