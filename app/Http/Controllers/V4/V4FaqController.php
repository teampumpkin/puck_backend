<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\V4Faq;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class V4FaqController extends Controller
{
    /**
     * GET /faqs
     */
    public function getFaqs(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'q'        => 'nullable|string|max:255',
                'page'     => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:500',
            ]);

            $perPage = (int) $request->get('per_page', 500);
            $search  = $request->get('q');

            $query = V4Faq::query()
                ->where('is_active', true)
                ->orderBy('order', 'asc')  // ⭐ sort by your custom field
                ->orderBy('id', 'desc');   // optional secondary sort


            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('question', 'like', "%{$search}%")
                        ->orWhere('answer', 'like', "%{$search}%");
                });
            }

            $faqs = $query->paginate($perPage);

            return response()->json([
                'success'    => true,
                'message'    => 'FAQs retrieved successfully.',
                'data'       => $faqs->items(),
                'pagination' => [
                    'current_page' => $faqs->currentPage(),
                    'per_page'     => $faqs->perPage(),
                    'total'        => $faqs->total(),
                    'last_page'    => $faqs->lastPage(),
                    'has_more'     => $faqs->hasMorePages(),
                ],
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->serverErrorResponse($e, 'Failed to retrieve FAQs.');
        }
    }

    /**
     * GET /faqs/{id}
     */
    public function getFaqById(Request $request,  $id): JsonResponse
    {
        try {
            $faq = V4Faq::findOrFail($id);

            return $this->successResponse('FAQ retrieved successfully.', $faq);
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('FAQ not found.');
        } catch (Exception $e) {
            return $this->serverErrorResponse($e, 'Failed to retrieve FAQ.');
        }
    }

    /**
     * POST /faqs
     */
    public function createFaq(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'question'  => 'required|string|max:255',
                'answer'    => 'required|string',
                'order'     => 'nullable|integer|min:0|max:255',
                'is_active' => 'nullable|boolean',
            ]);

            $faq = V4Faq::create($data);

            return $this->successResponse('FAQ created successfully.', $faq, 201);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->serverErrorResponse($e, 'Failed to create FAQ.');
        }
    }

    /**
     * PUT /faqs/{id}
     */
    public function updateFaq(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->validate([
                'question'  => 'sometimes|required|string|max:255',
                'answer'    => 'sometimes|required|string',
                'order'     => 'sometimes|integer|min:0|max:255',
                'is_active' => 'sometimes|boolean',
            ]);

            $faq = V4Faq::findOrFail($id);
            $faq->update($data);

            return $this->successResponse('FAQ updated successfully.', $faq);
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('FAQ not found.');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->serverErrorResponse($e, 'Failed to update FAQ.');
        }
    }

    /**
     * DELETE /faqs/{id}
     */
    public function softDeleteFaq(int $id): JsonResponse
    {
        try {
            $faq = V4Faq::findOrFail($id);
            $faq->delete();

            return $this->successResponse('FAQ deleted successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('FAQ not found.');
        } catch (Exception $e) {
            return $this->serverErrorResponse($e, 'Failed to delete FAQ.');
        }
    }

    /* -------------------------------------------------------
     *  Helper Response Methods
     * -------------------------------------------------------
     */

    private function successResponse(string $message, $data = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    private function notFoundResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 404);
    }

    private function validationErrorResponse(ValidationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors'  => $e->errors(),
        ], 422);
    }

    private function serverErrorResponse(Exception $e, string $clientMessage): JsonResponse
    {
        Log::error($clientMessage . ' Error: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $clientMessage,
            'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
        ], 500);
    }
}
