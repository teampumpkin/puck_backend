<?php

namespace App\Http\Controllers;

use App\Repositories\PlayableRepository;
use Exception;
use Illuminate\Http\Request;

class PlayableController extends Controller
{
    /**
     * @var PlayableRepository
     */
    private $playableRepository;

    /**
     *
     */
    public function __construct()
    {
        $this->playableRepository  = new PlayableRepository();
    }

    public function getPlayableList(Request $request)
    {
        try {
            $players = $this->playableRepository->getPlayables($request->header('Authorization'));

            if (empty($players)) {
                return prepare_response(200, false, 'No Playables available.');
            }

            return prepare_response(200, true, __('messages.playable_list_success'), $players);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

}
