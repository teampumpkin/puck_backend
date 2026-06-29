<?php

namespace App\Constants;

class HockeyListingCategories
{
    const PLAYER_STICKS = 'player_sticks';
    const GOALIE_STICKS = 'goalie_sticks';
    const STREET_MINI_HOCKEY_STICKS = 'street_mini_hockey_sticks';
    const PLAYER_SKATES = 'player_skates';
    const GOALIE_SKATES = 'goalie_skates';
    const SKATE_SHARPENING_SERVICES = 'skate_sharpening_services';
    const LEG_PADS = 'leg_pads';
    const BLOCKERS_CATCHERS_TRAPPER_SETS = 'blockers_catchers_trapper_sets';
    const GOALIE_MASKS_HELMETS = 'goalie_masks_helmets';
    const CHEST_ARM_PROTECTORS = 'chest_arm_protectors';
    const GOALIE_PANTS_ACCESSORIES = 'goalie_pants_accessories';
    const HELMETS_CAGES = 'helmets_cages';
    const SHOULDER_PADS = 'shoulder_pads';
    const ELBOW_PADS = 'elbow_pads';
    const SHIN_GUARDS = 'shin_guards';
    const GLOVES = 'gloves';
    const PANTS_GIRDLES = 'pants_girdles';
    const NECK_GUARDS_ACCESSORIES = 'neck_guards_accessories';
    const TEAM_JERSEYS = 'team_jerseys';
    const PRACTICE_JERSEYS_PINNIES = 'practice_jerseys_pinnies';
    const BASE_LAYERS = 'base_layers';
    const SOCKS_TAPE_ACCESSORIES = 'socks_tape_accessories';
    const HATS_TOQUES_FAN_APPAREL = 'hats_toques_fan_apparel';
    const SHOOTING_PADS_DRYLAND_TILES = 'shooting_pads_dryland_tiles';
    const STICKHANDLING_TOOLS = 'stickhandling_tools';
    const OFF_ICE_TRAINING_EQUIPMENT = 'off_ice_training_equipment';
    const TRAINING_NETS_TARGETS = 'training_nets_targets';
    const CUSTOM_TEAM_JERSEYS = 'custom_team_jerseys';
    const TEAM_EQUIPMENT_PACKAGES = 'team_equipment_packages';
    const LOCKER_ROOM_SUPPLIES = 'locker_room_supplies';
    const SKATE_SHARPENING = 'skate_sharpening';
    const EQUIPMENT_REPAIR = 'equipment_repair';
    const CUSTOM_FITTING_SERVICES = 'custom_fitting_services';
    const EQUIPMENT_RECONDITIONING = 'equipment_reconditioning';
    const PROTECTIVE_GEAR = 'protective_gear';
    const YOUTH_STARTER_PACKAGES = 'youth_starter_packages';

    public static function all(): array
    {
        return [
            self::PLAYER_STICKS,
            self::GOALIE_STICKS,
            self::STREET_MINI_HOCKEY_STICKS,
            self::PLAYER_SKATES,
            self::GOALIE_SKATES,
            self::SKATE_SHARPENING_SERVICES,
            self::LEG_PADS,
            self::BLOCKERS_CATCHERS_TRAPPER_SETS,
            self::GOALIE_MASKS_HELMETS,
            self::CHEST_ARM_PROTECTORS,
            self::GOALIE_PANTS_ACCESSORIES,
            self::HELMETS_CAGES,
            self::SHOULDER_PADS,
            self::ELBOW_PADS,
            self::SHIN_GUARDS,
            self::GLOVES,
            self::PANTS_GIRDLES,
            self::NECK_GUARDS_ACCESSORIES,
            self::TEAM_JERSEYS,
            self::PRACTICE_JERSEYS_PINNIES,
            self::BASE_LAYERS,
            self::SOCKS_TAPE_ACCESSORIES,
            self::HATS_TOQUES_FAN_APPAREL,
            self::SHOOTING_PADS_DRYLAND_TILES,
            self::STICKHANDLING_TOOLS,
            self::OFF_ICE_TRAINING_EQUIPMENT,
            self::TRAINING_NETS_TARGETS,
            self::CUSTOM_TEAM_JERSEYS,
            self::TEAM_EQUIPMENT_PACKAGES,
            self::LOCKER_ROOM_SUPPLIES,
            self::SKATE_SHARPENING,
            self::EQUIPMENT_REPAIR,
            self::CUSTOM_FITTING_SERVICES,
            self::EQUIPMENT_RECONDITIONING,
            self::PROTECTIVE_GEAR,
            self::YOUTH_STARTER_PACKAGES,
        ];
    }
}
