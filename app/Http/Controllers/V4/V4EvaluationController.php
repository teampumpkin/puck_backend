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
                    'message' => 'Question not found'
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
                'category' => $question->category
            ];

            return response()->json([
                'success' => true,
                'message' => 'Question retrieved successfully',
                'data' => $questionData
            ], 200);

        } catch (Exception $e) {
            Log::error('Error fetching question: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'question_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve question',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
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
                    'category' => $question->category
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Active questions retrieved successfully',
                'data' => [
                    'questions' => $questionsData
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Error fetching active questions: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve active questions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
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
                    'category' => $question->category
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'All questions retrieved successfully',
                'data' => [
                    'questions' => $questionsData
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Error fetching all questions: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve all questions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Delete a question by ID
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteQuestion(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:evaluation_questions,id'
            ]);

            $question = EvaluationQuestion::findOrFail($validated['id']);
            $question->delete();

            return response()->json([
                'success' => true,
                'message' => 'Question deleted successfully'
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error deleting question: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'question_id' => $request->input('id'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete question',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
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
                'category_id' => 'required|integer|exists:evaluation_categories,id',
                'title' => 'required|string|max:255',
                'question' => 'required|string',
                'required' => 'nullable|boolean',
                'sort_order' => 'nullable|integer|min:1',
                'meta' => 'nullable|array',
                'meta.*' => 'string'
            ]);

            // Check for duplicate title in the same category
            $existingTitle = EvaluationQuestion::where('category_id', $validated['category_id'])
                ->where('title', $validated['title'])
                ->first();

            if ($existingTitle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Title already exists for this category'
                ], 400);
            }

            // Check for duplicate question text in the same category
            $existingQuestion = EvaluationQuestion::where('category_id', $validated['category_id'])
                ->where('question', $validated['question'])
                ->first();

            if ($existingQuestion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Question text already exists for this category'
                ], 400);
            }

            // Set default values
            $validated['required'] = $validated['required'] ?? false;

            // If sort_order not provided, get the next available order for this category
            if (!isset($validated['sort_order'])) {
                $maxSortOrder = EvaluationQuestion::where('category_id', $validated['category_id'])->max('sort_order') ?? 0;
                $validated['sort_order'] = $maxSortOrder + 1;
            } else {
                // Check for duplicate sort_order in the same active category
                $existingSortOrder = EvaluationQuestion::where('category_id', $validated['category_id'])
                    ->where('sort_order', $validated['sort_order'])
                    ->where('active', true)
                    ->first();

                if ($existingSortOrder) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Sort order already exists for an active question in this category'
                    ], 400);
                }
            }

            // Handle meta data
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
            $validated['meta'] = $meta;

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
                    'category' => $question->category
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error creating question: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create question',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
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
                'sort_order' => 'sometimes|required|integer|min:1',
                'active' => 'sometimes|required|boolean',
                'meta' => 'sometimes|nullable|array',
                'meta.*' => 'string'
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
                            'message' => 'Title already exists for this category'
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
                            'message' => 'Question text already exists for this category'
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
            if (isset($validated['sort_order'])) {
                $activeToCheck = isset($validated['active']) ? $validated['active'] : $question->active;
                $categoryIdToCheck = isset($validated['category_id']) ? $validated['category_id'] : $question->category_id;

                if ($activeToCheck === true) {
                    $existingSortOrder = EvaluationQuestion::where('category_id', $categoryIdToCheck)
                        ->where('sort_order', $validated['sort_order'])
                        ->where('id', '!=', $validated['id'])
                        ->where('active', true)
                        ->first();

                    if ($existingSortOrder) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Sort order already exists for an active question in this category'
                        ], 400);
                    }
                }
                $updateData['sort_order'] = $validated['sort_order'];
                $hasAtLeastOneField = true;
            }

            // Handle active field with sort_order validation
            if (isset($validated['active'])) {
                if ($validated['active'] === true) {
                    $sortOrderToCheck = isset($validated['sort_order']) ? $validated['sort_order'] : $question->sort_order;
                    $categoryIdToCheck = isset($validated['category_id']) ? $validated['category_id'] : $question->category_id;

                    $existingSortOrder = EvaluationQuestion::where('category_id', $categoryIdToCheck)
                        ->where('sort_order', $sortOrderToCheck)
                        ->where('id', '!=', $validated['id'])
                        ->where('active', true)
                        ->first();

                    if ($existingSortOrder) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Cannot activate record: Sort order already exists for an active question in this category'
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
                    'message' => 'At least one field (category_id, title, question, required, sort_order, active, or meta) must be provided for update'
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
                    'category' => $question->category
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error updating question: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'question_id' => $request->input('id'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update question',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
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
                        'category' => $option->question->category
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Question options retrieved successfully',
                'data' => [
                    'options' => $optionsData
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Error fetching question options: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve question options',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
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
                    'message' => 'Question option not found'
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
                    'category' => $option->question->category
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Question option retrieved successfully',
                'data' => $optionData
            ], 200);

        } catch (Exception $e) {
            Log::error('Error fetching question option: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'option_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve question option',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
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
                'meta.*' => 'string'
            ]);

            // Validate rating is in multiples of 0.5
            if (fmod($validated['rating'], 0.5) !== 0.0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rating must be in multiples of 0.5 only'
                ], 400);
            }

            // Check for duplicate option text in the same question
            $existingOption = EvaluationQuestionOption::where('question_id', $validated['question_id'])
                ->where('option', $validated['option'])
                ->first();

            if ($existingOption) {
                return response()->json([
                    'success' => false,
                    'message' => 'Option text already exists for this question'
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
                        'message' => 'Sort order already exists for this question'
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
                    'message' => 'Rating already exists for this question'
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
                            'message' => 'Meta keys and values must be strings'
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
                        'category' => $option->question->category
                    ]
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error creating question option: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create question option',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
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
                'meta.*' => 'string'
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
                            'message' => 'Option text already exists for this question'
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
                            'message' => 'Rating must be in multiples of 0.5 only'
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
                                'message' => 'Rating already exists for this question'
                            ], 400);
                        }
                    }
                }
                $updateData['rating'] = $validated['rating'];
                $hasAtLeastOneField = true;
            }

            // Handle sort_order with duplicate check
            if (array_key_exists('sort_order', $validated)) {
                $questionIdToCheck = isset($validated['question_id']) ? $validated['question_id'] : $option->question_id;

                if ($validated['sort_order'] !== null) {
                    $existingSortOrder = EvaluationQuestionOption::where('question_id', $questionIdToCheck)
                        ->where('sort_order', $validated['sort_order'])
                        ->where('id', '!=', $validated['id'])
                        ->first();

                    if ($existingSortOrder) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Sort order already exists for this question'
                        ], 400);
                    }
                }
                $updateData['sort_order'] = $validated['sort_order'];
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
                    'message' => 'At least one field (question_id, title, option, rating, sort_order, or meta) must be provided for update'
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
                        'category' => $option->question->category
                    ]
                ]
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error updating question option: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'option_id' => $request->input('id'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update question option',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Delete a question option by ID
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteQuestionOption(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:evaluation_question_options,id'
            ]);

            $option = EvaluationQuestionOption::findOrFail($validated['id']);
            $option->delete();

            return response()->json([
                'success' => true,
                'message' => 'Question option deleted successfully'
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error deleting question option: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'option_id' => $request->input('id'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete question option',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
<<<<<<< HEAD

    /**
     * Upload evaluation video
     * This function can be called by other controllers
     *
     * @param Request $request
     * @param int $evaluationId (optional) - if provided, associates video with specific evaluation
     * @param int $userId (optional) - if provided, uses this user ID instead of authenticated user
     * @return JsonResponse
     */
    public function uploadEvaluationVideo(Request $request, $userId = null): JsonResponse
    {
        try {
            // Get user - either from parameter or authenticated user
            if ($userId) {
                $user = V4User::findOrFail($userId);
            } else {
                /** @var V4User $user */
                $user = Auth::guard('v4api')->user();
            }

            // Validate request
            $validated = $request->validate([
                'video' => 'required|file|mimes:mp4,avi,mov,wmv,flv,webm|max:102400', // 100MB max
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000'
            ]);

            // Handle file upload
            if ($request->hasFile('video')) {
                $file = $request->file('video');
                $mimeType = $file->getClientMimeType();
                $fileSize = $file->getSize(); // Size in bytes

                // Generate unique filename to prevent conflicts
                $filename = 'eval_video_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                // Store file in S3 under evaluation-videos directory
                $path = $file->storeAs(
                    'evaluation-videos/' . $user->id,
                    $filename,
                    's3'
                );

                $videoUrl = Storage::disk('s3')->url($path);
                $originalName = $file->getClientOriginalName();

                return response()->json([
                    'success' => true,
                    'message' => 'Evaluation video uploaded successfully',
                    'data' => [
                        'video_url' => $videoUrl,
                        'file_path' => $path,
                        'title' => $validated['title'] ?? $originalName,
                        'description' => $validated['description'],
                        'original_name' => $originalName,
                        'file_size' => $fileSize,
                        'mime_type' => $mimeType,
                        'uploaded_at' => now()->toISOString(),
                        'user_id' => $user->id,
                    ]
                ], 201);
            }

            return response()->json([
                'success' => false,
                'message' => 'No video file provided'
            ], 400);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Error uploading evaluation video: ' . $e->getMessage(), [
                'user_id' => $userId ?? Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload evaluation video',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
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
                        'mime_type' => Storage::disk('s3')->mimeType($file)
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
                    'total_size' => array_sum(array_column($videos, 'size'))
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Error fetching evaluation videos: ' . $e->getMessage(), [
                'user_id' => $userId ?? Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve evaluation videos',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
=======
>>>>>>> 8195f09359457f493e2e83a875f8e4760febbc66
}
