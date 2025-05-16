<?php
namespace App\Repositories\V2;

use App\Models\PrcSave;
use App\Models\PrcTeamMember;
use App\Models\User;
use Exception;

class V2TeamRepository {

	/**
     * @var User
     */
    private $team;

	/**
     * @var PrcSave
     */
	private $prc_save;

    /**
     * PlayerRepository constructor.
     */
    public function __construct()
    {
        $this->team = new User();
		$this->prc_save = new PrcSave();
    }

	public function getTeams($token = "", $data = [])
    {
		$pageSize = !empty($data['page_size']) ? $data['page_size'] : 10;
        $teams = $this->team->whereIn('type', [4,5]);

        $teams = $teams->orderBy('id')->paginate($pageSize, ['first_name', 'last_name', 'id', 'team_id', 'city', 'country', 'city_id', 'state_id', 'country_id', 's3_profile_picture', 'type', 'evaluated']);

        if (empty($teams->toArray())) {
            throw new Exception(__('messages.no_team_available'), 200);
        }

        $user = getUserIdAndType($token);

        foreach ($teams as $team) {
            createUserObjectPaginate($team, $user->id);
        }

        $result = $teams->toArray();

        unset($result['links']);

        return $result;
    }

	public function getSavedTeams($token, $data = [])
    {
		$pageSize = !empty($data['page_size']) ? $data['page_size'] : 10;
        $user = getUserIdAndType($token);

        $saved_players = $this->prc_save->where('user_id', $user->id)->first();

        if (empty($saved_players) || empty($saved_players->players)) {
            throw new Exception(str_replace('%type%', "teams", __('messages.not_favourite_item')), 200);
        }

        $player_data = json_decode($saved_players->players);

        $players = [];

		$users = $this->team->whereIn('id', $player_data)->whereIn('type', [4,5])->paginate($pageSize, ['first_name', 'last_name', 'id', 'city', 'country', 'city_id', 'state_id', 'country_id', 's3_profile_picture', 'type', 'evaluated']);

        foreach ($users as $user) {
            createUserObjectPaginate($user, $user->id);
        }

		$result = $users->toArray();
		
        if (empty($result)) {
            throw new Exception(str_replace('%type%', 'teams', __('messages.not_favourite_item')), 200);
        }

        unset($result['links']);

        return $result;
    }
}