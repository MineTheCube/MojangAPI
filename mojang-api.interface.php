<?php

/**
 * Fast and easy way to access Mojang API
 * 
 * Can be used to get Mojang status, UUID and username conversions, names history, and fetch skin.
 * 
 * @see http://wiki.vg/Mojang_API
 */
interface MojangAPI {

    /**
     * Get Mojang status
     *
     * @return array|bool Array with status, false on failure
     */ 
    public static function getStatus();

    /**
     * Get UUID from username, an optional time can be provided
     *
     * @param  string       $username
     * @param  int          $time optional
     * @return string|bool  UUID (without dashes) on success, false on failure
     */ 
    public static function getUuid($username, $time = 0);

    /**
     * Get username from UUID
     *
     * @param  string       $uuid
     * @return string|bool  Username on success, false on failure
     */
    public static function getUsername($uuid);

    /**
     * Get profile (username and UUID) from username, an optional time can be provided
     *
     * @param  string      $username
     * @param  int         $time optional
     * @return array|bool  Array with id and name, false on failure
     */ 
    public static function getProfile($username, $time = 0);

    /**
     * Get name history from UUID
     *
     * @param  string      $uuid
     * @return array|bool  Array with his username's history, false on failure
     */ 
    public static function getNameHistory($uuid);

    /**
     * Check if string is a valid Minecraft username
     *
     * @param  string $string to check
     * @return bool   Whether username is valid or not
     */
    public static function isValidUsername($string);

    /**
     * Check if string is a valid UUID, with or without dashes
     *
     * @param  string $string to check
     * @return bool   Whether UUID is valid or not
     */
    public static function isValidUuid($string);

    /**
     * Remove dashes from UUID
     *
     * @param  string       $uuid
     * @return string|bool  UUID without dashes (32 chars), false on failure
     */ 
    public static function minifyUuid($uuid);

    /**
     * Add dashes to an UUID
     *
     * @param  string       $uuid
     * @return string|bool  UUID with dashes (36 chars), false on failure
     */
    public static function formatUuid($uuid);

    /**
     * Check if username is Alex or Steve is a valid UUID, with or without dashes
     *
     * @param  string    $uuid
     * @return bool|null TRUE if Alex, FALSE if Steve, NULL on error
     */
    public static function isAlex($uuid);

    /**
     * Get profile (username and UUID) from uuid
     *
     * This has a rate limit to 1 per minute per profile
     *
     * @param  string      $uuid
     * @return array|bool  Array with profile and properties, false on failure
     */
    public static function getSessionProfile($uuid);

    /**
     * Get textures (usually skin and cape) from uuid
     *
     * This has a rate limit to 1 per minute per profile
     * @see getSessionProfile($uuid)
     *
     * @param  string      $uuid
     * @return array|bool  Array with profile and properties, false on failure
     */
    public static function getTextures($uuid);

    /**
     * Get skin url of player
     *
     * This has a rate limit to 1 per minute per profile
     * @see getSessionProfile($uuid)
     *
     * @param  string      $uuid
     * @return string|bool Skin url, false on failure
     */
    public static function getSkinUrl($uuid);

    /**
     * Get skin (in raw png) of player
     *
     * This has a rate limit to 1 per minute per profile
     * @see getSessionProfile($uuid)
     *
     * @param  string      $uuid
     * @return string|bool Skin picture, false on failure
     */
    public static function getSkin($uuid);

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
    public static function getPlayerHead($uuid, $size = 100);

    /**
     * Get steve skin (in raw png) of player
     *
     * @return string Steve skin
     */
    public static function getSteveSkin();

    /**
     * Get alex skin (in raw png) of player
     *
     * @return string Steve skin
     */
    public static function getAlexSkin();

    /**
     * Get player head (in raw png) of steve
     *
     * @return string Steve head
     */
    public static function getSteveHead($size = 100);

    /**
     * Get player head (in raw png) of alex
     *
     * @return string Alex head
     */
    public static function getAlexHead($size = 100);

    /**
     * Get player head (in raw png) from skin
     *
     * @param  string      $skin returned by getSkin($uuid)
     * @param  int         $size in pixels
     * @return string|bool Player head, false on failure
     */
    public static function getPlayerHeadFromSkin($skin, $size = 100);

    /**
     * Print image from raw png
     *
     * @param  string      $img
     * @param  int         $cache in seconds, 0 to disable
     */
    public static function printImage($img, $cache = 86400);

    /**
     * Embed image for <img> tag
     *
     * @param  string $img
     * @return string embed image
     */
    public static function embedImage($img);

}
