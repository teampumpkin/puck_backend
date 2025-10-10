<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\EvaluationCategory;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationQuestionOption;
use App\Models\EvaluationSubmission;
use App\Models\EvaluationSubmissionVersion;
use App\Models\EvaluatorAssignment;
use App\Models\V4PaymentRequest;
use App\Models\V4User;
use App\Models\V4InAppPurchase;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class V4EvaluationController extends Controller
{
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
            ;

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
     * Upload evaluation video
     * This function can be called by other controllers
     *
     * @param Request $request
     * @param int $evaluationId (optional) - if provided, associates video with specific evaluation
     * @param int $userId (optional) - if provided, uses this user ID instead of authenticated user
     * @return JsonResponse
     */
    public function uploadEvaluationVideo(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            $playerId = $user->id; // Get user_id from token

            // Validate only video file is required
            $request->validate([
                'video' => 'required|file',
            ]);

            // Check if payment is completed for this player
            $paymentRequest = V4PaymentRequest::where('player_id', $playerId)
                ->where('status', V4PaymentRequest::STATUS_PAID)
                ->first();

            if (!$paymentRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not completed for this player',
                ], 400);
            }

            // Handle file upload
            if (!$request->hasFile('video')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No video file provided',
                ], 400);
            }

            $file = $request->file('video');

            // Check if file upload was successful
            if (!$file->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'File upload failed: ' . $file->getError(),
                ], 422);
            }

            $mimeType = $file->getClientMimeType();
            $fileSize = $file->getSize();

            // Check if it's a video file
            if (!str_starts_with($mimeType, 'video/')) {
                return response()->json([
                    'success' => false,
                    'message' => 'File must be a video',
                ], 422);
            }

            // Check file size (100MB max)
            $maxSizeInBytes = 100 * 1024 * 1024; // 100MB
            if ($fileSize > $maxSizeInBytes) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video file size must not exceed 100MB',
                ], 422);
            }

            // Check if submission already exists for this player
            $existingSubmission = EvaluationSubmission::where('player_id', $playerId)
                ->where('payment_request_id', $paymentRequest->id)
                ->first();

            if ($existingSubmission) {
                // Only allow new version if status is rejected
                if ($existingSubmission->status !== EvaluationSubmission::STATUS_REJECTED) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Already uploaded a video for evaluation',
                        'submission_id' => $existingSubmission->id,
                        'current_status' => $existingSubmission->status,
                    ], 400);
                }
            }

            // Generate unique filename to prevent conflicts
            $filename = 'eval_video_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Store file in S3 under evaluation-videos directory
            $path = $file->storeAs(
                'evaluation-videos/' . $playerId,
                $filename,
                's3'
            );

            $videoUrl = Storage::disk('s3')->url($path);
            $originalName = $file->getClientOriginalName();

            // Prepare file metadata
            $fileMeta = [
                'original_name' => $originalName,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'video_url' => $videoUrl,
                'uploaded_at' => now()->toISOString(),
            ];

            // Create or update submission
            if ($existingSubmission) {
                // Update existing submission status to uploaded
                $existingSubmission->update([
                    'status' => EvaluationSubmission::STATUS_UPLOADED,
                ]);
                $submission = $existingSubmission;
            } else {
                // Create new submission
                $submission = EvaluationSubmission::create([
                    'player_id' => $playerId,
                    'payment_request_id' => $paymentRequest->id,
                    'status' => EvaluationSubmission::STATUS_UPLOADED,
                ]);
            }

            // Create submission version
            $submissionVersion = EvaluationSubmissionVersion::create([
                'submission_id' => $submission->id,
                'file_path' => $videoUrl,
                'file_meta' => $fileMeta,
                'uploaded_by' => $user->id,
            ]);

            // Update submission with current version ID
            $submission->update([
                'current_version_id' => $submissionVersion->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Evaluation video uploaded successfully',
                'data' => [
                    'player_id' => $playerId,
                    'submission_id' => $submission->id,
                    'submission_version_id' => $submissionVersion->id,
                    'status' => $submission->status,
                    'video_url' => $videoUrl,
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType,
                    'uploaded_at' => now()->toISOString(),
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error uploading evaluation video: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload evaluation video',
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

    /**
     * Allot evaluator for submission
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function allotEvaluatorForSubmission(Request $request): JsonResponse
    {
        try {
            // Validate required fields
            $request->validate([
                'evaluator_id' => 'required|integer|exists:v4_users,id',
                'submission_id' => 'required|integer|exists:evaluation_submissions,id',
            ]);

            $evaluatorId = $request->input('evaluator_id');
            $submissionId = $request->input('submission_id');

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

            // Check submission status - only allow if status is 'uploaded'
            if ($submission->status !== EvaluationSubmission::STATUS_UPLOADED) {
                $existingAssignment = EvaluatorAssignment::where('submission_id', $submissionId)->first();
                return response()->json([
                    'success' => false,
                    'message' => "Submission already {$submission->status}",
                    'submission_id' => $submissionId,
                    'evaluator_id' => $existingAssignment->evaluator_id,
                    'current_status' => $submission->status,
                ], 400);
            }

            // Create evaluator assignment
            $assignment = EvaluatorAssignment::create([
                'submission_id' => $submissionId,
                'evaluator_id' => $evaluatorId,
                'status' => EvaluatorAssignment::STATUS_PENDING,
                'assigned_at' => now(),
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
                    'evaluator_name' => $evaluator->first_name . ' ' . $evaluator->last_name,
                    'assignment_status' => $assignment->status,
                    'assigned_at' => $assignment->assigned_at->toISOString(),
                    'submission_status' => $submission->status,
                ],
            ], 201);
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

            // Validate status parameter
            if (!in_array($status, ['pending', 'completed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status. Must be pending or completed',
                ], 400);
            }

            // Build query based on status and current user's evaluator ID
            $query = EvaluatorAssignment::with([
                'submission.player',
                'submission.paymentRequest.inAppPurchase',
                'evaluator',
            ])->where('evaluator_id', $user->id);

            if ($status === 'pending') {
                $query->where('status', EvaluatorAssignment::STATUS_PENDING);
            } else {
                // For completed, include both completed and rejected
                $query->whereIn('status', [
                    EvaluatorAssignment::STATUS_COMPLETED,
                    EvaluatorAssignment::STATUS_REJECTED,
                ]);
            }

            $assignments = $query->orderBy('assigned_at', 'desc')->get();

            $formattedAssignments = $assignments->map(function ($assignment) {
                return [
                    'assignment_id' => $assignment->id,
                    'status' => $assignment->status,
                    'notes' => $assignment->notes,
                    'submission_date' => $assignment->submission->updated_at->toISOString(),
                    'player' => [
                        'id' => $assignment->submission->player->id,
                        'name' => $assignment->submission->player->first_name . ' ' . $assignment->submission->player->last_name,
                        'role' => $assignment->submission->player->role,
                    ],
                    'in_app_purchase' => $assignment->submission->paymentRequest->inAppPurchase ? [
                        'id' => $assignment->submission->paymentRequest->inAppPurchase->id,
                        'sku' => $assignment->submission->paymentRequest->inAppPurchase->sku,
                        'title' => $assignment->submission->paymentRequest->inAppPurchase->title,
                        'active' => $assignment->submission->paymentRequest->inAppPurchase->active,
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => "Evaluator assignments retrieved successfully",
                'data' => [
                    'assignments' => $formattedAssignments,
                    'total_count' => $formattedAssignments->count(),
                    'status_filter' => $status,
                    'evaluator_id' => $user->id,
                    'filters_applied' => $status === 'pending'
                        ? ['status' => 'pending', 'evaluator_id' => $user->id]
                        : ['status' => ['completed', 'rejected'], 'evaluator_id' => $user->id],
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
     * Check video evaluation status for a player
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function videoEvaluationStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'sku' => 'required|string',
                'user_id' => 'sometimes|integer|exists:v4_users,id'
            ]);

            $sku = $request->input('sku');
            $userId = $request->input('user_id') ?? Auth::guard('v4api')->id();

            if (!$userId) {
                return response()->json(['success' => false, 'message' => 'Authentication required'], 401);
            }

            $user = V4User::find($userId);
            if (!$user || $user->role !== 'player') {
                return response()->json(['success' => false, 'message' => 'Access denied. Only players can check evaluation status.'], 403);
            }

            $inAppPurchase = V4InAppPurchase::where('sku', $sku)->active()->first();
            if (!$inAppPurchase) {
                return response()->json(['success' => false, 'message' => 'Invalid SKU'], 400);
            }

            $paymentRequest = V4PaymentRequest::where('in_app_purchase_id', $inAppPurchase->id)
                ->where('player_id', $userId)
                ->orderBy('updated_at', 'desc')
                ->first();

            // Handle payment statuses
            if (!$paymentRequest || in_array($paymentRequest->status, [V4PaymentRequest::STATUS_FAILED, V4PaymentRequest::STATUS_PARENT_REJECTED])) {
                return response()->json(['success' => true, 'redirect' => 'make_payment'], 200);
            }

            if ($paymentRequest->status === V4PaymentRequest::STATUS_PENDING) {
                return response()->json(['success' => true, 'redirect' => 'payment_approval_pending'], 200);
            }

            if ($paymentRequest->status === V4PaymentRequest::STATUS_PAYMENT_INITIATED) {
                return response()->json(['success' => true, 'redirect' => 'payment_in_process'], 200);
            }

            // Handle paid status - check evaluation submission
            if ($paymentRequest->status === V4PaymentRequest::STATUS_PAID) {
                $evaluationSubmission = EvaluationSubmission::where('payment_request_id', $paymentRequest->id)
                    ->where('player_id', $userId)
                    ->first();

                if (!$evaluationSubmission || $evaluationSubmission->status === EvaluationSubmission::STATUS_PENDING) {
                    return response()->json(['success' => true, 'status' => 'pending', 'redirect' => 'submit_video'], 200);
                }

                if (in_array($evaluationSubmission->status, [EvaluationSubmission::STATUS_REJECTED, EvaluationSubmission::STATUS_COMPLETED])) {
                    return response()->json(['success' => true, 'redirect' => 'make_payment'], 200);
                }

                if ($evaluationSubmission->status === EvaluationSubmission::STATUS_ASSIGNED) {
                    return response()->json(['success' => true, 'status' => 'assigned', 'redirect' => 'evaluation_in_process'], 200);
                }

                if ($evaluationSubmission->status === EvaluationSubmission::STATUS_UPLOADED) {
                    return response()->json(['success' => true, 'status' => 'uploaded', 'redirect' => 'evaluation_in_process'], 200);
                }
            }

            return response()->json(['success' => true, 'redirect' => 'make_payment'], 200);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Video evaluation status check failed', ['error' => $e->getMessage(), 'sku' => $request->input('sku'), 'user_id' => $request->input('user_id')]);
            return response()->json(['success' => false, 'message' => 'Failed to check video evaluation status', 'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'], 500);
        }
    }
}
