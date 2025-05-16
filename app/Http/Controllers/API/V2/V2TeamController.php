<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Repositories\V2\V2TeamRepository;
use Exception;
use Illuminate\Http\Request;

class V2TeamController extends Controller
{

    private $team_repository;

    public function __construct()
    {
        $this->team_repository = new V2TeamRepository();
    }

    public function getTeams(Request $request)
    {
        try {
            $teams = $this->team_repository->getTeams($request->header('Authorization'), $request->all());

            return prepare_response(200, true, __('messages.team_list'), $teams, [], "2.0");
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    public function getSavedTeams(Request $request)
    {
        try {
            $saved_teams = $this->team_repository->getSavedTeams($request->header('Authorization'), $request->all());

            return prepare_response(200, true, __('messages.favourite_team_list'), $saved_teams);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

}
