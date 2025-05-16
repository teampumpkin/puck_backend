<?php

namespace App\Console\Commands;

use App\Models\PrcAdvanceAssessmentCategory;
use App\Models\PrcAdvanceAssessmentSkill;
use App\Models\PrcAdvanceAssessmentValue;
use App\Models\PrcAdvanceAssessmentValueStatement;
use App\Models\PrcPosition;
use Illuminate\Console\Command;

/**
 * Class SyncAdvanceAssessmentSkill
 * @package App\Console\Commands
 */
class SyncAdvanceAssessmentSkill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:skill {--position_id=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Skills for new Position';
    /**
     * @var PrcPosition
     */
    private $player_position;
    /**
     * @var PrcAdvanceAssessmentCategory
     */
    private $assessment_category;
    /**
     * @var PrcAdvanceAssessmentSkill
     */
    private $assessment_skill;
    /**
     * @var PrcAdvanceAssessmentValue
     */
    private $assessment_skill_value;
    /**
     * @var PrcAdvanceAssessmentValueStatement
     */
    private $assessment_skill_value_statement;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->player_position                  = new PrcPosition();
        $this->assessment_category              = new PrcAdvanceAssessmentCategory();
        $this->assessment_skill                 = new PrcAdvanceAssessmentSkill();
        $this->assessment_skill_value           = new PrcAdvanceAssessmentValue();
        $this->assessment_skill_value_statement = new PrcAdvanceAssessmentValueStatement();
    }

    /**
     * @return int
     */
    public function handle()
    {
        $count = 0;
        if ($this->option('position_id') > 0) {
            $position = $this->player_position->with(['assessment_categories'])
                ->where('id', $this->option('position_id'))
                ->where('status', 1)
                ->first();

            if (!empty($position)) {
                $count = $this->syncSkills($position);
            }
        } else {
            $positions = $this->player_position->with(['assessment_categories'])
                ->where('id', '!=', 5)
                ->where('status', 1)
                ->get();
            foreach ($positions as $position) {
                $count = $this->syncSkills($position);
            }
        }
        if ($count == 0) {
            $this->line('<fg=red>Nothing to sync</>');
            return 0;
        }
        $this->line('<fg=green>All skill have been sync with new player position</>');
        return 0;
    }

    /**
     * @param $position
     * @return int
     */
    public function syncSkills($position)
    {
        $count                 = 0;
        $assessment_categories = $this->assessment_category->with([
            'skills',
            'skills.skill_values',
            'skills.skill_values.assessment_statements',

        ])
            ->where('player_position_id', 6)
            ->get();

        if (empty($assessment_categories)) {
            return $count;
        }

        foreach ($assessment_categories as $assessment_category) {
            if (!empty($position->assessment_categories) && array_search($assessment_category->category_name, array_column($position->assessment_categories->toArray(), 'category_name')) !== FALSE) {
                continue;
            }
            $count++;
            $category = $this->assessment_category->create([
                'player_position_id' => $position->id,
                'category_name'      => $assessment_category->category_name,
                'category_info'      => $assessment_category->category_info
            ]);

            foreach ($assessment_category->skills as $skill) {
                $skill_obj = $this->assessment_skill->create([
                    'category_id' => $category->id,
                    'skill_name'  => $skill->skill_name,
                    'skill_info'  => $skill->skill_info
                ]);

                foreach ($skill->skill_values as $skill_value) {
                    $assessment_skill_value_obj = $this->assessment_skill_value->create([
                        'skill_id'              => $skill_obj->id,
                        'rating'                => $skill_value->rating,
                        'key_word'              => $skill_value->key_word,
                        'rubric_classification' => $skill_value->rubric_classification,
                    ]);

                    foreach ($skill_value->assessment_statements as $assessment_statement) {
                        $this->assessment_skill_value_statement->create([
                            'assessment_value_id' => $assessment_skill_value_obj->id,
                            'statement'           => $assessment_statement->statement
                        ]);
                    }
                }
            }
        }
        return $count;
    }
}
