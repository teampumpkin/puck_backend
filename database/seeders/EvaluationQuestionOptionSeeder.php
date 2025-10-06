<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationQuestionOption;

class EvaluationQuestionOptionSeeder extends Seeder
{
    public function run()
    {
        // Clear existing options
        EvaluationQuestionOption::truncate();

        // All questions + their options and ratings (using SHORT TITLES from QuestionSeeder)
        $optionsData = [

            // --- SKATING ---
            'Skating Mechanics' => [
                [null, 'Very straight up, no edge work, not stable', 1],
                [null, 'Laboring, not balanced, very disrupted in between movements', 1.5],
                [null, 'Choppy, over working, short stride', 2],
                [null, 'Not getting under body with legs, not using edges properly', 2.5],
                [null, 'Moving body in other directions than is headed, bobbing or straight legged', 3],
                [null, 'Not changing stance with direction changes or fatigue', 3.5],
                [null, 'Fluid gear changes, moving consistently smooth on transitions', 4],
                [null, 'Floating body, legs and arms moving separately but in unison with body.', 4.5],
                [null, 'Quiet head, fluid body moving forward, perfect edge selection', 5],
            ],
            'Skating Control' => [
                [null, 'Very long process to have movement or urgency or know how to get moving.', 1],
                [null, 'No control over edges or body', 1.5],
                [null, 'No real rhythm of stride or push, gravity pull seems stronger than normal', 2],
                [null, 'Parts of body move in many different directions, trying to get momentum with jerky movements', 2.5],
                [null, 'Continuous progression without starting from beginning', 3],
                [null, 'Low, wide, right edges and momentum shifts', 3.5],
                [null, 'Loads power efficiently, and body works in unison', 4],
                [null, 'Body stays aligned to centre of gravity, does not shift too far from either way', 4.5],
                [null, 'Very small, smooth movements, everything done low and in control', 5],
            ],
            'Skating Speed' => [
                [null, 'No edge selection or urgency', 1],
                [null, 'Legs not moving quick to push in proper direction', 1.5],
                [null, 'Body not working together', 2],
                [null, 'Body moving together, fluently and in good rhythm', 2.5],
                [null, 'Speed adjustments, proper timing, not drifting', 3],
                [null, 'Always moving and using quick bursts', 3.5],
                [null, 'Power shifts quick, smooth and in proper directions', 4],
                [null, 'Low, powerful and uniformed fundamentals', 4.5],
                [null, 'Deliberate, urgent, desperate, controlled movements', 5],
            ],

            // --- COMPETE ---
            'Compete Engagement' => [
                [null, 'Player does not engage', 1],
                [null, 'Shys away from contact', 1.5],
                [null, 'Seems to engage from time to time, but with low interest level', 2],
                [null, 'Will engage from time to time, but only with support', 2.5],
                [null, 'Will engage, but is inconsistent', 3],
                [null, 'Engages often and stays with the battle/engagement', 3.5],
                [null, 'Engages when available, wins at least most battles', 4],
                [null, 'Engages all the time, establishes space early and sustains entire game.', 4.5],
                [null, 'Engages all the time, establishes space early and everywhere, does NOT lose battles or engagements', 5],
            ],
            'Compete Technique' => [
                [null, 'Has little to no technique, or is reckless', 1],
                [null, 'Uses one element of their lineup', 1.5],
                [null, 'Uses two elements of their lineup', 2],
                [null, 'Uses lineup but lacks influence', 2.5],
                [null, 'Uses lineup, and tries to influence', 3],
                [null, 'Establishes lineup and is able to influence and contain', 3.5],
                [null, 'Establishes lineup and is able to influence, contain, and close puck carrier', 4],
                [null, 'Establishes lineup early, influences early, closes very fast.', 4.5],
                [null, 'Never loses lineup, has an influence at and away from puck, always anticipating', 5],
            ],
            'Compete Persistence' => [
                [null, 'No existence', 1],
                [null, 'Moves around, no purpose', 1.5],
                [null, 'Little purpose, little involvement', 2],
                [null, 'Moves feet & gets around, but with little reason or purpose', 2.5],
                [null, 'Will backcheck and forecheck with support but has some inconsistency', 3],
                [null, 'Is on time to puck races as well as on backcheck, and forecheck', 3.5],
                [null, 'Communicates and consistently involved, on time with backcheck, forward check and will consistently be F1', 4],
                [null, 'Shifts always short (40 seconds or under) communicates, feet always moving, always around the puck. On time or first to all assigned spots', 4.5],
                [null, 'Purely relentless, always ahead of play, communicates unconditionally and is overwhelming to play against.', 5],
            ],

            // --- HOCKEY IQ ---
            'Hockey IQ Vision' => [
                [null, 'Little situational awareness', 1],
                [null, 'Head down and panic outcome', 1.5],
                [null, 'Little prior observation of all options', 2],
                [null, 'Forces high risk option', 2.5],
                [null, 'Creates space and executes simple play', 3],
                [null, 'Interprets the situation and controls outcome', 3.5],
                [null, 'Reads the situation and executes the best option', 4],
                [null, 'Situationally aware of all players', 4.5],
                [null, 'Ahead of the situation, reads all options and executes', 5],
            ],
            'Hockey IQ Position' => [
                [null, 'Little situation and ice positioning', 1],
                [null, 'Significant gaps between the opponents and little space awareness', 1.5],
                [null, 'Gives up the middle with an outside to inside attack', 2],
                [null, 'Gets caught off guard, but recovers majority of the time through the middle', 2.5],
                [null, 'Normal support and location. Misses on occasion but recovers back through the middle', 3],
                [null, 'Majority of time in proper space and quick recover on error to proper area', 3.5],
                [null, 'In proper space and recovers gaps with skills', 4],
                [null, 'Great support and proper spacing', 4.5],
                [null, 'Perfect spacing and read', 5],
            ],
            'Hockey IQ Execution' => [
                [null, 'Consistent turnovers', 1],
                [null, 'Simplistic plays with little creativity', 1.5],
                [null, 'Wrong selection of options majority of time with poor outcomes', 2],
                [null, 'Little action towards creating time and space to give minimal options of any execution', 2.5],
                [null, 'Quick to follow through with available options', 3],
                [null, 'Finds open ice and creates opportunities', 3.5],
                [null, 'Choice of multiple options with good results will consistently be F1', 4],
                [null, 'Creates space and time and makes heads-up follow through', 4.5],
                [null, 'Perfect selection of options and perfect play', 5],
            ],

            // --- SKILLS ---
            'Skills Puck Handling' => [
                [null, 'Loud chopping motion against the ice, not efficient', 1],
                [null, 'Can not separate hands from feet, must slow down to handle puck', 1.5],
                [null, 'Slow decisions which slows abilities with skating', 2],
                [null, 'Struggles with abilities in many situations', 2.5],
                [null, 'Average handling, does not stand out from other player', 3],
                [null, 'Makes all the standard plays, efficient with puck', 3.5],
                [null, 'Elite player with the puck, stands out from other players', 4],
                [null, 'High ability. Puck stays on the stick in all situations', 4.5],
                [null, 'Smooth, very little sound against the ice', 5],
            ],
            'Skills Passing' => [
                [null, 'Struggles in all game situations, pressure causes inability to perform simple passes', 1],
                [null, 'Lacks physical strength or pushes pucks to make passes causing a slow-moving pass', 1.5],
                [null, 'Does not see ice well at all, must look at puck on own stick to be comfortable making passes', 2],
                [null, 'Average passer that struggles with decisions of who to pass to under pressure', 2.5],
                [null, 'Receives passes well, minimal sound when puck hits stick', 3],
                [null, 'Mostly receives and executes passes', 3.5],
                [null, 'Makes all required passes, reads ice very well', 4],
                [null, 'Makes majority of all difficult passes, both forehand and backhand', 4.5],
                [null, 'Gifted, head up at all times, sees all situations before they happen', 5],
            ],
            'Skills Shooting' => [
                [null, 'No shot accuracy or power', 1],
                [null, 'Average shot but very detrimental due to lack of hitting the net, mainly due to having head down', 1.5],
                [null, 'Lacks physical strength, or positions picks on wrong side area of stick blade to generate hard shot', 2],
                [null, 'Missing shot opportunities due to incorrect stick placement when receiving pass from teammates in shooting areas', 2.5],
                [null, 'Not a quick enough release, shots blocked due to lack of quickness', 3],
                [null, 'Extremely powerful shot, however not accurate all the time, misses net often', 3.5],
                [null, 'Hits net in most situations, know where to position body in scoring areas very well', 4],
                [null, 'High percentage of hitting target, can put puck in any window opening', 4.5],
                [null, 'Stick always in proper position to execute any shot, head always looking at target before receiving pass for shot', 5],
            ],
        ];

        $optionsCreated = 0;

        // Seed options into DB
        foreach ($optionsData as $questionTitle => $options) {
            $question = EvaluationQuestion::where('title', $questionTitle)->first();

            if (!$question) {
                $this->command->warn("Question not found: $questionTitle");
                continue;
            }

            foreach ($options as $index => [$title, $option, $rating]) {
                EvaluationQuestionOption::create([
                    'question_id' => $question->id,
                    'title' => $title, // null
                    'option' => $option,
                    'rating' => $rating,
                    'sort_order' => $index + 1,
                    'meta' => null,
                ]);
                $optionsCreated++;
            }
        }

        $this->command->info("Created {$optionsCreated} evaluation question options successfully!");
    }
}