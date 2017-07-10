<?php

/**
 * Fast and easy way to access Mojang API
 *
 * This interface is NOT needed in your project
 *
 * @author MineTheCube
 * @link https://github.com/MineTheCube/MojangAPI
 * @see http://wiki.vg/Mojang_API
 */
interface MojangAPI
{
    /**
     * Get Mojang status
     *
     * @return array|bool  Array with status, FALSE on failure
     */
    public static function getStatus();

    /**
     * Get UUID from username, an optional time can be provided
     *
     * @param  string       $username
     * @param  int          $time optional
     * @return string|bool  UUID (without dashes) on success, FALSE on failure
     */
    public static function getUuid($username, $time = 0);

    /**
     * Get username from UUID
     *
     * @param  string       $uuid
     * @return string|bool  Username on success, FALSE on failure
     */
    public static function getUsername($uuid);

    /**
     * Get profile (username and UUID) from username, an optional time can be provided
     *
     * @param  string      $username
     * @param  int         $time optional
     * @return array|bool  Array with id and name, FALSE on failure
     */
    public static function getProfile($username, $time = 0);

    /**
     * Get name history from UUID
     *
     * @param  string      $uuid
     * @return array|bool  Array with his username's history, FALSE on failure
     */
    public static function getNameHistory($uuid);

    /**
     * Check if string is a valid Minecraft username
     *
     * @param  string  $string to check
     * @return bool    Whether username is valid or not
     */
    public static function isValidUsername($string);

    /**
     * Check if string is a valid UUID, with or without dashes
     *
     * @param  string  $string to check
     * @return bool    Whether UUID is valid or not
     */
    public static function isValidUuid($string);

    /**
     * Remove dashes from UUID
     *
     * @param  string       $uuid
     * @return string|bool  UUID without dashes (32 chars), FALSE on failure
     */
    public static function minifyUuid($uuid);

    /**
     * Add dashes to an UUID
     *
     * @param  string       $uuid
     * @return string|bool  UUID with dashes (36 chars), FALSE on failure
     */
    public static function formatUuid($uuid);

    /**
     * Check if username is Alex or Steve is a valid UUID, with or without dashes
     *
     * @param  string     $uuid
     * @return bool|null  TRUE if Alex, FALSE if Steve, NULL on error
     */
    public static function isAlex($uuid);

    /**
     * Get profile (username and UUID) from UUID
     *
     * This has a rate limit to 1 per minute per profile
     *
     * @param  string      $uuid
     * @return array|bool  Array with profile and properties, FALSE on failure
     */
    public static function getSessionProfile($uuid);

    /**
     * Get textures (usually skin and cape, or empty array) from UUID
     *
     * This has a rate limit to 1 per minute per profile
     * @see getSessionProfile($uuid)
     *
     * @param  string      $uuid
     * @return array|bool  Array with profile and properties, FALSE on failure
     */
    public static function getTextures($uuid);

    /**
     * Get skin url of player
     *
     * This has a rate limit to 1 per minute per profile
     * @see getSessionProfile($uuid)
     *
     * @param  string            $uuid
     * @return string|null|bool  Skin url, NULL if he hasn't a skin, FALSE on failure
     */
    public static function getSkinUrl($uuid);

    /**
     * Get skin (in raw png) of player
     *
     * This has a rate limit to 1 per minute per profile
     * @see getSessionProfile($uuid)
     *
     * @param  string            $uuid
     * @return string|null|bool  Skin picture, NULL if he hasn't a skin, FALSE on failure
     */
    public static function getSkin($uuid);

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
    public static function getPlayerHead($uuid, $size = 100);

    /**
     * Get Steve skin (in raw png)
     *
     * @return string  Steve skin
     */
    public static function getSteveSkin();

    /**
     * Get Alex skin (in raw png)
     *
     * @return string  Alex skin
     */
    public static function getAlexSkin();

    /**
     * Get Steve head (in raw png)
     *
     * @return string  Steve head
     */
    public static function getSteveHead($size = 100);

    /**
     * Get Alex head (in raw png)
     *
     * @return string  Alex head
     */
    public static function getAlexHead($size = 100);

    /**
     * Get player head (in raw png) from skin
     *
     * @param  string       $skin returned by getSkin($uuid)
     * @param  int          $size in pixels
     * @return string|bool  Player head, FALSE on failure
     */
    public static function getPlayerHeadFromSkin($skin, $size = 100);

    /**
     * Print image from raw png
     *
     * Nothing should be displayed on the page other than this image
     *
     * @param  string  $img
     * @param  int     $cache in seconds, 0 to disable
     */
    public static function printImage($img, $cache = 86400);

    /**
     * Embed image for <img> tag
     *
     * @param  string  $img
     * @return string  embed image
     */
    public static function embedImage($img);

    /**
     * Authenticate with a Minecraft account
     *
     * After a few fails, Mojang server will deny all requests !
     *
     * @param  string      $id Minecraft username or Mojang email
     * @param  string      $password Account's password
     * @return array|bool  Array with id and name, FALSE if authentication failed
     */
    public static function authenticate($id, $password);

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
    public static function query($address, $port = 25565, $timeout = 2);

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
    public static function ping($address, $port = 25565, $timeout = 2);
}
