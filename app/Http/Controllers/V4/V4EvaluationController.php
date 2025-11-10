<?php

namespace App\Http\Controllers\V4;

use App\Constants\MarketplaceTypes;
use App\Constants\Weekdays;
use App\Http\Controllers\Controller;
use App\Models\EvaluationCategory;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationQuestionOption;
use App\Models\EvaluationSubmission;
use App\Models\EvaluationSubmissionVersion;
use App\Models\EvaluationRejectionReason;
use App\Models\EvaluatorAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationAnswer;
use App\Models\V4ConsultationFeedback;
use App\Models\V4ConsultationRequest;
use App\Models\V4Marketplace;
use App\Models\V4PaymentRequest;
use Carbon\Carbon;
use App\Models\V4User;
use App\Models\V4InAppPurchase;
use App\Services\NotificationService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class V4EvaluationController extends Controller
{

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all evaluation questions with categories and options
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCategoriesQuestionsOptions(Request $request): JsonResponse
    {
        try {
            /** @var V4User $user */
            $user = Auth::guard('v4api')->user();

            // Get all active categories with their questions and options
            $categories = EvaluationCategory::active()
                ->with([
                    'questions' => function ($query) {
                        $query->active()->orderBy('sort_order');
                    },
                    'questions.options' => function ($query) {
                        $query->orderBy('sort_order');
                    },
                ])
                ->get();

            // Transform the data for better API response structure
            $evaluationData = $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'sort_order' => $category->sort_order,
                    'questions' => $category->questions->map(function ($question) {
                        return [
                            'id' => $question->id,
                            'title' => $question->title,
                            'question' => $question->question,
                            'required' => $question->required,
                            'sort_order' => $question->sort_order,
                            'options' => $question->options->map(function ($option) {
                                return [
                                    'id' => $option->id,
                                    'title' => $option->title,
                                    'option' => $option->option,
                                    'rating' => (float) $option->rating,
                                    'sort_order' => $option->sort_order,
                                ];
                            }),
                        ];
                    }),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Evaluation questions retrieved successfully',
                'data' => [
                    'categories' => $evaluationData,
                    'total_categories' => $categories->count(),
                    'total_questions' => $categories->sum(fn($cat) => $cat->questions->count()),
                    'total_options' => $categories->sum(
                        fn($cat) =>
                        $cat->questions->sum(fn($q) => $q->options->count())
                    ),
                ],
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching evaluation questions: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve evaluation questions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get questions for a specific category
     *
     * @param Request $request
     * @param int $categoryId
     * @return JsonResponse
     */
    public function getCategoryQuestions(Request $request, int $categoryId): JsonResponse
    {
        try {
            /** @var V4User $user */
            $user = Auth::guard('v4api')->user();

            // Find the category
            $category = EvaluationCategory::active()
                ->with([
                    'questions' => function ($query) {
                        $query->active()->orderBy('sort_order');
                    },
                    'questions.options' => function ($query) {
                        $query->orderBy('sort_order');
                    },
                ])
                ->find($categoryId);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found or inactive',
                ], 404);
            }

            $categoryData = [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'sort_order' => $category->sort_order,
                'meta' => $category->meta,
                'active' => $category->active,
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
                'questions' => $category->questions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'title' => $question->title,
                        'question' => $question->question,
                        'required' => $question->required,
                        'sort_order' => $question->sort_order,
                        'options' => $question->options->map(function ($option) {
                            return [
                                'id' => $option->id,
                                'title' => $option->title,
                                'option' => $option->option,
                                'rating' => (float) $option->rating,
                                'sort_order' => $option->sort_order,
                            ];
                        }),
                    ];
                }),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Category questions retrieved successfully',
                'data' => $categoryData,
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching category questions: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'category_id' => $categoryId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve category questions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get all active categories (without questions)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCategories(Request $request): JsonResponse
    {
        try {
            /** @var V4User $user */
            $user = Auth::guard('v4api')->user();

            $categories = EvaluationCategory::active()->get();

            return response()->json([
                'success' => true,
                'message' => 'Evaluation categories retrieved successfully',
                'data' => $categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'description' => $category->description,
                        'active' => $category->active,
                        'sort_order' => $category->sort_order,
                        'meta' => $category->meta,
                        'created_at' => $category->created_at,
                        'updated_at' => $category->updated_at,
                    ];
                }),
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching evaluation categories: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve evaluation categories',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function getAllCategories(Request $request): JsonResponse
    {
        try {
            /** @var V4User $user */
            $user = Auth::guard('v4api')->user();

            $categories = EvaluationCategory::orderBy('sort_order')->get();

            return response()->json([
                'success' => true,
                'message' => 'All evaluation categories retrieved successfully',
                'data' => $categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'description' => $category->description,
                        'active' => $category->active,
                        'sortOrder' => $category->sort_order,
                        'meta' => $category->meta,
                        'created_at' => $category->created_at,
                        'updated_at' => $category->updated_at,
                    ];
                }),
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching all evaluation categories: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve all evaluation categories',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function createCategory(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255',
                'description' => 'required|string',
                'meta' => 'nullable|array',
                'meta.*' => 'string',
            ]);

            // Generate slug from name if not provided
            if (empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            $existingName = EvaluationCategory::where('name', $validated['name'])
                ->where('active', true)
                ->first();
            if ($existingName) {
                return response()->json([
                    'success' => false,
                    'message' => 'Name already exists for an active category',
                ], 400);
            }

            $existingSlug = EvaluationCategory::where('slug', $validated['slug'])
                ->where('active', true)
                ->first();
            if ($existingSlug) {
                return response()->json([
                    'success' => false,
                    'message' => 'Slug already exists for an active category',
                ], 400);
            }

            $highestSortOrder = EvaluationCategory::max('sort_order') ?? 0;
            $nextSortOrder = $highestSortOrder + 1;

            $meta = null;
            if (isset($validated['meta']) && is_array($validated['meta'])) {
                $meta = [];
                foreach ($validated['meta'] as $key => $value) {
                    if (!is_string($key) || !is_string($value)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Meta keys and values must be strings',
                        ], 400);
                    }
                    $meta[$key] = $value;
                }
            }

            $category = EvaluationCategory::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'],
                'active' => true,
                'sort_order' => $nextSortOrder,
                'meta' => $meta,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => $category,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function deleteCategoryById(int $id): JsonResponse
    {
        try {
            if (!is_numeric($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid ID',
                ], 400);
            }

            $category = EvaluationCategory::findOrFail($id);
            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function updateCategory(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:evaluation_categories,id',
                'name' => 'sometimes|required|string|max:255',
                'slug' => 'sometimes|nullable|string|max:255',
                'description' => 'sometimes|required|string',
                'active' => 'sometimes|required|boolean',
                'sort_order' => 'sometimes|required|integer|min:1',
                'meta' => 'sometimes|nullable|array',
                'meta.*' => 'string',
            ]);

            $category = EvaluationCategory::findOrFail($validated['id']);

            $updateData = [];
            $hasAtLeastOneField = false;

            if (isset($validated['name'])) {
                $updateData['name'] = $validated['name'];
                $hasAtLeastOneField = true;
            }

            if (isset($validated['slug'])) {
                $slugToUse = $validated['slug'];
            } elseif (isset($validated['name'])) {
                $slugToUse = Str::slug($validated['name']);
            } else {
                $slugToUse = $category->slug;
            }

            if (isset($validated['name']) || isset($validated['slug'])) {
                if ($slugToUse !== $category->slug) {
                    $existingSlug = EvaluationCategory::where('slug', $slugToUse)
                        ->where('id', '!=', $validated['id'])
                        ->where('active', true)
                        ->first();
                    if ($existingSlug) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Slug already exists for an active category',
                        ], 400);
                    }
                }
                $updateData['slug'] = $slugToUse;
                $hasAtLeastOneField = true;
            }

            if (isset($validated['description'])) {
                $updateData['description'] = $validated['description'];
                $hasAtLeastOneField = true;
            }

            if (isset($validated['name'])) {
                if ($validated['name'] !== $category->name) {
                    $existingName = EvaluationCategory::where('name', $validated['name'])
                        ->where('id', '!=', $validated['id'])
                        ->where('active', true)
                        ->first();
                    if ($existingName) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Name already exists for an active category',
                        ], 400);
                    }
                }
            }

            if (isset($validated['active']) && $validated['active'] === true) {
                $sortOrderToCheck = isset($validated['sort_order']) ? $validated['sort_order'] : $category->sort_order;

                $existingSortOrder = EvaluationCategory::where('sort_order', $sortOrderToCheck)
                    ->where('id', '!=', $validated['id'])
                    ->where('active', true)
                    ->first();

                if ($existingSortOrder) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot activate record: Sort order ' . $sortOrderToCheck . ' already exists for an active category (ID: ' . $existingSortOrder->id . ')',
                    ], 400);
                }
            }

            if (isset($validated['sort_order'])) {
                $activeToCheck = isset($validated['active']) ? $validated['active'] : $category->active;

                if ($activeToCheck === true) {
                    $existingSortOrder = EvaluationCategory::where('sort_order', $validated['sort_order'])
                        ->where('id', '!=', $validated['id'])
                        ->where('active', true)
                        ->first();
                    if ($existingSortOrder) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Sort order ' . $validated['sort_order'] . ' already exists for an active category (ID: ' . $existingSortOrder->id . ')',
                        ], 400);
                    }
                }
                $updateData['sort_order'] = $validated['sort_order'];
                $hasAtLeastOneField = true;
            }

            if (isset($validated['active'])) {
                $updateData['active'] = $validated['active'];
                $hasAtLeastOneField = true;
            }

            if (isset($validated['meta'])) {
                $meta = null;
                if (is_array($validated['meta'])) {
                    $meta = [];
                    foreach ($validated['meta'] as $key => $value) {
                        if (!is_string($key) || !is_string($value)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Meta keys and values must be strings',
                            ], 400);
                        }
                        $meta[$key] = $value;
                    }
                }
                $updateData['meta'] = $meta;
                $hasAtLeastOneField = true;
            }

            if (!$hasAtLeastOneField) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one field (name, slug, description, active, sort_order, or meta) must be provided for update',
                ], 400);
            }

            $updateData['updated_at'] = now()->format('Y-m-d H:i:s');

            $category->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => $category->fresh(),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function updateCategoryById(Request $request, int $id): JsonResponse
    {
        try {
            if (!is_numeric($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid ID',
                ], 400);
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'slug' => 'sometimes|nullable|string|max:255',
                'description' => 'sometimes|required|string',
                'active' => 'sometimes|required|boolean',
                'sort_order' => 'sometimes|required|integer|min:1',
                'meta' => 'sometimes|nullable|array',
                'meta.*' => 'string',
            ]);

            $category = EvaluationCategory::findOrFail($id);

            $updateData = [];
            $hasAtLeastOneField = false;

            if (isset($validated['name'])) {
                $updateData['name'] = $validated['name'];
                $hasAtLeastOneField = true;
            }

            if (isset($validated['slug'])) {
                $slugToUse = $validated['slug'];
            } elseif (isset($validated['name'])) {
                $slugToUse = Str::slug($validated['name']);
            } else {
                $slugToUse = $category->slug;
            }

            if (isset($validated['name']) || isset($validated['slug'])) {
                if ($slugToUse !== $category->slug) {
                    $existingSlug = EvaluationCategory::where('slug', $slugToUse)
                        ->where('id', '!=', $id)
                        ->where('active', true)
                        ->first();
                    if ($existingSlug) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Slug already exists for an active category',
                        ], 400);
                    }
                }
                $updateData['slug'] = $slugToUse;
                $hasAtLeastOneField = true;
            }

            if (isset($validated['description'])) {
                $updateData['description'] = $validated['description'];
                $hasAtLeastOneField = true;
            }

            if (isset($validated['name'])) {
                if ($validated['name'] !== $category->name) {
                    $existingName = EvaluationCategory::where('name', $validated['name'])
                        ->where('id', '!=', $id)
                        ->where('active', true)
                        ->first();
                    if ($existingName) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Name already exists for an active category',
                        ], 400);
                    }
                }
            }

            if (isset($validated['active']) && $validated['active'] === true) {
                $sortOrderToCheck = isset($validated['sort_order']) ? $validated['sort_order'] : $category->sort_order;

                $existingSortOrder = EvaluationCategory::where('sort_order', $sortOrderToCheck)
                    ->where('id', '!=', $id)
                    ->where('active', true)
                    ->first();

                if ($existingSortOrder) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot activate record: Sort order ' . $sortOrderToCheck . ' already exists for an active category (ID: ' . $existingSortOrder->id . ')',
                    ], 400);
                }
            }

            if (isset($validated['sort_order'])) {
                $activeToCheck = isset($validated['active']) ? $validated['active'] : $category->active;

                if ($activeToCheck === true) {
                    $existingSortOrder = EvaluationCategory::where('sort_order', $validated['sort_order'])
                        ->where('id', '!=', $id)
                        ->where('active', true)
                        ->first();
                    if ($existingSortOrder) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Sort order ' . $validated['sort_order'] . ' already exists for an active category (ID: ' . $existingSortOrder->id . ')',
                        ], 400);
                    }
                }
                $updateData['sort_order'] = $validated['sort_order'];
                $hasAtLeastOneField = true;
            }

            if (isset($validated['active'])) {
                $updateData['active'] = $validated['active'];
                $hasAtLeastOneField = true;
            }

            if (isset($validated['meta'])) {
                $meta = null;
                if (is_array($validated['meta'])) {
                    $meta = [];
                    foreach ($validated['meta'] as $key => $value) {
                        if (!is_string($key) || !is_string($value)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Meta keys and values must be strings',
                            ], 400);
                        }
                        $meta[$key] = $value;
                    }
                }
                $updateData['meta'] = $meta;
                $hasAtLeastOneField = true;
            }

            if (!$hasAtLeastOneField) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one field (name, slug, description, active, sort_order, or meta) must be provided for update',
                ], 400);
            }

            $updateData['updated_at'] = now()->format('Y-m-d H:i:s');

            $category->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => $category->fresh(),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getCategory(Request $request, int $id): JsonResponse
    {
        try {
            $category = EvaluationCategory::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Category retrieved successfully',
                'data' => $category,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 404);
        }
    }

    public function reorderCategories(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'categories' => 'required|array',
                'categories.*.id' => 'required|integer|exists:evaluation_categories,id',
                'categories.*.sortOrder' => 'required|integer|min:0',
            ]);

            foreach ($validated['categories'] as $categoryData) {
                EvaluationCategory::where('id', $categoryData['id'])
                    ->update(['sort_order' => $categoryData['sortOrder']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Categories reordered successfully.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get a single question by ID
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function getQuestion(Request $request, int $id): JsonResponse
    {
        try {
            /** @var V4User $user */
            $user = Auth::guard('v4api')->user();

            // Get the question with full category information
            $question = EvaluationQuestion::with(['category'])
                ->find($id);

            if (!$question) {
                return response()->json([
                    'success' => false,
                    'message' => 'Question not found',
                ], 404);
            }

            // Transform the data for API response
            $questionData = [
                'id' => $question->id,
                'title' => $question->title,
                'question' => $question->question,
                'required' => $question->required,
                'sort_order' => $question->sort_order,
                'active' => $question->active,
                'meta' => $question->meta,
                'created_at' => $question->created_at,
                'updated_at' => $question->updated_at,
                'category' => $question->category,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Question retrieved successfully',
                'data' => $questionData,
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching question: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'question_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve question',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get all active questions
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getQuestions(Request $request): JsonResponse
    {
        try {
            /** @var V4User $user */
            $user = Auth::guard('v4api')->user();

            // Get all active questions with full category information, ordered by category ID then question sort_order
            $questions = EvaluationQuestion::where('active', true)
                ->with(['category'])
                ->orderBy('category_id')
                ->orderBy('sort_order')
                ->get();

            // Transform the data for API response
            $questionsData = $questions->map(function ($question) {
                return [
                    'id' => $question->id,
                    'title' => $question->title,
                    'question' => $question->question,
                    'required' => $question->required,
                    'sort_order' => $question->sort_order,
                    'active' => $question->active,
                    'meta' => $question->meta,
                    'created_at' => $question->created_at,
                    'updated_at' => $question->updated_at,
                    'category' => $question->category,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Active questions retrieved successfully',
                'data' => [
                    'questions' => $questionsData,
                ],
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching active questions: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve active questions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get all questions (both active and inactive)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllQuestions(Request $request): JsonResponse
    {
        try {
            /** @var V4User $user */
            $user = Auth::guard('v4api')->user();

            // Get all questions (active and inactive) with full category information, ordered by category ID then question sort_order
            $questions = EvaluationQuestion::with(['category'])
                ->orderBy('category_id')
                ->orderBy('sort_order')
                ->get();

            // Transform the data for API response
            $questionsData = $questions->map(function ($question) {
                return [
                    'id' => $question->id,
                    'title' => $question->title,
                    'question' => $question->question,
                    'required' => $question->required,
                    'sort_order' => $question->sort_order,
                    'active' => $question->active,
                    'meta' => $question->meta,
                    'created_at' => $question->created_at,
                    'updated_at' => $question->updated_at,
                    'category' => $question->category,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'All questions retrieved successfully',
                'data' => [
                    'questions' => $questionsData,
                ],
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching all questions: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve all questions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function getAllQuestionsById($id): JsonResponse
    {
        try {
            // Optionally validate manually
            if (!is_numeric($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid ID',
                ], 400);
            }

            $questions = EvaluationQuestion::with(['category'])
                ->where('category_id', $id)
                ->orderBy('category_id')
                ->orderBy('sort_order')
                ->get();

            $questionsData = $questions->map(function ($question) {
                return [
                    'id' => $question->id,
                    'title' => $question->title,
                    'question' => $question->question,
                    'required' => $question->required,
                    'sortOrder' => $question->sort_order,
                    'active' => $question->active,
                    'meta' => $question->meta,
                    'created_at' => $question->created_at,
                    'updated_at' => $question->updated_at,
                    'category' => $question->category,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Active questions retrieved successfully',
                'data' => [
                    'questions' => $questionsData,
                ],
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching all questions: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve all questions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Delete a question by ID
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteQuestion(int $id): JsonResponse
    {
        try {
            $question = EvaluationQuestion::findOrFail($id);
            $question->delete();

            return response()->json([
                'success' => true,
                'message' => 'Question deleted successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error deleting question: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'question_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete question',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Create a new question
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createQuestion(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'categoryId' => 'required|integer|exists:evaluation_categories,id',
                'title' => 'required|string|max:255',
                'question' => 'required|string',
                'required' => 'nullable|boolean',
                'active' => 'sometimes|required|boolean',
                'meta' => 'nullable|array',
                'meta.*' => 'string',
            ]);

            // Check for duplicate title in the same category
            $existingTitle = EvaluationQuestion::where('category_id', $validated['categoryId'])
                ->where('title', $validated['title'])
                ->first();

            if ($existingTitle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Title already exists for this category',
                ], 400);
            }

            // Check for duplicate question text in the same category
            $existingQuestion = EvaluationQuestion::where('category_id', $validated['categoryId'])
                ->where('question', $validated['question'])
                ->first();

            if ($existingQuestion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Question text already exists for this category',
                ], 400);
            }

            // Set default values
            $validated['required'] = $validated['required'] ?? false;
            // Handle meta data
            $meta = null;
            if (isset($validated['meta']) && is_array($validated['meta'])) {
                $meta = [];
                foreach ($validated['meta'] as $key => $value) {
                    if (!is_string($key) || !is_string($value)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Meta keys and values must be strings',
                        ], 400);
                    }
                    $meta[$key] = $value;
                }
            }
            $validated['meta'] = $meta;
            $validated['category_id'] = $validated['categoryId'];

            $question = EvaluationQuestion::create($validated);
            $question->load('category');

            return response()->json([
                'success' => true,
                'message' => 'Question created successfully',
                'data' => [
                    'id' => $question->id,
                    'title' => $question->title,
                    'question' => $question->question,
                    'required' => $question->required,
                    'sort_order' => $question->sort_order,
                    'active' => true,
                    'meta' => $question->meta,
                    'created_at' => $question->created_at,
                    'updated_at' => $question->updated_at,
                    'category' => $question->category,
                ],
            ], 201);
        } catch (ValidationException $e) {
            Log::error('Error Validation failed  creating question: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error creating question: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create question',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Update a question by ID
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateQuestion(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:evaluation_questions,id',
                'category_id' => 'sometimes|required|integer|exists:evaluation_categories,id',
                'title' => 'sometimes|required|string|max:255',
                'question' => 'sometimes|required|string',
                'required' => 'sometimes|required|boolean',
                'sortOrder' => 'sometimes|required|integer|min:1',
                'active' => 'sometimes|required|boolean',
                'meta' => 'sometimes|nullable|array',
                'meta.*' => 'string',
            ]);

            $question = EvaluationQuestion::findOrFail($validated['id']);

            $updateData = [];
            $hasAtLeastOneField = false;

            // Handle category_id update
            if (isset($validated['category_id'])) {
                $updateData['category_id'] = $validated['category_id'];
                $hasAtLeastOneField = true;
            }

            // Handle title update with duplicate check
            if (isset($validated['title'])) {
                $categoryIdToCheck = isset($validated['category_id']) ? $validated['category_id'] : $question->category_id;

                if ($validated['title'] !== $question->title) {
                    $existingTitle = EvaluationQuestion::where('category_id', $categoryIdToCheck)
                        ->where('title', $validated['title'])
                        ->where('id', '!=', $validated['id'])
                        ->first();

                    if ($existingTitle) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Title already exists for this category',
                        ], 400);
                    }
                }
                $updateData['title'] = $validated['title'];
                $hasAtLeastOneField = true;
            }

            // Handle question text update with duplicate check
            if (isset($validated['question'])) {
                $categoryIdToCheck = isset($validated['category_id']) ? $validated['category_id'] : $question->category_id;

                if ($validated['question'] !== $question->question) {
                    $existingQuestion = EvaluationQuestion::where('category_id', $categoryIdToCheck)
                        ->where('question', $validated['question'])
                        ->where('id', '!=', $validated['id'])
                        ->first();

                    if ($existingQuestion) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Question text already exists for this category',
                        ], 400);
                    }
                }
                $updateData['question'] = $validated['question'];
                $hasAtLeastOneField = true;
            }

            // Handle required field
            if (isset($validated['required'])) {
                $updateData['required'] = $validated['required'];
                $hasAtLeastOneField = true;
            }

            // Handle sort_order with duplicate check for active questions
            if (isset($validated['sortOrder'])) {
                $activeToCheck = isset($validated['active']) ? $validated['active'] : $question->active;
                $categoryIdToCheck = isset($validated['category_id']) ? $validated['category_id'] : $question->category_id;

                if ($activeToCheck === true) {
                    $existingSortOrder = EvaluationQuestion::where('category_id', $categoryIdToCheck)
                        ->where('sort_order', $validated['sortOrder'])
                        ->where('id', '!=', $validated['id'])
                        ->where('active', true)
                        ->first();

                    if ($existingSortOrder) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Sort order already exists for an active question in this category',
                        ], 400);
                    }
                }
                $updateData['sort_order'] = $validated['sortOrder'];
                $hasAtLeastOneField = true;
            }

            // Handle active field with sort_order validation
            if (isset($validated['active'])) {
                if ($validated['active'] === true) {
                    $sortOrderToCheck = isset($validated['sortOrder']) ? $validated['sortOrder'] : $question->sort_order;
                    $categoryIdToCheck = isset($validated['category_id']) ? $validated['category_id'] : $question->category_id;

                    $existingSortOrder = EvaluationQuestion::where('category_id', $categoryIdToCheck)
                        ->where('sort_order', $sortOrderToCheck)
                        ->where('id', '!=', $validated['id'])
                        ->where('active', true)
                        ->first();

                    if ($existingSortOrder) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Cannot activate record: Sort order already exists for an active question in this category',
                        ], 400);
                    }
                }
                $updateData['active'] = $validated['active'];
                $hasAtLeastOneField = true;
            }

            // Handle meta data
            if (isset($validated['meta'])) {
                $meta = null;
                if (is_array($validated['meta'])) {
                    $meta = [];
                    foreach ($validated['meta'] as $key => $value) {
                        if (!is_string($key) || !is_string($value)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Meta keys and values must be strings',
                            ], 400);
                        }
                        $meta[$key] = $value;
                    }
                }
                $updateData['meta'] = $meta;
                $hasAtLeastOneField = true;
            }

            if (!$hasAtLeastOneField) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one field (category_id, title, question, required, sort_order, active, or meta) must be provided for update',
                ], 400);
            }

            $question->update($updateData);
            $question->load('category');

            return response()->json([
                'success' => true,
                'message' => 'Question updated successfully',
                'data' => [
                    'id' => $question->id,
                    'title' => $question->title,
                    'question' => $question->question,
                    'required' => $question->required,
                    'sort_order' => $question->sort_order,
                    'active' => $question->active,
                    'meta' => $question->meta,
                    'created_at' => $question->created_at,
                    'updated_at' => $question->updated_at,
                    'category' => $question->category,
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error updating question: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'question_id' => $request->input('id'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update question',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function reorderQuestions(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'questions' => 'required|array',
                'questions.*.id' => 'required|integer|exists:evaluation_questions,id',
                'questions.*.sortOrder' => 'required|integer|min:0',
            ]);

            foreach ($validated['questions'] as $questionData) {
                EvaluationQuestion::where('id', $questionData['id'])
                    ->update(['sort_order' => $questionData['sortOrder']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Questions reordered successfully.',
            ]);
        } catch (ValidationException $e) {
            Log::error('Error Validation reordering question: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'question_id' => $request->input('id'),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error reordering question: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'question_id' => $request->input('id'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get all question options
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getQuestionOptions(Request $request): JsonResponse
    {
        try {
            /** @var V4User $user */
            $user = Auth::guard('v4api')->user();

            $options = EvaluationQuestionOption::with(['question.category'])
                ->orderBy('question_id')
                ->orderBy('sort_order')
                ->get();

            // Transform the data for API response
            $optionsData = $options->map(function ($option) {
                return [
                    'id' => $option->id,
                    'title' => $option->title,
                    'option' => $option->option,
                    'rating' => (float) $option->rating,
                    'sort_order' => $option->sort_order,
                    'meta' => $option->meta,
                    'active' => $option->active,
                    'created_at' => $option->created_at,
                    'updated_at' => $option->updated_at,
                    'question' => [
                        'id' => $option->question->id,
                        'title' => $option->question->title,
                        'question' => $option->question->question,
                        'required' => $option->question->required,
                        'sort_order' => $option->question->sort_order,
                        'active' => $option->question->active,
                        'meta' => $option->question->meta,
                        'created_at' => $option->question->created_at,
                        'updated_at' => $option->question->updated_at,
                        'category' => $option->question->category,
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Question options retrieved successfully',
                'data' => [
                    'options' => $optionsData,
                ],
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching question options: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve question options',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function getQuestionOptionsById($id): JsonResponse
    {
        try {
            // Optionally validate manually
            if (!is_numeric($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid ID',
                ], 400);
            }

            // Get the question option with full question and category information
            $options = EvaluationQuestionOption::with(['question.category'])
                ->where('question_id', $id)
                ->get();

            if (!$options) {
                return response()->json([
                    'success' => false,
                    'message' => 'Question option not found',
                ], 404);
            }

            // Transform the data for API response
            $optionsData = $options->map(function ($option) {
                return [
                    'id' => $option->id,
                    'title' => $option->title,
                    'option' => $option->option,
                    'rating' => (float) $option->rating,
                    'sortOrder' => $option->sort_order,
                    'meta' => $option->meta,
                    'active' => $option->active,
                    'created_at' => $option->created_at,
                    'updated_at' => $option->updated_at,
                    'question' => [
                        'id' => $option->question->id,
                        'title' => $option->question->title,
                        'question' => $option->question->question,
                        'required' => $option->question->required,
                        'sortOrder' => $option->question->sort_order,
                        'active' => $option->question->active,
                        'meta' => $option->question->meta,
                        'created_at' => $option->question->created_at,
                        'updated_at' => $option->question->updated_at,
                        'category' => $option->question->category,
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Question option retrieved successfully',
                'data' => $optionsData,
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching question option: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'option_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve question option',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get a single question option by ID
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function getQuestionOption(Request $request, int $id): JsonResponse
    {
        try {
            /** @var V4User $user */
            $user = Auth::guard('v4api')->user();

            // Get the question option with full question and category information
            $option = EvaluationQuestionOption::with(['question.category'])
                ->find($id);

            if (!$option) {
                return response()->json([
                    'success' => false,
                    'message' => 'Question option not found',
                ], 404);
            }

            // Transform the data for API response
            $optionData = [
                'id' => $option->id,
                'title' => $option->title,
                'option' => $option->option,
                'rating' => (float) $option->rating,
                'sort_order' => $option->sort_order,
                'meta' => $option->meta,
                'active' => $option->active,
                'created_at' => $option->created_at,
                'updated_at' => $option->updated_at,
                'question' => [
                    'id' => $option->question->id,
                    'title' => $option->question->title,
                    'question' => $option->question->question,
                    'required' => $option->question->required,
                    'sort_order' => $option->question->sort_order,
                    'active' => $option->question->active,
                    'meta' => $option->question->meta,
                    'created_at' => $option->question->created_at,
                    'updated_at' => $option->question->updated_at,
                    'category' => $option->question->category,
                ],
            ];

            return response()->json([
                'success' => true,
                'message' => 'Question option retrieved successfully',
                'data' => $optionData,
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching question option: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'option_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve question option',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Create a new question option
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createQuestionOption(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'question_id' => 'required|integer|exists:evaluation_questions,id',
                'title' => 'nullable|string|max:255',
                'option' => 'required|string',
                'rating' => 'required|numeric|min:0|max:5',
                'sort_order' => 'nullable|integer|min:1',
                'meta' => 'nullable|array',
                'meta.*' => 'string',
            ]);

            // Validate rating is in multiples of 0.5
            if (fmod($validated['rating'], 0.5) !== 0.0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rating must be in multiples of 0.5 only',
                ], 400);
            }

            // Check for duplicate option text in the same question
            $existingOption = EvaluationQuestionOption::where('question_id', $validated['question_id'])
                ->where('option', $validated['option'])
                ->first();

            if ($existingOption) {
                return response()->json([
                    'success' => false,
                    'message' => 'Option text already exists for this question',
                ], 400);
            }

            // If sort_order not provided, get the next available order for this question
            if (!isset($validated['sort_order'])) {
                $maxSortOrder = EvaluationQuestionOption::where('question_id', $validated['question_id'])->max('sort_order') ?? 0;
                $validated['sort_order'] = $maxSortOrder + 1;
            } else {
                // Check for duplicate sort_order in the same question
                $existingSortOrder = EvaluationQuestionOption::where('question_id', $validated['question_id'])
                    ->where('sort_order', $validated['sort_order'])
                    ->first();

                if ($existingSortOrder) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Sort order already exists for this question',
                    ], 400);
                }
            }

            // Check for duplicate rating in the same question
            $existingRating = EvaluationQuestionOption::where('question_id', $validated['question_id'])
                ->where('rating', $validated['rating'])
                ->first();

            if ($existingRating) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rating already exists for this question',
                ], 400);
            }

            // Handle meta data
            $meta = null;
            if (isset($validated['meta']) && is_array($validated['meta'])) {
                $meta = [];
                foreach ($validated['meta'] as $key => $value) {
                    if (!is_string($key) || !is_string($value)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Meta keys and values must be strings',
                        ], 400);
                    }
                    $meta[$key] = $value;
                }
            }
            $validated['meta'] = $meta;

            $option = EvaluationQuestionOption::create($validated);
            $option->load('question.category');

            return response()->json([
                'success' => true,
                'message' => 'Question option created successfully',
                'data' => [
                    'id' => $option->id,
                    'title' => $option->title,
                    'option' => $option->option,
                    'rating' => (float) $option->rating,
                    'sort_order' => $option->sort_order,
                    'meta' => $option->meta,
                    'created_at' => $option->created_at,
                    'updated_at' => $option->updated_at,
                    'question' => [
                        'id' => $option->question->id,
                        'title' => $option->question->title,
                        'question' => $option->question->question,
                        'required' => $option->question->required,
                        'sort_order' => $option->question->sort_order,
                        'active' => $option->question->active,
                        'meta' => $option->question->meta,
                        'created_at' => $option->question->created_at,
                        'updated_at' => $option->question->updated_at,
                        'category' => $option->question->category,
                    ],
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error creating question option: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create question option',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Update a question option by ID
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateQuestionOption(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:evaluation_question_options,id',
                'question_id' => 'sometimes|nullable|integer|exists:evaluation_questions,id',
                'title' => 'sometimes|nullable|string|max:255',
                'option' => 'sometimes|nullable|string',
                'rating' => 'sometimes|nullable|numeric|min:0|max:5',
                'sort_order' => 'sometimes|nullable|integer|min:1',
                'meta' => 'sometimes|nullable|array',
                'active' => 'sometimes|required|boolean',
                'meta.*' => 'string',
            ]);

            $option = EvaluationQuestionOption::findOrFail($validated['id']);

            $updateData = [];
            $hasAtLeastOneField = false;

            // Handle question_id update (can be null to remove association)
            if (array_key_exists('question_id', $validated)) {
                $updateData['question_id'] = $validated['question_id'];
                $hasAtLeastOneField = true;
            }

            // Handle title update
            if (array_key_exists('title', $validated)) {
                $updateData['title'] = $validated['title'];
                $hasAtLeastOneField = true;
            }

            // Handle option text update with duplicate check
            if (array_key_exists('option', $validated)) {
                $questionIdToCheck = isset($validated['question_id']) ? $validated['question_id'] : $option->question_id;

                if ($validated['option'] !== $option->option) {
                    $existingOption = EvaluationQuestionOption::where('question_id', $questionIdToCheck)
                        ->where('option', $validated['option'])
                        ->where('id', '!=', $validated['id'])
                        ->first();

                    if ($existingOption) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Option text already exists for this question',
                        ], 400);
                    }
                }
                $updateData['option'] = $validated['option'];
                $hasAtLeastOneField = true;
            }

            // Handle rating update with validation and duplicate check
            if (array_key_exists('rating', $validated)) {
                // Only validate rating format if it's not null
                if ($validated['rating'] !== null) {
                    // Validate rating is in multiples of 0.5
                    if (fmod($validated['rating'], 0.5) !== 0.0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Rating must be in multiples of 0.5 only',
                        ], 400);
                    }

                    $questionIdToCheck = isset($validated['question_id']) ? $validated['question_id'] : $option->question_id;

                    if ($validated['rating'] !== $option->rating) {
                        $existingRating = EvaluationQuestionOption::where('question_id', $questionIdToCheck)
                            ->where('rating', $validated['rating'])
                            ->where('id', '!=', $validated['id'])
                            ->first();

                        if ($existingRating) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Rating already exists for this question',
                            ], 400);
                        }
                    }
                }
                $updateData['rating'] = $validated['rating'];
                $hasAtLeastOneField = true;
            }

            // Handle sort_order with duplicate check
            if (isset($validated['sort_order'])) {
                $activeToCheck = isset($validated['active']) ? $validated['active'] : $option->active;
                $questionIdToCheck = isset($validated['question_id']) ? $validated['question_id'] : $option->question_id;

                if ($activeToCheck === true) {
                    $existingSortOrder = EvaluationQuestionOption::where('question_id', $questionIdToCheck)
                        ->where('sort_order', $validated['sort_order'])
                        ->where('id', '!=', $validated['id'])
                        ->where('active', true)
                        ->first();

                    if ($existingSortOrder) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Sort order already exists for this question',
                        ], 400);
                    }
                }
                $updateData['sort_order'] = $validated['sort_order'];
                $hasAtLeastOneField = true;
            }
            if (isset($validated['active'])) {
                if ($validated['active'] === true) {

                    $sortOrderToCheck = isset($validated['sort_order']) ? $validated['sort_order'] : $option->sort_order;
                    $questionIdToCheck = isset($validated['question_id']) ? $validated['question_id'] : $option->question_id;

                    $existingSortOrder = EvaluationQuestionOption::where('question_id', $questionIdToCheck)
                        ->where('sort_order', $sortOrderToCheck)
                        ->where('id', '!=', $validated['id'])
                        ->where('active', true)
                        ->first();

                    if ($existingSortOrder) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Cannot activate record: Sort order already exists for this question',
                        ], 400);
                    }
                }

                $updateData['active'] = $validated['active'];
                $hasAtLeastOneField = true;
            }

            // Handle meta data
            if (array_key_exists('meta', $validated)) {
                $meta = null;
                if (is_array($validated['meta'])) {
                    $meta = [];
                    foreach ($validated['meta'] as $key => $value) {
                        if (!is_string($key) || !is_string($value)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Meta keys and values must be strings',
                            ], 400);
                        }
                        $meta[$key] = $value;
                    }
                }
                $updateData['meta'] = $meta;
                $hasAtLeastOneField = true;
            }

            if (!$hasAtLeastOneField) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one field (question_id, title, option, rating, sort_order, or meta) must be provided for update',
                ], 400);
            }

            $option->update($updateData);
            $option->load('question.category');

            return response()->json([
                'success' => true,
                'message' => 'Question option updated successfully',
                'data' => [
                    'id' => $option->id,
                    'title' => $option->title,
                    'option' => $option->option,
                    'rating' => (float) $option->rating,
                    'sort_order' => $option->sort_order,
                    'meta' => $option->meta,
                    'active' => $option->active,
                    'created_at' => $option->created_at,
                    'updated_at' => $option->updated_at,
                    'question' => [
                        'id' => $option->question->id,
                        'title' => $option->question->title,
                        'question' => $option->question->question,
                        'required' => $option->question->required,
                        'sort_order' => $option->question->sort_order,
                        'active' => $option->question->active,
                        'meta' => $option->question->meta,
                        'created_at' => $option->question->created_at,
                        'updated_at' => $option->question->updated_at,
                        'category' => $option->question->category,
                    ],
                ],
            ], 200);
        } catch (ValidationException $e) {
            Log::error('Error Validation updating question option: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'option_id' => $request->input('id'),
                'trace' => $e->getTraceAsString(),
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error updating question option: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'option_id' => $request->input('id'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update question option',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Delete a question option by ID
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteQuestionOption(Request $request, int $id): JsonResponse
    {
        try {

            $option = EvaluationQuestionOption::findOrFail($id);
            $option->delete();

            return response()->json([
                'success' => true,
                'message' => 'Question option deleted successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error deleting question option: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'option_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete question option',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function reorderQuestionOption(Request $request): JsonResponse
    {

        try {

            $validated = $request->validate([
                'options' => 'required|array',
                'options.*.id' => 'required|integer|exists:evaluation_question_options,id',
                'options.*.sortOrder' => 'required|integer|min:0',
            ]);

            foreach ($validated['options'] as $optionData) {
                EvaluationQuestionOption::where('id', $optionData['id'])
                    ->update(['sort_order' => $optionData['sortOrder']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Options reordered successfully.',
            ]);
        } catch (ValidationException $e) {
            Log::error('Error Validation reordering Options: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'question_id' => $request->input('id'),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error reordering Options: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'question_id' => $request->input('id'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Upload evaluation video or book consultation
     * This function can be called by other controllers
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadEvaluationSubmission(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            $playerId = $user->id;

            // Validate user must be a player
            if (!$user || $user->role !== 'player') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only players can upload evaluation videos or book consultations.',
                ], 403);
            }

            // Validate SKU (common for all)
            $request->validate([
                'sku' => 'required|string|exists:v4_in_app_purchases,sku',
            ]);

            // Get the in-app purchase by SKU
            $inAppPurchase = V4InAppPurchase::where('sku', $request->sku)->active()->first();
            if (!$inAppPurchase) {
                return response()->json(['success' => false, 'message' => 'Invalid SKU'], 400);
            }

            // Get marketplace item to determine type
            $marketplaceItem = V4Marketplace::where('in_app_purchase_id', $inAppPurchase->id)
                ->where('active', true)
                ->first();

            if (!$marketplaceItem) {
                return response()->json(['success' => false, 'message' => 'Marketplace item not found'], 400);
            }

            $marketplaceType = $marketplaceItem->type;

            // Get latest payment request for this player and SKU
            $paymentRequest = V4PaymentRequest::where('player_id', $playerId)
                ->where('in_app_purchase_id', $inAppPurchase->id)
                ->orderBy('updated_at', 'desc')
                ->first();

            // Check payment request status
            if (!$paymentRequest || in_array($paymentRequest->status, [V4PaymentRequest::STATUS_FAILED, V4PaymentRequest::STATUS_PARENT_REJECTED])) {
                return response()->json(['success' => false, 'message' => 'Payment is not done'], 400);
            }

            if ($paymentRequest->status === V4PaymentRequest::STATUS_PENDING) {
                return response()->json(['success' => false, 'message' => 'Payment pending from parent side'], 400);
            }

            if ($paymentRequest->status === V4PaymentRequest::STATUS_PAYMENT_INITIATED) {
                return response()->json(['success' => false, 'message' => 'Payment is under process'], 400);
            }

            // If payment status is paid, proceed based on marketplace type
            if ($paymentRequest->status === V4PaymentRequest::STATUS_PAID) {
                $submission = EvaluationSubmission::where('payment_request_id', $paymentRequest->id)
                    ->where('player_id', $playerId)
                    ->with(['evaluatorAssignment'])
                    ->first();

                // Check submission status
                if ($submission) {
                    if (in_array($submission->status, [EvaluationSubmission::STATUS_UPLOADED, EvaluationSubmission::STATUS_ASSIGNED, EvaluationSubmission::STATUS_IN_PROGRESS, EvaluationSubmission::STATUS_REQUEST_VIDEO, EvaluationSubmission::STATUS_REQUEST_VIDEO_REJECTED])) {
                        return response()->json(['success' => false, 'message' => 'A submission is already in process'], 400);
                    }

                    if ($submission->status === EvaluationSubmission::STATUS_COMPLETED) {
                        return response()->json(['success' => false, 'message' => 'Payment is not done'], 400);
                    }
                }

                // Handle different marketplace types
                if ($marketplaceType === MarketPlaceTypes::PERSONALIZED_VIDEO_EVALUATION) {
                    // === VIDEO EVALUATION LOGIC (default for other types) ===

                    // Validate video file
                    $request->validate([
                        'video' => 'required|file',
                    ]);

                    // Handle file upload
                    if (!$request->hasFile('video')) {
                        return response()->json(['success' => false, 'message' => 'No video file provided'], 400);
                    }

                    $file = $request->file('video');

                    // Validate file
                    if (!$file->isValid()) {
                        return response()->json(['success' => false, 'message' => 'File upload failed: ' . $file->getError()], 422);
                    }

                    $mimeType = $file->getClientMimeType();
                    $fileSize = $file->getSize();

                    if (!str_starts_with($mimeType, 'video/')) {
                        return response()->json(['success' => false, 'message' => 'File must be a video'], 422);
                    }

                    // Check file size (100MB max)
                    $maxSizeInBytes = 100 * 1024 * 1024;
                    if ($fileSize > $maxSizeInBytes) {
                        return response()->json(['success' => false, 'message' => 'Video file size must not exceed 100MB'], 422);
                    }

                    // Generate unique filename
                    $filename = 'eval_video_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                    // Upload to S3 (before transaction)
                    $path = $file->storeAs('evaluation-videos/' . $playerId, $filename, 's3');
                    $videoUrl = Storage::disk('s3')->url($path);
                    $originalName = $file->getClientOriginalName();

                    // Prepare file metadata
                    $fileMeta = [
                        'original_name' => $originalName,
                        'file_size' => $fileSize,
                        'mime_type' => $mimeType,
                        'video_url' => $videoUrl,
                        'marketplace_type' => $marketplaceType,
                        'uploaded_at' => now()->toISOString(),
                    ];

                    // Wrap all database operations in a transaction
                    DB::beginTransaction();
                    try {
                        // Create or update submission
                        if (!$submission) {
                            // Create new submission if not found
                            $submission = EvaluationSubmission::create([
                                'player_id' => $playerId,
                                'payment_request_id' => $paymentRequest->id,
                                'status' => EvaluationSubmission::STATUS_UPLOADED,
                            ]);
                        } elseif (in_array($submission->status, [EvaluationSubmission::STATUS_PENDING])) {
                            // Update pending submission to uploaded
                            $submission->update(['status' => EvaluationSubmission::STATUS_UPLOADED]);
                        } elseif (in_array($submission->status, [EvaluationSubmission::STATUS_REJECTED])) {
                            // 💥 Before updating status, delete old rejection notifications
                            $this->deleteEvaluationRejectionNotifications($submission);

                            // Update rejected submission to assigned
                            $submission->update(['status' => EvaluationSubmission::STATUS_ASSIGNED]);
                            $submission->evaluatorAssignment->update(['status' => EvaluatorAssignment::STATUS_PENDING]);
                        }

                        // Create submission version
                        $submissionVersion = EvaluationSubmissionVersion::create([
                            'submission_id' => $submission->id,
                            'file_path' => $videoUrl,
                            'file_meta' => $fileMeta,
                            'uploaded_by' => $user->id,
                        ]);

                        // Update submission with current version ID
                        $submission->update(['current_version_id' => $submissionVersion->id]);

                        DB::commit();

                        return response()->json([
                            'success' => true,
                            'message' => 'Evaluation video uploaded successfully',
                            'data' => [
                                'player_id' => $playerId,
                                'submission_id' => $submission->id,
                                'submission_version_id' => $submissionVersion->id,
                                'marketplace_type' => $marketplaceType,
                                'status' => $submission->status,
                                'video_url' => $videoUrl,
                                'file_size' => $fileSize,
                                'mime_type' => $mimeType,
                                'uploaded_at' => now()->toISOString(),
                            ],
                        ], 201);
                    } catch (Exception $e) {
                        DB::rollBack();
                        // Optionally delete uploaded file from S3 if DB transaction fails
                        Storage::disk('s3')->delete($path);
                        throw $e;
                    }
                } else if ($marketplaceType === MarketplaceTypes::CONSULTATION_VIDEO_CALL) {
                    // === ONE-ON-ONE CONSULTATION LOGIC ===

                    // Validate consultation-specific fields
                    $validated = $request->validate([
                        'evaluation_id' => 'required|integer|exists:evaluations,id',
                        'consultation_date' => 'required|date|after_or_equal:today',
                        'consultation_time' => 'required|date_format:H:i',
                    ]);

                    // Verify evaluation exists and is submitted
                    $evaluation = Evaluation::find($validated['evaluation_id']);
                    if (!$evaluation || $evaluation->status !== Evaluation::STATUS_SUBMITTED) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid evaluation or evaluation is not submitted'
                        ], 400);
                    }

                    // Verify the evaluation belongs to the player
                    if ($evaluation->submission->player_id !== $playerId) {
                        return response()->json([
                            'success' => false,
                            'message' => 'This evaluation does not belong to you'
                        ], 403);
                    }

                    // Wrap all database operations in a transaction
                    DB::beginTransaction();
                    try {
                        // Create or update submission
                        if (!$submission) {
                            // Create new submission if not found
                            $submission = EvaluationSubmission::create([
                                'player_id' => $playerId,
                                'payment_request_id' => $paymentRequest->id,
                                'status' => EvaluationSubmission::STATUS_UPLOADED,
                            ]);
                        } else if ($submission->status === EvaluationSubmission::STATUS_PENDING) {
                            // Update pending submission to uploaded
                            $submission->update(['status' => EvaluationSubmission::STATUS_UPLOADED]);
                        } elseif ($submission->status === EvaluationSubmission::STATUS_REJECTED) {
                            // Delete old consultation rejection notifications
                            $this->deleteEvaluationRejectionNotifications($submission);

                            // Update rejected submission to assigned
                            $submission->update(['status' => EvaluationSubmission::STATUS_ASSIGNED]);
                            $submission->evaluatorAssignment->update(['status' => EvaluatorAssignment::STATUS_PENDING]);
                        }


                        // Create submission version with consultation details
                        $submissionVersion = EvaluationSubmissionVersion::create([
                            'submission_id' => $submission->id,
                            'report_id' => $validated['evaluation_id'],
                            'consultation_date' => $validated['consultation_date'],
                            'consultation_time' => $validated['consultation_time'],
                            'uploaded_by' => $user->id,
                            'file_path' => "N/A",
                            'file_meta' => [],
                        ]);

                        // Update submission with current version ID
                        $submission->update(['current_version_id' => $submissionVersion->id]);

                        DB::commit();

                        return response()->json([
                            'success' => true,
                            'message' => 'Consultation booked successfully',
                            'data' => [
                                'player_id' => $playerId,
                                'submission_id' => $submission->id,
                                'submission_version_id' => $submissionVersion->id,
                                'evaluation_id' => $validated['evaluation_id'],
                                'consultation_date' => $validated['consultation_date'],
                                'consultation_time' => $validated['consultation_time'],
                                'marketplace_type' => $marketplaceType,
                                'status' => $submission->status,
                                'booked_at' => now()->toISOString(),
                            ],
                        ], 201);
                    } catch (Exception $e) {
                        DB::rollBack();
                        throw $e;
                    }
                } else if ($marketplaceType == MarketplaceTypes::MENTORSHIP_PROGRAM) {
                    // === MENTORSHIP PROGRAM LOGIC ===

                    // Validate mentorship-specific fields
                    $validated = $request->validate([
                        'weekday' => [
                            'required',
                            'string',
                            'in:' . implode(',', Weekdays::all())
                        ],
                        'time' => 'required|date_format:H:i',
                        'video' => 'nullable|file',
                        'evaluation_id' => 'nullable|integer|exists:evaluations,id',
                    ]);

                    // Ensure exactly one of video or evaluation_id is provided
                    if (!$request->hasFile('video') xor !empty($validated['evaluation_id'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Either video or evaluation_id is required',
                        ], 422);
                    }

                    // Handle evaluation_id (evaluation-based mentorship)
                    if (!empty($validated['evaluation_id'])) {
                        // Verify evaluation exists and is submitted
                        $evaluation = Evaluation::find($validated['evaluation_id']);
                        if (!$evaluation || $evaluation->status !== Evaluation::STATUS_SUBMITTED) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Invalid evaluation or evaluation is not submitted'
                            ], 400);
                        }

                        // Verify the evaluation belongs to the player
                        if ($evaluation->submission->player_id !== $playerId) {
                            return response()->json([
                                'success' => false,
                                'message' => 'This evaluation does not belong to you'
                            ], 403);
                        }

                        // Create/update submission with evaluation_id
                        DB::beginTransaction();
                        try {
                            if (!$submission) {
                                $submission = EvaluationSubmission::create([
                                    'player_id' => $playerId,
                                    'payment_request_id' => $paymentRequest->id,
                                    'status' => EvaluationSubmission::STATUS_UPLOADED,
                                ]);
                            } else if ($submission->status === EvaluationSubmission::STATUS_PENDING) {
                                // Update pending submission to uploaded
                                $submission->update(['status' => EvaluationSubmission::STATUS_UPLOADED]);
                            } elseif ($submission->status === EvaluationSubmission::STATUS_REJECTED) {
                                // Delete old consultation rejection notifications
                                $this->deleteEvaluationRejectionNotifications($submission);

                                // Update rejected submission to assigned
                                $submission->update(['status' => EvaluationSubmission::STATUS_ASSIGNED]);
                                $submission->evaluatorAssignment->update(['status' => EvaluatorAssignment::STATUS_PENDING]);
                            }

                            $submissionVersion = EvaluationSubmissionVersion::create([
                                'submission_id' => $submission->id,
                                'report_id' => $validated['evaluation_id'],
                                'mentorship_weekday' => $validated['weekday'],
                                'consultation_time' => $validated['time'],
                                'mentorship_upload_type' => EvaluationSubmissionVersion::MENTORSHIP_UPLOAD_TYPE_SUBMITTED_VIDEO,
                                'file_path' => 'N/A',
                                'uploaded_by' => $user->id,
                                'file_meta' => [],
                            ]);

                            $submission->update(['current_version_id' => $submissionVersion->id]);

                            DB::commit();

                            return response()->json([
                                'success' => true,
                                'message' => 'Mentorship program booked successfully with evaluation',
                                'data' => [
                                    'player_id' => $playerId,
                                    'submission_id' => $submission->id,
                                    'submission_version_id' => $submissionVersion->id,
                                    'weekday' => $validated['weekday'],
                                    'time' => $validated['time'],
                                    'evaluation_id' => $validated['evaluation_id'],
                                    'status' => $submission->status,
                                    'booked_at' => now()->toISOString(),
                                ],
                            ], 201);
                        } catch (Exception $e) {
                            DB::rollBack();
                            throw $e;
                        }
                    }

                    // Handle video (video-based mentorship with S3 upload)
                    else if ($request->hasFile('video')) {
                        $file = $request->file('video');

                        // Validate file
                        if (!$file->isValid()) {
                            return response()->json(['success' => false, 'message' => 'File upload failed: ' . $file->getError()], 422);
                        }

                        $mimeType = $file->getClientMimeType();
                        $fileSize = $file->getSize();

                        if (!str_starts_with($mimeType, 'video/')) {
                            return response()->json(['success' => false, 'message' => 'File must be a video'], 422);
                        }

                        // Check file size (100MB max)
                        $maxSizeInBytes = 100 * 1024 * 1024;
                        if ($fileSize > $maxSizeInBytes) {
                            return response()->json(['success' => false, 'message' => 'Video file size must not exceed 100MB'], 422);
                        }

                        // Generate unique filename
                        $filename = 'mentorship_video_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                        // Upload to S3 (before transaction)
                        $path = $file->storeAs('mentorship-videos/' . $playerId, $filename, 's3');
                        $videoUrl = Storage::disk('s3')->url($path);
                        $originalName = $file->getClientOriginalName();

                        // Prepare file metadata
                        $fileMeta = [
                            'type' => 'mentorship_with_video',
                            'original_name' => $originalName,
                            'file_size' => $fileSize,
                            'mime_type' => $mimeType,
                            'video_url' => $videoUrl,
                            'marketplace_type' => $marketplaceType,
                            'booked_at' => now()->toISOString(),
                        ];

                        // Create/update submission with video
                        DB::beginTransaction();
                        try {
                            if (!$submission) {
                                $submission = EvaluationSubmission::create([
                                    'player_id' => $playerId,
                                    'payment_request_id' => $paymentRequest->id,
                                    'status' => EvaluationSubmission::STATUS_UPLOADED,
                                ]);
                            } else if ($submission->status === EvaluationSubmission::STATUS_PENDING) {
                                // Update pending submission to uploaded
                                $submission->update(['status' => EvaluationSubmission::STATUS_UPLOADED]);
                            } elseif ($submission->status === EvaluationSubmission::STATUS_REJECTED) {
                                // Delete old consultation rejection notifications
                                $this->deleteEvaluationRejectionNotifications($submission);

                                // Update rejected submission to assigned
                                $submission->update(['status' => EvaluationSubmission::STATUS_ASSIGNED]);
                                $submission->evaluatorAssignment->update(['status' => EvaluatorAssignment::STATUS_PENDING]);
                            }

                            $submissionVersion = EvaluationSubmissionVersion::create([
                                'submission_id' => $submission->id,
                                'report_id' => null,
                                'mentorship_weekday' => $validated['weekday'],
                                'consultation_time' => $validated['time'],
                                'file_path' => $videoUrl,
                                'uploaded_by' => $user->id,
                                'file_meta' => $fileMeta,
                            ]);

                            $submission->update(['current_version_id' => $submissionVersion->id]);

                            DB::commit();

                            return response()->json([
                                'success' => true,
                                'message' => 'Mentorship program booked successfully with video',
                                'data' => [
                                    'player_id' => $playerId,
                                    'submission_id' => $submission->id,
                                    'submission_version_id' => $submissionVersion->id,
                                    'weekday' => $validated['weekday'],
                                    'time' => $validated['time'],
                                    'video_url' => $videoUrl,
                                    'file_size' => $fileSize,
                                    'mime_type' => $mimeType,
                                    'status' => $submission->status,
                                    'booked_at' => now()->toISOString(),
                                ],
                            ], 201);
                        } catch (Exception $e) {
                            DB::rollBack();
                            // Delete uploaded file from S3 if DB transaction fails
                            Storage::disk('s3')->delete($path);
                            throw $e;
                        }
                    }

                    return response()->json(['success' => false, 'message' => 'Something went wrong in validation'], 400);
                }
            }

            return response()->json(['success' => false, 'message' => 'Marketplace type not supported'], 400);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Error processing evaluation submission: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'sku' => $request->sku ?? 'unknown',
                'marketplace_type' => $marketplaceType ?? 'unknown',
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process submission',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get evaluation videos for a user from S3
     *
     * @param Request $request
     * @param int $userId (optional) - if provided, gets videos for this user
     * @return JsonResponse
     */
    public function getEvaluationVideos(Request $request, $userId = null): JsonResponse
    {
        try {
            // Get user - either from parameter or authenticated user
            if ($userId) {
                $user = V4User::findOrFail($userId);
            } else {
                /** @var V4User $user */
                $user = Auth::guard('v4api')->user();
            }

            // Get all files from the user's evaluation-videos directory
            $files = Storage::disk('s3')->files('evaluation-videos/' . $user->id);

            $videos = [];
            foreach ($files as $file) {
                // Only include video files
                if (preg_match('/\.(mp4|avi|mov|wmv|flv|webm)$/i', $file)) {
                    $videos[] = [
                        'file_path' => $file,
                        'video_url' => Storage::disk('s3')->url($file),
                        'filename' => basename($file),
                        'size' => Storage::disk('s3')->size($file),
                        'last_modified' => Storage::disk('s3')->lastModified($file),
                        'mime_type' => Storage::disk('s3')->mimeType($file),
                    ];
                }
            }

            // Sort by last modified (newest first)
            usort($videos, function ($a, $b) {
                return $b['last_modified'] - $a['last_modified'];
            });

            return response()->json([
                'success' => true,
                'message' => 'Evaluation videos retrieved successfully',
                'data' => [
                    'videos' => $videos,
                    'total_videos' => count($videos),
                    'total_size' => array_sum(array_column($videos, 'size')),
                ],
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching evaluation videos: ' . $e->getMessage(), [
                'user_id' => $userId ?? Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve evaluation videos',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }


    public function getAllEvaluationRequests(Request $request): JsonResponse
    {
        try {

            $validated = $request->validate([
                'q' => 'nullable|string|max:255',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'sort_by' => 'nullable|string|in:first_name,last_name,role,created_at',
                'sort_order' => 'nullable|string|in:asc,desc',
            ]);

            $searchTerm = $validated['q'] ?? '';
            $page = $validated['page'] ?? 1;
            $perPage = $validated['per_page'] ?? 10;
            $sortBy = $validated['sort_by'] ?? 'created_at';
            $sortOrder = $validated['sort_order'] ?? 'desc';

            $query = EvaluationSubmission::query();

            $query->with([
                'player',
                'paymentRequest.inAppPurchase.marketplaceItem',
                'versions.report.submission.currentVersion',
                'evaluatorAssignment',
                'consultationRequests.evaluator' => function ($q) {
                    $q->latest('created_at');
                },
                'currentVersion',
                'evaluations'
            ]);

            // if (! empty($searchTerm)) {
            //     $query->where(function ($q) use ($searchTerm) {
            //         $q->where('first_name', 'ilike', "%{$searchTerm}%")
            //             ->orWhere('last_name', 'ilike', "%{$searchTerm}%");
            //     });
            // }

            // Handle sorting by related model field
            if ($sortBy === 'current_version_created_at') {
                $query->leftJoin('evaluation_submission_versions as current_versions', 'current_versions.id', '=', 'evaluation_submissions.current_version_id')
                    ->orderBy('current_versions.created_at', $sortOrder)
                    ->select('evaluation_submissions.*'); // Important to avoid overriding base model fields
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }

            $data = collect();

            $submissions = $query->paginate($perPage, ['*'], 'page', $page);

            $data = $data->merge($submissions->map(function ($submission) {
                $result = [
                    'id' => $submission->id,
                    'playerId' => $submission->player->id,
                    'playerName' => $submission->player->name,
                    'type' => $submission->paymentRequest->inAppPurchase->marketplaceItem->type,
                    'purchaseDate' => $submission->paymentRequest->paymentTransaction->updated_at,
                    'price' => $submission->paymentRequest->amount_cents,
                    'updated_at' => $submission->updated_at,
                ];

                if ($submission->versions->isNotEmpty()) {
                    $result['materials'] = $submission->versions->map(function ($version) use ($result) {
                        if ($result['type'] == MarketplaceTypes::PERSONALIZED_VIDEO_EVALUATION) {
                            $fileMeta = $version->file_meta;
                            return [
                                'type' => 'video',
                                'id' => $version->id,
                                'name' => $fileMeta['original_name'],
                                'url' => $fileMeta['video_url'],
                                'uploadedAt' => $fileMeta['uploaded_at'],
                            ];
                        } else if ($result['type'] == MarketplaceTypes::CONSULTATION_VIDEO_CALL) {
                            $fileMeta = $version->report->submission->currentVersion->file_meta;
                            return [
                                'type' => 'consultation_report',
                                'id' => $version->id,
                                'reportId' => $version->report_id,
                                'consultationDate' => $version->consultation_date,
                                'consultationTime' => $version->consultation_time,
                                'name' => $fileMeta['original_name'] ?? '',
                                'url' => $fileMeta['video_url'] ?? '',
                                'uploadedAt' => $fileMeta['uploaded_at'] ?? '',
                                'feedback' => $version->feedback,
                            ];
                        } else if ($result['type'] == MarketplaceTypes::MENTORSHIP_PROGRAM) {
                            $fileMeta = $version->file_meta;
                            return [
                                'type' => 'mentorship_report',
                                'id' => $version->id,
                                'name' => $fileMeta['original_name'] ?? '',
                                'url' => $fileMeta['video_url'] ?? '',
                                'uploadedAt' => $fileMeta['uploaded_at'] ?? '',
                                'reportId' => $version->report_id ?? '',
                                'consultationDate' => $version->consultation_date ?? '',
                                'consultationTime' => $version->consultation_time ?? '',
                                'mentorshipWeekday' => $version->mentorship_weekday ?? '',
                            ];
                        } else {
                            $fileMeta = $version->file_meta;
                            return [
                                'type' => 'report',
                                'id' => $version->id,
                            ];
                        }
                    });
                } else {
                    $result['materials'] = [];
                }

                if ($submission->evaluatorAssignment != null) {
                    $result['assignedEvaluatorId'] = $submission->evaluatorAssignment->evaluator->id;
                    $result['assignedEvaluatorName'] = $submission->evaluatorAssignment->evaluator->name;
                    $result['status'] = $submission->status;
                } else if ($submission->consultationRequests->isNotEmpty()) {
                    $latestConsultationRequest = $submission->consultationRequests->first();
                    if ($latestConsultationRequest) {
                        $result['consultationRequest'] = [
                            'id' => $latestConsultationRequest->id,
                            'status' => $latestConsultationRequest->status,
                            'evaluatorId' => $latestConsultationRequest->evaluator_id,
                            'evaluationId' => $latestConsultationRequest->evaluation_id,
                            'submissionVersionId' => $latestConsultationRequest->submission_version_id,
                            'adminNotes' => $latestConsultationRequest->admin_notes,
                            'evaluatorNotes' => $latestConsultationRequest->evaluator_notes,
                        ];
                        $result['assignedEvaluatorId'] = $latestConsultationRequest->evaluator->id;
                        $result['assignedEvaluatorName'] = $latestConsultationRequest->evaluator->id;
                        $result['status'] = $latestConsultationRequest->status;
                    } else {
                        $result['status'] = 'pending_assignment';
                    }
                } else {
                    $result['status'] = 'pending_assignment';
                }

                if ($submission['status'] === 'completed') {
                    $result['completedDate'] = $submission->updated_at;
                }

                return $result;
            }));

            return response()->json([
                'data' => $data,
                'pagination' => [
                    'total' => $submissions->total(),
                    'per_page' => $submissions->perPage(),
                    'current_page' => $submissions->currentPage(),
                    'last_page' => $submissions->lastPage(),
                    'from' => $submissions->firstItem() ?? 0,
                    'to' => $submissions->lastItem() ?? 0,
                    'has_more_pages' => $submissions->hasMorePages(),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error allotting evaluator for submission: ' . $e->getMessage(), [
                'evaluator_id' => $request->input('evaluator_id'),
                'submission_id' => $request->input('submission_id'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to allot evaluator',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function getEvaluationRequestById(Request $request, int $id): JsonResponse
    {
        try {
            // Validate that the ID is a valid integer and exists in the database.
            // No need to validate as part of the request body anymore.
            // The id is already passed as a route parameter.

            // Fetch the specific submission by ID, with related models
            $submission = EvaluationSubmission::with([
                'player',
                'paymentRequest.inAppPurchase.marketplaceItem',
                'versions.report.submission.currentVersion',
                'consultationRequests.evaluator' => function ($q) {
                    $q->latest('created_at');
                },
                'currentVersion',
                'evaluatorAssignment',
                'evaluations'
            ])->findOrFail($id); // This will automatically throw a 404 if not found

            // Prepare the response data
            $result = [
                'id' => $submission->id,
                'playerId' => $submission->player->id,
                'playerName' => $submission->player->name,
                'type' => $submission->paymentRequest->inAppPurchase->marketplaceItem->type,
                'purchaseDate' => $submission->paymentRequest->paymentTransaction->updated_at,
                'price' => $submission->paymentRequest->amount_cents,
                'updated_at' => $submission->updated_at,
            ];

            // Include the materials if available
            if ($submission->versions->isNotEmpty()) {
                $result['materials'] = $submission->versions->map(function ($version) use ($result) {
                    if ($result['type'] == MarketplaceTypes::PERSONALIZED_VIDEO_EVALUATION) {
                        $fileMeta = $version->file_meta;
                        return [
                            'type' => 'video',
                            'id' => $version->id,
                            'name' => $fileMeta['original_name'],
                            'url' => $fileMeta['video_url'],
                            'uploadedAt' => $fileMeta['uploaded_at'],
                        ];
                    } else if ($result['type'] == MarketplaceTypes::CONSULTATION_VIDEO_CALL) {
                        $fileMeta = $version->report->submission->currentVersion->file_meta;
                        return [
                            'type' => 'consultation_report',
                            'id' => $version->id,
                            'reportId' => $version->report_id,
                            'consultationDate' => $version->consultation_date,
                            'consultationTime' => $version->consultation_time,
                            'name' => $fileMeta['original_name'] ?? '',
                            'url' => $fileMeta['video_url'] ?? '',
                            'uploadedAt' => $fileMeta['uploaded_at'] ?? '',
                            'feedback' => $version->feedback,
                        ];
                    } else if ($result['type'] == MarketplaceTypes::MENTORSHIP_PROGRAM) {
                        $fileMeta = $version->file_meta;
                        return [
                            'type' => 'mentorship_report',
                            'id' => $version->id,
                            'name' => $fileMeta['original_name'] ?? '',
                            'url' => $fileMeta['video_url'] ?? '',
                            'uploadedAt' => $fileMeta['uploaded_at'] ?? '',
                            'reportId' => $version->report_id ?? '',
                            'consultationDate' => $version->consultation_date ?? '',
                            'consultationTime' => $version->consultation_time ?? '',
                            'mentorshipWeekday' => $version->mentorship_weekday ?? '',
                        ];
                    } else {
                        $fileMeta = $version->file_meta;
                        return [
                            'type' => 'report',
                            'id' => $version->id,
                        ];
                    }
                });
            } else {
                $result['materials'] = [];
            }

            // Include evaluator details if assigned
            if ($submission->evaluatorAssignment != null) {
                $result['assignedEvaluatorId'] = $submission->evaluatorAssignment->evaluator->id;
                $result['assignedEvaluatorName'] = $submission->evaluatorAssignment->evaluator->name;
                $result['status'] = $submission->status;
            } else if ($submission->consultationRequests->isNotEmpty()) {
                $latestConsultationRequest = $submission->consultationRequests->first();
                if ($latestConsultationRequest) {
                    $result['consultationRequest'] = [
                        'id' => $latestConsultationRequest->id,
                        'status' => $latestConsultationRequest->status,
                        'evaluatorId' => $latestConsultationRequest->evaluator_id,
                        'evaluationId' => $latestConsultationRequest->evaluation_id,
                        'submissionVersionId' => $latestConsultationRequest->submission_version_id,
                        'adminNotes' => $latestConsultationRequest->admin_notes,
                        'evaluatorNotes' => $latestConsultationRequest->evaluator_notes,
                    ];
                    $result['assignedEvaluatorId'] = $latestConsultationRequest->evaluator->id;
                    $result['assignedEvaluatorName'] = $latestConsultationRequest->evaluator->id;
                    $result['status'] = $latestConsultationRequest->status;
                } else {
                    $result['status'] = 'pending_assignment';
                }
            } else {
                $result['status'] = 'pending_assignment';
            }


            // Add completed date if the status is 'completed'
            if ($submission->status === 'completed') {
                $result['completedDate'] = $submission->updated_at;
            }

            return response()->json([
                'data' => $result,
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle case where the record is not found
            Log::error('Evaluation Submission not found', [
                'submission_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Evaluation submission not found',
            ], 404);
        } catch (Exception $e) {
            // Log any other unexpected errors
            Log::error('Error fetching evaluation submission by ID: ' . $e->getMessage(), [
                'submission_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch evaluation submission',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function getEvaluationRequestByIdAndReportId(Request $request, int $id, int $reportId): JsonResponse
    {
        try {
            // Validate that the ID is a valid integer and exists in the database.
            // No need to validate as part of the request body anymore.
            // The id is already passed as a route parameter.

            // Fetch the specific submission by ID, with related models
            $submission = EvaluationSubmission::with([
                'player',
                'paymentRequest.inAppPurchase.marketplaceItem',
                'versions.report.submission.currentVersion',
                'consultationRequests.evaluator' => function ($q) {
                    $q->latest('created_at');
                },
                'currentVersion',
                'evaluatorAssignment',
                'evaluations',
                'evaluation',
            ])
                ->whereHas('evaluation', function ($q) use ($reportId) {
                    $q->where('id', $reportId);
                }) // or 'report_id', depending on your schema
                ->firstOrFail();

            // Prepare the response data
            $result = [
                'id' => $submission->id,
                'playerId' => $submission->player->id,
                'playerName' => $submission->player->name,
                'type' => $submission->paymentRequest->inAppPurchase->marketplaceItem->type,
                'purchaseDate' => $submission->paymentRequest->paymentTransaction->updated_at,
                'price' => $submission->paymentRequest->amount_cents,
                'updated_at' => $submission->updated_at,
            ];

            // Include the materials if available
            if ($submission->versions->isNotEmpty()) {
                $result['materials'] = $submission->versions->map(function ($version) use ($result) {
                    if ($result['type'] == MarketplaceTypes::PERSONALIZED_VIDEO_EVALUATION) {
                        $fileMeta = $version->file_meta;
                        return [
                            'type' => 'video',
                            'id' => $version->id,
                            'name' => $fileMeta['original_name'],
                            'url' => $fileMeta['video_url'],
                            'uploadedAt' => $fileMeta['uploaded_at'],
                        ];
                    } else if ($result['type'] == MarketplaceTypes::CONSULTATION_VIDEO_CALL) {
                        $fileMeta = $version->report->submission->currentVersion->file_meta;
                        return [
                            'type' => 'consultation_report',
                            'id' => $version->id,
                            'reportId' => $version->report_id,
                            'consultationDate' => $version->consultation_date,
                            'consultationTime' => $version->consultation_time,
                            'name' => $fileMeta['original_name'] ?? '',
                            'url' => $fileMeta['video_url'] ?? '',
                            'uploadedAt' => $fileMeta['uploaded_at'] ?? '',
                            'feedback' => $version->feedback,
                        ];
                    } else if ($result['type'] == MarketplaceTypes::MENTORSHIP_PROGRAM) {
                        $fileMeta = $version->file_meta;
                        return [
                            'type' => 'mentorship_report',
                            'id' => $version->id,
                            'name' => $fileMeta['original_name'] ?? '',
                            'url' => $fileMeta['video_url'] ?? '',
                            'uploadedAt' => $fileMeta['uploaded_at'] ?? '',
                            'reportId' => $version->report_id ?? '',
                            'consultationDate' => $version->consultation_date ?? '',
                            'consultationTime' => $version->consultation_time ?? '',
                            'mentorshipWeekday' => $version->mentorship_weekday ?? '',
                        ];
                    } else {
                        $fileMeta = $version->file_meta;
                        return [
                            'type' => 'report',
                            'id' => $version->id,
                        ];
                    }
                });
            } else {
                $result['materials'] = [];
            }

            // Include evaluator details if assigned
            if ($submission->evaluatorAssignment != null) {
                $result['assignedEvaluatorId'] = $submission->evaluatorAssignment->evaluator->id;
                $result['assignedEvaluatorName'] = $submission->evaluatorAssignment->evaluator->name;
                $result['status'] = $submission->status;
            } else if ($submission->consultationRequests->isNotEmpty()) {
                $latestConsultationRequest = $submission->consultationRequests->first();
                if ($latestConsultationRequest) {
                    $result['consultationRequest'] = [
                        'id' => $latestConsultationRequest->id,
                        'status' => $latestConsultationRequest->status,
                        'evaluatorId' => $latestConsultationRequest->evaluator_id,
                        'evaluationId' => $latestConsultationRequest->evaluation_id,
                        'submissionVersionId' => $latestConsultationRequest->submission_version_id,
                        'adminNotes' => $latestConsultationRequest->admin_notes,
                        'evaluatorNotes' => $latestConsultationRequest->evaluator_notes,
                    ];
                    $result['assignedEvaluatorId'] = $latestConsultationRequest->evaluator->id;
                    $result['assignedEvaluatorName'] = $latestConsultationRequest->evaluator->id;
                    $result['status'] = $latestConsultationRequest->status;
                } else {
                    $result['status'] = 'pending_assignment';
                }
            } else {
                $result['status'] = 'pending_assignment';
            }


            // Add completed date if the status is 'completed'
            if ($submission->status === 'completed') {
                $result['completedDate'] = $submission->updated_at;
            }

            return response()->json([
                'data' => $result,
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle case where the record is not found
            Log::error('Evaluation Submission not found', [
                'submission_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Evaluation submission not found',
            ], 404);
        } catch (Exception $e) {
            // Log any other unexpected errors
            Log::error('Error fetching evaluation submission by ID: ' . $e->getMessage(), [
                'submission_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch evaluation submission',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Allot evaluator for submission
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function allotEvaluatorForSubmission(Request $request, int $id): JsonResponse
    {
        $authUser = Auth::guard('v4api')->user();
        try {
            // Validate required fields
            $request->validate([
                'evaluatorId' => 'required|integer|exists:v4_users,id',
            ]);

            $evaluatorId = $request->input('evaluatorId');
            $submissionId = $id;

            // Check if evaluator exists and has evaluator role
            $evaluator = V4User::where('id', $evaluatorId)
                ->where('role', 'evaluator')
                ->first();

            if (!$evaluator) {
                return response()->json([
                    'success' => false,
                    'message' => 'Evaluator not found or does not have evaluator role',
                ], 404);
            }

            // Check if submission exists
            $submission = EvaluationSubmission::find($submissionId);

            if (!$submission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Submission not found',
                ], 404);
            }

            $inAppPurchase = $submission->paymentRequest->inAppPurchase;
            $marketplaceItem = V4Marketplace::where('in_app_purchase_id', $inAppPurchase->id)
                ->where('active', true)
                ->first();

            if (!$marketplaceItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Marketplace item not found',
                ], 400);
            }

            $marketplaceType = $marketplaceItem->type;

            // Handle different marketplace types
            if ($marketplaceType === MarketplaceTypes::PERSONALIZED_VIDEO_EVALUATION) {
                // === PERSONALIZED VIDEO EVALUATION LOGIC ===

                // Check submission status - only allow if status is 'uploaded'
                if ($submission->status !== EvaluationSubmission::STATUS_UPLOADED) {
                    $existingAssignment = EvaluatorAssignment::where('submission_id', $submissionId)->first();
                    if ($existingAssignment) {
                        return response()->json([
                            'success' => false,
                            'message' => "Submission already {$submission->status}",
                            'submission_id' => $submissionId,
                            'evaluator_id' => $existingAssignment->evaluator_id,
                            'current_status' => $submission->status,
                        ], 400);
                    }
                }

                $conversationId = null;
                try {
                    $token = $request->bearerToken();

                    $baseUrl = config('app.env') === 'production' ? config('CHAT_APP_HOST_PRODUCTION') : env('CHAT_APP_HOST');

                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type' => 'application/json',
                    ])->post($baseUrl . '/conversation/create', [
                        'type' => 'single',
                        'participants' => [
                            (string) $authUser->id,
                            (string) $submission->player_id
                        ],
                    ]);

                    if ($response->successful() && isset($response->json()['_id'])) {
                        $conversationId = $response->json()['_id'];
                    } else {
                        Log::warning('Conversation API failed', [
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::error('Conversation API error', ['error' => $e->getMessage()]);
                }

                // Create evaluator assignment
                $assignment = EvaluatorAssignment::create([
                    'submission_id' => $submissionId,
                    'evaluator_id' => $evaluatorId,
                    'status' => EvaluatorAssignment::STATUS_PENDING,
                    'assigned_at' => now(),
                    'meta' => [
                        'conversation_id' => $conversationId,
                    ]
                ]);

                // Update submission status to assigned
                $submission->update([
                    'status' => EvaluationSubmission::STATUS_ASSIGNED,
                    'evaluator_assignment_id' => $assignment->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Evaluator allotted successfully',
                    'data' => [
                        'assignment_id' => $assignment->id,
                        'evaluator_name' => "{$evaluator->first_name} {$evaluator->last_name}",
                        'assignment_status' => $assignment->status,
                        'assigned_at' => $assignment->assigned_at->toISOString(),
                        'submission_status' => $submission->status,
                    ],
                ], 201);
            } elseif ($marketplaceType === MarketplaceTypes::CONSULTATION_VIDEO_CALL) {
                // === ONE-ON-ONE CONSULTATION LOGIC ===

                // Check submission status - only allow if status is 'uploaded'
                if ($submission->status !== EvaluationSubmission::STATUS_UPLOADED) {
                    return response()->json([
                        'success' => false,
                        'message' => "Submission already {$submission->status}",
                        'submission_id' => $submissionId,
                        'current_status' => $submission->status,
                    ], 400);
                }

                // Check if submission has a current version with evaluation (report_id)
                $submissionVersion = $submission->currentVersion;
                if (!$submissionVersion || !$submissionVersion->report_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Consultation request must have a valid evaluation report',
                    ], 400);
                }

                // Check if consultation request already exists for this submission
                $existingRequest = V4ConsultationRequest::where('submission_id', $submissionId)
                    ->where('submission_version_id', $submissionVersion->id)
                    ->first();

                if ($existingRequest) {
                    // If consultation request exists and is not rejected, return error
                    if ($existingRequest->status !== V4ConsultationRequest::STATUS_REQUEST_REJECTED) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Consultation already allotted',
                            'request_id' => $existingRequest->id,
                            'evaluator_id' => $existingRequest->evaluator_id,
                            'status' => $existingRequest->status,
                        ], 400);
                    }

                    // If consultation was rejected, delete the old request
                    $existingRequest->delete();
                }

                // Create consultation request
                $consultationRequest = V4ConsultationRequest::create([
                    'submission_version_id' => $submissionVersion->id,
                    'submission_id' => $submissionId,
                    'evaluation_id' => $submissionVersion->report_id,
                    'evaluator_id' => $evaluatorId,
                    'status' => V4ConsultationRequest::STATUS_PENDING,
                ]);

                // Get player for notification
                $player = $submission->player;

                $this->sendConsultationRequestNotification($player, $evaluator, $consultationRequest);

                return response()->json([
                    'success' => true,
                    'message' => 'Evaluator allotted for consultation successfully',
                    'data' => [
                        'consultation_request_id' => $consultationRequest->id,
                        'evaluator_id' => $evaluator->id,
                        'evaluator_name' => $evaluator->first_name . ' ' . $evaluator->last_name,
                        'request_status' => $consultationRequest->status,
                        'consultation_date' => $submissionVersion->consultation_date,
                        'consultation_time' => $submissionVersion->consultation_time,
                    ],
                ], 201);
            } else if ($marketplaceType === MarketplaceTypes::MENTORSHIP_PROGRAM) {
                // === MENTORSHIP PROGRAM LOGIC ===
                // Check submission status - only allow if status is 'uploaded'
                if ($submission->status !== EvaluationSubmission::STATUS_UPLOADED) {
                    return response()->json([
                        'success' => false,
                        'message' => "Submission already {$submission->status}",
                        'submission_id' => $submissionId,
                        'current_status' => $submission->status,
                    ], 400);
                }

                $submissionVersion = $submission->currentVersion;

                // Check if consultation request already exists for this submission
                $existingRequest = V4ConsultationRequest::where('submission_id', $submissionId)
                    ->where('submission_version_id', $submissionVersion->id)
                    ->first();

                if ($existingRequest) {
                    // If consultation request exists and is not rejected, return error
                    if ($existingRequest->status !== V4ConsultationRequest::STATUS_REQUEST_REJECTED) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Mentorship already allotted',
                            'request_id' => $existingRequest->id,
                            'evaluator_id' => $existingRequest->evaluator_id,
                            'status' => $existingRequest->status,
                        ], 400);
                    }

                    // If consultation was rejected, delete the old request
                    $existingRequest->delete();
                }

                // Create consultation request
                $consultationRequest = V4ConsultationRequest::create([
                    'submission_version_id' => $submissionVersion->id,
                    'submission_id' => $submissionId,
                    'evaluation_id' => $submissionVersion->report_id,
                    'evaluator_id' => $evaluatorId,
                    'status' => V4ConsultationRequest::STATUS_PENDING,
                ]);

                // Get player for notification
                $player = $submission->player;

                $this->sendMentorshipRequestNotification($player, $evaluator, $consultationRequest);

                return response()->json([
                    'success' => true,
                    'message' => 'Evaluator allotted for mentorship successfully',
                    'data' => [
                        'consultation_request_id' => $consultationRequest->id,
                        'evaluator_id' => $evaluator->id,
                        'evaluator_name' => $evaluator->first_name . ' ' . $evaluator->last_name,
                        'request_status' => $consultationRequest->status,
                        'consultation_date' => $submissionVersion->consultation_date,
                        'consultation_time' => $submissionVersion->consultation_time,
                    ],
                ], 201);
            } else {
                // === UNSUPPORTED SKU TYPE ===
                return response()->json([
                    'success' => false,
                    'message' => 'Not applicable for provided sku.',
                    'marketplace_type' => $marketplaceType,
                ], 400);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error allotting evaluator for submission: ' . $e->getMessage(), [
                'evaluator_id' => $request->input('evaluatorId'),
                'submission_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to allot evaluator',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get evaluator assignments filtered by status
     *
     * @param Request $request
     * @param string $status
     * @return JsonResponse
     */
    public function getStatusFilteredEvaluatorAssignments(Request $request, $status): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            $statusMap = [
                'pending' => [EvaluatorAssignment::STATUS_PENDING],
                'in_progress' => [EvaluatorAssignment::STATUS_IN_PROGRESS],
                'completed' => [
                    EvaluatorAssignment::STATUS_COMPLETED,
                    EvaluatorAssignment::STATUS_REJECTED,
                ],
            ];

            // Validate status parameter
            if (!array_key_exists($status, $statusMap)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status. Must be pending, in_progress, or completed',
                ], 400);
            }

            // Build base query with eager loading
            $assignments = EvaluatorAssignment::with([
                'submission.player',
                'submission.currentVersion',
                'submission.paymentRequest.inAppPurchase.marketplaceItem',
                'evaluator',
            ])->where('evaluator_id', $user->id)
                ->whereIn('status', $statusMap[$status])
                ->orderBy('assigned_at', 'desc')
                ->get();

            // For completed status, batch load all evaluations in a single query
            $evaluationsByAssignment = collect();
            if ($status === 'completed' && $assignments->isNotEmpty()) {
                $assignmentIds = $assignments->pluck('id')->toArray();

                // Single query to get all evaluations for all assignments
                $evaluations = Evaluation::whereIn('assignment_id', $assignmentIds)
                    ->get()
                    ->groupBy('assignment_id');

                $evaluationsByAssignment = $evaluations;
            }

            // Process assignments into formatted output
            $formattedAssignments = collect();

            foreach ($assignments as $assignment) {
                // Skip invalid assignments
                if (!$assignment->submission || !$assignment->submission->player) {
                    continue;
                }

                // Base assignment data (reusable)
                $baseData = [
                    'assignment_id' => $assignment->id,
                    'status' => $assignment->status,
                    'notes' => $assignment->notes,
                    'submission_date' => optional($assignment->updated_at)->toISOString(),
                    'file_path' => optional($assignment->submission->currentVersion)->file_path,
                    'report_id' => optional($assignment->submission->currentVersion)->report_id,
                    'player' => [
                        'id' => $assignment->submission->player->id,
                        'name' => $assignment->submission->player->first_name . ' ' . $assignment->submission->player->last_name,
                        'role' => $assignment->submission->player->role,
                        'profile_photo' => $assignment->submission->player->profile_photo,
                        'location' => $assignment->submission->player->state . ', ' . $assignment->submission->player->country,
                    ],
                    'in_app_purchase' => $assignment->submission->paymentRequest->inAppPurchase ?? null,
                    'conversation_id' => $assignment->meta['conversation_id'] ?? '',
                ];

                // For completed status, add evaluation data
                if ($status === 'completed') {
                    $assignmentEvaluations = $evaluationsByAssignment->get($assignment->id, collect());

                    if ($assignmentEvaluations->isEmpty()) {
                        // No evaluations yet (edge case)
                        $formattedAssignments->push($baseData);
                    } else {
                        // Create one entry per evaluation
                        foreach ($assignmentEvaluations as $evaluation) {
                            $formattedAssignments->push(array_merge($baseData, [
                                'evaluation_id' => $evaluation->id,
                            ]));
                        }
                    }
                } else {
                    // For pending/in_progress, just add the base data
                    $formattedAssignments->push($baseData);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Evaluator assignments retrieved successfully",
                'data' => [
                    'assignments' => $formattedAssignments->values(),
                    'total_count' => $formattedAssignments->count(),
                    'status_filter' => $status,
                    'evaluator_id' => $user->id,
                    'filters_applied' => [
                        'status' => $statusMap[$status],
                        'evaluator_id' => $user->id,
                    ],
                ],
            ], 200);
        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve evaluator assignments',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get player's evaluated submissions filtered by SKU
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMyEvaluatedSubmissions(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            // Check if user is a player
            if (!$user || $user->role !== 'player') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only players can access their evaluated submissions.'
                ], 403);
            }

            // Validate request
            $validated = $request->validate([
                'sku' => 'required|string|exists:v4_in_app_purchases,sku',
            ]);

            $sku = $validated['sku'];

            // Get evaluations for this player filtered by SKU with submitted status
            $evaluations = Evaluation::with([
                'evaluator:id,first_name,last_name,profile_photo',
                'submission.paymentRequest.inAppPurchase',
            ])
                ->where('status', Evaluation::STATUS_SUBMITTED)
                ->whereHas('submission', function ($query) use ($user, $sku) {
                    $query->where('player_id', $user->id)
                        ->whereHas('paymentRequest.inAppPurchase', function ($q) use ($sku) {
                            $q->where('sku', $sku);
                        });
                })
                ->orderBy('created_at', 'desc')
                ->get();

            // Format the response
            $formattedEvaluations = $evaluations->map(function ($evaluation) {
                return [
                    'evaluation_id' => $evaluation->id,
                    'created_date' => $evaluation->created_at->toISOString(),
                    'sku_title' => optional(optional($evaluation->submission->paymentRequest)->inAppPurchase)->title,
                    'evaluator' => [
                        'id' => $evaluation->evaluator->id,
                        'first_name' => $evaluation->evaluator->first_name,
                        'last_name' => $evaluation->evaluator->last_name,
                        'full_name' => $evaluation->evaluator->first_name . ' ' . $evaluation->evaluator->last_name,
                        'profile_photo' => $evaluation->evaluator->profile_photo,
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Evaluated submissions retrieved successfully',
                'data' => [
                    'evaluations' => $formattedEvaluations,
                    'total_count' => $formattedEvaluations->count(),
                    'sku' => $sku,
                    'player_id' => $user->id,
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error fetching evaluated submissions: ' . $e->getMessage(), [
                'user_id' => Auth::guard('v4api')->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve evaluated submissions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }


    /**
     * Check video evaluation status for a player
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function submissionEvaluationStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'sku' => 'required|string',
                'user_id' => 'sometimes|integer|exists:v4_users,id'
            ]);

            $userId = $request->input('user_id') ?? Auth::guard('v4api')->id();
            if (!$userId) {
                return response()->json(['success' => false, 'message' => 'Authentication required'], 401);
            }

            $user = V4User::find($userId);
            if (!$user || $user->role !== 'player') {
                return response()->json(['success' => false, 'message' => 'Access denied. Only players can check evaluation status.'], 403);
            }

            $inAppPurchase = V4InAppPurchase::where('sku', $request->input('sku'))->active()->first();
            if (!$inAppPurchase) {
                return response()->json(['success' => false, 'message' => 'Invalid SKU'], 400);
            }

            // Get marketplace item to determine type
            $marketplaceItem = V4Marketplace::where('in_app_purchase_id', $inAppPurchase->id)
                ->where('active', true)
                ->first();

            if (!$marketplaceItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Marketplace item not found',
                ], 400);
            }

            $marketplaceType = $marketplaceItem->type;

            $paymentRequest = V4PaymentRequest::where('in_app_purchase_id', $inAppPurchase->id)
                ->where('player_id', $userId)
                ->orderBy('updated_at', 'desc')
                ->first();

            // Payment status checks
            if (!$paymentRequest || in_array($paymentRequest->status, [V4PaymentRequest::STATUS_FAILED, V4PaymentRequest::STATUS_PARENT_REJECTED])) {
                return response()->json(['success' => true, 'redirect' => 'make_payment'], 200);
            }

            $statusMap = [
                V4PaymentRequest::STATUS_PENDING => 'payment_approval_pending',
                V4PaymentRequest::STATUS_PAYMENT_INITIATED => 'payment_in_process',
            ];

            if (isset($statusMap[$paymentRequest->status])) {
                return response()->json(['success' => true, 'redirect' => $statusMap[$paymentRequest->status]], 200);
            }

            // Handle paid status
            if ($paymentRequest->status === V4PaymentRequest::STATUS_PAID) {
                $submission = EvaluationSubmission::where('payment_request_id', $paymentRequest->id)
                    ->where('player_id', $userId)
                    ->with(['evaluatorAssignment', 'evaluations'])
                    ->first();

                if (!$submission || $submission->status === EvaluationSubmission::STATUS_PENDING) {
                    // Determine redirect based on marketplace type
                    switch ($marketplaceType) {
                        case MarketplaceTypes::PERSONALIZED_VIDEO_EVALUATION:
                            return response()->json(['success' => true, 'status' => 'pending', 'redirect' => 'submit_video'], 200);

                        case MarketplaceTypes::CONSULTATION_VIDEO_CALL:
                            return response()->json(['success' => true, 'status' => 'pending', 'redirect' => 'book_consultation'], 200);
                        case MarketplaceTypes::MENTORSHIP_PROGRAM:
                            return response()->json(['success' => true, 'status' => 'pending', 'redirect' => 'book_mentorship'], 200);

                        default:
                            return response()->json(['success' => true, 'status' => 'pending', 'redirect' => 'submit'], 200);
                    }
                }

                if ($submission->status === EvaluationSubmission::STATUS_REJECTED) {
                    $latestEvaluation = $submission->evaluations
                        ->sortByDesc('created_at')
                        ->first();


                    $rejectionReason = null;
                    $notes = null;

                    if ($latestEvaluation && !empty($latestEvaluation->meta) && isset($latestEvaluation->meta['reason_id'])) {
                        $rejectionReason = EvaluationRejectionReason::find($latestEvaluation->meta['reason_id']);
                        $notes = $latestEvaluation->meta['notes'];
                    }
                    switch ($marketplaceType) {
                        case MarketplaceTypes::PERSONALIZED_VIDEO_EVALUATION:
                            return response()->json([
                                'success' => true,
                                'status' => 'rejected',
                                'redirect' => 'redo_video',
                                'rejection_reason' => $rejectionReason,
                                'notes' => $notes
                            ], 200);

                        case MarketplaceTypes::CONSULTATION_VIDEO_CALL:
                            return response()->json([
                                'success' => true,
                                'status' => 'rejected',
                                'redirect' => 'redo_consultation',
                                'rejection_reason' => $rejectionReason,
                                'notes' => $notes
                            ], 200);

                        case MarketplaceTypes::MENTORSHIP_PROGRAM:
                            return response()->json([
                                'success' => true,
                                'status' => 'rejected',
                                'redirect' => 'redo_mentorship',
                                'rejection_reason' => $rejectionReason,
                                'notes' => $notes
                            ], 200);

                        default:
                            return response()->json([
                                'success' => true,
                                'status' => 'rejected',
                                'redirect' => 'redo_submission',
                                'rejection_reason' => $rejectionReason,
                                'notes' => $notes
                            ], 200);
                    }
                }

                if ($submission->status === EvaluationSubmission::STATUS_COMPLETED) {
                    return response()->json(['success' => true, 'redirect' => 'make_payment'], 200);
                }

                if (in_array($submission->status, [EvaluationSubmission::STATUS_ASSIGNED, EvaluationSubmission::STATUS_UPLOADED, EvaluationSubmission::STATUS_IN_PROGRESS])) {
                    // Determine redirect based on marketplace type
                    switch ($marketplaceType) {
                        case MarketplaceTypes::PERSONALIZED_VIDEO_EVALUATION:
                            return response()->json(['success' => true, 'status' => $submission->status, 'redirect' => 'evaluation_in_process'], 200);

                        case MarketplaceTypes::CONSULTATION_VIDEO_CALL:
                            return response()->json(['success' => true, 'status' => $submission->status, 'redirect' => 'consultation_in_process'], 200);
                        case MarketplaceTypes::MENTORSHIP_PROGRAM:
                            return response()->json(['success' => true, 'status' => $submission->status, 'redirect' => 'mentorship_in_process'], 200);

                        default:
                            return response()->json(['success' => true, 'status' => $submission->status, 'redirect' => 'submission_in_process'], 200);
                    }
                }

                if (in_array($submission->status, [EvaluationSubmission::STATUS_REQUEST_VIDEO, EvaluationSubmission::STATUS_REQUEST_VIDEO_REJECTED]) && $marketplaceType === MarketplaceTypes::MENTORSHIP_PROGRAM) {
                    return response()->json(['success' => true, 'status' => $submission->status, 'redirect' => 'mentorship_request_video'], 200);
                }
            }

            // Fallback for unexpected/unhandled states
            Log::warning('Unexpected payment or submission state in videoEvaluationStatus', [
                'payment_status' => $paymentRequest->status ?? 'no_payment',
                'submission_status' => $submission->status ?? 'no_submission',
                'user_id' => $userId,
                'sku' => $request->input('sku'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to determine evaluation status',
            ], 500);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Video evaluation status check failed', ['error' => $e->getMessage(), 'sku' => $request->input('sku'), 'user_id' => $request->input('user_id')]);
            return response()->json(['success' => false, 'message' => 'Failed to check video evaluation status', 'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'], 500);
        }
    }

    /**
     * Handle consultation request action (accept/reject)
     *
     * @param Request $request
     * @param string $action
     * @return JsonResponse
     */
    public function handleConsultationRequestAction(Request $request, string $action): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            // Validate user must be an evaluator
            if (!$user || $user->role !== 'evaluator') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only evaluators can perform this action.',
                ], 403);
            }

            // Validate action parameter
            if (!in_array($action, ['accept', 'reject'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid action. Must be either "accept" or "reject"',
                ], 400);
            }

            // Validate request body
            $validated = $request->validate([
                'consultation_req_id' => 'required|integer|exists:v4_consultation_requests,id',
            ]);

            $consultationRequestId = $validated['consultation_req_id'];

            // Get consultation request
            $consultationRequest = V4ConsultationRequest::with([
                'submissionVersion',
                'submission.player',
                'evaluator'
            ])->find($consultationRequestId);

            if (!$consultationRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Consultation request not found',
                ], 404);
            }

            // ✅ Authorization: ensure evaluator owns the consultation request
            if ($consultationRequest->evaluator_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This consultation request does not belong to you.',
                ], 403);
            }

            // Check if consultation request is not pending
            if ($consultationRequest->status !== V4ConsultationRequest::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => "Consultation already {$consultationRequest->status}",
                    'current_status' => $consultationRequest->status,
                ], 400);
            }

            // Verify the consultation request is assigned to this evaluator
            if ($consultationRequest->evaluator_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This consultation request is not assigned to you',
                ], 403);
            }

            // Wrap all operations in a transaction
            DB::beginTransaction();
            try {
                if ($action === 'reject') {
                    // Update consultation request status to rejected
                    $consultationRequest->update([
                        'status' => V4ConsultationRequest::STATUS_REQUEST_REJECTED,
                    ]);

                    // 🔹 Delete related pending notifications
                    $consultationRequest->notifications()
                        ->where('type', 'consultation_request')
                        ->delete();

                    // 🔹 Send new rejection notification (to evaluator)
                    $this->sendConsultationStatusNotification($user, $consultationRequest, 'rejected');


                    // If consultation was rejected, delete the old request
                    $consultationRequest->delete();

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Consultation request rejected successfully',
                        'data' => [
                            'consultation_request_id' => $consultationRequest->id,
                            'status' => $consultationRequest->status,
                            'submission_status' => $consultationRequest->submission->status,
                        ],
                    ], 200);
                } else {
                    // accept
                    // Update consultation request status to accepted
                    $consultationRequest->update([
                        'status' => V4ConsultationRequest::STATUS_REQUEST_ACCEPTED,
                    ]);


                    $conversationId = null;
                    try {
                        $token = $request->bearerToken();

                        $baseUrl = config('app.env') === 'production' ? config('CHAT_APP_HOST_PRODUCTION') : env('CHAT_APP_HOST');

                        $response = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $token,
                            'Content-Type' => 'application/json',
                        ])->post($baseUrl . '/conversation/create', [
                            'type' => 'single',
                            'participants' => [
                                (string) $consultationRequest->submission->player_id,
                                (string) $user->id
                            ],
                        ]);

                        if ($response->successful() && isset($response->json()['_id'])) {
                            $conversationId = $response->json()['_id'];
                        } else {
                            Log::warning('Conversation API failed', [
                                'status' => $response->status(),
                                'body' => $response->body(),
                            ]);
                        }
                    } catch (\Throwable $e) {
                        Log::error('Conversation API error', ['error' => $e->getMessage()]);
                    }

                    // Create evaluator assignment
                    $assignment = EvaluatorAssignment::create([
                        'submission_id' => $consultationRequest->submission_id,
                        'evaluator_id' => $user->id,
                        'status' => EvaluatorAssignment::STATUS_PENDING,
                        'assigned_at' => now(),
                        'meta' => [
                            'conversation_id' => $conversationId,
                        ]
                    ]);

                    // Update submission status to assigned
                    $consultationRequest->submission->update([
                        'status' => EvaluationSubmission::STATUS_ASSIGNED,
                    ]);

                    // 🔹 Delete old notification
                    $consultationRequest->notifications()
                        ->where('type', 'consultation_request')
                        ->delete();

                    // 🔹 Send new acceptance notification (to evaluator)
                    $this->sendConsultationStatusNotification(v4User::find($user->id), $consultationRequest, 'accepted');


                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Consultation request accepted successfully',
                        'data' => [
                            'consultation_request_id' => $consultationRequest->id,
                            'assignment_id' => $assignment->id,
                            'status' => $consultationRequest->status,
                            'submission_status' => $consultationRequest->submission->status,
                            'consultation_date' => $consultationRequest->submissionVersion->consultation_date ?? null,
                            'consultation_time' => $consultationRequest->submissionVersion->consultation_time ?? null,
                        ],
                    ], 200);
                }
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error handling consultation request action: ' . $e->getMessage(), [
                'action' => $action ?? 'unknown',
                'user_id' => Auth::guard('v4api')->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process consultation request action',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function getConsultationRequestById(Request $request, ?int $consultationRequestId): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            // Ensure the user is an evaluator
            if (!$user || $user->role !== 'evaluator') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only evaluators can perform this action.',
                ], 403);
            }

            $request->validate([
                'consultationRequestId' => 'nullable|integer|exists:v4_consultation_requests,id',
            ]);

            $consultationRequest = V4ConsultationRequest::with([
                'submissionVersion',
                'submission.player',
                'submission.paymentRequest.inAppPurchase.marketplaceItems',
                'evaluator'
            ])
                ->where('evaluator_id', $user->id)
                ->findOrFail($consultationRequestId);


            if ($consultationRequest->status !== V4ConsultationRequest::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => "Consultation already {$consultationRequest->status}",
                    'current_status' => $consultationRequest->status,
                ], 400);
            }

            $consultationDate = $consultationRequest->submissionVersion->consultation_date;
            $consultationTime = $consultationRequest->submissionVersion->consultation_time;

            // Combine date and time into a Carbon instance
            $consultationDateTime = null;
            if ($consultationDate && $consultationTime) {
                $consultationDateTime = Carbon::parse($consultationDate . ' ' . $consultationTime)->format('Y-m-d H:i:s');
            }

            $data = [
                'id' => $consultationRequest->id,
                'player_name' => $consultationRequest->submission->player->name ?? 'N/A',
                'marketplaceItem' => $consultationRequest->submission->paymentRequest->inAppPurchase->marketplaceItem,
                'date_requested' => optional($consultationRequest->created_at)->format('Y-m-d H:i:s'),
                'selected_time_for_consultation' => $consultationDateTime ?? 'Not selected',
            ];

            return response()->json([
                'success' => true,
                'message' => 'Consultation request retrieved successfully.',
                'data' => $data,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Consultation request not found or not assigned to you.',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error retrieving consultation request', [
                'user_id' => Auth::guard('v4api')->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while processing your request.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Handle mentorship request action (accept/reject)
     *
     * @param Request $request
     * @param string $action
     * @return JsonResponse
     */
    public function handleMentorshipRequestAction(Request $request, string $action): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            // Validate user must be an evaluator
            if (!$user || $user->role !== 'evaluator') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only evaluators can perform this action.',
                ], 403);
            }

            // Validate action parameter
            if (!in_array($action, ['accept', 'reject'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid action. Must be either "accept" or "reject"',
                ], 400);
            }

            // Validate request body
            $validated = $request->validate([
                'mentorship_req_id' => 'required|integer|exists:v4_consultation_requests,id',
            ]);

            $mentorshipRequestId = $validated['mentorship_req_id'];

            // Get mentorship request
            $mentorshipRequest = V4ConsultationRequest::with([
                'submissionVersion',
                'submission.player',
                'evaluator'
            ])->find($mentorshipRequestId);

            if (!$mentorshipRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mentorship request not found',
                ], 404);
            }

            // ✅ Authorization: ensure evaluator owns the mentorship request
            if ($mentorshipRequest->evaluator_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This mentorship request does not belong to you.',
                ], 403);
            }

            // Check if mentorship request is not pending
            if ($mentorshipRequest->status !== V4ConsultationRequest::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => "Mentorship already {$mentorshipRequest->status}",
                    'current_status' => $mentorshipRequest->status,
                ], 400);
            }

            // Wrap all operations in a transaction
            DB::beginTransaction();
            try {
                if ($action === 'reject') {
                    // Update mentorship request status to rejected
                    $mentorshipRequest->update([
                        'status' => V4ConsultationRequest::STATUS_REQUEST_REJECTED,
                    ]);

                    // Delete related pending notifications
                    $mentorshipRequest->notifications()
                        ->where('type', 'mentorship_request')
                        ->delete();

                    // Send new rejection notification
                    $this->sendMentorshipStatusNotification(V4User::find($user->id), $mentorshipRequest, 'rejected');

                    // If mentorship was rejected, delete the old request
                    $mentorshipRequest->delete();

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Mentorship request rejected successfully',
                        'data' => [
                            'mentorship_request_id' => $mentorshipRequest->id,
                            'status' => $mentorshipRequest->status,
                            'submission_status' => $mentorshipRequest->submission->status,
                        ],
                    ], 200);
                } else {
                    // accept
                    // Update mentorship request status to accepted
                    $mentorshipRequest->update([
                        'status' => V4ConsultationRequest::STATUS_REQUEST_ACCEPTED,
                    ]);

                    $conversationId = null;
                    try {
                        $token = $request->bearerToken();

                        $baseUrl = config('app.env') === 'production' ? config('CHAT_APP_HOST_PRODUCTION') : env('CHAT_APP_HOST');

                        $response = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $token,
                            'Content-Type' => 'application/json',
                        ])->post($baseUrl . '/conversation/create', [
                            'type' => 'single',
                            'participants' => [
                                (string) $mentorshipRequest->submission->player_id,
                                (string) $user->id
                            ],
                        ]);

                        if ($response->successful() && isset($response->json()['_id'])) {
                            $conversationId = $response->json()['_id'];
                        } else {
                            Log::warning('Conversation API failed', [
                                'status' => $response->status(),
                                'body' => $response->body(),
                            ]);
                        }
                    } catch (\Throwable $e) {
                        Log::error('Conversation API error', ['error' => $e->getMessage()]);
                    }

                    // Create evaluator assignment
                    $assignment = EvaluatorAssignment::create([
                        'submission_id' => $mentorshipRequest->submission_id,
                        'evaluator_id' => $user->id,
                        'status' => EvaluatorAssignment::STATUS_PENDING,
                        'assigned_at' => now(),
                        'meta' => [
                            'conversation_id' => $conversationId,
                        ]
                    ]);

                    // Update submission status to assigned
                    $mentorshipRequest->submission->update([
                        'status' => EvaluationSubmission::STATUS_ASSIGNED,
                    ]);

                    // Delete old notification
                    $mentorshipRequest->notifications()
                        ->where('type', 'mentorship_request')
                        ->delete();

                    // Send new acceptance notification
                    $this->sendMentorshipStatusNotification(V4User::find($user->id), $mentorshipRequest, 'accepted');

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Mentorship request accepted successfully',
                        'data' => [
                            'mentorship_request_id' => $mentorshipRequest->id,
                            'assignment_id' => $assignment->id,
                            'status' => $mentorshipRequest->status,
                            'submission_status' => $mentorshipRequest->submission->status,
                            'weekday' => $mentorshipRequest->submissionVersion->mentorship_weekday ?? null,
                            'time' => $mentorshipRequest->submissionVersion->consultation_time ?? null,
                        ],
                    ], 200);
                }
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error handling mentorship request action: ' . $e->getMessage(), [
                'action' => $action ?? 'unknown',
                'user_id' => Auth::guard('v4api')->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process mentorship request action',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Send mentorship status notification to evaluator
     */
    public function sendMentorshipStatusNotification(V4User $evaluator, V4ConsultationRequest $mentorshipRequest, string $status)
    {
        $player = $mentorshipRequest->submission->player;
        $playerName = $player->first_name . ' ' . $player->last_name;

        $title = '12-Week Mentorship Program Update';
        $message = $status === 'accepted'
            ? "You have accepted $playerName's mentorship program request."
            : "You have rejected $playerName's mentorship program request.";

        $data = [
            'type' => 'mentorship_request_' . $status,
            'action_required' => false,
            'player' => $player->only(['id', 'first_name', 'last_name', 'profile_photo', 'role']),
            'mentorship_request_id' => $mentorshipRequest->id,
            'evaluation_id' => $mentorshipRequest->evaluation_id ?? null,
            'weekday' => $mentorshipRequest->submissionVersion->mentorship_weekday ?? null,
            'time' => $mentorshipRequest->submissionVersion->consultation_time ?? null,
            'has_video' => !empty($mentorshipRequest->submissionVersion->file_path) && $mentorshipRequest->submissionVersion->file_path !== 'N/A',
            'status' => $status,
        ];

        return $this->notificationService->sendToUserWithImage(
            $evaluator,
            $title,
            $message,
            $player->profile_photo ?? "",
            $data,
            'mentorship_request_' . $status,
            "mentorship/requests/{$mentorshipRequest->id}",
            'mentorship_request_status',
            $mentorshipRequest
        );
    }

    public function makeEvaluationInProgress(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            $request->validate([
                'assignment_id' => 'required|integer|exists:evaluator_assignments,id',
            ]);
            $assignment = EvaluatorAssignment::with(['submission'])->findOrFail($request->assignment_id);

            // ✅ Authorization: ensure evaluator owns the assignment
            if ($assignment->evaluator_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This assignment does not belong to you.',
                ], 403);
            }

            // ✅ Prevent changing if already in progress or completed
            if (!$assignment->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending assignments can be started.',
                ], 400);
            }

            // ✅ Optional: Prevent duplicate evaluations
            // if ($assignment->evaluation()->exists()) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'An evaluation already exists for this assignment.',
            //     ], 409);
            // }

            // ✅ Mark the assignment as in progress
            $assignment->submission->update([
                'status' => EvaluationSubmission::STATUS_IN_PROGRESS,
            ]);
            $assignment->markInProgress();

            return response()->json([
                'success' => true,
                'message' => 'Assignment status updated to in progress.',
                'data' => $assignment,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error updating assignment to in_progress: ' . $e->getMessage(), [
                'assignment_id' => $request->input('assignment_id'),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update assignment status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Submit evaluator assignment with answers and notes
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function submitEvaluatorAssignment(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            $validated = $request->validate([
                'assignment_id' => 'required|integer|exists:evaluator_assignments,id',
                'notes' => 'sometimes|array',
                'notes.*' => 'nullable|string',
                'answers' => 'required|array',
                'answers.*' => 'required|integer',
            ]);

            $assignmentId = $validated['assignment_id'];
            $notes = $validated['notes'];
            $answers = $validated['answers'];

            // Get assignment with submission in single query
            $assignment = EvaluatorAssignment::with('submission')->find($assignmentId);

            if (!$assignment) {
                return response()->json(['success' => false, 'message' => 'Assignment not found'], 404);
            }

            if ($assignment->evaluator_id != $user->id) {
                return response()->json(['success' => false, 'message' => 'This assignment not assigned to you'], 403);
            }
            // Check if assignment status is pending
            if ($assignment->status !== EvaluatorAssignment::STATUS_PENDING && $assignment->status !== EvaluatorAssignment::STATUS_IN_PROGRESS) {
                return response()->json([
                    'success' => false,
                    'message' => "Assignment is already {$assignment->status}"
                ], 400);
            }

            // Validate all category slugs in one query
            $slugs = array_keys($notes);
            $categories = EvaluationCategory::whereIn('slug', $slugs)->where('active', true)->get()->keyBy('slug');

            $invalidSlugs = array_diff($slugs, $categories->keys()->toArray());
            if (!empty($invalidSlugs)) {
                return response()->json(['success' => false, 'message' => 'Invalid category slug: ' . implode(', ', $invalidSlugs)], 400);
            }

            // Fetch all questions and options in one query
            $questionIds = array_keys($answers);
            $questions = EvaluationQuestion::with(['category', 'options'])
                ->whereIn('id', $questionIds)
                ->get()
                ->keyBy('id');

            // Validate all questions exist and are active
            $missingOrInactiveQuestions = array_diff($questionIds, $questions->keys()->toArray());
            if (!empty($missingOrInactiveQuestions)) {
                return response()->json(['success' => false, 'message' => 'Invalid or inactive question_id: ' . implode(', ', $missingOrInactiveQuestions)], 400);
            }

            // Validate options and prepare answer data
            $evaluationAnswers = [];
            foreach ($answers as $questionId => $optionId) {
                $question = $questions[$questionId];
                $option = $question->options->firstWhere('id', $optionId);

                if (!$option) {
                    return response()->json(['success' => false, 'message' => "Invalid option_id: {$optionId} for question_id: {$questionId}"], 400);
                }

                $categorySlug = $question->category->slug ?? null;
                $evaluationAnswers[] = [
                    'question_id' => $questionId,
                    'question_option_id' => $optionId,
                    'comment' => $categorySlug && isset($notes[$categorySlug]) ? $notes[$categorySlug] : null,
                    'rating' => $option->rating,
                ];
            }

            // Execute all database operations in transaction
            DB::beginTransaction();
            try {
                // Create evaluation
                $evaluation = Evaluation::create([
                    'submission_id' => $assignment->submission_id,
                    'assignment_id' => $assignmentId,
                    'evaluator_id' => $user->id,
                    'status' => Evaluation::STATUS_SUBMITTED,
                ]);

                // Bulk insert evaluation answers with timestamps
                $timestamp = now();
                $answersToInsert = array_map(function ($answer) use ($evaluation, $timestamp) {
                    return array_merge($answer, [
                        'evaluation_id' => $evaluation->id,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);
                }, $evaluationAnswers);

                EvaluationAnswer::insert($answersToInsert);

                // Update assignment and submission in single update each
                $assignment->update([
                    'status' => EvaluatorAssignment::STATUS_COMPLETED,
                    'completed_at' => $timestamp,
                ]);

                $assignment->submission->update([
                    'status' => EvaluationSubmission::STATUS_COMPLETED,
                ]);

                $this->sendEvaluationCompleteNotification($evaluation, $assignment);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Evaluator assignment submitted successfully',
                    'data' => [
                        'evaluation_id' => $evaluation->id,
                        'assignment_id' => $assignmentId,
                        'submission_id' => $assignment->submission_id,
                        'total_answers' => count($evaluationAnswers),
                    ],
                ], 201);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Error submitting evaluator assignment: ' . $e->getMessage(), [
                'assignment_id' => $request->input('assignment_id'),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit evaluator assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Reject evaluator assignment with reason
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function rejectEvaluatorAssignment(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            // Validate request
            $validated = $request->validate([
                'assignment_id' => 'required|integer|exists:evaluator_assignments,id',
                'rejection_reason_id' => 'required|integer|exists:evaluation_rejection_reasons,id',
                'notes' => 'nullable|string',
            ]);

            $assignmentId = $validated['assignment_id'];
            $reasonId = $validated['rejection_reason_id'];
            $notes = $validated['notes'] ?? "";

            $rejectionReason = EvaluationRejectionReason::find($reasonId);

            // Get assignment with submission
            $assignment = EvaluatorAssignment::with('submission')->find($assignmentId);

            if (!$assignment) {
                return response()->json(['success' => false, 'message' => 'Assignment not found'], 404);
            }

            // Check if assignment belongs to authenticated evaluator
            if ($assignment->evaluator_id != $user->id) {
                return response()->json(['success' => false, 'message' => 'This assignment not assigned to you'], 403);
            }

            // Check if assignment status is pending
            if ($assignment->status !== EvaluatorAssignment::STATUS_PENDING && $assignment->status !== EvaluatorAssignment::STATUS_IN_PROGRESS) {
                return response()->json([
                    'success' => false,
                    'message' => "Assignment is already {$assignment->status}"
                ], 400);
            }

            // Execute all database operations in transaction
            DB::beginTransaction();
            try {
                $timestamp = now();

                // Create evaluation entry with rejected status
                $evaluation = Evaluation::create([
                    'submission_id' => $assignment->submission_id,
                    'assignment_id' => $assignmentId,
                    'evaluator_id' => $user->id,
                    'status' => Evaluation::STATUS_REJECTED,
                    'meta' => [
                        'by_evaluator' => $user->id,
                        'reason_id' => $reasonId,
                        'notes' => $notes,
                        'at' => $timestamp->toDateTimeString(),
                    ],
                ]);

                // Update assignment status to rejected
                $assignment->update([
                    'status' => EvaluatorAssignment::STATUS_REJECTED,
                    'completed_at' => $timestamp,
                ]);

                // Update submission status to rejected
                $assignment->submission->update([
                    'status' => EvaluationSubmission::STATUS_REJECTED,
                ]);

                // Send rejection notification to video owner
                $this->sendEvaluationRejectedNotification($evaluation, $rejectionReason, $assignment, $notes);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Evaluator assignment rejected successfully',
                    'data' => [
                        'evaluation_id' => $evaluation->id,
                        'assignment_id' => $assignmentId,
                        'submission_id' => $assignment->submission_id,
                        'rejection_reason_id' => $reasonId,
                        'assignment_status' => $assignment->status,
                        'submission_status' => $assignment->submission->status,
                    ],
                ], 201);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Error rejecting evaluator assignment: ' . $e->getMessage(), [
                'assignment_id' => $request->input('assignment_id'),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject evaluator assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Submit consultation assignment
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function submitConsultationAssignment(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            // Validate user must be an evaluator
            if (!$user || $user->role !== 'evaluator') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only evaluators can perform this action.',
                ], 403);
            }

            // Validate request body
            $validated = $request->validate([
                'assignment_id' => 'required|integer|exists:evaluator_assignments,id',
                'remark' => 'required|string',
                'url' => 'required|url',
            ]);

            $assignmentId = $validated['assignment_id'];

            // Get assignment with submission
            $assignment = EvaluatorAssignment::with([
                'submission.paymentRequest.inAppPurchase.marketplaceItems',
                'evaluator'
            ])->find($assignmentId);

            if (!$assignment) {
                return response()->json(['success' => false, 'message' => 'Assignment not found'], 404);
            }

            // ✅ Authorization: ensure evaluator owns the assignment
            if ($assignment->evaluator_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This assignment does not belong to you.',
                ], 403);
            }

            // Get marketplace type
            $marketplaceType = optional($assignment->submission->paymentRequest->inAppPurchase->marketplaceItems->first())->type ?? null;

            // Verify this is a consultation assignment
            if (!in_array($marketplaceType, [MarketplaceTypes::CONSULTATION_VIDEO_CALL])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This endpoint is only for consultation assignments',
                ], 400);
            }

            // Validate assignment status - must be pending or in_progress
            if (!in_array($assignment->status, [EvaluatorAssignment::STATUS_PENDING, EvaluatorAssignment::STATUS_IN_PROGRESS])) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot submit assignment with status: {$assignment->status}",
                ], 400);
            }

            // Get consultation request for this assignment
            $consultationRequest = V4ConsultationRequest::where('submission_id', $assignment->submission_id)
                ->where('evaluator_id', $user->id)
                ->first();

            // Wrap all operations in a transaction
            DB::beginTransaction();
            try {
                // Create evaluation with submitted status
                $evaluation = Evaluation::create([
                    'submission_id' => $assignment->submission_id,
                    'assignment_id' => $assignment->id,
                    'evaluator_id' => $user->id,
                    'status' => Evaluation::STATUS_SUBMITTED,
                    'meta' => [
                        'by_evaluator' => $user->id,
                        'completed_at' => now()->format('Y-m-d H:i:s'),
                    ],
                ]);

                // Create consultation feedback entry
                $consultationFeedback = V4ConsultationFeedback::create([
                    'submission_version_id' => $assignment->submission->current_version_id,
                    'submission_id' => $assignment->submission_id,
                    'evaluation_id' => $evaluation->id,
                    'evaluator_id' => $user->id,
                    'remarks' => $validated['remark'],
                    'urls' => $validated['url'],
                ]);

                // Update assignment status to completed
                $assignment->update([
                    'status' => EvaluatorAssignment::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);

                // Update submission status to completed
                $assignment->submission->update([
                    'status' => EvaluationSubmission::STATUS_COMPLETED,
                ]);

                // Update consultation request status to completed if exists
                if ($consultationRequest) {
                    $consultationRequest->update([
                        'status' => V4ConsultationRequest::STATUS_COMPLETED,
                    ]);
                }

                DB::commit();

                // Send notification to player
                $player = $assignment->submission->player;
                $evaluatorName = $user->first_name . ' ' . $user->last_name;
                $title = 'Consultation Completed';
                $message = "Your 1 on 1 consultation Report is ready";

                $notificationData = [
                    'type' => 'consultation_completed',
                    'action_required' => false,
                    'evaluator' => $user->only(['id', 'first_name', 'last_name', 'profile_photo', 'role']),
                    'assignment_id' => $assignment->id,
                    'evaluation_id' => $evaluation->id,
                    'feedback_id' => $consultationFeedback->id,
                    'recording_url' => $validated['url'],
                ];

                $this->notificationService->sendToUserWithMaterialIcon(
                    $player,
                    $title,
                    $message,
                    'consultation_completed',
                    '#4CAF50',
                    $notificationData,
                    'consultation_completed',
                    "evaluation/submissions/{$assignment->submission_id}",
                    'consultation_completed_action',
                    $assignment
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Consultation assignment submitted successfully',
                    'data' => [
                        'assignment_id' => $assignment->id,
                        'evaluation_id' => $evaluation->id,
                        'feedback_id' => $consultationFeedback->id,
                        'assignment_status' => $assignment->status,
                        'submission_status' => $assignment->submission->status,
                        'consultation_request_status' => $consultationRequest->status ?? null,
                        'marketplace_type' => $marketplaceType,
                    ],
                ], 200);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error submitting consultation assignment: ' . $e->getMessage(), [
                'assignment_id' => $request->input('assignment_id'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit consultation assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }


    /**
     * Reject consultation assignment
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function rejectConsultationAssignment(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            // Validate user must be an evaluator
            if (!$user || $user->role !== 'evaluator') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only evaluators can perform this action.',
                ], 403);
            }

            // Validate request body
            $validated = $request->validate([
                'assignment_id' => 'required|integer|exists:evaluator_assignments,id',
                'reason_id' => 'required|integer|exists:evaluation_rejection_reasons,id',
                'notes' => 'nullable|string',
            ]);

            $assignmentId = $validated['assignment_id'];

            // Get assignment with submission
            $assignment = EvaluatorAssignment::with([
                'submission.paymentRequest.inAppPurchase.marketplaceItems',
                'evaluator'
            ])->find($assignmentId);

            if (!$assignment) {
                return response()->json(['success' => false, 'message' => 'Assignment not found'], 404);
            }

            // ✅ Authorization: ensure evaluator owns the assignment
            if ($assignment->evaluator_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This assignment does not belong to you.',
                ], 403);
            }

            // Get marketplace type
            $marketplaceType = optional($assignment->submission->paymentRequest->inAppPurchase->marketplaceItems->first())->type ?? null;

            // Verify this is a consultation assignment
            if (!in_array($marketplaceType, [MarketplaceTypes::CONSULTATION_VIDEO_CALL])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This endpoint is only for consultation assignments',
                ], 400);
            }

            // Validate assignment status - must be pending or in_progress
            if (!in_array($assignment->status, [EvaluatorAssignment::STATUS_IN_PROGRESS])) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot reject assignment with status: {$assignment->status}",
                ], 400);
            }

            // Get consultation request for this assignment
            $consultationRequest = V4ConsultationRequest::where('submission_id', $assignment->submission_id)
                ->where('evaluator_id', $user->id)
                ->first();

            // Wrap all operations in a transaction
            DB::beginTransaction();
            try {

                $rejectionReason = EvaluationRejectionReason::find($validated['reason_id']);

                // Create evaluation with rejected status
                $evaluation = Evaluation::create([
                    'submission_id' => $assignment->submission_id,
                    'assignment_id' => $assignment->id,
                    'evaluator_id' => $user->id,
                    'status' => Evaluation::STATUS_REJECTED,
                    'meta' => [
                        'by_evaluator' => $user->id,
                        'reason_id' => $validated['reason_id'] ?? null,
                        'notes' => $validated['notes'] ?? "",
                        'at' => now()->format('Y-m-d H:i:s'),
                    ],
                ]);

                // Update assignment status to rejected
                $assignment->update([
                    'status' => EvaluatorAssignment::STATUS_REJECTED,
                ]);

                // Update submission status to rejected
                $assignment->submission->update([
                    'status' => EvaluationSubmission::STATUS_REJECTED,
                ]);

                // Update consultation request status to rejected if exists
                if ($consultationRequest) {
                    $consultationRequest->update([
                        'status' => V4ConsultationRequest::STATUS_REJECTED,
                    ]);
                }


                // Send notification to player
                $player = $assignment->submission->player;
                $evaluatorName = $user->first_name . ' ' . $user->last_name;
                $title = 'Consultation Rejected';
                $message = "Your 1 on 1 Video Evaluation is Rejected by the evaluator";

                $notificationData = [
                    'marketplace_item_type' => '1on1 Consultation Video Call',
                    'evaluation_id' => $evaluation->id,
                    'assignment_id' => $assignment->id,
                    'submission_id' => $assignment->submission->id,
                    'rejection_reason' => $rejectionReason,
                    'sku' => $assignment->submission->paymentRequest->inAppPurchase->sku,
                    'notes' => $validated['notes'] ?? 'No reason provided',
                ];

                $this->notificationService->sendToUserWithMaterialIcon(
                    $player,
                    $title,
                    $message,
                    'consultation_rejected',
                    '#F44336',
                    $notificationData,
                    'consultation_rejected',
                    "evaluation/submissions/{$assignment->submission_id}",
                    'consultation_rejected_action',
                    $evaluation,
                );

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Consultation assignment rejected successfully',
                    'data' => [
                        'assignment_id' => $assignment->id,
                        'evaluation_id' => $evaluation->id,
                        'assignment_status' => $assignment->status,
                        'submission_status' => $assignment->submission->status,
                        'consultation_request_status' => $consultationRequest->status ?? null,
                        'marketplace_type' => $marketplaceType,
                    ],
                ], 200);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error rejecting consultation assignment: ' . $e->getMessage(), [
                'assignment_id' => $request->input('assignment_id'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject consultation assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }


    /**
     * Submit mentorship assignment with evaluation and feedback
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function submitMentorshipAssignment(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            // Validate user must be an evaluator
            if (!$user || $user->role !== 'evaluator') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only evaluators can perform this action.',
                ], 403);
            }

            // Validate request body
            $validated = $request->validate([
                'assignment_id' => 'required|integer|exists:evaluator_assignments,id',
                'remark' => 'required|string',
                'url' => 'required|url',
                'notes' => 'sometimes|array',
                'notes.*' => 'nullable|string',
                'answers' => 'required|array',
                'answers.*' => 'required|integer',
            ]);

            $assignmentId = $validated['assignment_id'];
            $notes = $validated['notes'] ?? [];
            $answers = $validated['answers'];

            // Get assignment with submission
            $assignment = EvaluatorAssignment::with([
                'submission.paymentRequest.inAppPurchase.marketplaceItems',
                'evaluator'
            ])->find($assignmentId);

            if (!$assignment) {
                return response()->json(['success' => false, 'message' => 'Assignment not found'], 404);
            }

            // ✅ Authorization: ensure evaluator owns the assignment
            if ($assignment->evaluator_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This assignment does not belong to you.',
                ], 403);
            }

            // Get marketplace type
            $marketplaceType = optional($assignment->submission->paymentRequest->inAppPurchase->marketplaceItems->first())->type ?? null;

            // Verify this is a mentorship assignment
            if (!in_array($marketplaceType, [MarketplaceTypes::MENTORSHIP_PROGRAM])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This endpoint is only for mentorship assignments',
                ], 400);
            }

            // Validate assignment status - must be pending or in_progress
            if (!in_array($assignment->status, [EvaluatorAssignment::STATUS_PENDING, EvaluatorAssignment::STATUS_IN_PROGRESS])) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot submit assignment with status: {$assignment->status}",
                ], 400);
            }

            // Validate all category slugs in one query
            $slugs = array_keys($notes);
            $categories = EvaluationCategory::whereIn('slug', $slugs)->where('active', true)->get()->keyBy('slug');

            $invalidSlugs = array_diff($slugs, $categories->keys()->toArray());
            if (!empty($invalidSlugs)) {
                return response()->json(['success' => false, 'message' => 'Invalid category slug: ' . implode(', ', $invalidSlugs)], 400);
            }

            // Fetch all questions and options in one query
            $questionIds = array_keys($answers);
            $questions = EvaluationQuestion::with(['category', 'options'])
                ->whereIn('id', $questionIds)
                ->get()
                ->keyBy('id');

            // Validate all questions exist and are active
            $missingOrInactiveQuestions = array_diff($questionIds, $questions->keys()->toArray());
            if (!empty($missingOrInactiveQuestions)) {
                return response()->json(['success' => false, 'message' => 'Invalid or inactive question_id: ' . implode(', ', $missingOrInactiveQuestions)], 400);
            }

            // Validate options and prepare answer data
            $evaluationAnswers = [];
            foreach ($answers as $questionId => $optionId) {
                $question = $questions[$questionId];
                $option = $question->options->firstWhere('id', $optionId);

                if (!$option) {
                    return response()->json(['success' => false, 'message' => "Invalid option_id: {$optionId} for question_id: {$questionId}"], 400);
                }

                $categorySlug = $question->category->slug ?? null;
                $evaluationAnswers[] = [
                    'question_id' => $questionId,
                    'question_option_id' => $optionId,
                    'comment' => $categorySlug && isset($notes[$categorySlug]) ? $notes[$categorySlug] : null,
                    'rating' => $option->rating,
                ];
            }

            // Get mentorship request for this assignment
            $mentorshipRequest = V4ConsultationRequest::where('submission_id', $assignment->submission_id)
                ->where('evaluator_id', $user->id)
                ->first();

            // Wrap all operations in a transaction
            DB::beginTransaction();
            try {
                // Create evaluation with submitted status
                $evaluation = Evaluation::create([
                    'submission_id' => $assignment->submission_id,
                    'assignment_id' => $assignment->id,
                    'evaluator_id' => $user->id,
                    'status' => Evaluation::STATUS_SUBMITTED,
                    'meta' => [
                        'by_evaluator' => $user->id,
                        'completed_at' => now()->format('Y-m-d H:i:s'),
                    ],
                ]);

                // Bulk insert evaluation answers with timestamps
                $timestamp = now();
                $answersToInsert = array_map(function ($answer) use ($evaluation, $timestamp) {
                    return array_merge($answer, [
                        'evaluation_id' => $evaluation->id,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);
                }, $evaluationAnswers);

                EvaluationAnswer::insert($answersToInsert);

                // Create mentorship feedback entry
                $mentorshipFeedback = V4ConsultationFeedback::create([
                    'submission_version_id' => $assignment->submission->current_version_id,
                    'submission_id' => $assignment->submission_id,
                    'evaluation_id' => $evaluation->id,
                    'evaluator_id' => $user->id,
                    'remarks' => $validated['remark'],
                    'urls' => $validated['url'],
                ]);

                // Update assignment status to completed
                $assignment->update([
                    'status' => EvaluatorAssignment::STATUS_COMPLETED,
                    'completed_at' => $timestamp,
                ]);

                // Update submission status to completed
                $assignment->submission->update([
                    'status' => EvaluationSubmission::STATUS_COMPLETED,
                ]);

                // Update mentorship request status to completed if exists
                if ($mentorshipRequest) {
                    $mentorshipRequest->update([
                        'status' => V4ConsultationRequest::STATUS_COMPLETED,
                    ]);
                }

                DB::commit();

                // Send notification to player
                $player = $assignment->submission->player;
                $evaluatorName = $user->first_name . ' ' . $user->last_name;
                $title = 'Mentorship Program Completed';
                $message = "Your 12-Week Mentorship Program evaluation is ready";

                $notificationData = [
                    'type' => 'mentorship_completed',
                    'action_required' => false,
                    'evaluator' => $user->only(['id', 'first_name', 'last_name', 'profile_photo', 'role']),
                    'assignment_id' => $assignment->id,
                    'evaluation_id' => $evaluation->id,
                    'feedback_id' => $mentorshipFeedback->id,
                    'recording_url' => $validated['url'],
                    'total_answers' => count($evaluationAnswers),
                ];

                $this->notificationService->sendToUserWithMaterialIcon(
                    $player,
                    $title,
                    $message,
                    'mentorship_completed',
                    '#4CAF50',
                    $notificationData,
                    'mentorship_completed',
                    "evaluation/submissions/{$assignment->submission_id}",
                    'mentorship_completed_action',
                    $assignment
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Mentorship assignment submitted successfully',
                    'data' => [
                        'assignment_id' => $assignment->id,
                        'evaluation_id' => $evaluation->id,
                        'feedback_id' => $mentorshipFeedback->id,
                        'assignment_status' => $assignment->status,
                        'submission_status' => $assignment->submission->status,
                        'mentorship_request_status' => $mentorshipRequest->status ?? null,
                        'marketplace_type' => $marketplaceType,
                        'total_answers' => count($evaluationAnswers),
                    ],
                ], 200);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error submitting mentorship assignment: ' . $e->getMessage(), [
                'assignment_id' => $request->input('assignment_id'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit mentorship assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Upload requested video for mentorship assignment
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadMentorshipAssignmentRequestVideo(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            // Validate user must be a player
            if (!$user || $user->role !== 'player') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only players can upload videos.',
                ], 403);
            }

            // Validate video file
            $request->validate([
                'video' => 'required|file',
            ]);

            if (!$request->hasFile('video')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No video file provided'
                ], 400);
            }

            $playerId = $user->id;
            $file = $request->file('video');

            // Validate file
            if (!$file->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'File upload failed: ' . $file->getError()
                ], 422);
            }

            $mimeType = $file->getClientMimeType();
            $fileSize = $file->getSize();

            if (!str_starts_with($mimeType, 'video/')) {
                return response()->json([
                    'success' => false,
                    'message' => 'File must be a video'
                ], 422);
            }

            // Check file size (100MB max)
            $maxSizeInBytes = 100 * 1024 * 1024;
            if ($fileSize > $maxSizeInBytes) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video file size must not exceed 100MB'
                ], 422);
            }

            // Get latest mentorship submission for this player
            $submission = EvaluationSubmission::with([
                'currentVersion',
                'paymentRequest.inAppPurchase.marketplaceItems'
            ])
                ->where('player_id', $playerId)
                ->whereHas('paymentRequest.inAppPurchase.marketplaceItems', function ($query) {
                    $query->where('type', MarketplaceTypes::MENTORSHIP_PROGRAM)
                        ->where('active', true);
                })
                ->orderBy('updated_at', 'desc')
                ->first();

            // Check if submission exists
            if (!$submission) {
                return response()->json([
                    'success' => false,
                    'message' => 'No mentorship submission found for this player',
                ], 404);
            }

            // Check submission status must be request_video
            if (!in_array($submission->status, [EvaluationSubmission::STATUS_REQUEST_VIDEO, EvaluationSubmission::STATUS_REQUEST_VIDEO_REJECTED])) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot upload video. Submission status is '{$submission->status}', but must be 'request_video' or 'request_video_rejected'",
                    'current_status' => $submission->status,
                ], 400);
            }

            // Get previous version
            $previousVersion = $submission->currentVersion;

            if (!$previousVersion) {
                return response()->json([
                    'success' => false,
                    'message' => 'No previous submission version found',
                ], 404);
            }

            // Generate unique filename
            $filename = 'mentorship_requested_video_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Upload to S3 (before transaction)
            $path = $file->storeAs('mentorship-videos/' . $playerId, $filename, 's3');
            $videoUrl = Storage::disk('s3')->url($path);
            $originalName = $file->getClientOriginalName();

            // Prepare file metadata
            $fileMeta = [
                'type' => 'mentorship_requested_video',
                'original_name' => $originalName,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'video_url' => $videoUrl,
                'marketplace_type' => MarketplaceTypes::MENTORSHIP_PROGRAM,
                'uploaded_at' => now()->toISOString(),
                'request_type' => 'evaluator_requested',
            ];

            // Wrap all database operations in a transaction
            DB::beginTransaction();
            try {
                // Create new submission version based on previous one, only update file_path and file_meta
                $newVersion = EvaluationSubmissionVersion::create([
                    'submission_id' => $submission->id,
                    'report_id' => $previousVersion->report_id,
                    'mentorship_weekday' => $previousVersion->mentorship_weekday,
                    'consultation_time' => $previousVersion->consultation_time,
                    'consultation_date' => $previousVersion->consultation_date,
                    'mentorship_upload_type' => EvaluationSubmissionVersion::MENTORSHIP_UPLOAD_TYPE_REQUESTED_VIDEO,
                    'file_path' => $videoUrl,
                    'uploaded_by' => $user->id,
                    'file_meta' => $fileMeta,
                ]);

                // Update submission with new version and change status to in_progress
                $submission->update([
                    'current_version_id' => $newVersion->id,
                    'status' => EvaluationSubmission::STATUS_IN_PROGRESS,
                ]);

                DB::commit();

                // Send notification to evaluator
                $submission->load('evaluatorAssignment.evaluator', 'player');
                $evaluator = $submission->evaluatorAssignment->evaluator ?? null;
                $player = $submission->player ?? $user;

                if ($evaluator) {
                    $playerName = $player->first_name . ' ' . $player->last_name;
                    $title = 'Mentorship Video Uploaded';
                    $message = "{$playerName} uploaded the video for mentorship program";

                    // Get old video using same logic as getMentorshipReport
                    $getVideoPath = function ($submissionVersion) {
                        if (!$submissionVersion) {
                            return null;
                        }

                        // If submission version has report_id, follow the chain
                        if ($submissionVersion->report_id) {
                            $linkedEvaluation = Evaluation::with('submission.currentVersion')->find($submissionVersion->report_id);
                            if ($linkedEvaluation && $linkedEvaluation->submission && $linkedEvaluation->submission->currentVersion) {
                                return $linkedEvaluation->submission->currentVersion->file_path;
                            }
                        }

                        // Otherwise, return the file_path directly
                        return $submissionVersion->file_path;
                    };

                    // Get old video (oldest submission version with mentorship_upload_type = 'submitted_video')
                    $oldestSubmissionVersion = EvaluationSubmissionVersion::where('submission_id', $submission->id)
                        ->where('mentorship_upload_type', EvaluationSubmissionVersion::MENTORSHIP_UPLOAD_TYPE_SUBMITTED_VIDEO)
                        ->orderBy('created_at', 'asc')
                        ->first();
                    $oldVideo = $getVideoPath($oldestSubmissionVersion);

                    $notificationData = [
                        'type' => 'mentorship_video_uploaded',
                        'action_required' => true,
                        'player' => $player->only(['id', 'first_name', 'last_name', 'profile_photo', 'role']),
                        'submission_id' => $submission->id,
                        'submission_version_id' => $newVersion->id,
                        'new_video' => $videoUrl,
                        'old_video' => $oldVideo,
                    ];

                    $this->notificationService->sendToUserWithUrlIcon(
                        $evaluator,
                        $title,
                        $message,
                        $player->profile_photo,
                        '#2196F3',
                        $notificationData,
                        'mentorship_video_uploaded',
                        "evaluation/submissions/{$submission->id}",
                        'mentorship_video_uploaded_action',
                        $submission
                    );
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Requested video uploaded successfully',
                    'data' => [
                        'player_id' => $playerId,
                        'submission_id' => $submission->id,
                        'submission_version_id' => $newVersion->id,
                        'previous_version_id' => $previousVersion->id,
                        'weekday' => $previousVersion->mentorship_weekday,
                        'time' => $previousVersion->consultation_time,
                        'video_url' => $videoUrl,
                        'file_size' => $fileSize,
                        'mime_type' => $mimeType,
                        'status' => $submission->status,
                        'uploaded_at' => now()->toISOString(),
                    ],
                ], 201);
            } catch (Exception $e) {
                DB::rollBack();
                // Delete uploaded file from S3 if DB transaction fails
                Storage::disk('s3')->delete($path);
                throw $e;
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error uploading mentorship requested video: ' . $e->getMessage(), [
                'user_id' => Auth::guard('v4api')->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload requested video',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get requested video status for mentorship program
     *
     * @param String $assignment_id
     * @return JsonResponse
     */
    public function getRequestedVideoStatus(string $assignment_id): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            // Validate user must be an evaluator
            if (!$user || $user->role !== 'evaluator') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only evaluators can check video status.',
                ], 403);
            }

            // Get assignment with relationships
            $assignment = EvaluatorAssignment::with([
                'submission.paymentRequest.inAppPurchase.marketplaceItems',
                'submission.currentVersion',
                'evaluator'
            ])->find((int) $assignment_id);

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignment not found',
                ], 404);
            }

            // Authorization: ensure evaluator owns the assignment
            if ($assignment->evaluator_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This assignment does not belong to you.',
                ], 403);
            }

            // Get marketplace type
            $marketplaceType = optional($assignment->submission->paymentRequest->inAppPurchase->marketplaceItems->first())->type ?? null;

            // Verify this is a mentorship program
            if ($marketplaceType !== MarketplaceTypes::MENTORSHIP_PROGRAM) {
                return response()->json([
                    'success' => false,
                    'message' => 'This endpoint is only for mentorship program assignments',
                    'marketplace_type' => $marketplaceType,
                ], 400);
            }

            $submission = $assignment->submission;
            $submissionStatus = $submission->status;

            // Check if status is STATUS_REQUEST_VIDEO_REJECTED or STATUS_REQUEST_VIDEO
            if (in_array($submissionStatus, [EvaluationSubmission::STATUS_REQUEST_VIDEO_REJECTED, EvaluationSubmission::STATUS_REQUEST_VIDEO])) {
                return response()->json([
                    'success' => true,
                    'message' => 'Video request is in progress',
                    'redirect' => 'already-requested-video',
                ], 200);
            }

            // Check if status is IN_PROGRESS
            if ($submissionStatus === EvaluationSubmission::STATUS_IN_PROGRESS) {
                $currentVersion = $submission->currentVersion;

                if (!$currentVersion) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No current version found for this submission',
                    ], 404);
                }

                $mentorshipUploadType = $currentVersion->mentorship_upload_type;

                // If mentorship_upload_type is SUBMITTED_VIDEO
                if ($mentorshipUploadType === EvaluationSubmissionVersion::MENTORSHIP_UPLOAD_TYPE_SUBMITTED_VIDEO) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Video upload is pending review',
                        'redirect' => 'request_video'
                    ], 200);
                }

                // If mentorship_upload_type is REQUESTED_VIDEO
                if ($mentorshipUploadType === EvaluationSubmissionVersion::MENTORSHIP_UPLOAD_TYPE_REQUESTED_VIDEO) {
                    $getVideoPath = function ($submissionVersion) {
                        if (!$submissionVersion) {
                            return null;
                        }

                        if ($submissionVersion->report_id) {
                            $linkedEvaluation = Evaluation::with('submission.currentVersion')->find($submissionVersion->report_id);
                            if ($linkedEvaluation && $linkedEvaluation->submission && $linkedEvaluation->submission->currentVersion) {
                                return $linkedEvaluation->submission->currentVersion->file_path;
                            }
                        }

                        return $submissionVersion->file_path;
                    };

                    $newVideo = $currentVersion->file_path;

                    $oldestSubmissionVersion = EvaluationSubmissionVersion::where('submission_id', $submission->id)
                        ->where('mentorship_upload_type', EvaluationSubmissionVersion::MENTORSHIP_UPLOAD_TYPE_SUBMITTED_VIDEO)
                        ->orderBy('created_at', 'asc')
                        ->first();
                    $oldVideo = $getVideoPath($oldestSubmissionVersion);

                    return response()->json([
                        'success' => true,
                        'message' => 'Requested video has been uploaded',
                        'redirect' => 'requested_video_review',
                        'data' => [
                            'new_video' => $newVideo,
                            'old_video' => $oldVideo,
                        ],
                    ], 200);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid mentorship upload type',
                    'current_upload_type' => $mentorshipUploadType,
                ], 400);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid submission status for video request check',
                'submission_status' => $submissionStatus,
            ], 400);
        } catch (Exception $e) {
            Log::error('Error getting requested video status: ' . $e->getMessage(), [
                'assignment_id' => $assignment_id,
                'user_id' => Auth::guard('v4api')->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get requested video status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Reject uploaded request video for mentorship program
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function rejectUploadedRequestVideo(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            // Validate user must be an evaluator
            if (!$user || $user->role !== 'evaluator') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only evaluators can reject videos.',
                ], 403);
            }

            // Validate request
            $validated = $request->validate([
                'assignment_id' => 'required|integer|exists:evaluator_assignments,id',
                'reason_id' => 'required|integer|exists:evaluation_rejection_reasons,id',
                'note' => 'nullable|string|max:1000',
            ]);

            $assignmentId = $validated['assignment_id'];
            $reasonId = $validated['reason_id'];
            $note = $validated['note'] ?? null;

            // Get assignment with relationships
            $assignment = EvaluatorAssignment::with([
                'submission.paymentRequest.inAppPurchase.marketplaceItems',
                'submission.currentVersion',
                'submission.player',
                'evaluator'
            ])->find($assignmentId);

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignment not found',
                ], 404);
            }

            // Authorization: ensure evaluator owns the assignment
            if ($assignment->evaluator_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This assignment does not belong to you.',
                ], 403);
            }

            // Get marketplace type
            $marketplaceType = optional($assignment->submission->paymentRequest->inAppPurchase->marketplaceItems->first())->type ?? null;

            // Verify this is a mentorship program
            if ($marketplaceType !== MarketplaceTypes::MENTORSHIP_PROGRAM) {
                return response()->json([
                    'success' => false,
                    'message' => 'This endpoint is only for mentorship program assignments',
                    'marketplace_type' => $marketplaceType,
                ], 400);
            }

            // Get submission
            $submission = $assignment->submission;
            $currentVersion = $submission->currentVersion;

            if (!$currentVersion) {
                return response()->json([
                    'success' => false,
                    'message' => 'No current version found for this submission',
                ], 404);
            }

            // Get rejection reason
            $rejectionReason = EvaluationRejectionReason::find($reasonId);
            if (!$rejectionReason) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid rejection reason',
                ], 400);
            }

            // Wrap in transaction
            DB::beginTransaction();
            try {
                // Update current version with rejection reason and note
                $versionNotes = [
                    'rejection_reason_id' => $reasonId,
                    'rejection_reason' => $rejectionReason->reason,
                    'evaluator_note' => $note,
                    'rejected_at' => now()->toISOString(),
                    'rejected_by' => $user->id,
                ];

                $currentVersion->update([
                    'notes' => json_encode($versionNotes),
                ]);

                // Update submission status to request_video_rejected
                $submission->update([
                    'status' => EvaluationSubmission::STATUS_REQUEST_VIDEO_REJECTED,
                ]);

                DB::commit();

                // Send notification to player
                $player = $submission->player;
                $evaluatorName = $user->first_name . ' ' . $user->last_name;
                $title = 'Mentorship Video Rejected';
                $message = 'Your uploaded video for mentorship program has been rejected';

                $notificationData = [
                    'type' => 'mentorship_video_rejected',
                    'action_required' => false,
                    'evaluator' => $user->only(['id', 'first_name', 'last_name', 'profile_photo', 'role']),
                    'assignment_id' => $assignment->id,
                    'submission_id' => $submission->id,
                    'rejection_reason' => [
                        'id' => $rejectionReason->id,
                        'reason' => $rejectionReason->reason,
                        'note' => $note,
                    ],
                ];

                $this->notificationService->sendToUserWithUrlIcon(
                    $player,
                    $title,
                    $message,
                    'mentorship_video_rejected', // Evaluator's profile photo as icon
                    '#F44336', // Red color for rejection
                    $notificationData,
                    'mentorship_video_rejected',
                    "evaluation/submissions/{$submission->id}",
                    'mentorship_video_rejected_action',
                    $submission
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Video rejected successfully',
                    'data' => [
                        'assignment_id' => $assignment->id,
                        'submission_id' => $submission->id,
                        'submission_status' => $submission->status,
                        'current_version_id' => $currentVersion->id,
                        'rejection_reason' => [
                            'id' => $rejectionReason->id,
                            'reason' => $rejectionReason->reason,
                            'note' => $note,
                        ],
                        'player_id' => $player->id,
                        'player_name' => $player->first_name . ' ' . $player->last_name,
                    ],
                ], 200);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error rejecting uploaded request video: ' . $e->getMessage(), [
                'assignment_id' => $request->input('assignment_id'),
                'reason_id' => $request->input('reason_id'),
                'user_id' => Auth::guard('v4api')->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject uploaded request video',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Reject mentorship assignment
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function rejectMentorshipAssignment(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            // Validate user must be an evaluator
            if (!$user || $user->role !== 'evaluator') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only evaluators can perform this action.',
                ], 403);
            }

            // Validate request body
            $validated = $request->validate([
                'assignment_id' => 'required|integer|exists:evaluator_assignments,id',
                'reason_id' => 'required|integer|exists:evaluation_rejection_reasons,id',
                'notes' => 'required|string|max:1000',
            ]);

            $assignmentId = $validated['assignment_id'];

            // Get assignment with submission
            $assignment = EvaluatorAssignment::with([
                'submission.paymentRequest.inAppPurchase.marketplaceItems',
                'evaluator'
            ])->find($assignmentId);

            if (!$assignment) {
                return response()->json(['success' => false, 'message' => 'Assignment not found'], 404);
            }

            // ✅ Authorization: ensure evaluator owns the assignment
            if ($assignment->evaluator_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This assignment does not belong to you.',
                ], 403);
            }

            // Get marketplace type
            $marketplaceType = optional($assignment->submission->paymentRequest->inAppPurchase->marketplaceItems->first())->type ?? null;

            // Verify this is a mentorship assignment
            if (!in_array($marketplaceType, [MarketplaceTypes::MENTORSHIP_PROGRAM])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This endpoint is only for mentorship assignments',
                ], 400);
            }

            // Validate assignment status - must be pending or in_progress
            if (!in_array($assignment->status, [EvaluatorAssignment::STATUS_PENDING, EvaluatorAssignment::STATUS_IN_PROGRESS])) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot reject assignment with status: {$assignment->status}",
                ], 400);
            }

            // Get mentorship request for this assignment
            $mentorshipRequest = V4ConsultationRequest::where('submission_id', $assignment->submission_id)
                ->where('evaluator_id', $user->id)
                ->first();

            // Wrap all operations in a transaction
            DB::beginTransaction();
            try {
                // Create evaluation with rejected status
                $evaluation = Evaluation::create([
                    'submission_id' => $assignment->submission_id,
                    'assignment_id' => $assignment->id,
                    'evaluator_id' => $user->id,
                    'status' => Evaluation::STATUS_REJECTED,
                    'meta' => [
                        'by_evaluator' => $user->id,
                        'reason_id' => $validated['reason_id'] ?? null,
                        'notes' => $validated['notes'] ?? null,
                        'at' => now()->format('Y-m-d H:i:s'),
                    ],
                ]);

                // Update assignment status to rejected
                $assignment->update([
                    'status' => EvaluatorAssignment::STATUS_REJECTED,
                ]);

                // Update submission status to rejected
                $assignment->submission->update([
                    'status' => EvaluationSubmission::STATUS_REJECTED,
                ]);

                // Update mentorship request status to rejected if exists
                if ($mentorshipRequest) {
                    $mentorshipRequest->update([
                        'status' => V4ConsultationRequest::STATUS_REJECTED,
                    ]);
                }

                DB::commit();

                // Send notification to player
                $player = $assignment->submission->player;
                $evaluatorName = $user->first_name . ' ' . $user->last_name;
                $title = 'Mentorship Program Rejected';
                $message = "Your 12-Week Mentorship Program submission has been rejected by the evaluator";

                $notificationData = [
                    'type' => 'mentorship_rejected',
                    'action_required' => false,
                    'evaluator' => $user->only(['id', 'first_name', 'last_name', 'profile_photo', 'role']),
                    'assignment_id' => $assignment->id,
                    'evaluation_id' => $evaluation->id,
                    'reason' => $validated['notes'] ?? 'No reason provided',
                ];

                $this->notificationService->sendToUserWithMaterialIcon(
                    $player,
                    $title,
                    $message,
                    'mentorship_rejected',
                    '#F44336',
                    $notificationData,
                    'mentorship_rejected',
                    "evaluation/submissions/{$assignment->submission_id}",
                    'mentorship_rejected_action',
                    $assignment
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Mentorship assignment rejected successfully',
                    'data' => [
                        'assignment_id' => $assignment->id,
                        'evaluation_id' => $evaluation->id,
                        'assignment_status' => $assignment->status,
                        'submission_status' => $assignment->submission->status,
                        'mentorship_request_status' => $mentorshipRequest->status ?? null,
                        'marketplace_type' => $marketplaceType,
                    ],
                ], 200);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error rejecting mentorship assignment: ' . $e->getMessage(), [
                'assignment_id' => $request->input('assignment_id'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject mentorship assignment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Request video for mentorship program
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function requestVideoForMentorship(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            // Validate user must be an evaluator
            if (!$user || $user->role !== 'evaluator') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only evaluators can request videos.',
                ], 403);
            }

            // Validate request
            $validated = $request->validate([
                'assignment_id' => 'required|integer|exists:evaluator_assignments,id',
            ]);

            $assignmentId = $validated['assignment_id'];

            // Get assignment with relationships
            $assignment = EvaluatorAssignment::with([
                'submission.paymentRequest.inAppPurchase.marketplaceItems',
                'submission.player',
                'evaluator'
            ])->find($assignmentId);

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignment not found',
                ], 404);
            }

            // Authorization: ensure evaluator owns the assignment
            if ($assignment->evaluator_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This assignment does not belong to you.',
                ], 403);
            }

            // Get marketplace type
            $marketplaceType = optional($assignment->submission->paymentRequest->inAppPurchase->marketplaceItems->first())->type ?? null;

            // Verify this is a mentorship program
            if ($marketplaceType !== MarketplaceTypes::MENTORSHIP_PROGRAM) {
                return response()->json([
                    'success' => false,
                    'message' => 'This endpoint is only for mentorship program assignments',
                    'marketplace_type' => $marketplaceType,
                ], 400);
            }

            // Check assignment status must be in_progress
            if ($assignment->status !== EvaluatorAssignment::STATUS_IN_PROGRESS) {
                return response()->json([
                    'success' => false,
                    'message' => "Assignment status must be 'in_progress'. Current status: {$assignment->status}",
                    'current_status' => $assignment->status,
                ], 400);
            }

            // Check submission status must be in_progress
            $submission = $assignment->submission;
            if ($submission->status !== EvaluationSubmission::STATUS_IN_PROGRESS) {
                return response()->json([
                    'success' => false,
                    'message' => "Submission status must be 'in_progress'. Current status: {$submission->status}",
                    'current_status' => $submission->status,
                ], 400);
            }

            // Wrap in transaction
            DB::beginTransaction();
            try {
                // Update submission status to request_video
                $submission->update([
                    'status' => EvaluationSubmission::STATUS_REQUEST_VIDEO,
                ]);

                DB::commit();

                // Send notification to player
                $player = $submission->player;
                $title = '12-Week Mentorship Program';
                $message = 'Your 12 week mentorship program requested your last video upload';

                $notificationData = [
                    'type' => 'mentorship_video_requested',
                    'action_required' => false,
                    'evaluator' => $user->only(['id', 'first_name', 'last_name', 'profile_photo', 'role']),
                    'assignment_id' => $assignment->id,
                    'submission_id' => $submission->id,
                ];

                $this->notificationService->sendToUserWithUrlIcon(
                    $player,
                    $title,
                    $message,
                    "mentorship_video_requested",
                    '#FF9800',
                    $notificationData,
                    'mentorship_video_requested',
                    "evaluation/submissions/{$submission->id}",
                    'mentorship_video_requested_action',
                    $submission
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Video request sent successfully',
                    'data' => [
                        'assignment_id' => $assignment->id,
                        'submission_id' => $submission->id,
                        'submission_status' => $submission->status,
                        'player_id' => $player->id,
                        'player_name' => $player->first_name . ' ' . $player->last_name,
                    ],
                ], 200);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error requesting video for mentorship: ' . $e->getMessage(), [
                'assignment_id' => $request->input('assignment_id'),
                'user_id' => Auth::guard('v4api')->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to request video for mentorship',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get consultation report by evaluation ID
     *
     * @param int $evaluation_id
     * @return JsonResponse
     */
    public function getConsultationReport(string $evaluation_id): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            // Get evaluation with all relationships
            $evaluation = Evaluation::with([
                'submission.paymentRequest.inAppPurchase.marketplaceItems',
                'submission.currentVersion',
                'submission.player',
                'evaluator'
            ])->find((int) $evaluation_id);

            if (!$evaluation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Evaluation not found',
                ], 404);
            }

            // Get marketplace type
            $marketplaceType = optional($evaluation->submission->paymentRequest->inAppPurchase->marketplaceItems->first())->type ?? null;

            // Verify this is a one-on-one consultation
            if ($marketplaceType !== MarketplaceTypes::CONSULTATION_VIDEO_CALL) {
                return response()->json([
                    'success' => false,
                    'message' => 'This endpoint is only for one-on-one consultation evaluations',
                    'marketplace_type' => $marketplaceType,
                ], 400);
            }

            // Get the submission and current version
            $submission = $evaluation->submission;
            $currentVersion = $submission->currentVersion;

            if (!$currentVersion) {
                return response()->json([
                    'success' => false,
                    'message' => 'No current version found for this submission',
                ], 404);
            }

            // Get the report_id from current version
            $reportId = $currentVersion->report_id;

            if (!$reportId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No personalized report linked to this consultation',
                ], 404);
            }

            // Now get the actual personalized evaluation report
            $personalizedEvaluation = Evaluation::find($reportId);

            if (!$personalizedEvaluation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Personalized evaluation report not found',
                ], 404);
            }

            // Get consultation feedback for this evaluation
            $feedback = V4ConsultationFeedback::with([
                'submissionVersion'
            ])->where('evaluation_id', $evaluation_id)->first();

            if (!$feedback) {
                return response()->json([
                    'success' => false,
                    'message' => 'Consultation feedback not found for this evaluation',
                ], 404);
            }

            // Get player and evaluator
            $player = $evaluation->submission->player ?? null;
            $evaluator = $evaluation->evaluator;
            $inAppPurchase = $evaluation->submission->paymentRequest->inAppPurchase ?? null;

            // Format the response
            $reportData = [
                'evaluation_id' => $evaluation->id,
                'feedback_id' => $feedback->id,
                'consultation_date' => $feedback->submissionVersion->consultation_date ?? null,
                'consultation_time' => $feedback->submissionVersion->consultation_time ?? null,
                'created_at' => $evaluation->created_at->toISOString(),

                // Feedback details
                'feedback' => [
                    'id' => $feedback->id,
                    'remarks' => $feedback->remarks,
                    'urls' => $feedback->urls,
                ],

                // Evaluator details
                'evaluator' => $evaluator ? [
                    'id' => $evaluator->id,
                    'first_name' => $evaluator->first_name,
                    'last_name' => $evaluator->last_name,
                    'full_name' => $evaluator->first_name . ' ' . $evaluator->last_name,
                    'email' => $evaluator->email,
                    'profile_photo' => $evaluator->profile_photo,
                    'role' => $evaluator->role,
                ] : null,

                // Player details
                'player' => $player ? [
                    'id' => $player->id,
                    'first_name' => $player->first_name,
                    'last_name' => $player->last_name,
                    'full_name' => $player->first_name . ' ' . $player->last_name,
                    'email' => $player->email,
                    'profile_photo' => $player->profile_photo,
                    'role' => $player->role,
                    'date_of_birth' => $player->date_of_birth,
                    'location' => $player->state . ', ' . $player->country,
                ] : null,

                // Evaluation details
                'personalized_evaluation' => $personalizedEvaluation,

                // In-app purchase details
                'in_app_purchase' => $inAppPurchase ? [
                    'id' => $inAppPurchase->id,
                    'sku' => $inAppPurchase->sku,
                    'title' => $inAppPurchase->title,
                    'amount' => $inAppPurchase->amount,
                    'formatted_amount' => $inAppPurchase->formatted_amount,
                    'currency' => $inAppPurchase->currency,
                ] : null,

                // Submission details
                'submission' => [
                    'id' => $evaluation->submission_id,
                    'status' => $evaluation->submission->status ?? null,
                ],

                // Marketplace type
                'marketplace_type' => $marketplaceType,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Consultation report retrieved successfully',
                'data' => $reportData,
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching consultation report: ' . $e->getMessage(), [
                'evaluation_id' => $evaluation_id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve consultation report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get mentorship report by evaluation ID
     *
     * @param int $evaluation_id
     * @return JsonResponse
     */
    public function getMentorshipReport(string $evaluation_id): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            // Get evaluation with all relationships
            $evaluation = Evaluation::with([
                'submission.paymentRequest.inAppPurchase.marketplaceItems',
                'submission.currentVersion',
                'submission.player.playerProfile',
                'evaluator',
                'answers.question.category',
                'answers.option'
            ])->find((int) $evaluation_id);

            if (!$evaluation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Evaluation not found',
                ], 404);
            }

            // Get marketplace type
            $marketplaceType = $evaluation->submission->paymentRequest->inAppPurchase->marketplaceItems->first()->type ?? null;

            // Verify this is a mentorship program
            if ($marketplaceType !== MarketplaceTypes::MENTORSHIP_PROGRAM) {
                return response()->json([
                    'success' => false,
                    'message' => 'This endpoint is only for 12-week mentorship program evaluations',
                    'marketplace_type' => $marketplaceType,
                ], 400);
            }

            // Get the submission and current version
            $submission = $evaluation->submission;
            $currentVersion = $submission->currentVersion;

            if (!$currentVersion) {
                return response()->json([
                    'success' => false,
                    'message' => 'No current version found for this submission',
                ], 404);
            }

            // Get the report_id from current version (for linked personalized evaluation)
            $reportId = $currentVersion->report_id;

            // Determine mentorship type
            $mentorshipType = $reportId ? 'by_evaluation' : 'by_video';

            // Get personalized evaluation if linked (only for by_evaluation type)
            $personalizedEvaluation = null;
            if ($mentorshipType === 'by_evaluation' && $reportId) {
                $personalizedEvaluation = Evaluation::find($reportId);
            }

            // Get mentorship feedback for this evaluation
            $feedback = V4ConsultationFeedback::with([
                'submissionVersion'
            ])->where('evaluation_id', $evaluation_id)->first();

            if (!$feedback) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mentorship feedback not found for this evaluation',
                ], 404);
            }

            // Get player and evaluator
            $player = $evaluation->submission->player ?? null;
            $playerProfile = $player->playerProfile ?? null;
            $evaluator = $evaluation->evaluator;
            $inAppPurchase = $evaluation->submission->paymentRequest->inAppPurchase ?? null;

            // Group answers by category (using same structure as getEvaluationReport)
            $categorizedAnswers = [];

            foreach ($evaluation->answers as $answer) {
                $category = $answer->question->category;
                $categorySlug = $category->slug;

                // Initialize category if not exists
                if (!isset($categorizedAnswers[$categorySlug])) {
                    $categorizedAnswers[$categorySlug] = [
                        'category_id' => $category->id,
                        'category_name' => $category->name,
                        'category_slug' => $categorySlug,
                        'note' => null,
                        'average_rating' => 0,
                        'total_rating' => 0,
                        'question_count' => 0,
                        'questions' => []
                    ];
                }

                // Add question and answer details
                $categorizedAnswers[$categorySlug]['questions'][] = [
                    'question_id' => $answer->question->id,
                    'question_title' => $answer->question->title,
                    'question_text' => $answer->question->question,
                    'selected_option_id' => $answer->question_option_id,
                    'selected_option_text' => $answer->option->option ?? null,
                    'rating' => $answer->rating,
                ];

                // Accumulate ratings for average calculation
                $categorizedAnswers[$categorySlug]['total_rating'] += $answer->rating;
                $categorizedAnswers[$categorySlug]['question_count']++;

                // Set note for category (same for all questions in category)
                if ($answer->comment && !$categorizedAnswers[$categorySlug]['note']) {
                    $categorizedAnswers[$categorySlug]['note'] = $answer->comment;
                }
            }

            // Calculate average rating for each category and clean up temporary fields
            foreach ($categorizedAnswers as $slug => &$category) {
                if ($category['question_count'] > 0) {
                    $category['average_rating'] = round($category['total_rating'] / $category['question_count'], 1);
                }
                // Remove temporary calculation fields
                unset($category['total_rating']);
                unset($category['question_count']);
            }

            // Convert to indexed array
            $categories = array_values($categorizedAnswers);

            // Helper function to get video path from submission version
            $getVideoPath = function ($submissionVersion) {
                if (!$submissionVersion) {
                    return null;
                }

                // If submission version has report_id, follow the chain
                if ($submissionVersion->report_id) {
                    $linkedEvaluation = Evaluation::with('submission.currentVersion')->find($submissionVersion->report_id);
                    if ($linkedEvaluation && $linkedEvaluation->submission && $linkedEvaluation->submission->currentVersion) {
                        return $linkedEvaluation->submission->currentVersion->file_path;
                    }
                }

                // Otherwise, return the file_path directly
                return $submissionVersion->file_path;
            };

            // Get new video (latest submission version)
            $latestSubmissionVersion = EvaluationSubmissionVersion::where('submission_id', $submission->id)
                ->orderBy('created_at', 'desc')
                ->first();
            $newVideo = $getVideoPath($latestSubmissionVersion);

            // Get old video (oldest submission version)
            $oldestSubmissionVersion = EvaluationSubmissionVersion::where('submission_id', $submission->id)
                ->where('mentorship_upload_type', EvaluationSubmissionVersion::MENTORSHIP_UPLOAD_TYPE_SUBMITTED_VIDEO)
                ->orderBy('created_at', 'asc')
                ->first();
            $oldVideo = $getVideoPath($oldestSubmissionVersion);

            // Build evaluation object based on mentorship type
            $evaluationData = [
                'id' => $evaluation->id,
                'status' => $evaluation->status,
                'notes' => $evaluation->notes,
                'created_at' => $evaluation->created_at->toISOString(),
                'meta' => $evaluation->meta,
                'new_video' => $newVideo,
                'old_video' => $oldVideo,
            ];

            // Format the response
            $reportData = [
                'evaluation_id' => $evaluation->id,
                'feedback_id' => $feedback->id,
                'mentorship_type' => $mentorshipType,
                'mentorship_weekday' => $feedback->submissionVersion->mentorship_weekday ?? null,
                'mentorship_time' => $feedback->submissionVersion->consultation_time ?? null,
                'created_at' => $evaluation->created_at->toISOString(),

                // Feedback details
                'feedback' => $feedback,

                // Evaluator details
                'evaluator' => $evaluator ? [
                    'id' => $evaluator->id,
                    'first_name' => $evaluator->first_name,
                    'last_name' => $evaluator->last_name,
                    'full_name' => $evaluator->first_name . ' ' . $evaluator->last_name,
                    'email' => $evaluator->email,
                    'profile_photo' => $evaluator->profile_photo,
                    'role' => $evaluator->role,
                ] : null,

                // Player details
                'player' => $player ? [
                    'id' => $player->id,
                    'first_name' => $player->first_name,
                    'last_name' => $player->last_name,
                    'full_name' => $player->first_name . ' ' . $player->last_name,
                    'email' => $player->email,
                    'profile_photo' => $player->profile_photo,
                    'role' => $player->role,
                    'date_of_birth' => $player->date_of_birth,
                    'location' => $player->state . ', ' . $player->country,
                    'player_profile' => $playerProfile,
                ] : null,

                'personalized_evaluation' => ($mentorshipType === 'by_evaluation' && $personalizedEvaluation) ? $personalizedEvaluation : null,

                'mentorship_result' => [
                    // Evaluation details (with conditional personalized_evaluation)
                    'evaluation' => $evaluationData,

                    // Categories with average ratings (same structure as getEvaluationReport)
                    'categories' => $categories,
                ],

                // In-app purchase details
                'in_app_purchase' => $inAppPurchase,

                // Submission details
                'submission' => [
                    'id' => $evaluation->submission_id,
                    'status' => $evaluation->submission->status ?? null,
                ],
            ];

            return response()->json([
                'success' => true,
                'message' => 'Mentorship report retrieved successfully',
                'data' => $reportData,
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching mentorship report: ' . $e->getMessage(), [
                'evaluation_id' => $evaluation_id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve mentorship report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get submission result with evaluation details
     *
     * @param Request $request
     * @param int $evaluationId
     * @return JsonResponse
     */
    public function getEvaluationReport(string $evaluationId): JsonResponse
    {
        try {
            // Get evaluation with all related data
            $evaluation = Evaluation::with([
                'submission.player.playerProfile',
                'submission.currentVersion',
                'evaluator',
                'answers.question.category',
                'answers.option'
            ])->find((int) $evaluationId);

            if (!$evaluation) {
                return response()->json(['success' => false, 'message' => 'Evaluation not found'], 404);
            }

            // Get marketplace type
            $marketplaceType = optional($evaluation->submission->paymentRequest->inAppPurchase->marketplaceItems->first())->type ?? null;

            // Verify this is a one-on-one consultation
            if ($marketplaceType !== MarketplaceTypes::PERSONALIZED_VIDEO_EVALUATION) {
                return response()->json([
                    'success' => false,
                    'message' => 'This endpoint is only for personalized video evaluations',
                    'marketplace_type' => $marketplaceType,
                ], 400);
            }

            $player = $evaluation->submission->player;
            $playerProfile = $player->playerProfile;
            $submissionVersion = $evaluation->submission->currentVersion;

            // Group answers by category
            $categorizedAnswers = [];

            foreach ($evaluation->answers as $answer) {
                $category = $answer->question->category;
                $categorySlug = $category->slug;

                // Initialize category if not exists
                if (!isset($categorizedAnswers[$categorySlug])) {
                    $categorizedAnswers[$categorySlug] = [
                        'category_id' => $category->id,
                        'category_name' => $category->name,
                        'category_slug' => $categorySlug,
                        'note' => null,
                        'average_rating' => 0,
                        'total_rating' => 0,
                        'question_count' => 0,
                        'questions' => []
                    ];
                }

                // Add question and answer details
                $categorizedAnswers[$categorySlug]['questions'][] = [
                    'question_id' => $answer->question->id,
                    'question_title' => $answer->question->title,
                    'question_text' => $answer->question->question,
                    'selected_option_id' => $answer->question_option_id,
                    'selected_option_text' => $answer->option->option ?? null,
                    'rating' => $answer->rating,
                ];

                // Accumulate ratings for average calculation
                $categorizedAnswers[$categorySlug]['total_rating'] += $answer->rating;
                $categorizedAnswers[$categorySlug]['question_count']++;

                // Set note for category (same for all questions in category)
                if ($answer->comment && !$categorizedAnswers[$categorySlug]['note']) {
                    $categorizedAnswers[$categorySlug]['note'] = $answer->comment;
                }
            }

            // Calculate average rating for each category and clean up temporary fields
            foreach ($categorizedAnswers as $slug => &$category) {
                if ($category['question_count'] > 0) {
                    $category['average_rating'] = round($category['total_rating'] / $category['question_count'], 1);
                }
                // Remove temporary calculation fields
                unset($category['total_rating']);
                unset($category['question_count']);
            }

            // Convert to indexed array
            $categories = array_values($categorizedAnswers);

            return response()->json([
                'success' => true,
                'message' => 'Submission result retrieved successfully',
                'data' => [
                    'evaluation' => [
                        'evaluation_id' => $evaluation->id,
                        'status' => $evaluation->status,
                        'video_url' => $submissionVersion->file_path ?? null,
                        'video_meta' => $submissionVersion->file_meta ?? null,
                        'created_at' => $evaluation->created_at->toISOString(),
                        'updated_at' => $evaluation->updated_at->toISOString(),
                    ],
                    'player' => [
                        'user_id' => $player->id,
                        'first_name' => $player->first_name,
                        'last_name' => $player->last_name,
                        'email' => $player->email,
                        'date_of_birth' => $player->date_of_birth,
                        'profile_photo' => $player->profile_photo,
                        'profile' => [
                            'teams' => $playerProfile->teams ?? null,
                            'leagues' => $playerProfile->leagues ?? null,
                            'position' => $playerProfile->position ?? null,
                            'handedness' => $playerProfile->handedness ?? null,
                            'height' => $playerProfile->height ?? null,
                            'weight' => $playerProfile->weight ?? null,
                            'gender' => $playerProfile->gender ?? null,
                        ]
                    ],
                    'evaluator' => [
                        'evaluator_id' => $evaluation->evaluator->id,
                        'first_name' => $evaluation->evaluator->first_name,
                        'last_name' => $evaluation->evaluator->last_name,
                        'profile_photo' => $evaluation->evaluator->profile_photo,
                        'country' => $evaluation->evaluator->country,
                        'state' => $evaluation->evaluator->state,
                        'city' => $evaluation->evaluator->city,
                        'zip' => $evaluation->evaluator->zip,
                        'email' => $evaluation->evaluator->email,
                        'phone' => $evaluation->evaluator->phone,
                    ],
                    'categories' => $categories,
                ],
            ], 200);
        } catch (Exception $e) {
            Log::error('Error retrieving submission result: ' . $e->getMessage(), [
                'evaluation_id' => $evaluationId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve submission result',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get player's own evaluation reports filtered by status
     *
     * @param Request $request
     * @param string $status
     * @return JsonResponse
     */
    public function getStatusFilteredMyReports(Request $request, string $status): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            // Validate user must be a player
            if (!$user || $user->role !== 'player') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only players can access their reports.',
                ], 403);
            }

            $playerId = $user->id;

            // Define status mapping
            $statusMap = [
                'pending' => [EvaluationSubmission::STATUS_PENDING],
                'completed' => [
                    EvaluationSubmission::STATUS_COMPLETED,
                    EvaluationSubmission::STATUS_REJECTED,
                ],
                'on_going' => 'exclude', // Special case: all except pending, completed, rejected
            ];

            // Validate status parameter
            if (!array_key_exists($status, $statusMap)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status. Must be pending, on_going, or completed',
                ], 400);
            }

            // Build base query
            $query = EvaluationSubmission::with([
                'paymentRequest.inAppPurchase.marketplaceItems',
            ])->where('player_id', $playerId);

            // Apply status filter
            if ($status === 'on_going') {
                // Exclude pending, completed, and rejected
                $query->whereNotIn('status', [
                    EvaluationSubmission::STATUS_PENDING,
                    EvaluationSubmission::STATUS_COMPLETED,
                    EvaluationSubmission::STATUS_REJECTED,
                ]);
            } else {
                // Use specific status values
                $query->whereIn('status', $statusMap[$status]);
            }

            $query->orderBy('created_at', 'desc');

            $submissions = $query->get();

            // Format the response
            $reports = $submissions->map(function ($submission) use ($status) {
                $marketplaceItem = $submission->paymentRequest->inAppPurchase->marketplaceItems->first();

                $report = [
                    'submission_id' => $submission->id,
                    'created_at' => $submission->created_at->toISOString(),
                    'status' => $submission->status,
                    'marketplace_title' => $marketplaceItem->title ?? null,
                    'marketplace_type' => $marketplaceItem->type ?? null,
                    'in_app_purchase_sku' => $submission->paymentRequest->inAppPurchase->sku ?? null,
                ];

                // Add evaluation_id for completed status
                if ($status === 'completed') {
                    // Get latest evaluation for this submission
                    $latestEvaluation = Evaluation::where('submission_id', $submission->id)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    $report['evaluation'] = $latestEvaluation;
                }

                return $report;
            });

            return response()->json([
                'success' => true,
                'message' => 'Reports retrieved successfully',
                'data' => [
                    'reports' => $reports,
                    'total_count' => $reports->count(),
                    'status_filter' => $status,
                    'player_id' => $playerId,
                ],
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching player reports: ' . $e->getMessage(), [
                'user_id' => Auth::guard('v4api')->id(),
                'status' => $status,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reports',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    protected function sendEvaluationRejectedNotification(Evaluation $evaluation, EvaluationRejectionReason $rejectionReason, EvaluatorAssignment $assignment, $notes)
    {
        $user = $assignment->submission->player;
        $title = "Video Evaluation Rejected";
        $message = "Your Personalized Video Evaluation is Rejected by the evaluator";

        $data = [
            'marketplace_item_type' => 'Personalized Video Evaluation',
            'evaluation_id' => $evaluation->id,
            'submission_id' => $assignment->submission_id,
            'rejection_reason' => $rejectionReason,
            'sku' => $assignment->submission->paymentRequest->inAppPurchase->sku,
            'notes' => $notes ?? 'No reason provided',
        ];

        // Send notification with appropriate icon
        $notification = $this->notificationService->sendToUserWithMaterialIcon(
            $user,
            $title,
            $message,
            'cancel', // Material icon for rejection
            '#F44336', // Red color for rejection
            $data,
            'video_evaluation_rejected',
            "/video-evaluations/$evaluation->id", // Redirect to evaluation details
            'video_evaluation_action',
            $evaluation // Reference to evaluation model
        );

        return $notification;
    }

    protected function sendEvaluationCompleteNotification(Evaluation $evaluation, EvaluatorAssignment $assignment)
    {
        $user = $assignment->submission->player;
        $title = "🎉 Video Evaluation Complete!";
        $message = "Your Personalized Video Evaluation is Completed";

        $data = [
            'marketplace_item_type' => 'Personalized Video Evaluation',
            'evaluation_id' => $evaluation->id,
            'submission_id' => $assignment->submission_id,
            'sku' => $assignment->submission->paymentRequest->inAppPurchase->sku,
        ];

        // Send notification with appropriate icon
        $notification = $this->notificationService->sendToUserWithMaterialIcon(
            $user,
            $title,
            $message,
            'task_alt', // Material icon for rejection
            '#4CAF50', // Red color for rejection
            $data,
            'video_evaluation_completed',
            "/video-evaluations/$evaluation->id/results", // Redirect to evaluation details
            'video_evaluation_results_action',
            $evaluation // Reference to evaluation model
        );

        return $notification;
    }


    /**
     * Send consultation request notification to evaluator
     */
    public function sendConsultationRequestNotification(V4User $player, V4User $evaluator, V4ConsultationRequest $consultationRequest)
    {
        $playerName = $player->first_name . ' ' . $player->last_name;
        $title = '1-on-1 Consultation Request';
        $message = "$playerName requested for a 1 on 1 consultation";

        $data = [
            'type' => 'consultation_request',
            'quick_actions' => ['accept', 'reject'],
            'action_required' => true,
            'player' => $player->only(['id', 'first_name', 'last_name', 'profile_photo', 'role']),
            'consultation_request_id' => $consultationRequest->id,
            'evaluation_id' => $consultationRequest->evaluation_id,
            'consultation_date' => $consultationRequest->submissionVersion->consultation_date ?? null,
            'consultation_time' => $consultationRequest->submissionVersion->consultation_time ?? null,
        ];

        return $this->notificationService->sendToUserWithImage(
            $evaluator,
            $title,
            $message,
            $player->profile_photo ?? "",
            $data,
            'consultation_request',
            "consultation/requests/{$consultationRequest->id}",
            'consultation_request_action',
            $consultationRequest
        );
    }

    /**
     * Send mentorship request notification to evaluator
     */
    public function sendMentorshipRequestNotification(V4User $player, V4User $evaluator, V4ConsultationRequest $mentorshipRequest)
    {
        $playerName = $player->first_name . ' ' . $player->last_name;
        $title = '12-Week Mentorship Program Request';
        $message = "$playerName requested for a 12-week mentorship program";

        $data = [
            'type' => 'mentorship_request',
            'quick_actions' => ['accept', 'reject'],
            'action_required' => true,
            'player' => $player->only(['id', 'first_name', 'last_name', 'profile_photo', 'role']),
            'consultation_request_id' => $mentorshipRequest->id,
            'submission_id' => $mentorshipRequest->submission_id,
            'evaluation_id' => $mentorshipRequest->evaluation_id ?? null,
            'weekday' => $mentorshipRequest->submissionVersion->mentorship_weekday ?? null,
            'time' => $mentorshipRequest->submissionVersion->consultation_time ?? null,
            'has_video' => !empty($mentorshipRequest->submissionVersion->file_path) && $mentorshipRequest->submissionVersion->file_path !== 'N/A',
            'video_url' => $mentorshipRequest->submissionVersion->file_path !== 'N/A' ? $mentorshipRequest->submissionVersion->file_path : null,
        ];

        return $this->notificationService->sendToUserWithImage(
            $evaluator,
            $title,
            $message,
            $player->profile_photo ?? "",
            $data,
            'mentorship_request',
            "mentorship/requests/{$mentorshipRequest->id}",
            'mentorship_request_action',
            $mentorshipRequest
        );
    }


    public function sendConsultationStatusNotification(V4User $evaluator, V4ConsultationRequest $consultationRequest, string $status)
    {
        $player = $consultationRequest->submission->player;
        $playerName = $player->first_name . ' ' . $player->last_name;

        $title = '1-on-1 Consultation Update';
        $message = $status === 'accepted'
            ? "You have accepted $playerName's consultation request."
            : "You have rejected $playerName's consultation request.";

        $data = [
            'type' => 'consultation_request_' . $status,
            'action_required' => false,
            'player' => $player->only(['id', 'first_name', 'last_name', 'profile_photo', 'role']),
            'consultation_request_id' => $consultationRequest->id,
            'evaluation_id' => $consultationRequest->evaluation_id,
            'consultation_date' => $consultationRequest->submissionVersion->consultation_date ?? null,
            'consultation_time' => $consultationRequest->submissionVersion->consultation_time ?? null,
            'status' => $status,
        ];

        return $this->notificationService->sendToUserWithImage(
            $evaluator,
            $title,
            $message,
            $player->profile_photo ?? "",
            $data,
            'consultation_request_' . $status,
            "consultation/requests/{$consultationRequest->id}",
            'consultation_request_status',
            $consultationRequest
        );
    }

    protected function deleteEvaluationRejectionNotifications(EvaluationSubmission $submission)
    {
        try {
            // Ensure evaluations are loaded to minimize queries
            $submission->load('evaluations.notifications');

            // Loop through each evaluation for this submission
            foreach ($submission->evaluations as $evaluation) {
                $evaluation->notifications()
                    ->where('type', [
                        'video_evaluation_rejected',
                        'consultation_rejected',
                        'mentorship_rejected'
                    ])
                    ->delete();
            }

            Log::info('Deleted rejection notifications for resubmitted evaluation.', [
                'submission_id' => $submission->id,
                'evaluation_ids' => $submission->evaluations->pluck('id'),
            ]);
        } catch (Exception $e) {
            Log::warning('Failed to delete evaluation rejection notifications', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
