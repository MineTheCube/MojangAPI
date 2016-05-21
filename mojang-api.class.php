<?php

/**
 * Fast and easy way to access Mojang API
 * 
 * Can be used to get Mojang status, UUID and username conversions, names history, and fetch skin.
 * 
 * @see http://wiki.vg/Mojang_API
 */
class MojangAPI {

    /**
     * Get Mojang status
     *
     * @return array|bool Array with status, false on failure
     */ 
    public static function getStatus() {
        $json = self::fetchJson('https://status.mojang.com/check');
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
     * @return string|bool  UUID (without dashes) on success, false on failure
     */ 
    public static function getUuid($username, $time = 0) {
        $profile = self::getProfile($username, $time);
        if (is_array($profile) and isset($profile['id'])) {
            return $profile['id'];
        }
        return false;
    }

    /**
     * Get username from UUID
     *
     * @param  string       $uuid
     * @return string|bool  Username on success, false on failure
     */
    public static function getUsername($uuid) {
        $history = self::getNameHistory($uuid);
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
     * @return array|bool  Array with id and name, false on failure
     */ 
    public static function getProfile($username, $time = 0) {
        if (self::isValidUsername($username) and is_numeric($time)) {
            return self::fetchJson('https://api.mojang.com/users/profiles/minecraft/' . $username . ($time > 0 ? '?at=' . $time : ''));
        }
        return false;
    }
    
    /**
     * Get name history from UUID
     *
     * @param  string      $uuid
     * @return array|bool  Array with his username's history, false on failure
     */ 
    public static function getNameHistory($uuid) {
        if (self::isValidUuid($uuid)) {
            return self::fetchJson('https://api.mojang.com/user/profiles/' . self::minifyUuid($uuid) . '/names');
        }
        return false;
    }

    /**
     * Check if string is a valid Minecraft username
     *
     * @param  string $string to check
     * @return bool   Whether username is valid or not
     */
    public static function isValidUsername($string) {
        return is_string($string) and strlen($string) >= 2 and strlen($string) <= 16 and ctype_alnum(str_replace('_', '', $string));
    }

    /**
     * Check if string is a valid UUID, with or without dashes
     *
     * @param  string $string to check
     * @return bool   Whether UUID is valid or not
     */
    public static function isValidUuid($string) {
        return is_string(self::minifyUuid($string));
    }

    /**
     * Remove dashes from UUID
     *
     * @param  string       $uuid
     * @return string|bool  UUID without dashes (32 chars), false on failure
     */ 
    public static function minifyUuid($uuid) {
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
     * @return string|bool  UUID with dashes (36 chars), false on failure
     */
    public static function formatUuid($uuid) {
        $uuid = self::minifyUuid($uuid);
        if (is_string($uuid)) {
            return substr($uuid, 0, 8) . '-' . substr($uuid, 8, 4) . '-' . substr($uuid, 12, 4) . '-' . substr($uuid, 16, 4) . '-' . substr($uuid, 20, 12);
        }
        return false;
    }

    /**
     * Check if username is Alex or Steve is a valid UUID, with or without dashes
     *
     * @param  string    $uuid
     * @return bool|null TRUE if Alex, FALSE if Steve, NULL on error
     */
    public static function isAlex($uuid) {
        $uuid = self::minifyUuid($uuid);
        if (is_string($uuid)) {
            $sub = array();
            for ($i = 0; $i < 4; $i++) {
                $sub[$i] = intval('0x' . substr($s, $i * 8, 8) + 0, 16);
            }
            return (bool) ((($sub[0] ^ $sub[1]) ^ ($sub[2] ^ $sub[3])) % 2);
        }
        return null;
    }

    /**
     * Get profile (username and UUID) from uuid
     *
     * This has a rate limit to 1 per minute per profile
     *
     * @param  string      $uuid
     * @return array|bool  Array with profile and properties, false on failure
     */
    public static function getSessionProfile($uuid) {
        if (self::isValidUuid($uuid)) {
            return self::fetchJson('https://sessionserver.mojang.com/session/minecraft/profile/' . self::minifyUuid($uuid));
        }
        return false;
    }

    /**
     * Get textures (usually skin and cape) from uuid
     *
     * This has a rate limit to 1 per minute per profile
     * @see getSessionProfile($uuid)
     *
     * @param  string      $uuid
     * @return array|bool  Array with profile and properties, false on failure
     */
    public static function getTextures($uuid) {
        $profile = self::getSessionProfile($uuid);
        if (is_array($profile) and array_key_exists('properties', $profile) and is_array($profile['properties']) and !empty($profile['properties']) and array_key_exists('value', $profile['properties'][0])) {
            $json = base64_decode($profile['properties'][0]['value']);
            if (!empty($json)) {
                $textures = self::parseJson($json);
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
     * @param  string      $uuid
     * @return string|bool Skin url, false on failure
     */
    public static function getSkinUrl($uuid) {
        $textures = self::getTextures($uuid);
        if (is_array($textures) and array_key_exists('SKIN', $textures) and array_key_exists('url', $textures['SKIN']) and !empty($textures['SKIN']['url'])) {
            return $textures['SKIN']['url'];
        }
        return false;
    }

    /**
     * Get skin (in raw png) of player
     *
     * This has a rate limit to 1 per minute per profile
     * @see getSessionProfile($uuid)
     *
     * @param  string      $uuid
     * @return string|bool Skin picture, false on failure
     */
    public static function getSkin($uuid) {
        $skinUrl = self::getSkinUrl($uuid);
        if (is_string($skinUrl)) {
            return self::fetch($skinUrl);
        }
        return false;
    }

    /**
     * Get player head (in raw png) of player
     *
     * This has a rate limit to 1 per minute per profile
     * @see getSessionProfile($uuid)
     *
     * @param  string      $uuid
     * @param  int         $size in pixels
     * @return string|bool Player head, false on failure
     */
    public static function getPlayerHead($uuid, $size = 100) {
        return self::getPlayerHeadFromSkin(self::getSkin($uuid), $size);
    }

    /**
     * Get steve skin (in raw png) of player
     *
     * @return string Steve skin
     */
    public static function getSteveSkin() {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAAFDUlEQVR42u2a20sUURzH97G0LKMotPuWbVpslj1olJXdjCgyisowsSjzgrB0gSKyC5UF1ZNQWEEQSBQ9dHsIe+zJ/+nXfM/sb/rN4ZwZ96LOrnPgyxzP/M7Z+X7OZc96JpEISfWrFhK0YcU8knlozeJKunE4HahEqSc2nF6zSEkCgGCyb+82enyqybtCZQWAzdfVVFgBJJNJn1BWFgC49/VpwGVlD0CaxQiA5HSYEwBM5sMAdKTqygcAG9+8coHKY/XXAZhUNgDYuBSPjJL/GkzVVhAEU5tqK5XZ7cnFtHWtq/TahdSw2l0HUisr1UKIWJQBAMehDuqiDdzndsP2EZECAG1ZXaWMwOCODdXqysLf++uXUGv9MhUHIByDOijjdiSAoH3ErANQD73C7TXXuGOsFj1d4YH4OTJAEy8y9Hd0mCaeZ5z8dfp88zw1bVyiYhCLOg1ZeAqC0ybaDttHRGME1DhDeVWV26u17lRAPr2+mj7dvULfHw2q65fhQRrLXKDfIxkau3ZMCTGIRR3URR5toU38HbaPiMwUcKfBAkoun09PzrbQ2KWD1JJaqswjdeweoR93rirzyCMBCmIQizqoizZkm2H7iOgAcHrMHbbV9KijkUYv7qOn55sdc4fo250e+vUg4329/Xk6QB/6DtOws+dHDGJRB3XRBve+XARt+4hIrAF4UAzbnrY0ve07QW8uHfB+0LzqanMM7qVb+3f69LJrD90/1axiEIs6qIs21BTIToewfcSsA+Bfb2x67OoR1aPPzu2i60fSNHRwCw221Suz0O3jO+jh6V1KyCMGse9721XdN5ePutdsewxS30cwuMjtC860T5JUKpXyKbSByUn7psi5l+juDlZYGh9324GcPKbkycaN3jUSAGxb46IAYPNZzW0AzgiQ5tVnzLUpUDCAbakMQXXrOtX1UMtHn+Q9/X5L4wgl7t37r85OSrx+TYl379SCia9KXjxRpiTjIZTBFOvrV1f8ty2eY/T7XJ81FQAwmA8ASH1ob68r5PnBsxA88/xAMh6SpqW4HRnLBrkOA9Xv5wPAZjAUgOkB+SHxgBgR0qSMh0zmZRsmwDJm1gFg2PMDIC8/nAHIMls8x8GgzOsG5WiaqREgYzDvpTwjLDy8NM15LpexDEA3LepjU8Z64my+8PtDCmUyRr+fFwA2J0eAFYA0AxgSgMmYBMZTwFQnO9RNAEaHOj2DXF5UADmvAToA2ftyxZYA5BqgmZZApDkdAK4mAKo8GzPlr8G8AehzMAyA/i1girUA0HtYB2CaIkUBEHQ/cBHSvwF0AKZFS5M0ZwMQtEaEAmhtbSUoDADH9ff3++QZ4o0I957e+zYAMt6wHkhzpjkuAcgpwNcpA7AZDLsvpwiuOkBvxygA6Bsvb0HlaeKIF2EbADZpGiGzBsA0gnwQHGOhW2snRpbpPexbAB2Z1oicAMQpTnGKU5ziFKc4xSlOcYpTnOIUpzgVmgo+XC324WfJAdDO/+ceADkCpuMFiFKbApEHkOv7BfzfXt+5gpT8V7rpfYJcDz+jAsB233r6yyBsJ0mlBCDofuBJkel4vOwBFPv8fyYAFPJ+wbSf/88UANNRVy4Awo6+Ig2gkCmgA5DHWjoA+X7AlM//owLANkX0w0359od++pvX8fdMAcj3/QJ9iJsAFPQCxHSnQt8vMJ3v2wCYpkhkAOR7vG7q4aCXoMoSgG8hFAuc/grMdAD4B/kHl9da7Ne9AAAAAElFTkSuQmCC');
    }

    /**
     * Get alex skin (in raw png) of player
     *
     * @return string Steve skin
     */
    public static function getAlexSkin() {
        return base64_decode('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');
    }

    /**
     * Get player head (in raw png) of steve
     *
     * @return string Steve head
     */
    public static function getSteveHead($size = 100) {
        return self::getPlayerHeadFromSkin(self::getSteveSkin(), $size);
    }

    /**
     * Get player head (in raw png) of alex
     *
     * @return string Alex head
     */
    public static function getAlexHead($size = 100) {
        return self::getPlayerHeadFromSkin(self::getAlexSkin(), $size);
    }

    /**
     * Get player head (in raw png) from skin
     *
     * @param  string      $skin returned by getSkin($uuid)
     * @param  int         $size in pixels
     * @return string|bool Player head, false on failure
     */
    public static function getPlayerHeadFromSkin($skin, $size = 100) {
        if (is_string($skin)) {

            $im = imagecreatefromstring($skin);
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
        return false;
    }

    /**
     * Print image from raw png
     *
     * Nothing should be displayed on the page other than this image
     *
     * @param  string      $img
     * @param  int         $cache in seconds, 0 to disable
     */
    public static function printImage($img, $cache = 86400) {
        header('Content-type: image/png');
        header('Pragma: public');
        header('Cache-Control: max-age=86400');
        header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + $cache));
        echo $img;
    }

    /**
     * Embed image for <img> tag
     *
     * @param  string $img
     * @return string embed image
     */
    public static function embedImage($img) {
        return 'data:image/png;base64,' . base64_encode($img);
    }

    /**
     * Parse JSON
     *
     * @param  string      $json
     * @return string|bool json data, false on failure
     */
    private static function parseJson($json) {
        $data = json_decode($json, true);
        return empty($data) ? false : $data;
    }

    /**
     * Fetch url content
     *
     * @param  string       $url
     * @return string|false content, false on failure
     */
    private static function fetch($url) {
        if (function_exists('curl_init') and extension_loaded('curl')) {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); 
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

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
     * @param  string      $url
     * @return array|false json data, false on failure
     */
    private static function fetchJson($url) {
        $output = self::fetch($url);

        if (!empty($output)) {
            $json = self::parseJson($output);
            if (is_array($json) and !array_key_exists('error', $json)) {
                return $json;
            }
        }

        return false;
    }

}
