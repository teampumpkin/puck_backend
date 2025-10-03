<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\EvaluationCategory;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationQuestionOption;
use App\Models\V4User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class V4EvaluationController extends Controller
{
    /**
     * Get all evaluation questions with categories and options
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllQuestions(Request $request): JsonResponse
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
                    }
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
                            })
                        ];
                    })
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
                    )
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Error fetching evaluation questions: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve evaluation questions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
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
                    }
                ])
                ->find($categoryId);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found or inactive'
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
                        })
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'message' => 'Category questions retrieved successfully',
                'data' => $categoryData
            ], 200);

        } catch (Exception $e) {
            Log::error('Error fetching category questions: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'category_id' => $categoryId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve category questions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
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
                })
            ], 200);

        } catch (Exception $e) {
            Log::error('Error fetching evaluation categories: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve evaluation categories',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
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
                        'sort_order' => $category->sort_order,
                        'meta' => $category->meta,
                        'created_at' => $category->created_at,
                        'updated_at' => $category->updated_at,
                    ];
                })
            ], 200);

        } catch (Exception $e) {
            Log::error('Error fetching all evaluation categories: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve all evaluation categories',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
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
                'meta.*' => 'string'
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
                    'message' => 'Name already exists for an active category'
                ], 400);
            }

            $existingSlug = EvaluationCategory::where('slug', $validated['slug'])
                ->where('active', true)
                ->first();
            if ($existingSlug) {
                return response()->json([
                    'success' => false,
                    'message' => 'Slug already exists for an active category'
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
                            'message' => 'Meta keys and values must be strings'
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
                'updated_at' => now()->format('Y-m-d H:i:s')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => $category
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function deleteCategory(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:evaluation_categories,id'
            ]);

            $category = EvaluationCategory::findOrFail($validated['id']);
            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully'
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null
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
                'meta.*' => 'string'
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
                            'message' => 'Slug already exists for an active category'
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
                            'message' => 'Name already exists for an active category'
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
                        'message' => 'Cannot activate record: Sort order ' . $sortOrderToCheck . ' already exists for an active category (ID: ' . $existingSortOrder->id . ')'
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
                            'message' => 'Sort order ' . $validated['sort_order'] . ' already exists for an active category (ID: ' . $existingSortOrder->id . ')'
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
                                'message' => 'Meta keys and values must be strings'
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
                    'message' => 'At least one field (name, slug, description, active, sort_order, or meta) must be provided for update'
                ], 400);
            }

            $updateData['updated_at'] = now()->format('Y-m-d H:i:s');

            $category->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => $category->fresh()
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null
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
                'data' => $category
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 404);
        }
    }
}
