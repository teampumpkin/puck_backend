<?php
namespace App\Repositories\V2;

use App\Models\PrcLeague;
use App\Models\PrcPosition;
use App\Models\PrcTeamMember;
use App\Models\User;

class V2PlayerRepository {
	
	/**
     * @var User
     */
    private $player;

	public function __construct() {
		$this->player = new User();
	}

	public function searchUsers($token, $data = [])
    {
        $user    = getUserIdAndType($token);
        $players = $this->player->whereNotIn('type', [1, 3, 8])->where('status', 'Active');
        $order = 'ASC';
		$pageSize = !empty($data['page_size']) ? $data['page_size'] : 10;

        if ($user->type !== 8) {
            $players = $players->whereIn('type', [2, 4, 5, 6, 7, 9]);
        }

        if (!empty($data['order_by'])) {
            $order = $data['order_by'];
        }

        if (!empty($data)) {
            if (!empty($data['year'])) {
                $players = $players->where('dob', 'like', $data['year'] . '%');
            }

            if (!empty($data['league'])) {
                $players = $players->where('league', $data['league']);
            }

            if (!empty($data['team'])) {
                $players = $players->where('team_id', $data['team']);
            }

            if (!empty($data['sort_by'])) {
                switch (strtolower($data['sort_by'])) {
                    case "id":
                        $players = $players->orderBy('id', $order);
                        break;
                    case "player":
                        $players = $players->orderBy('first_name', $order)->orderBy('last_name', $order);
                        break;
                    case "dob":
                        $players = $players->orderBy('dob', $order);
                        break;
                    case "position":
                        $players = $players->orderBy(PrcPosition::select('position_name')->whereRaw('prc_positions.id = CAST(prc_users.position AS Bigint)'), $order);
                        break;
                    case "team":
                        $players = $players->orderByRaw('type = ?', [4])->orderBy('first_name', $order)->orderBy('last_name', $order);
                        break;
                    case "league":
                        $players = $players->orderBy(PrcLeague::select('league_name')->whereColumn('prc_leagues.id', 'prc_users.league'), $order);
                        break;
                    case "scouts":
                        $players = $players->orderByRaw('type = ?', [7])->orderBy('first_name', $order)->orderBy('last_name', $order);
                        break;
                    case "coaches":
                        $players = $players->orderBy(PrcTeamMember::select('first_name')->whereColumn('prc_team_members.user_id', 'prc_users.id'), $order);
                        break;
                    default:
                        break;
                }
            }else {
                $players = $players->orderBy('first_name', $order)->orderBy('last_name', $order);
            }
        }

        $players = $players->paginate($pageSize, ['first_name', 'last_name', 'id', 'city', 'country', 'city_id', 'state_id', 'country_id', 's3_profile_picture', 'type', 'evaluated']);

        if ($user->type == 1 || $user->type == 8) {
            $players->makeVisible(['status']);

            return $players;
        }

        if (!empty($players)) {
            foreach ($players as $player) {
                createUserObjectPaginate($player, $user->id);
            }
        }

        $result = $players->toArray();

        unset($result['links']);

        return $result;
    }

    public function getPlayers($token, $data = [])
    {
        $user = getUserIdAndType($token);
        $pageSize = !empty($data['page_size']) ? $data['page_size'] : 10;

        $players = $this->player->where('type', 2)
            ->where('status', 'Active')
            ->orderBy('rating_count', 'DESC')
            ->paginate($pageSize, ['first_name', 'last_name', 'id', 'city', 'country', 'city_id', 'state_id', 'country_id', 's3_profile_picture', 'type', 'evaluated']);


        if (!empty($players)) {
            foreach ($players as $player) {
                createUserObjectPaginate($player, $user->id);
            }
        }

        $result = $players->toArray();

        unset($result['links']);

        return $result;
    }

}