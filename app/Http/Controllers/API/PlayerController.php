<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\BlockUnBlockRequest;
use App\Http\Requests\API\FollowUnFollowRequest;
use App\Http\Requests\API\GetMediasRequest;
use App\Http\Requests\API\RemoveMediaRequest;
use App\Http\Requests\API\SaveUnSaveRequest;
use App\Http\Requests\API\ScoutCancelRequest;
use App\Http\Requests\API\UploadMediasRequest;
use App\Http\Requests\API\DownloadMediasRequest;
use App\Http\Requests\API\NotificationPreferencesRequest;
use App\Http\Requests\EditMediaRequest;
use App\Repositories\PlayerRepository;
use App\Repositories\SharedRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Class PlayerController
 * @package App\Http\Controllers\API
 */
class PlayerController extends Controller
{
    /**
     * @var PlayerRepository
     */
    private $playerRepository;
    /**
     * @var SharedRepository
     */
    private $shared_repository;

    /**
     *
     */
    public function __construct()
    {
        $this->playerRepository  = new PlayerRepository();
        $this->shared_repository = new SharedRepository();
    }

    /**
     * @OA\Get (
     * path="/get-top-players",
     * summary="Get Top Players",
     * description="Retrieve Top Players",
     * operationId="getTopPlayers",
     * tags={"Players"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Here is the top players list")
     *        )
     *     )
     * )
     */
    public function getTopPlayers(Request $request)
    {
        try {
            $players = $this->playerRepository->getTopPlayer($request->header('Authorization'));

            if (empty($players)) {
                return prepare_response(200, false, 'No player available.');
            }

            return prepare_response(200, true, __('messages.top_player_list_success'), $players);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/get-all-players",
     * summary="Get All Players",
     * description="Retrieve All Players",
     * operationId="getAllPlayers",
     * tags={"Players"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="Order By",
     *    in="query",
     *    name="order_by",
     *    example="asc|desc",
     *    @OA\Schema(
     *       type="string",
     *       format="text"
     *    )
     * ),
     * @OA\Parameter(
     *    description="Year",
     *    in="query",
     *    name="year",
     *    example="1998",
     *    @OA\Schema(
     *       type="integer",
     *       format="number"
     *    )
     * ),
     * @OA\Parameter(
     *    description="league",
     *    in="query",
     *    name="league",
     *    example="1",
     *    @OA\Schema(
     *       type="integer",
     *       format="number"
     *    )
     * ),
     * @OA\Parameter(
     *    description="team",
     *    in="query",
     *    name="team",
     *    example="1",
     *    @OA\Schema(
     *       type="integer",
     *       format="number"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Players list have been retrieve successfully")
     *        )
     *     )
     * )
     */
    public function getAllPlayers(Request $request)
    {
        try {
            $players = $this->playerRepository->getAllPlayers($request->header('Authorization'), $request->all());

            if (empty($players)) {
                return prepare_response(200, false, 'No player available.');
            }

            return prepare_response(200, true, __('messages.all_player_list_success'), $players);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/save-unsave-user",
     * summary="save unsave user",
     * description="save unsave user using user id",
     * operationId="saveUnsaveUser",
     * tags={"Players"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Save or Unsave user",
     *    @OA\JsonContent(
     *       required={"user_id"},
     *       @OA\Property(property="user_id", type="integer", example=1)
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Success response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Player has been saved successfully!")
     *        )
     *     )
     * )
     */
    public function saveUnSaveUser(SaveUnSaveRequest $request)
    {
        DB::beginTransaction();
        try {
            $return_message = $this->playerRepository->saveUnSaveUser($request->user_id, $request->header('Authorization'));

            DB::commit();
            return prepare_response(200, true, $return_message);
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/follow-unfollow-user",
     * summary="follow unfollow user",
     * description="follow unfollow user using user id",
     * operationId="followUnFollowUser",
     * tags={"Players"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Save or Unsave user",
     *    @OA\JsonContent(
     *       required={"user_id"},
     *       @OA\Property(property="user_id", type="integer", example=1)
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Success response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="You have been followed successfully!")
     *        )
     *     )
     * )
     */
    public function followUnFollowUser(FollowUnFollowRequest $request)
    {
        DB::beginTransaction();
        try {
            $response = $this->playerRepository->followUnFollowUser($request->user_id,
                $request->header('Authorization'));

            DB::commit();
            return prepare_response(200, true, $response['message'], $response['follower_count']);
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/block-unblock-user",
     * summary="block unblock user",
     * description="block unblock user using user id",
     * operationId="blockUnBlockUser",
     * tags={"Players"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Save or Unsave user",
     *    @OA\JsonContent(
     *       required={"user_id"},
     *       @OA\Property(property="user_id", type="integer", example=1)
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Success response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="You block the user successfully!")
     *        )
     *     )
     * )
     */
    public function blockUnBlockUser(BlockUnBlockRequest $request)
    {
        DB::beginTransaction();
        try {
            $return_message = $this->playerRepository->blockUnBlockUser($request->user_id,
                $request->header('Authorization'));

            DB::commit();
            return prepare_response(200, true, $return_message);
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/send-scout-request",
     * summary="send scouting request",
     * description="send scouting request",
     * operationId="sendScoutRequest",
     * tags={"Players"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Send scout request",
     *    @OA\JsonContent(
     *       required={"league"},
     *       @OA\Property(property="league", type="integer", example=1)
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Assessment request sent.")
     *        )
     *     )
     * )
     */
    public function sendScoutRequest(Request $request)
    {
        DB::beginTransaction();
        try {
            $this->playerRepository->sendScoutRequest($request->header('Authorization'), $request->all());
            DB::commit();
            return prepare_response(200, true, __('messages.assessment_request_sent'));
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/get-followers",
     * summary="Get All Followers",
     * description="Retrieve All Followers",
     * operationId="getFollowers",
     * tags={"Players"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="User id",
     *    in="query",
     *    name="user_id",
     *    required=true,
     *    example="1",
     *    @OA\Schema(
     *       type="integer",
     *       format="number"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Follower list has been retrieve successfully")
     *        )
     *     )
     * )
     */
    public function getFollowers(Request $request)
    {
        try {
            $followers = $this->playerRepository->getFollowers($request->get('user_id'));

            if (empty($followers)) {
                return prepare_response(200, false, "No followers.");
            }
            return prepare_response(200, true, __('messages.follower_list_success'), $followers);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/get-followings",
     * summary="Get All Followings",
     * description="Retrieve All Followings",
     * operationId="getFollowings",
     * tags={"Players"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="User id",
     *    in="query",
     *    name="user_id",
     *    required=true,
     *    example="1",
     *    @OA\Schema(
     *       type="integer",
     *       format="number"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Following list retrieved.")
     *        )
     *     )
     * )
     */
    public function getFollowings(Request $request)
    {
        try {
            $followers = $this->playerRepository->getFollowings($request->get('user_id'));

            if (empty($followers)) {
                return prepare_response(200, false, "No following.");
            }
            return prepare_response(200, true, __('messages.following_list_success'), $followers);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/cancel-scout-request",
     * summary="cancel scouting request",
     * description="cancel scouting request",
     * operationId="cancelScoutRequest",
     * tags={"Players"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Request Id",
     *    @OA\JsonContent(
     *       required={"request_id"},
     *       @OA\Property(property="request_id", type="integer", example="1"),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Assessment request canceled.")
     *        )
     *     )
     * )
     */
    public function cancelScoutRequest(ScoutCancelRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->playerRepository->cancelScoutRequest($request->request_id);
            DB::commit();
            return prepare_response(200, true, __('messages.cancel_assessment_request'));
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/medias",
     * summary="Uploaded media information",
     * description="Retrieve the medias uploaded by the user",
     * operationId="playerMedias",
     * tags={"Players"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="User Id",
     *    in="query",
     *    name="user_id",
     *    required=true,
     *    example="",
     *    @OA\Schema(
     *       type="integer",
     *       format="number"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Medias have been found!")
     *        )
     *     )
     * )
     */
    public function playerMedias(GetMediasRequest $request)
    {
        try {
            $medias = $this->playerRepository->playerMedias($request->user_id);
            return prepare_response(200, true, __('messages.player_media_found'), $medias);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/media-upload",
     * summary="Upload media",
     * description="upload media by player",
     * operationId="playerMediaUpload",
     * tags={"Players"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Media file",
     *    @OA\JsonContent(
     *       required={"media"},
     *       @OA\Property(property="media", type="file", example=""),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Media upload canceled.")
     *        )
     *     )
     * )
     */
    public function playerMediaUpload(UploadMediasRequest $request)
    {
        try {
            $presignedUrl = $this->playerRepository->playerMediaUpload($request->header('Authorization'), $request->all());
            return prepare_response(200, true, __('messages.player_media_upload_url'), $presignedUrl);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/media-edit",
     * summary="Edit media",
     * description="Edit media by player",
     * operationId="playerMediaEdit",
     * tags={"Players"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Media file",
     *    @OA\JsonContent(
     *       required={"media"},
     *       @OA\Property(property="media", type="file", example=""),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Media edit canceled.")
     *        )
     *     )
     * )
     */
    public function playerMediaEdit(EditMediaRequest $request)
    {
        try {
            $media = $this->playerRepository->playerMediaEdit($request->header('Authorization'), $request->all());
            if(empty($media)){return prepare_response(404, false, __('Media not found'));}
            return prepare_response(200, true, __('messages.player_media_edit'), $media);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get(
     * path="/media-download",
     * summary="Download media",
     * description="download media by player",
     * operationId="playerMediaDownload",
     * tags={"Players"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="media id",
     *    in="query",
     *    name="media_id",
     *    required=true,
     *    example="1",
     *    @OA\Schema(
     *       type="number",
     *       format="integer"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Media download canceled.")
     *        )
     *     )
     * )
     */
    public function playerMediaDownload(DownloadMediasRequest $request)
    {
        try {
            $presignedUrl = $this->playerRepository->playerMediaDownload($request->header('Authorization'), $request->media_id);
            return prepare_response(200, true, __('messages.player_media_download_url'), $presignedUrl);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Delete (
     * path="/media-delete",
     * summary="Media delete",
     * description="remove media by player",
     * operationId="deleteMedia",
     * tags={"Players"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="media id",
     *    in="query",
     *    name="media_id",
     *    required=true,
     *    example="1",
     *    @OA\Schema(
     *       type="number",
     *       format="integer"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Your media has been removed.")
     *        )
     *     )
     * )
     */
    public function deleteMedia(RemoveMediaRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->playerRepository->removeMedia($request->media_id, $request->header('Authorization'));
            DB::commit();
            return prepare_response(200, true, __('Your media has been removed'));
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/get-positions",
     * summary="Get Position",
     * description="Retrieve player positions",
     * operationId="getPositions",
     * tags={"Players"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Positions retrieved.")
     *        )
     *     )
     * )
     */
    public function getPositions()
    {
        try {
            $positions = $this->shared_repository->getPositions();

            return prepare_response(200, true, __('messages.player_position_list'), $positions);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/mentorship-plan-price",
     * summary="Retrieve mentor ship plan price",
     * description="Retrieve mentor ship plan price",
     * operationId="mentorshipPlanPrice",
     * tags={"Players"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Plan price has been retrieve")
     *        )
     *     )
     * )
     */
    public function mentorshipPlanPrice()
    {
        try {
            $price = $this->playerRepository->mentorshipPlanPrice();

            return prepare_response(200, true, __('messages.plan_prices'), $price);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    public function getNotificationPreferences(Request $request)
    {
        try {
            $preferences = $this->playerRepository->notification_preferences($request->header('Authorization'));

            return prepare_response(200, true, __('messages.get_notification_preferences'), $preferences);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    public function setNotificationPreferences(NotificationPreferencesRequest $request)
    {
        try {
            $preferences = $this->playerRepository->update_notification_preferences($request->all(), $request->header('Authorization'));

            return prepare_response(200, true, __('messages.get_notification_preferences'), $preferences);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }
}
