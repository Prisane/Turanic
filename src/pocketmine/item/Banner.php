<?php

/*
 *
 *    _______                    _
 *   |__   __|                  (_)
 *      | |_   _ _ __ __ _ _ __  _  ___
 *      | | | | | '__/ _` | '_ \| |/ __|
 *      | | |_| | | | (_| | | | | | (__
 *      |_|\__,_|_|  \__,_|_| |_|_|\___|
 *
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author TuranicTeam
 * @link https://github.com/TuranicTeam/Turanic
 *
 */

declare(strict_types=1);

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\Banner as TileBanner;

class Banner extends Item{

    const TAG_BASE = TileBanner::TAG_BASE;
    const TAG_PATTERNS = TileBanner::TAG_PATTERNS;
    const TAG_PATTERN_COLOR = TileBanner::TAG_PATTERN_COLOR;
    const TAG_PATTERN_NAME = TileBanner::TAG_PATTERN_NAME;

    public function __construct($meta = 0, $count = 1){
        $this->block = Block::get(Block::STANDING_BANNER);
        parent::__construct(self::BANNER, $meta, 1, "Banner");
    }

    public function getMaxStackSize() : int{
        return 16;
    }

    /**
     * Returns the color of the banner base.
     *
     * @return int
     */
    public function getBaseColor() : int{
        return $this->getNamedTag()->getInt(self::TAG_BASE, 0);
    }

    /**
     * Sets the color of the banner base.
     * Banner items have to be resent to see the changes in the inventory.
     *
     * @param int $color
     */
    public function setBaseColor(int $color){
        $namedTag = $this->getNamedTag();
        $namedTag->setInt(self::TAG_BASE, $color & 0x0f);
        $this->setNamedTag($namedTag);
    }

    /**
     * Applies a new pattern on the banner with the given color.
     * Banner items have to be resent to see the changes in the inventory.
     *
     * @param string $pattern
     * @param int    $color
     *
     * @return int ID of pattern.
     */
    public function addPattern(string $pattern, int $color) : int{
        $patternId = 0;
        if($this->getPatternCount() !== 0){
            $patternId = max($this->getPatternIds()) + 1;
        }

        $patternsTag = $this->getNamedTag()->getListTag(self::TAG_PATTERNS);
        assert($patternsTag !== null);

        $patternsTag[$patternId] = new CompoundTag("", [
            new IntTag(self::TAG_PATTERN_COLOR, $color & 0x0f),
            new StringTag(self::TAG_PATTERN_NAME, $pattern)
        ]);

        $this->setNamedTagEntry($patternsTag);

        return $patternId;
    }

    /**
     * Returns whether a pattern with the given ID exists on the banner or not.
     *
     * @param int $patternId
     *
     * @return bool
     */
    public function patternExists(int $patternId) : bool{
        $this->correctNBT();
        return isset($this->getNamedTag()->getListTag(self::TAG_PATTERNS)[$patternId]);
    }

    /**
     * Returns the data of a pattern with the given ID.
     *
     * @param int $patternId
     *
     * @return array
     */
    public function getPatternData(int $patternId) : array{
        if(!$this->patternExists($patternId)){
            return [];
        }

        $patternsTag = $this->getNamedTag()->getListTag(self::TAG_PATTERNS);
        assert($patternsTag !== null);
        $pattern = $patternsTag[$patternId];
        assert($pattern instanceof CompoundTag);

        return [
            self::TAG_PATTERN_COLOR => $pattern->getInt(self::TAG_PATTERN_COLOR),
            self::TAG_PATTERN_NAME => $pattern->getString(self::TAG_PATTERN_NAME)
        ];
    }

    /**
     * Changes the pattern of a previously existing pattern.
     * Banner items have to be resent to see the changes in the inventory.
     *
     * @param int    $patternId
     * @param string $pattern
     * @param int    $color
     *
     * @return bool indicating success.
     */
    public function changePattern(int $patternId, string $pattern, int $color) : bool{
        if(!$this->patternExists($patternId)){
            return false;
        }

        $patternsTag = $this->getNamedTag()->getListTag(self::TAG_PATTERNS);
        assert($patternsTag !== null);

        $patternsTag[$patternId] = new CompoundTag("", [
            new IntTag(self::TAG_PATTERN_COLOR, $color & 0x0f),
            new StringTag(self::TAG_PATTERN_NAME, $pattern)
        ]);

        $this->setNamedTagEntry($patternsTag);
        return true;
    }

    /**
     * Deletes a pattern from the banner with the given ID.
     * Banner items have to be resent to see the changes in the inventory.
     *
     * @param int $patternId
     *
     * @return bool indicating whether the pattern existed or not.
     */
    public function deletePattern(int $patternId) : bool{
        if(!$this->patternExists($patternId)){
            return false;
        }

        $patternsTag = $this->getNamedTag()->getListTag(self::TAG_PATTERNS);
        if($patternsTag instanceof ListTag){
            unset($patternsTag[$patternId]);
            $this->setNamedTagEntry($patternsTag);
        }

        return true;
    }

    /**
     * Deletes the top most pattern of the banner.
     * Banner items have to be resent to see the changes in the inventory.
     *
     * @return bool indicating whether the banner was empty or not.
     */
    public function deleteTopPattern() : bool{
        $keys = $this->getPatternIds();
        if(empty($keys)){
            return false;
        }

        return $this->deletePattern(max($keys));
    }

    /**
     * Deletes the bottom pattern of the banner.
     * Banner items have to be resent to see the changes in the inventory.
     *
     * @return bool indicating whether the banner was empty or not.
     */
    public function deleteBottomPattern() : bool{
        $keys = $this->getPatternIds();
        if(empty($keys)){
            return false;
        }

        return $this->deletePattern(min($keys));
    }

    /**
     * Returns an array containing all pattern IDs
     *
     * @return array
     */
    public function getPatternIds() : array{
        $this->correctNBT();

        $keys = array_keys((array) ($this->getNamedTag()->getListTag(self::TAG_PATTERNS) ?? []));

        return array_filter($keys, function($key){
            return is_numeric($key);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Returns the total count of patterns on this banner.
     *
     * @return int
     */
    public function getPatternCount() : int{
        return count($this->getPatternIds());
    }

    public function correctNBT(){
        $tag = $this->getNamedTag();
        if(!$tag->hasTag(self::TAG_BASE, IntTag::class)){
            $tag->setInt(self::TAG_BASE, 0);
        }

        if(!$tag->hasTag(self::TAG_PATTERNS, ListTag::class)){
            $tag->setTag(new ListTag(self::TAG_PATTERNS));
        }
        $this->setNamedTag($tag);
    }
}