<?php

namespace Database\Seeders;

use App\Models\PrcAdvanceAssessmentCategory;
use App\Models\PrcAdvanceAssessmentSkill;
use App\Models\PrcAdvanceAssessmentValue;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AssessmentDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $category = PrcAdvanceAssessmentCategory::first();

        $timestamp = Carbon::now();

        if (empty($category)) {
            PrcAdvanceAssessmentCategory::insert([
                [
                    'player_position_id' => 6,
                    'category_name'      => 'Skating',
                    'editor'             => 'Joel',
                    'created_at'         => $timestamp,
                    'updated_at'         => $timestamp
                ],
                [
                    'player_position_id' => 6,
                    'category_name'      => 'Compete',
                    'editor'             => 'Nate',
                    'created_at'         => $timestamp,
                    'updated_at'         => $timestamp
                ],
                [
                    'player_position_id' => 6,
                    'category_name'      => 'Hockey IQ',
                    'editor'             => 'Jason K',
                    'created_at'         => $timestamp,
                    'updated_at'         => $timestamp
                ],
                [
                    'player_position_id' => 6,
                    'category_name'      => 'Skills',
                    'editor'             => 'Turner',
                    'created_at'         => $timestamp,
                    'updated_at'         => $timestamp
                ],
                [
                    'player_position_id' => 5,
                    'category_name'      => 'Technical',
                    'editor'             => 'Turner',
                    'created_at'         => $timestamp,
                    'updated_at'         => $timestamp
                ],
                [
                    'player_position_id' => 5,
                    'category_name'      => 'Athleticism',
                    'editor'             => 'Turner',
                    'created_at'         => $timestamp,
                    'updated_at'         => $timestamp
                ],
                [
                    'player_position_id' => 5,
                    'category_name'      => 'Hockey IQ',
                    'editor'             => 'Turner',
                    'created_at'         => $timestamp,
                    'updated_at'         => $timestamp
                ],
            ]);
        }

        $skill = PrcAdvanceAssessmentSkill::first();

        if (empty($skill)) {
            PrcAdvanceAssessmentSkill::insert([
                [
                    'category_id' => 1,
                    'skill_name'  => 'Mechanics',
                    'skill_info'  => 'Quiet head,fluid body moving forward, perfect edge selection',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 1,
                    'skill_name'  => 'Control',
                    'skill_info'  => 'Very small, smooth movements, done low and in control',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 1,
                    'skill_name'  => 'Speed',
                    'skill_info'  => 'Deliberate, urgent, desperate and controlled movements ',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 2,
                    'skill_name'  => 'Engagement',
                    'skill_info'  => 'Fully engaged, establishing space early while not losing battles ',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 2,
                    'skill_name'  => 'Technique',
                    'skill_info'  => 'Solid structure,having influence away from the puck with high anticipation and recognition of outcome (*lineup=proper stance, kness over toes, hips square over knees, shoulders proper over core, heads up)',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 2,
                    'skill_name'  => 'Persistence',
                    'skill_info'  => 'Relentless pursuit, staying ahead of the play and communicating direction, while being overwhelming to play against ',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 3,
                    'skill_name'  => 'Vision',
                    'skill_info'  => 'Ahead of  the situation, with ability to read all options and execute ',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 3,
                    'skill_name'  => 'Position',
                    'skill_info'  => 'Precise spacing and positioning in relation to the play',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 3,
                    'skill_name'  => 'Execution',
                    'skill_info'  => 'Efficient and effective action on available  options to create time and space',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 4,
                    'skill_name'  => 'Puck Handling',
                    'skill_info'  => 'Fluid smooth movements with little sound and precise directions ',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 4,
                    'skill_name'  => 'Passing',
                    'skill_info'  => 'Gifted heads up vision, with deliberate and precise accuracy ',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 4,
                    'skill_name'  => 'Shooting',
                    'skill_info'  => 'Proper stick position, with powerful and descriptive release',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 5,
                    'skill_name'  => 'Movement',
                    'skill_info'  => 'Precise edges and expansive mobility, explosive power (drops hard, rises fast), post save recoveries (up and down), balance/control is fluid (push and pull- limbs collaborative), efficiency (eliminates delays without delay)',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 5,
                    'skill_name'  => 'Position',
                    'skill_info'  => 'Movement (tracking efficiency), angle/square, depth, set',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 5,
                    'skill_name'  => 'Positional situations',
                    'skill_info'  => 'Entries/rush, laterals, clears shots, net drives, recoveries, tip deflection, pass outs, walkouts, wraps, breakaways',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 5,
                    'skill_name'  => 'Rebound control',
                    'skill_info'  => 'Body/cradles, glove (catches), blocker (places pucks 4 pads), stick, gathers pucks ',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 5,
                    'skill_name'  => 'Puck Handling',
                    'skill_info'  => 'Decisive/confident, urgent, understands/sees options',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 5,
                    'skill_name'  => 'Traffic Management',
                    'skill_info'  => 'Positioning around screens, maintain visual contact, finds pucks, depth management, maintains stance',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 6,
                    'skill_name'  => 'Compete',
                    'skill_info'  => 'Battles for puck,  proper structure, broken plays, reactivity ',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 6,
                    'skill_name'  => 'Athletic',
                    'skill_info'  => 'Flexibility, balance control, reactions (quick reflex) ',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 6,
                    'skill_name'  => 'Net coverage',
                    'skill_info'  => 'Height/stance (uses effective/efficient) stance biomechanics=balance deficiencies, proper stance through ankles, knees, hips, back, neck physical presence box control (access to pucks) blocking (closes on pucks)',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 7,
                    'skill_name'  => 'Reads',
                    'skill_info'  => 'Ice awareness, identifies threats, depth adjustments, ready early',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 7,
                    'skill_name'  => 'Releases',
                    'skill_info'  => 'Patient (holds feet), tracking (stays on puck), shifts on puck (blocking)',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 7,
                    'skill_name'  => 'Save selection/execution',
                    'skill_info'  => 'Make key saves, performance under pressure, displays confidence in all areas',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ],
                [
                    'category_id' => 7,
                    'skill_name'  => 'Communication',
                    'skill_info'  => 'Mental,calm demeanor, body language',
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp
                ]
            ]);
        }

        $assessment_value = PrcAdvanceAssessmentValue::first();

        if (empty($assessment_value)) {
            PrcAdvanceAssessmentValue::insert([
                [
                    'skill_id'              => 1,
                    'rating'                => 5,
                    'key_word'              => 'Ideal Structure',
                    'rubric_classification' => 'Quiet Head, fluid body moving forward, perfect edge selection'
                ],
                [
                    'skill_id'              => 1,
                    'rating'                => 4.5,
                    'key_word'              => 'Solid Equilibrium',
                    'rubric_classification' => 'Floating body, legs and arms moving separately but in unison with body'
                ],
                [
                    'skill_id'              => 1,
                    'rating'                => 4,
                    'key_word'              => 'Poised',
                    'rubric_classification' => 'Fluid gear changes, moving consistently smooth on transitions'
                ],
                [
                    'skill_id'              => 1,
                    'rating'                => 3.5,
                    'key_word'              => 'Helpful Posture',
                    'rubric_classification' => 'Not changing stance with direction changes or fatigue'
                ],
                [
                    'skill_id'              => 1,
                    'rating'                => 3,
                    'key_word'              => 'Inconsistent Stance',
                    'rubric_classification' => 'Moving body in other directions than is headed, bobbing or straight legged'
                ],
                [
                    'skill_id'              => 1,
                    'rating'                => 2.5,
                    'key_word'              => 'Shaky',
                    'rubric_classification' => 'Not getting under body with legs, not using edges properly'
                ],
                [
                    'skill_id'              => 1,
                    'rating'                => 2,
                    'key_word'              => 'Flailing',
                    'rubric_classification' => 'Choppy, over working, short stride'
                ],
                [
                    'skill_id'              => 1,
                    'rating'                => 1.5,
                    'key_word'              => 'Tottery',
                    'rubric_classification' => 'Laboring, not balanced, very disrupted in between movements'
                ],
                [
                    'skill_id'              => 1,
                    'rating'                => 1,
                    'key_word'              => 'Rigid',
                    'rubric_classification' => 'Quiet head, fluid body moving forward, perfect edge selection'
                ],
                [
                    'skill_id'              => 2,
                    'rating'                => 5,
                    'key_word'              => 'Fluid Mobility',
                    'rubric_classification' => 'Very small, smooth movements, everything done low and in control'
                ],
                [
                    'skill_id'              => 2,
                    'rating'                => 4.5,
                    'key_word'              => 'Firm Agility',
                    'rubric_classification' => 'Body stays aligned to center of gravity, does not shift too far from either way'
                ],
                [
                    'skill_id'              => 2,
                    'rating'                => 4,
                    'key_word'              => 'Strong Sharpness',
                    'rubric_classification' => 'Loads power efficiently, and body works in unison'
                ],
                [
                    'skill_id'              => 2,
                    'rating'                => 3.5,
                    'key_word'              => 'Balance',
                    'rubric_classification' => 'Low, wide, right edges and momentum shifts'
                ],
                [
                    'skill_id'              => 2,
                    'rating'                => 3,
                    'key_word'              => 'Maneuverable',
                    'rubric_classification' => 'Continues progression without starting from beginning'
                ],
                [
                    'skill_id'              => 2,
                    'rating'                => 2.5,
                    'key_word'              => 'Bounce',
                    'rubric_classification' => 'Parts of body move in many different directions, trying to get momentum with jerky movements'
                ],
                [
                    'skill_id'              => 2,
                    'rating'                => 2,
                    'key_word'              => 'Sluggish',
                    'rubric_classification' => 'No real rhythm of stride or push, gravity pull seems stronger than normal'
                ],
                [
                    'skill_id'              => 2,
                    'rating'                => 1.5,
                    'key_word'              => 'Delayed',
                    'rubric_classification' => 'No control over edges or body'
                ],
                [
                    'skill_id'              => 2,
                    'rating'                => 1,
                    'key_word'              => 'Planted',
                    'rubric_classification' => 'Very long process to have movement or urgency or know how to get moving'
                ],
                [
                    'skill_id'              => 3,
                    'rating'                => 5,
                    'key_word'              => 'Explosive',
                    'rubric_classification' => 'Deliberate,  urgent, desperate, controlled movements'
                ],
                [
                    'skill_id'              => 3,
                    'rating'                => 4.5,
                    'key_word'              => 'Strong Acceleration',
                    'rubric_classification' => 'Low, powerful and uniformed fundamentals'
                ],
                [
                    'skill_id'              => 3,
                    'rating'                => 4,
                    'key_word'              => 'Effective Velocity',
                    'rubric_classification' => 'Power shifts quick, smooth and in proper directions'
                ],
                [
                    'skill_id'              => 3,
                    'rating'                => 3.5,
                    'key_word'              => 'Swift Pace',
                    'rubric_classification' => 'Always moving and using quick bursts'
                ],
                [
                    'skill_id'              => 3,
                    'rating'                => 3,
                    'key_word'              => 'Cruise',
                    'rubric_classification' => 'Speed adjustments, proper timing, not drifting'
                ],
                [
                    'skill_id'              => 3,
                    'rating'                => 2.5,
                    'key_word'              => 'Momentum',
                    'rubric_classification' => ' Body moving together, fluently and in good rythm'
                ],
                [
                    'skill_id'              => 3,
                    'rating'                => 2,
                    'key_word'              => 'Slow',
                    'rubric_classification' => 'Body not working together'
                ],
                [
                    'skill_id'              => 3,
                    'rating'                => 1.5,
                    'key_word'              => 'Stuck',
                    'rubric_classification' => 'Legs not moving quick to push in proper direction'
                ],
                [
                    'skill_id'              => 3,
                    'rating'                => 1,
                    'key_word'              => 'Weak',
                    'rubric_classification' => 'No edge selection or urgency'
                ],
                [
                    'skill_id'              => 4,
                    'rating'                => 5,
                    'key_word'              => 'Persistence',
                    'rubric_classification' => 'Engages all of the time, establishes space early and everywhere, does NOT lose battles or engagements '
                ],
                [
                    'skill_id'              => 4,
                    'rating'                => 4.5,
                    'key_word'              => 'Battles',
                    'rubric_classification' => 'Engages all of the times, establishes space early and sustains entire game'
                ],
                [
                    'skill_id'              => 4,
                    'rating'                => 4,
                    'key_word'              => 'Finishes',
                    'rubric_classification' => 'Engages when available, wins at least most battles'
                ],
                [
                    'skill_id'              => 4,
                    'rating'                => 3.5,
                    'key_word'              => 'On time',
                    'rubric_classification' => 'Engages often and stays with the battle/engagement'
                ],
                [
                    'skill_id'              => 4,
                    'rating'                => 3,
                    'key_word'              => 'Early or Late',
                    'rubric_classification' => 'Will engage, but is inconsistent'
                ],
                [
                    'skill_id'              => 4,
                    'rating'                => 2.5,
                    'key_word'              => 'Tentative',
                    'rubric_classification' => 'Will enngage time to time, but only with support'
                ],
                [
                    'skill_id'              => 4,
                    'rating'                => 2,
                    'key_word'              => 'Turns away',
                    'rubric_classification' => 'Seems to engame to from time to time, but with low interest level'
                ],
                [
                    'skill_id'              => 4,
                    'rating'                => 1.5,
                    'key_word'              => 'Late',
                    'rubric_classification' => 'Shys away from contact'
                ],
                [
                    'skill_id'              => 4,
                    'rating'                => 1,
                    'key_word'              => 'Quits',
                    'rubric_classification' => 'Player does not engage'
                ],
                [
                    'skill_id'              => 5,
                    'rating'                => 5,
                    'key_word'              => 'Dominant',
                    'rubric_classification' => 'Never loses lineup, has an influence at and away from puck always anticipating'
                ],
                [
                    'skill_id'              => 5,
                    'rating'                => 4.5,
                    'key_word'              => 'Aggressive',
                    'rubric_classification' => 'Establishes lineup early, influences early, Closes very fast'
                ],
                [
                    'skill_id'              => 5,
                    'rating'                => 4,
                    'key_word'              => 'Determined',
                    'rubric_classification' => 'Establishes lineup and is able to influence, contain, and close puck carrier'
                ],
                [
                    'skill_id'              => 5,
                    'rating'                => 3.5,
                    'key_word'              => 'Stays Focused',
                    'rubric_classification' => 'Establishes lineup and is able to influence and contain'
                ],
                [
                    'skill_id'              => 5,
                    'rating'                => 3,
                    'key_word'              => 'Engaged',
                    'rubric_classification' => 'Uses lineup, and tries to influence'
                ],
                [
                    'skill_id'              => 5,
                    'rating'                => 2.5,
                    'key_word'              => 'Allowing',
                    'rubric_classification' => 'Uses lineup but lacks influence'
                ],
                [
                    'skill_id'              => 5,
                    'rating'                => 2,
                    'key_word'              => 'Unconfident',
                    'rubric_classification' => 'Uses two elements of their lineup'
                ],
                [
                    'skill_id'              => 5,
                    'rating'                => 1.5,
                    'key_word'              => 'Weak',
                    'rubric_classification' => 'Uses one element of their* lineup'
                ],
                [
                    'skill_id'              => 5,
                    'rating'                => 1,
                    'key_word'              => 'Quit',
                    'rubric_classification' => 'Has little to no technique, or is reckless'
                ],
                [
                    'skill_id'              => 6,
                    'rating'                => 5,
                    'key_word'              => 'Unrelenting',
                    'rubric_classification' => 'Purely relentless, always ahead of play, communicates unconditionally and is overwhelming to play against '
                ],
                [
                    'skill_id'              => 6,
                    'rating'                => 4.5,
                    'key_word'              => 'Driven',
                    'rubric_classification' => 'Shifts always short (40 seconds or under) communicates, feet always moving, always around the puck. On time or first to all assigned spots'
                ],
                [
                    'skill_id'              => 6,
                    'rating'                => 4,
                    'key_word'              => 'Consistent',
                    'rubric_classification' => 'Communicates and consistently involved, on time with backcheck, forward check and will consistently be F1'
                ],
                [
                    'skill_id'              => 6,
                    'rating'                => 3.5,
                    'key_word'              => 'Focused',
                    'rubric_classification' => 'Is on time to puck races as well as on backcheck, and forecheck'
                ],
                [
                    'skill_id'              => 6,
                    'rating'                => 3,
                    'key_word'              => 'Some compromise',
                    'rubric_classification' => 'Will backcheck and forecheck with support but has some inconsistency'
                ],
                [
                    'skill_id'              => 6,
                    'rating'                => 2.5,
                    'key_word'              => 'Inconsistent',
                    'rubric_classification' => 'Moves feet & gets around, but with little reason or purpose'
                ],
                [
                    'skill_id'              => 6,
                    'rating'                => 2,
                    'key_word'              => 'Concession',
                    'rubric_classification' => 'Little purpose, little involvement'
                ],
                [
                    'skill_id'              => 6,
                    'rating'                => 1.5,
                    'key_word'              => 'Unengaged',
                    'rubric_classification' => 'Moves around, no purpose'
                ],
                [
                    'skill_id'              => 6,
                    'rating'                => 1,
                    'key_word'              => 'Avoid',
                    'rubric_classification' => 'No existence'
                ],
                [
                    'skill_id'              => 7,
                    'rating'                => 5,
                    'key_word'              => 'Radical',
                    'rubric_classification' => 'Ahead of the situation, reads all options and executes'
                ],
                [
                    'skill_id'              => 7,
                    'rating'                => 4.5,
                    'key_word'              => 'Lofty',
                    'rubric_classification' => 'Situationally aware of all players'
                ],
                [
                    'skill_id'              => 7,
                    'rating'                => 4,
                    'key_word'              => 'Imaginary',
                    'rubric_classification' => 'Reads the situation and executes the best option'
                ],
                [
                    'skill_id'              => 7,
                    'rating'                => 3.5,
                    'key_word'              => 'Idealistic',
                    'rubric_classification' => 'Interprets the situation and controls outcome'
                ],
                [
                    'skill_id'              => 7,
                    'rating'                => 3,
                    'key_word'              => 'Medium',
                    'rubric_classification' => 'Creates space and executes simple play'
                ],
                [
                    'skill_id'              => 7,
                    'rating'                => 2.5,
                    'key_word'              => 'Dreaming',
                    'rubric_classification' => 'Forces high risk option'
                ],
                [
                    'skill_id'              => 7,
                    'rating'                => 2,
                    'key_word'              => 'Inconsistent',
                    'rubric_classification' => 'Little prior observation of all options'
                ],
                [
                    'skill_id'              => 7,
                    'rating'                => 1.5,
                    'key_word'              => 'Impractical',
                    'rubric_classification' => 'Head down and panic outcome'
                ],
                [
                    'skill_id'              => 7,
                    'rating'                => 1,
                    'key_word'              => 'Unrealistic',
                    'rubric_classification' => 'Little situation awareness'
                ],
                [
                    'skill_id'              => 8,
                    'rating'                => 5,
                    'key_word'              => 'Exact',
                    'rubric_classification' => 'Perfect spacing and read'
                ],
                [
                    'skill_id'              => 8,
                    'rating'                => 4.5,
                    'key_word'              => 'Harmonize',
                    'rubric_classification' => 'Great support and proper spacing'
                ],
                [
                    'skill_id'              => 8,
                    'rating'                => 4,
                    'key_word'              => 'Appropriate',
                    'rubric_classification' => 'In the proper space and recovers gaps with skills'
                ],
                [
                    'skill_id'              => 8,
                    'rating'                => 3.5,
                    'key_word'              => 'Fitting',
                    'rubric_classification' => 'Majority of time in proper space and quick recover on error to proper area'
                ],
                [
                    'skill_id'              => 8,
                    'rating'                => 3,
                    'key_word'              => 'Average',
                    'rubric_classification' => 'Normal support and location. Misses on occasion but recovers back through the middle'
                ],
                [
                    'skill_id'              => 8,
                    'rating'                => 2.5,
                    'key_word'              => 'Inconsistent',
                    'rubric_classification' => 'Gets caught off guard, but recovers majority of the time through the middle'
                ],
                [
                    'skill_id'              => 8,
                    'rating'                => 2,
                    'key_word'              => 'Fine',
                    'rubric_classification' => 'Gives up the middle with an outside to inside attack'
                ],
                [
                    'skill_id'              => 8,
                    'rating'                => 1.5,
                    'key_word'              => 'Meant well',
                    'rubric_classification' => 'Significant gaps between the opponents and little space awareness'
                ],
                [
                    'skill_id'              => 8,
                    'rating'                => 1,
                    'key_word'              => 'Lost',
                    'rubric_classification' => 'Little situation and ice positioning'
                ],
                [
                    'skill_id'              => 9,
                    'rating'                => 5,
                    'key_word'              => 'Precisive',
                    'rubric_classification' => 'Perfect selection of options and perfect play'
                ],
                [
                    'skill_id'              => 9,
                    'rating'                => 4.5,
                    'key_word'              => 'Exactness',
                    'rubric_classification' => 'Creates space and time and makes heads-up follow through'
                ],
                [
                    'skill_id'              => 9,
                    'rating'                => 4,
                    'key_word'              => 'Sureness',
                    'rubric_classification' => 'Choice of multiple options with good results'
                ],
                [
                    'skill_id'              => 9,
                    'rating'                => 3.5,
                    'key_word'              => 'Attention',
                    'rubric_classification' => 'Finds open ice and creates opportunities'
                ],
                [
                    'skill_id'              => 9,
                    'rating'                => 3,
                    'key_word'              => 'Average',
                    'rubric_classification' => 'Quick to follow through with available options'
                ],
                [
                    'skill_id'              => 9,
                    'rating'                => 2.5,
                    'key_word'              => 'Carelessness',
                    'rubric_classification' => 'Little action towards creating time and space to give minimal options of any execution'
                ],
                [
                    'skill_id'              => 9,
                    'rating'                => 2,
                    'key_word'              => 'Inaccuracy',
                    'rubric_classification' => 'Wrong selection of options majority of time with poor outcomes'
                ],
                [
                    'skill_id'              => 9,
                    'rating'                => 1.5,
                    'key_word'              => 'Imprecision',
                    'rubric_classification' => 'Simplistic plays with little creativity'
                ],
                [
                    'skill_id'              => 9,
                    'rating'                => 1,
                    'key_word'              => 'Neglect',
                    'rubric_classification' => 'Consistent turnovers'
                ],
                [
                    'skill_id'              => 10,
                    'rating'                => 5,
                    'key_word'              => 'Effortless',
                    'rubric_classification' => 'Smooth,very little sound against the ice'
                ],
                [
                    'skill_id'              => 10,
                    'rating'                => 4.5,
                    'key_word'              => 'Magnetic',
                    'rubric_classification' => 'High ability. Puck stays on stick in all situations'
                ],
                [
                    'skill_id'              => 10,
                    'rating'                => 4,
                    'key_word'              => 'Precisive',
                    'rubric_classification' => 'Elite player with the puck, stands out from other players'
                ],
                [
                    'skill_id'              => 10,
                    'rating'                => 3.5,
                    'key_word'              => 'Effective',
                    'rubric_classification' => 'Makes all the standard plays,efficient with puck'
                ],
                [
                    'skill_id'              => 10,
                    'rating'                => 3,
                    'key_word'              => 'Complimentary',
                    'rubric_classification' => 'Average handling, does not stand out from other player'
                ],
                [
                    'skill_id'              => 10,
                    'rating'                => 2.5,
                    'key_word'              => 'Inconsistent',
                    'rubric_classification' => 'Struggles with abilities in many situations '
                ],
                [
                    'skill_id'              => 10,
                    'rating'                => 2,
                    'key_word'              => 'Jumbled',
                    'rubric_classification' => 'Slow decisions which slows abilities with skating'
                ],
                [
                    'skill_id'              => 10,
                    'rating'                => 1.5,
                    'key_word'              => 'Delayed',
                    'rubric_classification' => 'Can not separate hands from feet, must slow down to handle puck'
                ],
                [
                    'skill_id'              => 10,
                    'rating'                => 1,
                    'key_word'              => 'Detrimental',
                    'rubric_classification' => 'Loud chopping motion against ice,not efficient '
                ],
                [
                    'skill_id'              => 11,
                    'rating'                => 5,
                    'key_word'              => 'Precise',
                    'rubric_classification' => 'Gifted, head up at all times, sees all situations before they happen'
                ],
                [
                    'skill_id'              => 11,
                    'rating'                => 4.5,
                    'key_word'              => 'Accurate',
                    'rubric_classification' => 'Makes majority of all difficult passes, both forehand and backhand'
                ],
                [
                    'skill_id'              => 11,
                    'rating'                => 4,
                    'key_word'              => 'Strong',
                    'rubric_classification' => 'Makes all required passed, reads ice very well'
                ],
                [
                    'skill_id'              => 11,
                    'rating'                => 3.5,
                    'key_word'              => 'Push/point',
                    'rubric_classification' => 'Mostly receives and executes passes'
                ],
                [
                    'skill_id'              => 11,
                    'rating'                => 3,
                    'key_word'              => 'Smooth/Soft',
                    'rubric_classification' => 'Receives passes well, minimal sound when puck hits stick'
                ],
                [
                    'skill_id'              => 11,
                    'rating'                => 2.5,
                    'key_word'              => 'Inconsistent ',
                    'rubric_classification' => 'Average passer that struggles with decisions of who to pass to under pressure '
                ],
                [
                    'skill_id'              => 11,
                    'rating'                => 2,
                    'key_word'              => 'Head down',
                    'rubric_classification' => 'Does not see ice well at all, must look at puck on own stick to be comfortable making passes'
                ],
                [
                    'skill_id'              => 11,
                    'rating'                => 1.5,
                    'key_word'              => 'Weak',
                    'rubric_classification' => 'Lacks physical strength or pushes pucks to make passes causing a slow moving pass'
                ],
                [
                    'skill_id'              => 11,
                    'rating'                => 1,
                    'key_word'              => 'Selfish',
                    'rubric_classification' => 'Struggles in all game situations, pressure causes inability to perform simple passes'
                ],
                [
                    'skill_id'              => 12,
                    'rating'                => 5,
                    'key_word'              => 'Precise',
                    'rubric_classification' => 'Stick always in proper position to execute any shot, head always looking at target before receiving pass for shot'
                ],
                [
                    'skill_id'              => 12,
                    'rating'                => 4.5,
                    'key_word'              => 'Accurate',
                    'rubric_classification' => 'Hgh percentage of hitting target, can put puck in any window opening'
                ],
                [
                    'skill_id'              => 12,
                    'rating'                => 4,
                    'key_word'              => 'Deliberate',
                    'rubric_classification' => 'Hits net in most situations, know where to position body in scoring areas very well'
                ],
                [
                    'skill_id'              => 12,
                    'rating'                => 3.5,
                    'key_word'              => 'Hard',
                    'rubric_classification' => 'Extremely powerful shot, however not accurate all the time, misses net often'
                ],
                [
                    'skill_id'              => 12,
                    'rating'                => 3,
                    'key_word'              => 'Varied',
                    'rubric_classification' => 'Not a quick enough release, shots blocked due to lack of quickness'
                ],
                [
                    'skill_id'              => 12,
                    'rating'                => 2.5,
                    'key_word'              => 'Timing',
                    'rubric_classification' => 'Missing shot opportunities due to incorrect stick placement when receiving pass from teammates in shooting areas'
                ],
                [
                    'skill_id'              => 12,
                    'rating'                => 2,
                    'key_word'              => 'Soft',
                    'rubric_classification' => 'Lacks physical strength, or positions picks on wrong area of stick blade to generate hard shot'
                ],
                [
                    'skill_id'              => 12,
                    'rating'                => 1.5,
                    'key_word'              => 'Inaccurate',
                    'rubric_classification' => 'Average shot but very detrimental due to lack of hitting the net, mainly due to having head down'
                ],
                [
                    'skill_id'              => 12,
                    'rating'                => 1,
                    'key_word'              => 'Weak',
                    'rubric_classification' => 'No shot accuracy or power'
                ],
                [
                    'skill_id'              => 13,
                    'rating'                => 5,
                    'key_word'              => 'Precise Edges',
                    'rubric_classification' => 'Precise edge usage, smooth mobility, explosive power that drops hard and rises with spring, post save recoveries are immediate, balance and control is fluid, no delay in reaction'
                ],
                [
                    'skill_id'              => 13,
                    'rating'                => 4.5,
                    'key_word'              => 'Excellent Edges',
                    'rubric_classification' => 'Excellent  edge control with fluid mobility . Powerful drops and rises for a fluid control and balance and little delay in reaction'
                ],
                [
                    'skill_id'              => 13,
                    'rating'                => 4,
                    'key_word'              => 'Proper Edges',
                    'rubric_classification' => 'Proper edge choice with smooth mobility. Powerful drops and rises for save recoveries, creating a fluid balanced movement with minor delays'
                ],
                [
                    'skill_id'              => 13,
                    'rating'                => 3.5,
                    'key_word'              => 'Acceptable Edges',
                    'rubric_classification' => 'Acceptable edge control while moving with ease for drops and rises for adequate save recoveries. Fairly balanced with some delay in reaction '
                ],
                [
                    'skill_id'              => 13,
                    'rating'                => 3,
                    'key_word'              => 'Edges 50%',
                    'rubric_classification' => 'Edge selection proper 50% of the time creating choppy mobility and delayed drops and rises for save recoveries. Balance and delay reaction are weakened'
                ],
                [
                    'skill_id'              => 13,
                    'rating'                => 2.5,
                    'key_word'              => 'Inadequate Edges',
                    'rubric_classification' => 'Inadequate edge control choices creating balance issues and slow rises and drops with slower than average reaction time'
                ],
                [
                    'skill_id'              => 13,
                    'rating'                => 2,
                    'key_word'              => 'Inappropriate Edges',
                    'rubric_classification' => 'Inappropriate edge selection creating slow drops and rises with 20% of available mobility. Slow reaction and weak balance'
                ],
                [
                    'skill_id'              => 13,
                    'rating'                => 1.5,
                    'key_word'              => 'Improper Edges',
                    'rubric_classification' => 'Improper edges, control and balance which limits drops, rises and reaction times'
                ],
                [
                    'skill_id'              => 13,
                    'rating'                => 1,
                    'key_word'              => 'Edge Unawareness',
                    'rubric_classification' => 'Unaware of edges which disables mobility in drops, rises, post recovery, control and efficiency '
                ],
                [
                    'skill_id'              => 14,
                    'rating'                => 5,
                    'key_word'              => 'Precise tracking',
                    'rubric_classification' => 'Swift and precise movement, do to excellent tracking. Set square with ideal depth and angles'
                ],
                [
                    'skill_id'              => 14,
                    'rating'                => 4.5,
                    'key_word'              => 'Proper tracking',
                    'rubric_classification' => 'Properly executed movements do to great tracking. Depth control, remaining set and proper depth for excellent positioning'
                ],
                [
                    'skill_id'              => 14,
                    'rating'                => 4,
                    'key_word'              => 'Effective tracking',
                    'rubric_classification' => 'Deliberate movement from effective tracking. Set square with proper angles and depth'
                ],
                [
                    'skill_id'              => 14,
                    'rating'                => 3.5,
                    'key_word'              => 'Adequate tracking',
                    'rubric_classification' => 'Adequate tracking for movements  allowing square setup and decent depth while maintaining set'
                ],
                [
                    'skill_id'              => 14,
                    'rating'                => 3,
                    'key_word'              => 'Average tracking',
                    'rubric_classification' => 'Average movement through mostly effective tracking. 50% of the time angles and depth are proper'
                ],
                [
                    'skill_id'              => 14,
                    'rating'                => 2.5,
                    'key_word'              => 'B-Average tracking',
                    'rubric_classification' => 'Below average tracking skills hindering movements, depth and angles'
                ],
                [
                    'skill_id'              => 14,
                    'rating'                => 2,
                    'key_word'              => 'Weak tracking',
                    'rubric_classification' => 'Weak tracking movements, angles and depth are off most of the time'
                ],
                [
                    'skill_id'              => 14,
                    'rating'                => 1.5,
                    'key_word'              => 'Inadequate tracking',
                    'rubric_classification' => 'Inadequate tracking ability causing movement, depth and angle issues '
                ],
                [
                    'skill_id'              => 14,
                    'rating'                => 1,
                    'key_word'              => 'Ineffective tracking',
                    'rubric_classification' => 'In affective movement, due to improper tracking. Creating bad angles and wrong depth in regards to net and shooter'
                ],
                [
                    'skill_id'              => 15,
                    'rating'                => 5,
                    'key_word'              => '',
                    'rubric_classification' => ''
                ],
                [
                    'skill_id'              => 15,
                    'rating'                => 4.5,
                    'key_word'              => '',
                    'rubric_classification' => ''
                ],
                [
                    'skill_id'              => 15,
                    'rating'                => 4,
                    'key_word'              => '',
                    'rubric_classification' => ''
                ],
                [
                    'skill_id'              => 15,
                    'rating'                => 3.5,
                    'key_word'              => '',
                    'rubric_classification' => ''
                ],
                [
                    'skill_id'              => 15,
                    'rating'                => 3,
                    'key_word'              => '',
                    'rubric_classification' => ''
                ],
                [
                    'skill_id'              => 15,
                    'rating'                => 2.5,
                    'key_word'              => '',
                    'rubric_classification' => ''
                ],
                [
                    'skill_id'              => 15,
                    'rating'                => 2,
                    'key_word'              => '',
                    'rubric_classification' => ''
                ],
                [
                    'skill_id'              => 15,
                    'rating'                => 1.5,
                    'key_word'              => '',
                    'rubric_classification' => ''
                ],
                [
                    'skill_id'              => 15,
                    'rating'                => 1,
                    'key_word'              => '',
                    'rubric_classification' => ''
                ],
                [
                    'skill_id'              => 16,
                    'rating'                => 5,
                    'key_word'              => 'Phenominal Absorbing',
                    'rubric_classification' => 'Absorbing body that cradles the puck. Swift catcher and blocker that deflects the puck to the pads. Perfect use of stick to gather in pucks'
                ],
                [
                    'skill_id'              => 16,
                    'rating'                => 4.5,
                    'key_word'              => 'Excellent Absorbing',
                    'rubric_classification' => 'Sponge like ability on puck reception with a fast glove and precise block to deflect pucks to pads.Good stick to gather pucks in'
                ],
                [
                    'skill_id'              => 16,
                    'rating'                => 4,
                    'key_word'              => 'Good Absorbing',
                    'rubric_classification' => 'Soft body cradling the puck, quick catcher and blocker controlling the puck, appropriate use of stick for loose pucks'
                ],
                [
                    'skill_id'              => 16,
                    'rating'                => 3.5,
                    'key_word'              => 'Variable Absorbing',
                    'rubric_classification' => 'Cradling body with ability to use effective glove and blocker to control missed played shots. Ability to gather pucks with stick'
                ],
                [
                    'skill_id'              => 16,
                    'rating'                => 3,
                    'key_word'              => 'Average Absorbing',
                    'rubric_classification' => 'Average use of body to absorb and cradle the puck.50% efficient use of glove and blocker. Occasional use of stick to gather pucks'
                ],
                [
                    'skill_id'              => 16,
                    'rating'                => 2.5,
                    'key_word'              => 'Occasional Absorbing',
                    'rubric_classification' => 'Occasional ability to absorb, catch or blocker pucks through proper sources. Little use of stick to gather pucks'
                ],
                [
                    'skill_id'              => 16,
                    'rating'                => 2,
                    'key_word'              => 'Inconsistent Absorbing ',
                    'rubric_classification' => 'Firm body creating little cradling of the puck, inconsistency catcher and rare deflection of pucks to the pads. Rare use of stick to gather pucks'
                ],
                [
                    'skill_id'              => 16,
                    'rating'                => 1.5,
                    'key_word'              => 'Sloppy Absorbing ',
                    'rubric_classification' => 'Stiff body for cradling pucks, with sloppy glove and blocker control and no obvious use of stick to assist in gathering pucks'
                ],
                [
                    'skill_id'              => 16,
                    'rating'                => 1,
                    'key_word'              => 'Improper Absorbing ',
                    'rubric_classification' => 'Rigid body creating loose pucks. Glove and blocker placement improper for save selection. Stick use if at all for gathering pucks'
                ],
                [
                    'skill_id'              => 17,
                    'rating'                => 5,
                    'key_word'              => 'Extraordinary Skills',
                    'rubric_classification' => '100% proper placement and playing the puck, ideal handling ability, intentional placement of the puck of the entire game '
                ],
                [
                    'skill_id'              => 17,
                    'rating'                => 4.5,
                    'key_word'              => 'Excellent Skills',
                    'rubric_classification' => 'Shows confidence with deliberate puck handling ability and sees the proper options that are available '
                ],
                [
                    'skill_id'              => 17,
                    'rating'                => 4,
                    'key_word'              => 'Accelerated Skills',
                    'rubric_classification' => 'Proper playing and placement of pucks, 80% handling ability, makes good decisions on clearing pucks'
                ],
                [
                    'skill_id'              => 17,
                    'rating'                => 3.5,
                    'key_word'              => 'Good Skills',
                    'rubric_classification' => 'Good puck playing and handling ability with a high probability of outcome cause of proper discernment '
                ],
                [
                    'skill_id'              => 17,
                    'rating'                => 3,
                    'key_word'              => 'Average Skills',
                    'rubric_classification' => 'Average execution of puck handling, options that are mildly effective cause lacking urgency and vision '
                ],
                [
                    'skill_id'              => 17,
                    'rating'                => 2.5,
                    'key_word'              => 'Minor Skills',
                    'rubric_classification' => 'Minor execution of pucking handling showing a lack of confidence and urgency '
                ],
                [
                    'skill_id'              => 17,
                    'rating'                => 2,
                    'key_word'              => 'Weak Skills',
                    'rubric_classification' => 'Rarely playing the puck both inside and outside of the crease, leaves little options for transition play'
                ],
                [
                    'skill_id'              => 17,
                    'rating'                => 1.5,
                    'key_word'              => 'No Skills',
                    'rubric_classification' => 'No obvious skill in puck handle creating few options to handle the puck'
                ],
                [
                    'skill_id'              => 17,
                    'rating'                => 1,
                    'key_word'              => 'No Interaction',
                    'rubric_classification' => 'Not leaving the net or handling the puck leaving little options'
                ],
                [
                    'skill_id'              => 18,
                    'rating'                => 5,
                    'key_word'              => 'Exceptional Visability',
                    'rubric_classification' => 'Uncanny ability to maintain visual contact around screens to find pucks while setting perfect depth and holds ideal stance'
                ],
                [
                    'skill_id'              => 18,
                    'rating'                => 4.5,
                    'key_word'              => 'Excellent Visability',
                    'rubric_classification' => 'Excellent ability to hold visual contact around screens, while finding pucks with proper depth maintaining set stance'
                ],
                [
                    'skill_id'              => 18,
                    'rating'                => 4,
                    'key_word'              => 'Visability 80%',
                    'rubric_classification' => '80% of visual contact around screens with proper depth and holds stance'
                ],
                [
                    'skill_id'              => 18,
                    'rating'                => 3.5,
                    'key_word'              => 'Good Visability',
                    'rubric_classification' => 'Good visual contact around screens with efficient stance and depth control'
                ],
                [
                    'skill_id'              => 18,
                    'rating'                => 3,
                    'key_word'              => 'Average Visability',
                    'rubric_classification' => 'Average visual contact around screens. Proper depth and stance proper 50% of the time'
                ],
                [
                    'skill_id'              => 18,
                    'rating'                => 2.5,
                    'key_word'              => 'B-Average Visability',
                    'rubric_classification' => 'Difficulty find pucks around screens creating poor depth and stance'
                ],
                [
                    'skill_id'              => 18,
                    'rating'                => 2,
                    'key_word'              => 'Weak Visability',
                    'rubric_classification' => 'Weak visual contact around screen and not maintaining stance. Proper depth inconsistent'
                ],
                [
                    'skill_id'              => 18,
                    'rating'                => 1.5,
                    'key_word'              => 'Guessed Visablity',
                    'rubric_classification' => 'Guessing where pucks will be around screens leaving little depth or stance control'
                ],
                [
                    'skill_id'              => 18,
                    'rating'                => 1,
                    'key_word'              => 'Little Visability',
                    'rubric_classification' => 'Little visual contact around screens and inadequate depth and stance'
                ],
                [
                    'skill_id'              => 19,
                    'rating'                => 5,
                    'key_word'              => '',
                    'rubric_classification' => 'Relentless compete on broken plays with proper structure and battles hard for pucks'
                ],
                [
                    'skill_id'              => 19,
                    'rating'                => 4.5,
                    'key_word'              => 'Relentless ',
                    'rubric_classification' => 'Tireless compete for pucks while maintaining proper structure on brokers plays, and excellent reaction in response '
                ],
                [
                    'skill_id'              => 19,
                    'rating'                => 4,
                    'key_word'              => 'Tireless ',
                    'rubric_classification' => 'Anticipation of puck trajectory, ability to read shooter and find pucks in scramble 80% of the time,'
                ],
                [
                    'skill_id'              => 19,
                    'rating'                => 3.5,
                    'key_word'              => 'Competition 80%',
                    'rubric_classification' => 'Good ability to react with proper structure in puck battles and broken plays'
                ],
                [
                    'skill_id'              => 19,
                    'rating'                => 3,
                    'key_word'              => 'Good Competition',
                    'rubric_classification' => 'Average skill in anticipation of puck trajectory, 50% success in reading shooter, find puck half of the time in scramble'
                ],
                [
                    'skill_id'              => 19,
                    'rating'                => 2.5,
                    'key_word'              => 'Average Competition',
                    'rubric_classification' => 'Occasional anticipation of puck trajectory, minimal success with reading shooter, limited success finding puck in scramble'
                ],
                [
                    'skill_id'              => 19,
                    'rating'                => 2,
                    'key_word'              => 'Occasional Competition',
                    'rubric_classification' => 'Subpar anticipation of puck trajectory, little success of reading shooter, loses must pucks in scramble'
                ],
                [
                    'skill_id'              => 19,
                    'rating'                => 1.5,
                    'key_word'              => 'Subpar Competion',
                    'rubric_classification' => 'Weak ability to read puck trajectory and  losing the majority of scrambles'
                ],
                [
                    'skill_id'              => 19,
                    'rating'                => 1,
                    'key_word'              => 'Weak Competition',
                    'rubric_classification' => 'Total disregard of future possible plays, misreads all shooters shots, puck is always lost in scramble'
                ],
                [
                    'skill_id'              => 20,
                    'rating'                => 5,
                    'key_word'              => 'No Competition',
                    'rubric_classification' => 'Ideal deliberate use of the body through flexibility, precise balance and use of reactions(reflexes)'
                ],
                [
                    'skill_id'              => 20,
                    'rating'                => 4.5,
                    'key_word'              => 'Exceptional Athleticism',
                    'rubric_classification' => 'Precise use of body through flexibility, balance and use of reflexes'
                ],
                [
                    'skill_id'              => 20,
                    'rating'                => 4,
                    'key_word'              => 'Excellent Athleticism',
                    'rubric_classification' => 'Deliberate use of the body through controlled balance and flexibility while using reflexes to support'
                ],
                [
                    'skill_id'              => 20,
                    'rating'                => 3.5,
                    'key_word'              => 'Good Athleticism',
                    'rubric_classification' => 'Excellent use of body with good flexibility, controlled balance and reflexes'
                ],
                [
                    'skill_id'              => 20,
                    'rating'                => 3,
                    'key_word'              => 'Adequate Athleticism',
                    'rubric_classification' => 'Average balance control, flexibility and reaction time with reflexes'
                ],
                [
                    'skill_id'              => 20,
                    'rating'                => 2.5,
                    'key_word'              => 'Average Athleticism',
                    'rubric_classification' => 'Balance, flexibility and reaction time (using reflexes) are weak'
                ],
                [
                    'skill_id'              => 20,
                    'rating'                => 2,
                    'key_word'              => 'B-Average Athleticism',
                    'rubric_classification' => 'Little balance control, use of reflexes and flexibility are inadequate '
                ],
                [
                    'skill_id'              => 20,
                    'rating'                => 1.5,
                    'key_word'              => 'Week Athleticism',
                    'rubric_classification' => 'Stumbling balance and flexibility with no obvious reflexes'
                ],
                [
                    'skill_id'              => 20,
                    'rating'                => 1,
                    'key_word'              => 'Stumbling Athleticism',
                    'rubric_classification' => 'Poor balance, not flexible and little reflex'
                ],
                [
                    'skill_id'              => 21,
                    'rating'                => 5,
                    'key_word'              => 'No Athleticism',
                    'rubric_classification' => 'Perfect stance through all structural components of the body and physical presence to control access and close on pucks'
                ],
                [
                    'skill_id'              => 21,
                    'rating'                => 4.5,
                    'key_word'              => 'Perfect Stance',
                    'rubric_classification' => 'Proper stance through all structural components of the body. Physical presence to access and close on pucks are on point'
                ],
                [
                    'skill_id'              => 21,
                    'rating'                => 4,
                    'key_word'              => 'Exceptional Stance',
                    'rubric_classification' => 'Excellent stance through structural components. The physical presence with control to access and close on pucks is on point'
                ],
                [
                    'skill_id'              => 21,
                    'rating'                => 3.5,
                    'key_word'              => 'Excellent Stance',
                    'rubric_classification' => 'Majority of the stance through structural components are consistent . The physical presence with control to access and close on pucks is consistent'
                ],
                [
                    'skill_id'              => 21,
                    'rating'                => 3,
                    'key_word'              => 'Above Average',
                    'rubric_classification' => 'Average stance through structural components and physical presence with control to access and close on pucks'
                ],
                [
                    'skill_id'              => 21,
                    'rating'                => 2.5,
                    'key_word'              => 'Average Stance',
                    'rubric_classification' => 'Stance and structural  components are inadequate. Control to access and close on pucks due to inconsistent play'
                ],
                [
                    'skill_id'              => 21,
                    'rating'                => 2,
                    'key_word'              => 'B-Average Stance',
                    'rubric_classification' => 'Weak stance through all structural components and physical presence with control to access and close on pucks'
                ],
                [
                    'skill_id'              => 21,
                    'rating'                => 1.5,
                    'key_word'              => 'Week Stance',
                    'rubric_classification' => 'Improper structure through majority of components and physical presence with control to access and close on pucks is minimal '
                ],
                [
                    'skill_id'              => 21,
                    'rating'                => 1,
                    'key_word'              => 'Improper Stance',
                    'rubric_classification' => 'Sloppy stance through structural components and physical presence with control to access and close on pucks is none existent '
                ],
                [
                    'skill_id'              => 22,
                    'rating'                => 5,
                    'key_word'              => 'Sloppy Stance',
                    'rubric_classification' => 'Ideal situational awareness, with perfect depth adjustment and ready position early'
                ],
                [
                    'skill_id'              => 22,
                    'rating'                => 4.5,
                    'key_word'              => 'Idea  Awareness',
                    'rubric_classification' => 'Precise depth adjustments, ready early while identifying threats and complete situational awareness '
                ],
                [
                    'skill_id'              => 22,
                    'rating'                => 4,
                    'key_word'              => 'Exceptional Awareness',
                    'rubric_classification' => 'High ice awareness to identify threats, making proper depth transitions and ready early'
                ],
                [
                    'skill_id'              => 22,
                    'rating'                => 3.5,
                    'key_word'              => 'Excellent  Awareness',
                    'rubric_classification' => 'Excellent ice awareness to identify threats while being ready early and proper depth transitions '
                ],
                [
                    'skill_id'              => 22,
                    'rating'                => 3,
                    'key_word'              => 'Good Awareness',
                    'rubric_classification' => 'Average situational awareness to identify threats while maintaining proper depth adjustments and being constantly ready early'
                ],
                [
                    'skill_id'              => 22,
                    'rating'                => 2.5,
                    'key_word'              => 'Average Awareness',
                    'rubric_classification' => 'Inadequate situational awareness to identify threats while maintaining proper depth and being ready early '
                ],
                [
                    'skill_id'              => 22,
                    'rating'                => 2,
                    'key_word'              => 'Inadequate Awareness',
                    'rubric_classification' => 'Weak situational ice awareness with depth overcorrection and rarely ready early'
                ],
                [
                    'skill_id'              => 22,
                    'rating'                => 1.5,
                    'key_word'              => 'Weak Awareness',
                    'rubric_classification' => 'Little ice awareness creating depth problems and not being ready early '
                ],
                [
                    'skill_id'              => 22,
                    'rating'                => 1,
                    'key_word'              => 'Little Awareness',
                    'rubric_classification' => 'No situational awareness and depth control. Occasional ready early'
                ],
                [
                    'skill_id'              => 23,
                    'rating'                => 5,
                    'key_word'              => 'No Awareness',
                    'rubric_classification' => 'Perfect body patience and holds feet position, precise tracking movement, and 100% accurate use of blocking pucks'
                ],
                [
                    'skill_id'              => 23,
                    'rating'                => 4.5,
                    'key_word'              => 'Perfect Patience',
                    'rubric_classification' => 'Patient and holds feet while tracking and accurate use of blocking pucks'
                ],
                [
                    'skill_id'              => 23,
                    'rating'                => 4,
                    'key_word'              => 'Exceptional Patience',
                    'rubric_classification' => 'Body is at patient and proper feet positioning, strong effective tracking and proper blocking of pucks'
                ],
                [
                    'skill_id'              => 23,
                    'rating'                => 3.5,
                    'key_word'              => 'Excellent Patience',
                    'rubric_classification' => 'Good patience and holding feet in position, while staying on pucks (tracking) and blocking of pucks'
                ],
                [
                    'skill_id'              => 23,
                    'rating'                => 3,
                    'key_word'              => 'Good Patience',
                    'rubric_classification' => 'Average body patience, tracking and blocking pucks is on point half of the game play'
                ],
                [
                    'skill_id'              => 23,
                    'rating'                => 2.5,
                    'key_word'              => 'Average Patience',
                    'rubric_classification' => 'Lacking patience and ability to track pucks creates excessive feet movements and improper blocking actions'
                ],
                [
                    'skill_id'              => 23,
                    'rating'                => 2,
                    'key_word'              => 'B-Average Patience',
                    'rubric_classification' => 'Weak body patience and active feet movement, hard time finding pucks and blocking appropriately '
                ],
                [
                    'skill_id'              => 23,
                    'rating'                => 1.5,
                    'key_word'              => 'Weak Patience',
                    'rubric_classification' => 'Patience and not holding feet in position are predominant throughout, tracking and blocking not effective '
                ],
                [
                    'skill_id'              => 23,
                    'rating'                => 1,
                    'key_word'              => 'lneffective Patience',
                    'rubric_classification' => 'Little body patience and significant feet movement, visually cannot find pucks which causes wrong blocking choices '
                ],
                [
                    'skill_id'              => 24,
                    'rating'                => 5,
                    'key_word'              => 'No Patience',
                    'rubric_classification' => 'Ideal ability to make key saves while performing under pressure displaying confidence in all areas'
                ],
                [
                    'skill_id'              => 24,
                    'rating'                => 4.5,
                    'key_word'              => 'Exceptional Saves',
                    'rubric_classification' => 'Shows confidence with an ability to perform under pressure and make key saves'
                ],
                [
                    'skill_id'              => 24,
                    'rating'                => 4,
                    'key_word'              => 'Excellent Saves',
                    'rubric_classification' => 'Majority of key saves made, strong performance under pressure, displays confidence in majority of areas'
                ],
                [
                    'skill_id'              => 24,
                    'rating'                => 3.5,
                    'key_word'              => 'Good Saves',
                    'rubric_classification' => 'Great ability to recover under pressure and to compete in making key safes. Areas of displaying confidence could be approved on'
                ],
                [
                    'skill_id'              => 24,
                    'rating'                => 3,
                    'key_word'              => 'Average Saves',
                    'rubric_classification' => 'Makes half of key saves needed, intimidation displayed under pressure half the time, inconsistent in confidence'
                ],
                [
                    'skill_id'              => 24,
                    'rating'                => 2.5,
                    'key_word'              => 'B- Average Saves',
                    'rubric_classification' => 'Displays a lack of confidence under pressure which allows for some key saves being missed'
                ],
                [
                    'skill_id'              => 24,
                    'rating'                => 2,
                    'key_word'              => 'Weak Saves',
                    'rubric_classification' => 'Makes some key saves, unable to compete under pressure, negative body language'
                ],
                [
                    'skill_id'              => 24,
                    'rating'                => 1.5,
                    'key_word'              => 'Ineffective Saves',
                    'rubric_classification' => 'Poor body language with little ability to compete under pressure or effort to make key saves'
                ],
                [
                    'skill_id'              => 24,
                    'rating'                => 1,
                    'key_word'              => 'Poor Saves',
                    'rubric_classification' => 'Ineffective at making any saves, falls apart under any pressure, models fear and intimidation'
                ],
                [
                    'skill_id'              => 25,
                    'rating'                => 5,
                    'key_word'              => 'No Saves',
                    'rubric_classification' => 'Body language on point at all times, with calm demeanor and, radically mentally engaged to take away options'
                ],
                [
                    'skill_id'              => 25,
                    'rating'                => 4.5,
                    'key_word'              => 'Perfect Alertness',
                    'rubric_classification' => 'Flawless body language, calm demeanor and mentally present to take appropriate action'
                ],
                [
                    'skill_id'              => 25,
                    'rating'                => 4,
                    'key_word'              => 'Excellent  Alertness',
                    'rubric_classification' => 'Alert most of the time, calm demeanor , concentration and body language is faultless'
                ],
                [
                    'skill_id'              => 25,
                    'rating'                => 3.5,
                    'key_word'              => 'Predominately Alert',
                    'rubric_classification' => 'Excellent concentration and body control with a calm demeanor'
                ],
                [
                    'skill_id'              => 25,
                    'rating'                => 3,
                    'key_word'              => 'Good Alertness',
                    'rubric_classification' => 'Alert half of playing time, concentration on and off and displays some body language issues'
                ],
                [
                    'skill_id'              => 25,
                    'rating'                => 2.5,
                    'key_word'              => 'Average Alertness',
                    'rubric_classification' => 'Lacking self control and concentration which creates an agitated demeanor '
                ],
                [
                    'skill_id'              => 25,
                    'rating'                => 2,
                    'key_word'              => 'B-Average Alertness',
                    'rubric_classification' => 'Weak ability to maintain self control, concentration weak and lacks self control'
                ],
                [
                    'skill_id'              => 25,
                    'rating'                => 1.5,
                    'key_word'              => 'Weak Alerness',
                    'rubric_classification' => 'Caught off guard a high percentage of time, concentration weak and lacks self control'
                ],
                [
                    'skill_id'              => 25,
                    'rating'                => 1,
                    'key_word'              => 'Ineffective Alertness',
                    'rubric_classification' => 'Completely inattentive, unaware of body language and mostly lack of focus'
                ]
            ]);
        }
    }
}
