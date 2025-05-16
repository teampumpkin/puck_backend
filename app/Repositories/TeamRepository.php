<?php

namespace App\Repositories;

use App\Models\PrcTeamMember;
use App\Models\User;
use Exception;

/**
 * Class TeamRepository
 * @package App\Repositories
 */
class TeamRepository
{
    /**
     * @var User
     */
    private $team;

    /**
     * @var PrcTeamMember
     */
    private $prc_team_members;

    /**
     * PlayerRepository constructor.
     */
    public function __construct()
    {
        $this->team             = new User();
        $this->prc_team_members = new PrcTeamMember();
    }

    /**
     * @param false $from_admin
     * @param string $token
     * @return array
     * @throws Exception
     */
    public function getTeams($from_admin = false, $token = "", $type = 4)
    {
        $teams = $this->team->where(function ($q) use ($from_admin, $type) {
            $query = $q->where('type', $type);

            if (!$from_admin) {
                $query->orWhere('type', 5);
            }
            return $query;
        });

        if (!$from_admin) {
            $teams = $teams->where('status', 'Active');
        }
        $teams = $teams->orderBy('id')->get();

        if (empty($teams->toArray())) {
            throw new Exception(__('messages.no_team_available'), 200);
        }

        if ($from_admin) {
            $teams->makeVisible('status');
            return $teams;
        }
        $user      = getUserIdAndType($token);
        $team_data = [];

        foreach ($teams as $team) {
            $team_data[] = createUserObject($team, $user->id);
        }

        return $team_data;
    }

    /**
     * @param $team_id
     * @param $token
     * @return mixed
     * @throws Exception
     */
    public function getTeam($team_id, $token)
    {
        $team = $this->team->where('status', 'Active')->whereIn('type', [4, 5])->where('id', $team_id)->first();

        if (empty($team)) {
            throw new Exception(__('messages.invalid_team_id'), 200);
        }
        $user                = getUserIdAndType($token);
        $team->team_managers = json_decode($team->team_managers);
        $team->coaches       = json_decode($team->coaches);
        $team->team_players  = json_decode($team->team_players);

        $team->country = $team->country_id ? $team->countryR->country_name : null;
        $team->state = $team->state_id ? $team->stateR->state_name : null;
        $team->city = $team->city_id ? $team->cityR->city_name : null;
        $team->country_flag = $team->country_id ? $team->countryR->country_flag : null;

        unset($team->cityR);
        unset($team->stateR);
        unset($team->countryR);

        return createUserObject($team, $user->id);
    }

    /**
     * @param $data
     * @param $token
     * @throws Exception
     */
    public function addManager($data, $token)
    {
        $this->addTeamMember($data, $token, 'manager');
    }

    /**
     * @param $data
     * @param $token
     * @param $type
     */
    public function addTeamMember($data, $token, $type)
    {
        $team = getUserInfo($token);

        $user = getUserInfo($data['email'], 'email');

        $this->prc_team_members->create([
            'team_id'         => $team->id,
            'user_id'         => (!empty($user)) ? $user->id : 0,
            'first_name'      => $data['first_name'],
            'last_name'       => $data['last_name'],
            'email'           => $data['email'],
            'type'            => $type,
            'profile_picture' => $data['profile_picture']
        ]);

        if (!empty($user) && !in_array($user->type, [1, 3, 8])) {
            if ($team->type == 4) {
                $user->team_id = $team->id;
            } else {
                $user->academy_id = $team->id;
            }
            $user->save();
        }
    }

    /**
     * @param $data
     * @param $token
     * @throws Exception
     */
    public function addCoach($data, $token)
    {
        $this->addTeamMember($data, $token, 'coach');
    }

    /**
     * @param $data
     * @param $token
     * @throws Exception
     */
    public function addPlayer($data, $token)
    {
        $this->addTeamMember($data, $token, 'player');
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function teamStatusChange($data)
    {
        $team = $this->team->whereIn('type', [4, 5])
            ->where('id', $data['team_id'])
            ->first();

        if (empty($team)) {
            return [
                "status"  => false,
                "message" => "Invalid team id"
            ];
        }

        $team->status = $data['status'];
        $team->save();

        return [
            "status"  => true,
            "message" => __('messages.team_status_update')
        ];
    }

    /**
     * @param $data
     * @throws Exception
     */
    public function editTeamMember($data)
    {
        $member = $this->prc_team_members->where('id', $data['member_id'])->first();

        if (empty($member)) {
            throw new Exception('Invalid member id', 200);
        }

        $member->first_name      = $data['first_name'];
        $member->last_name       = $data['last_name'];
        $member->profile_picture = checkEmpty($data, 'profile_picture', $member->profile_picture);
        $member->save();
    }

    /**
     * @param $member_id
     * @throws Exception
     */
    public function removeTeamMember($member_id)
    {
        $member = $this->prc_team_members->where('id', $member_id)->first();

        if (empty($member)) {
            throw new Exception(__('messages.invalid_member_id'), 200);
        }

        if ($member->user_id > 0) {
            $user = getUserInfo($member->user_id, 'id');
            $team = getUserInfo($member->team_id, 'id');

            if ($team->type == 4) {
                $user->team_id = 0;
            } else {
                $user->academy_id = 0;
            }

            $user->team_id = 0;
            $user->save();
        }

        $member->delete();


    }
}
