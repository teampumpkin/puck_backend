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
                        'sort_order' => $category->sort_order,
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
}
