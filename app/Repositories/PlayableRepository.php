<?php
namespace App\Repositories;

use App\Models\PrcPlayable;

class PlayableRepository {

	/**
     * @var PrcPlayable
     */
    private $prc_playable;

	public function __construct() {
		$this->prc_playable = new PrcPlayable();
	}

/**
     * @return mixed
     */
    public function getPlayables($token)
    {
        $user = getUserIdAndType($token);

        return $this->prc_playable->where('status', 1)->orderBy('id', 'asc')->get();
    }

}