<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Repositories\V2\V2PlayerRepository;
use Exception;
use Illuminate\Http\Request;

class V2PlayerController extends Controller
{
    /**
     * @var V2PlayerRepository
     */
    private $playerRepository;

    public function __construct()
    {
        $this->playerRepository  = new V2PlayerRepository();
    }

    public function search(Request $request)
    {
        try {
            $players = $this->playerRepository->searchUsers($request->header('Authorization'), $request->all());

            if (empty($players)) {
                return prepare_response(200, false, 'No player available.');
            }

            return prepare_response(200, true, __('messages.all_player_list_success'), $players, [], "2.0");
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    public function getPlayers(Request $request)
    {
        try {
            $players = $this->playerRepository->getPlayers($request->header('Authorization'), $request->all());

            if (empty($players)) {
                return prepare_response(200, false, 'No player available.');
            }

            return prepare_response(200, true, __('messages.top_player_list_success'), $players, [], "2.0");
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }
}
