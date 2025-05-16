<?php

namespace Database\Seeders;

use App\Models\PrcAdvanceAssessmentValueStatement;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AssessmentStatementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $assessment_statement = PrcAdvanceAssessmentValueStatement::first();

        if (empty($assessment_statement)) {
            $timestamp = Carbon::now();

            PrcAdvanceAssessmentValueStatement::insert([
                [
                    'assessment_value_id' => 1,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display a quiet head, excellent fluid forward body movement, and execute perfect edge selection. Their skating mechanics are impeccable."
                ],
                [
                    'assessment_value_id' => 1,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically give the appearance of floating on the ice. Their edge selection is ideal, and they appear to require little effort to exhibit perfect execution of body coordination."
                ],
                [
                    'assessment_value_id' => 2,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display the appearance of a floating body. The legs and arms are moving separately but in unison with all other movements. Near-perfect edge selection is evident."
                ],
                [
                    'assessment_value_id' => 2,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically give the appearance of fluid movements on the ice. The arms and legs of the player are highly coordinated. Their edge selection is excellent, and they appear to require little effort to exhibit perfect execution."
                ],
                [
                    'assessment_value_id' => 3,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display fluid gear changes and move smoothly and consistently in transitions. The player's edge selection is efficient.	Players with this rating typically give the appearance of consistent, smooth movements on the ice. Their edge selection is good, and their effort is minimally visible during skating."
                ],
                [
                    'assessment_value_id' => 3,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically give the appearance of consistent, smooth movements on the ice. Their edge selection is good, and their effort is minimally visible during skating."
                ],
                [
                    'assessment_value_id' => 4,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically do not alter their stance with a change in direction or acquired fatigue. The player's edge selection is adequate."
                ],
                [
                    'assessment_value_id' => 4,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display smooth movements when focused and can manage their fatigue in high-pressure situations. Their edge selection is sufficient during skating."
                ],
                [
                    'assessment_value_id' => 5,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display an inconsistent stance where their body movements propel in an alternate direction rather than where they intend to arrive. Sometimes the player will bob their head or keep their legs straight. The player's edge selection is inconsistently efficient."
                ],
                [
                    'assessment_value_id' => 5,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display intermediate body mechanics when focused and will fatigue after high-pressure situations. They may be inconsistent in coordinating movements. Their edge selection can be successful during skating situations."
                ],
                [
                    'assessment_value_id' => 6,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could maximize the placement and power of the legs, keeping them more under the body. They can work on improving proper edge usage."
                ],
                [
                    'assessment_value_id' => 6,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display evidence of growing strength and skill in skating mechanics and are encouraged to develop additional endurance to aid in their skating skill set. The player could maximize edge selection with practice and repetition of power skating techniques."
                ],
                [
                    'assessment_value_id' => 7,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could avoid choppy skating and shortened strides. They are encouraged to prevent the appearance of overworking to accomplish effective play."
                ],
                [
                    'assessment_value_id' => 7,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display elementary skills in skating mechanics and are encouraged to develop endurance to aid in their skating skill set. Edge selection education and smooth skating technique can become a priority to enhance their overall effectiveness on the ice."
                ],
                [
                    'assessment_value_id' => 8,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically can build their skating technique to avoid the appearance of forced, off-balance, and disrupted transitions between movements."
                ],
                [
                    'assessment_value_id' => 8,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are typically encouraged to learn the basics of skating mechanics and can benefit from overall fitness conditioning to transfer strength and endurance to the ice. Education in basic skating techniques is encouraged."
                ],
                [
                    'assessment_value_id' => 9,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display an upright stance and can practice the use of edgework  in order to change direction and build stability. Foundational skill work in forward and backward motion, along with stops and starts, is encouraged."
                ],
                [
                    'assessment_value_id' => 9,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could build overall mechanical skill and stability by engaging inconsistent training, where initial starts and stops, direction changes, and the purpose of the skate edges are explained and demonstrated. Fitness conditioning will be essential to learn skating basics to skate efficiency."
                ],
                [
                    'assessment_value_id' => 10,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display minimal large motor movements in the upper body, while maximum power is funneled to the lower body, in a highly efficient and controlled fashion."
                ],
                [
                    'assessment_value_id' => 10,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display a low compact playing stance and propel all of their power into their base. Their movements exude power and strength."
                ],
                [
                    'assessment_value_id' => 11,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display a stance that is aligned to the center of gravity and does not shift too far in any direction from this calibration. The player is efficient at maximizing powerful movements for their favor."
                ],
                [
                    'assessment_value_id' => 11,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display a low playing stance and send power predominantly into their base. Their movements are strong and controlled."
                ],
                [
                    'assessment_value_id' => 12,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display the ability to load power efficiently, while their body works in unison with their vision. The player uses their mastery of control and energy most of the time."
                ],
                [
                    'assessment_value_id' => 12,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically have excellent body alignment and maximize their opportunities by directing most of their available strength into their base. Their movements are predominately powerful."
                ],
                [
                    'assessment_value_id' => 13,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display a low, wide stance, while contributing to the play most of the time. The player would benefit from the application of more power, as it is evident that they have the skill set, but lack consistency."
                ],
                [
                    'assessment_value_id' => 13,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically align their bodies in a powerful skating stance when put in racing situations. Their power comes somewhat from their base, yet they can lose momentum when this power seeps into their arms."
                ],
                [
                    'assessment_value_id' => 14,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating display a continued progression and can follow the play, perhaps on the perimeter. The application of more power by the player would benefit the play."
                ],
                [
                    'assessment_value_id' => 14,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically attempt to use more than their core and base to generate momentum. Their legs are wide, and they tend to contribute to the play half of the time. Alignment and coordination can be observed in the player's movements."
                ],
                [
                    'assessment_value_id' => 15,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could work on unison movements between their arms and legs, while attempting to gain momentum. This refinement would reduce the appearance of chunky momentum."
                ],
                [
                    'assessment_value_id' => 15,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically can work on coordinating their arms and legs to generate power. Strengthening their base and lowering their center of gravity would elevate their level of success."
                ],
                [
                    'assessment_value_id' => 16,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating could work on displaying a consistent rhythm, stride, or push. With more strength-building exercises, gravity would seem to have less effect on the player's movements."
                ],
                [
                    'assessment_value_id' => 16,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically can practice coordination skill work while focusing on reaction time to a stimulus. Building strength in their base would bring power to their game."
                ],
                [
                    'assessment_value_id' => 17,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating could work on displaying control over edges or body movements. The player could increase efficient power exertion to produce effective gameplay."
                ],
                [
                    'assessment_value_id' => 17,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically can build elementary kinesthetic movement techniques. Strengthening exercises for both the core and legs is encouraged."
                ],
                [
                    'assessment_value_id' => 18,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could increase processing speed to coordinate movements. Players could practice and study the game to gain knowledge of how to improve reaction time."
                ],
                [
                    'assessment_value_id' => 18,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to start with beginning movement exercises off of the ice. Strengthening exercises for the legs and core are necessary, and reaction-time skill building will be required to continue their play."
                ],
                [
                    'assessment_value_id' => 19,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically have deliberate, urgent, desperate, precise movements towards a goal. Player's body movements show their laser focus and winning determination towards an outcome.This player's speed is highly impressive."
                ],
                [
                    'assessment_value_id' => 19,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically calculate and execute every movement of their body with the goal of funneling their power into explosive speed. They execute these movements with ultimate fluidity and the appearance of ease. These players are highly noticeable on the ice and are predominately first to the play if this position would be to their advantage. Their directional mobility is ideal."
                ],
                [
                    'assessment_value_id' => 20,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically have a low, powerful fundamental speed that works in unison with their body movements to accomplish excellent acceleration. They tend to end up first to every battle."
                ],
                [
                    'assessment_value_id' => 20,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically have all parts of their body moving together to contribute to explosive speed. They tend to execute this speed from their powerful foundation. Their focus is to make it to the puck first, before any other players, and they efficiently shift direction to make it to the play effortlessly."
                ],
                [
                    'assessment_value_id' => 21,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display swift power shifts that are smooth and in proper directions. Acceleration and velocity are mostly consistent across gameplay."
                ],
                [
                    'assessment_value_id' => 21,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically use body movements to contribute to their acceleration and speed on the ice. Direction shifts are solid and effective."
                ],
                [
                    'assessment_value_id' => 22,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating often move their feet with quick bursts of energy, although effort could be more consistent. The skill set necessary to produce speed is evident. "
                ],
                [
                    'assessment_value_id' => 22,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating have enough coordinating body parts to gain a speed advantage. Some time may be lost in directional changes. However, these players can typically make up this delay by executing their skills in skating speed to regain advantage."
                ],
                [
                    'assessment_value_id' => 23,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating often make speed adjustments, however they could improve timing. These players tend to avoid a cruising pace, yet could increase their speed and acceleration contribution."
                ],
                [
                    'assessment_value_id' => 23,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating balance both effort and ease while attempting to coordinate their moves to produce speed. Their feet occasionally cruise rather than move, and their countenance shows the appearance of drive towards the first touch of the puck."
                ],
                [
                    'assessment_value_id' => 24,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating could improve consistency in momentum and synchronized body movements. They typically arrive at the play as backup assistance."
                ],
                [
                    'assessment_value_id' => 24,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display visual effort while attempting to coordinate their moves that will produce speed. Their feet tend to cruise rather than move. Their countenance waivers in the appearance of drive towards the first touch of the puck."
                ],
                [
                    'assessment_value_id' => 25,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could improve body movements and coordination, which would work in conjunction towards a desired outcome on the ice. With determination, these players can break out of perimeter play to contribute to team effectiveness. "
                ],
                [
                    'assessment_value_id' => 25,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically are encouraged to build a skill set that includes coordinating body movements to contribute to a more explosive speed. Reducing the shuffling of feet could improve their ability to make it to the play to contribute."
                ],
                [
                    'assessment_value_id' => 26,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating could improve pace and focused power in their lower body, which would enhance their ability to skate with velocity in the desired direction."
                ],
                [
                    'assessment_value_id' => 26,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating will want to strengthen their overall body coordination to move effectively and explosively. They would benefit from more fluid transitional changes that do not delay their ability to keep up with the play and hinder their skating power."
                ],
                [
                    'assessment_value_id' => 27,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could improve edge selection and urgency towards the play. Acceleration is a critical component that they could add to their skillset. Basic skating mechanics will need to be strengthened. "
                ],
                [
                    'assessment_value_id' => 27,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating can focus on basic body coordination and strength to build their speed profile and skill. Remembering to keep their feet moving at all times and anticipating transitions will be helpful for their gameplay."
                ],
                [
                    'assessment_value_id' => 28,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically engage unconditionally, establishing space early on, within all areas of the ice. They do not lose battles or engagements regardless of the circumstance, and constantly seek ways to participate with maximum effort."
                ],
                [
                    'assessment_value_id' => 28,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating put every ounce of their strength and determination into every movement made on the ice. They are laser-focused and refuse to lose races, battles, and possession. Their intimidating presence is seen within all plays when they are off the bench."
                ],
                [
                    'assessment_value_id' => 29,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically engage unconditionally, establishing space early on in the game, and they remain dominant throughout the entire play. There is little left to give in the realm of effort."
                ],
                [
                    'assessment_value_id' => 29,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically engage at an extremely high level. Their focus is palatable, and they attempt to win almost all races, battles, and possession. Their stiff competition and presence is always noticeable on the ice."
                ],
                [
                    'assessment_value_id' => 30,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating always engage in the current play and will display more wins than losses during battles. Determination can be seen which, at times, will propel the player to success even when minor  elements could be refined. "
                ],
                [
                    'assessment_value_id' => 30,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically engage intensely. They are at the focus and center of many races and battles. They contribute to the overall energy of the game and are a difficult challenge for their opponents."
                ],
                [
                    'assessment_value_id' => 31,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically engage in the success of the game and stay within the battle or engagement until resolution, whether it is a win or loss. They can be counted on to attempt to control the play. "
                ],
                [
                    'assessment_value_id' => 31,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => 'Players with this rating typically engage most of the time. They contribute to races and battles and are team players who "works behind the scenes" to ensure their team is successful. They can tend to be unexpected competition for their opponents.'
                ],
                [
                    'assessment_value_id' => 32,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically will engage in battles and could strengthen consistency regarding intensity, to improve the chance of success. Finding their personal unique contribution to the overall team talent would be beneficial. "
                ],
                [
                    'assessment_value_id' => 32,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically engage in battles and races from time to time, especially when encouraged to do so. They seem to feel comfortable both contributing to and watching the play. At times they will let their competition win a battle in hopes to gain momentum after reprieve."
                ],
                [
                    'assessment_value_id' => 33,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically will engage in battles from time to time. They could build confidence by stepping out of their comfort zone to engage in one on one altercations without the security of support from other teammates."
                ],
                [
                    'assessment_value_id' => 33,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating will engage in battles and races when they are encouraged to do so. Their comfort zone seems to be observing and calculating and eventually contributing to the play. Their main tactic seems to be becoming an obstacle or using the length of their stick to make sweeping movements towards their opponent."
                ],
                [
                    'assessment_value_id' => 34,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically will engage in battles only occasionally. They could become more engaged, increasing the appearance of interest while proportionally raising their risk/reward ratio."
                ],
                [
                    'assessment_value_id' => 34,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically will want to sharpen their appearance of drive and engagement. They can evaluate their level of engagement by establishing specific future goals for their gameplay."
                ],
                [
                    'assessment_value_id' => 35,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically tend to shy away from contact and intensity. Their contribution can usually only been seen on the perimeter of the play. "
                ],
                [
                    'assessment_value_id' => 35,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically keep on the perimeter of the play and function mainly as an observer. Often they will engage in play when it appears safe to do so. With more risk will come more reward. Therefore these players are encouraged to step out of their comfort zone."
                ],
                [
                    'assessment_value_id' => 36,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically do not engage in battles or intense play. Basic skill building in all areas is encouraged to build confidence in contributing to team success. "
                ],
                [
                    'assessment_value_id' => 36,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating will want to explore the rewards of staying present in the play and exerting confident pressure on their opponent. Primary skill building in all areas will increase this confidence."
                ],
                [
                    'assessment_value_id' => 37,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display ideal body structure, including having eyes up, square shoulders, and stick-to-puck connection during most of the game. They have influence even away from the puck and are always ahead of the play, which creates a sense of awe on the ice."
                ],
                [
                    'assessment_value_id' => 37,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => 'Players with this rating could be considered to have "ideal" structuring in their stance, including eye, shoulder, and leg positioning. They are ahead of their peers in technique and skill. These players handle the puck with ease and precision and consistently take additional risks due to their strong skill foundation. Their execution of movements, resulting in success, is inspiring.'
                ],
                [
                    'assessment_value_id' => 38,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically establish upward eyes, square shoulders, and stick-to-puck connection early on in the game. They influence the play almost immediately and close the play swiftly."
                ],
                [
                    'assessment_value_id' => 38,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating display solid structure regarding eye, shoulder, and leg placement. They contribute extremely often to the play as the puck tends to be drawn to their stick. They can be seen using their skill set to contribute to goals and defense creatively."
                ],
                [
                    'assessment_value_id' => 39,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically establish upward eyes, square shoulders and stick to puck connection often. They can influence, contain and close in on the puck carrier efficiently."
                ],
                [
                    'assessment_value_id' => 39,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating have efficient structure in regards to eye, shoulder, and leg placement. They contribute to the game as a key player and will be noticeable on the ice for their level of skill with the puck. They are one of the top performers on their line."
                ],
                [
                    'assessment_value_id' => 40,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically establish upward eyes, square shoulders and stick to puck connection at some points during the game. They can influence the play most of the time."
                ],
                [
                    'assessment_value_id' => 40,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating display sufficient structure regarding eye, shoulder, and leg placement. Their skills are beneficial to the play most of the time. Their choice of technical moves is progressive, and they are often successful when they take risks."
                ],
                [
                    'assessment_value_id' => 41,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically establish upward eyes, square shoulders and stick to puck connection from time to time. They can influence and contain the play, but lack consistency."
                ],
                [
                    'assessment_value_id' => 41,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating show a basic understanding of using stance such as keeping eyes up, shoulders square, and solid legs to contribute to the probability of success. They show evidence of higher-level ability but may lack consistency in frequently executing these skills."
                ],
                [
                    'assessment_value_id' => 42,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically establish upward eyes, square shoulders, and stick to puck connection, but could do so more frequently. Their focus could be aimed toward more influence over the play."
                ],
                [
                    'assessment_value_id' => 42,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating display a sufficient stance regarding keeping their eyes up and their shoulders square. They are able to contribute to the play some of the time and can find more success by building on an already growing skill set."
                ],
                [
                    'assessment_value_id' => 43,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could improve consistency in line-up techniques, such as upward eyes, square shoulders, and stick-to-puck connection. Once more consistency is established, they will influence the play and backcheck more efficiently."
                ],
                [
                    'assessment_value_id' => 43,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating will want to adjust the alignment between shoulders, legs, and feet placement. When doing so, they will find greater success with the puck and proportionally increase their confidence to attempt moves that require finesse."
                ],
                [
                    'assessment_value_id' => 44,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could improve essential line-up techniques such as upward eyes, square shoulders, and stick-to-puck connection. Once improvement is established, they will more efficiently influence the play and backcheck and forecheck with more power."
                ],
                [
                    'assessment_value_id' => 44,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating can be more effective by working on proper stance and strengthening overall stickhandling, shooting, and skating skills. Basic foundational skills can be observed; however, repetition of practice is needed to contribute effectively to the gameplay."
                ],
                [
                    'assessment_value_id' => 45,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically need accelerated practice in all areas of technique and would benefit from repeated, focused skill training."
                ],
                [
                    'assessment_value_id' => 45,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating would benefit from overall skill development in stance alignment, skating, and shooting. It would be helpful for them to work just as much off the ice as on the ice to develop large and small motor muscles to execute moves properly, with strength."
                ],
                [
                    'assessment_value_id' => 46,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically are purely relentless in their drive. They are always ahead of the play, communicate unconditionally to their teammates, and are overwhelming to their opponents."
                ],
                [
                    'assessment_value_id' => 46,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating have an unrelenting drive that seems to obliterate all obstacles. Their energy and persistence is evident to everyone watching. They tend to exude the most power in the play. They will never give up a battle and set the tone whenever they are on the ice."
                ],
                [
                    'assessment_value_id' => 47,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically take shorter shifts to preserve their drive. Their feet are constantly moving, and they can be found near the puck most of the game. They are on time or first to all assigned spots."
                ],
                [
                    'assessment_value_id' => 47,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating have an incredibly high drive and seem not to fear the consequences of aggressive play. They tend to be consistently present where the action is on the ice, and they will win most battles due to determination."
                ],
                [
                    'assessment_value_id' => 48,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically communicate well and consistently evolve throughout the game. They are on time with their back check and forward check and will be predominately in the F1 position."
                ],
                [
                    'assessment_value_id' => 48,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating do not shy away from aggressive situations and plays. They are drawn to the movement rather than away from it and are incredibly challenging to play against."
                ],
                [
                    'assessment_value_id' => 49,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically are on time to puck races; however, they are rarely early. They are focused and could aim to have consistent drive."
                ],
                [
                    'assessment_value_id' => 49,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are focused and intend to fight for the puck most of the time. They do not easily give up and will consistently win races against their opponents."
                ],
                [
                    'assessment_value_id' => 50,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically will backcheck and forecheck appropriately, but they lack consistency; therefore, more persistence would produce greater opportunity."
                ],
                [
                    'assessment_value_id' => 50,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "It is evident that this type of player certainly has talent. They contribute their skills to the game and occasionally become involved in aggressive gameplay if they see a likely positive outcome."
                ],
                [
                    'assessment_value_id' => 51,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically will move their feet when needed, but are encouraged to improve direction and passion towards the play."
                ],
                [
                    'assessment_value_id' => 51,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating have skills to contribute but can be seen compromising some effort on behalf of security or uncertain outcome. They may seem to lack focus but may just need further encouragement to draw out their ability to be a strong contender."
                ],
                [
                    'assessment_value_id' => 52,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could show more passion toward puck possession and strive to engage more in the play."
                ],
                [
                    'assessment_value_id' => 52,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to hold back on the perimeter rather than using their skills to contribute. They are encouraged to evaluate their strengths to pursue their influence on the game more confidently."
                ],
                [
                    'assessment_value_id' => 53,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could strengthen the display of passion or drive on the ice in all game situations. Their focus needs to be more centered on overall contribution."
                ],
                [
                    'assessment_value_id' => 53,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to evaluate their mental game to strengthen their willingness to become part of the play. Confidence in basic skills will strengthen their drive to contribute."
                ],
                [
                    'assessment_value_id' => 54,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could focus on basic elements of play execution with a goal of ultimately contributing to the game's flow."
                ],
                [
                    'assessment_value_id' => 54,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically will avoid any aggressive or risky play. They may quit in pursuit rather than push through obstacles. These players are encouraged to continually work on drills to build a basic skill set that will contribute to persistence and drive."
                ],
                [
                    'assessment_value_id' => 55,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically are ahead of the play and read all options with pinpoint accuracy, while exhibiting perfect execution."
                ],
                [
                    'assessment_value_id' => 56,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically read the play with exceptional accuracy and are situationally aware of most players and possible outcomes."
                ],
                [
                    'assessment_value_id' => 57,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically read the play with sufficient accuracy and often execute the best option. They are a clear asset to their teammates."
                ],
                [
                    'assessment_value_id' => 58,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically interpret the game well and will often control the outcome of the play."
                ],
                [
                    'assessment_value_id' => 59,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically create space for others and will execute simple plays. Often their puck placement creates an element of success for their teammates."
                ],
                [
                    'assessment_value_id' => 60,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could work on reading the play accurately and should avoid forcing high-risk options."
                ],
                [
                    'assessment_value_id' => 61,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could strengthen evidence of thinking ahead of the play. Their choice of puck placement seem more random or by chance. "
                ],
                [
                    'assessment_value_id' => 62,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically are encouraged to avoid executing plays that convey little vision and panicked movements."
                ],
                [
                    'assessment_value_id' => 63,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could work on skills that build situational awareness in all game events."
                ],
                [
                    'assessment_value_id' => 55,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating can seem to predict plays two to three moves ahead of the puck. They are phenomenally creative and surprise their opponents with unexpected maneuvers that drive the success of the team. They can make any player look skilled when playing on their line."
                ],
                [
                    'assessment_value_id' => 56,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating outmaneuver the opponent's plays by predicting the outcome of each move. They can be seen leading and directing their teammates and are predominantly successful at predicting the outcome of their opponent's potential strategy."
                ],
                [
                    'assessment_value_id' => 57,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating understand the potential outcomes of their competition's tactics and usually choose the appropriate counter move to create challenges for their opponent. They can be seen as creative and visionary."
                ],
                [
                    'assessment_value_id' => 58,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating can see potential in maneuvers; however, they may not consistently execute their visionary skills. These players can be seen leading the play in many situations, and they contribute well to most plays."
                ],
                [
                    'assessment_value_id' => 59,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to be willing to give and take in the play and are easily directed by others. They may shy away from complex plays at times."
                ],
                [
                    'assessment_value_id' => 60,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to be more successful at being a supporting cast to the play. They can take direction but may lack the visionary skill to move to the intended placement of the opponent or puck."
                ],
                [
                    'assessment_value_id' => 61,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating will want to strengthen their skills to contribute more to the play. They are encouraged to watch more film and former games to see the talent of vision."
                ],
                [
                    'assessment_value_id' => 62,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically may be rushed into making mistakes that can be avoided with patience and practicing visionary predicting. The player may be hesitant in their ability, yet they may need additional time to understand the game overall."
                ],
                [
                    'assessment_value_id' => 63,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically seem unaware of their surroundings. Often they are not looking for a play as they are more focused on maintaining basic skills. They are encouraged to strengthen reactionary moves."
                ],
                [
                    'assessment_value_id' => 64,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically execute perfect spacing and read of the ice. As a result, they tend to always be in the right place at the right time to create opportunity and success."
                ],
                [
                    'assessment_value_id' => 65,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically execute excellent support and proper spacing. They are often in the right place and position to receive the puck."
                ],
                [
                    'assessment_value_id' => 66,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are typically in the proper position for the play and recover gaps with their skill set."
                ],
                [
                    'assessment_value_id' => 67,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically are in the appropriate space, most of the time, and quickly recover from their errors to navigate to the proper position."
                ],
                [
                    'assessment_value_id' => 68,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically execute average support and location. They may be misaligned to position on occasion; however, they can recover back through the middle."
                ],
                [
                    'assessment_value_id' => 69,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically can be caught off guard, but can recover most of the time through the middle. They can lose proper positioning through fast game play."
                ],
                [
                    'assessment_value_id' => 70,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could strengthen their game in the middle of the ice rather than displaying outside to inside attacks."
                ],
                [
                    'assessment_value_id' => 71,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating could reduce significant gaps between the opponents and display stronger spatial awareness."
                ],
                [
                    'assessment_value_id' => 72,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could strengthen situational awareness and appropriate ice positioning."
                ],
                [
                    'assessment_value_id' => 64,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating have impeccable placing and are always in an ideal position for the play. They experience multiple opportunities due to their stance and spacing. The player consistently picks off plays because of their amazing ability to read their opponents."
                ],
                [
                    'assessment_value_id' => 65,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically create ongoing opportunities for their team by consistently being in key areas of potential, which drives success. These players give ample spacing between their teammates and the puck."
                ],
                [
                    'assessment_value_id' => 66,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically show proper positioning on the ice and tend to be in many plays when the flow of the game is predictable. When they find themselves out of a position of opportunity, they can recover quickly because of developed skills."
                ],
                [
                    'assessment_value_id' => 67,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating will vary with effective positioning. However, they can recover reasonably quickly. Their spacing with other players is variable; however, they can still find collaborative success."
                ],
                [
                    'assessment_value_id' => 68,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to lack consistency in proper positioning yet find themselves successful when resetting before a play. Their efficient support and spacing between the puck can vary."
                ],
                [
                    'assessment_value_id' => 69,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating may have some success in positioning the body to the puck and play but lack displaying knowledge of where to be. They can strengthen their level of success by observing seasoned players and analyzing their position on the ice, compared to their level of opportunity."
                ],
                [
                    'assessment_value_id' => 70,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating can build their level of proper positioning and find further success playing more within the zone of their particular position rather than just following the puck and action."
                ],
                [
                    'assessment_value_id' => 71,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically will need to work on learning about opportunities in spacing and gaps. They will want to understand how to use gaps and positions in their favor."
                ],
                [
                    'assessment_value_id' => 72,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically need basic knowledge of the pattern and spacing of plays and the role each position plays in the team's overall success."
                ],
                [
                    'assessment_value_id' => 73,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display the most efficient and appropriate selection of options and execute a perfectly skilled play. They are an irreplaceable leader on their team."
                ],
                [
                    'assessment_value_id' => 74,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically create space and time for their teammates, which elevates the team overall. They tend to display a heads-up follow through, that significantly impacts the outcome of the game."
                ],
                [
                    'assessment_value_id' => 75,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically find multiple options within the gameplay and execute efficient actions to create many opportunities."
                ],
                [
                    'assessment_value_id' => 76,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically find open ice and create occasional opportunities for their teammates."
                ],
                [
                    'assessment_value_id' => 77,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could strengthen swiftness in follow-through and can explore more creative options."
                ],
                [
                    'assessment_value_id' => 78,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically can strengthen creativity in establishing time and space and could increase options of execution."
                ],
                [
                    'assessment_value_id' => 79,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could explore more play options to increase the success of outcomes."
                ],
                [
                    'assessment_value_id' => 80,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could strive for more complex plays with more creativity."
                ],
                [
                    'assessment_value_id' => 81,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically need to avoid consistent turnovers while becoming more engaged in the play."
                ],
                [
                    'assessment_value_id' => 73,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating calculate and follow through on the most ideal play. They can predict and direct the overall pace, energy, and direction of the game. These players are always the leader and director of their line."
                ],
                [
                    'assessment_value_id' => 74,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating can see and execute ideal plays and will direct their linemates in most situations. Their contribution to overall game flow and an ultimately successful outcome is significant."
                ],
                [
                    'assessment_value_id' => 75,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically do not hesitate to make decisions on the ice and usually make choices that bring about success for their line and team. They tend to maximize opportunities and typically will be a solid asset to the gameplay."
                ],
                [
                    'assessment_value_id' => 76,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating look for opportunities that are executed with ease and create opportunities in many situations."
                ],
                [
                    'assessment_value_id' => 77,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating occasionally execute options that are beneficial to their teammates. It is variable whether they will take advantage of open ice to create opportunities for success."
                ],
                [
                    'assessment_value_id' => 78,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically can expand their vision to find opportunities to generate plays, especially during high-pressure situations that require swift reactions."
                ],
                [
                    'assessment_value_id' => 79,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to observe gameplay situations to predict and explore options of plays. They can then transfer this knowledge onto the ice to generate confidence and success."
                ],
                [
                    'assessment_value_id' => 80,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating display simple plays with delayed execution at times. They are encouraged to expand their knowledge of various reactions to game situations to add to their knowledge skill base."
                ],
                [
                    'assessment_value_id' => 81,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to build their strength and skill set in all areas to prevent multiple turnovers. A combination of foundational skill and confidence is imperative to grow as a contributing player."
                ],
                [
                    'assessment_value_id' => 82,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically execute amazingly smooth, precise, fluid puck handling, producing minimal sound against the ice. Their puck handling skills are extremely obvious at first glance."
                ],
                [
                    'assessment_value_id' => 83,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display an accelerated ability to keep the puck on the stick during all situations."
                ],
                [
                    'assessment_value_id' => 84,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to be precise with puck placement and are often in control of the outcome of the play."
                ],
                [
                    'assessment_value_id' => 85,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically make all the standard plays with efficient puck placement."
                ],
                [
                    'assessment_value_id' => 86,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display average puck handling skills while not being overly demonstrative."
                ],
                [
                    'assessment_value_id' => 87,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could strengthen their ability to keep the puck on the stick in many situations on the ice."
                ],
                [
                    'assessment_value_id' => 88,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could execute quicker decisions and more controlled handling of the puck. These factors will increase skating acceleration."
                ],
                [
                    'assessment_value_id' => 89,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically can work on separating hands from the feet and avoid decelerating when handling the puck."
                ],
                [
                    'assessment_value_id' => 90,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could reduce chopping motion against the ice when trying to control the puck and increase efficiency while contributing to the play."
                ],
                [
                    'assessment_value_id' => 82,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating use their stick as an extension of their body, while the puck seems to be drawn to their stick magnetically. Their puck handling is fluid, smooth, and quiet. When handling the puck, their mobility appears to be effortless, and their skill draws every observer to focus on their outstanding performance."
                ],
                [
                    'assessment_value_id' => 83,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating find the puck with their stick, even with eyes up and chest forward. They can maneuver through almost any obstacle and are terminally skilled with the puck in most situations."
                ],
                [
                    'assessment_value_id' => 84,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are precise in their actions and can maneuver through obstacles well. They contribute to the play by keeping the puck in their control, as needed, and are predominately able to handle the puck with their eyes on the target."
                ],
                [
                    'assessment_value_id' => 85,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are usually effective with stick work and puck placement. They impact the overall success of the game with variable contributions within pressure and non-pressure situations."
                ],
                [
                    'assessment_value_id' => 86,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating complement their team with their puck handling and placement skills during the game but may lack some consistency in effort or execution."
                ],
                [
                    'assessment_value_id' => 87,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating can continue to capitalize on their growing skillset of puck handling and puck placement. Consistent practice is encouraged while focusing on eyes up and smooth movements."
                ],
                [
                    'assessment_value_id' => 88,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to avoid jumbled movements while keeping their eyes on the puck. Basic puck handling skills can be built using obstacle maneuvering to increase speed."
                ],
                [
                    'assessment_value_id' => 89,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating can build fundamental hand and eye coordination skills to transfer this skill to stick to puck work. They will want to start with maneuvering the stick without the puck to increase confidence and skill."
                ],
                [
                    'assessment_value_id' => 90,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to start foundational skill-building using the stick during hand, feet, and eye coordination. Using a static stance while puck handling with eyes up is an initial drill that will need to be repeated to build confidence."
                ],
                [
                    'assessment_value_id' => 91,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are exceptionally gifted with their head up in all situations, showing vision within the play before it happens."
                ],
                [
                    'assessment_value_id' => 92,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically make the majority of all difficult passes using both forehand and backhand skills."
                ],
                [
                    'assessment_value_id' => 93,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically make and receive all required passes,  reading the ice extremely well."
                ],
                [
                    'assessment_value_id' => 94,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating catches most passes with little fumbling and ensures their passes are strong and ultimately connect to the intended recipient. "
                ],
                [
                    'assessment_value_id' => 95,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically receive passes well, producing minimal sound when the puck lands on the stick."
                ],
                [
                    'assessment_value_id' => 96,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are typically average in passing skills and could strengthen quick decisions regarding choosing a recipient when under pressure."
                ],
                [
                    'assessment_value_id' => 97,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically can work on strengthening visioning of the ice and avoid looking at the puck on their stick in order to be comfortable making passes."
                ],
                [
                    'assessment_value_id' => 98,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could work on increasing physical strength to pass efficiently and could avoid pushing the puck to make passes, which can cause a slower interchange."
                ],
                [
                    'assessment_value_id' => 99,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could work on strengthening skills pertaining to most game situations and work on their ability to perform simple passes."
                ],
                [
                    'assessment_value_id' => 91,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating seem to place the puck exactly where they desire at all times. They are an integral part of every point and goal when they are on the ice. They can be relied on to make even the unpredictable and outlandish plays lead to ultimate success."
                ],
                [
                    'assessment_value_id' => 92,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating will make difficult passes seem effortless. They have laser focus and can use both their forehand and backhand to achieve their desired results. These players make a significant contribution to most plays."
                ],
                [
                    'assessment_value_id' => 93,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating have vision and precision on the ice most of the time. They can pass with ease and only need minor adjustments on positioning when connecting the puck to other players."
                ],
                [
                    'assessment_value_id' => 94,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating can receive and give a pass well and contribute to the play when they are confident of the outcome. They read the ice with some vision and creativity."
                ],
                [
                    'assessment_value_id' => 95,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically vary on their ability to pass successfully and receive a pass with quiet absorption. When confident, they will utilize their teammates to advance the puck to the goal."
                ],
                [
                    'assessment_value_id' => 96,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically find success in less pressure situations and may or may not pass in plays that would be more effective to do so. They are encouraged to observe other players with high assist points to understand the value of advancing the puck for an overall win."
                ],
                [
                    'assessment_value_id' => 97,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to struggle with finding open teammates due to a lack of experience in the vision of potential plays. They are encouraged to practice passing while subjected to various reaction-time measured scenarios."
                ],
                [
                    'assessment_value_id' => 98,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to practice basic passing skills while building overall strength, which will add power to their passing technique."
                ],
                [
                    'assessment_value_id' => 99,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically need to understand the fundamental value of passing overall and acknowledge its impact on their own game. These players are encouraged to practice foundational shooting and passing techniques designed to land on a specific target."
                ],
                [
                    'assessment_value_id' => 100,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically hold the stick in the proper position at all times to execute any desired shot with maximum velocity. Their head always looks at the target before receiving a pass for a shot, and the shot consistently arrives where it is aimed."
                ],
                [
                    'assessment_value_id' => 101,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically have a high percentage of hitting a target and can place the puck in any window opening, with speed and accuracy."
                ],
                [
                    'assessment_value_id' => 102,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically hit the net with power in most situations, and know where to position their bodies in scoring zones to produce a goal."
                ],
                [
                    'assessment_value_id' => 103,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically have an extremely powerful shot; however, their placement lacks consistent accuracy."
                ],
                [
                    'assessment_value_id' => 104,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could strengthen quick-release skills to prevent shots from being blocked, due to delayed execution."
                ],
                [
                    'assessment_value_id' => 105,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically can work on missing fewer shot opportunities due to incorrect stick placement, when receiving passes from teammates in shooting zones."
                ],
                [
                    'assessment_value_id' => 106,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could work on improving their strength and puck position on the stick blade to generate more powerful shots."
                ],
                [
                    'assessment_value_id' => 107,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically possess an average shot that could be improved by keeping their head up."
                ],
                [
                    'assessment_value_id' => 108,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could work on basic shooting skills to prevent shots with a lack of accuracy and power."
                ],
                [
                    'assessment_value_id' => 100,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically can place the puck in every area of the net with power and precision. There is no flailing or lack of trajectory when switching to the backhand, allowing multiple options. These players shoot with confidence, even under high pressure or while in unique angles to the net. They are a reliable leader of their team in shooting skills."
                ],
                [
                    'assessment_value_id' => 101,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating demand attention on the ice as they take risks with the puck with the outcome of success most of the time. They can place the puck where they desire with a strong shot using both forehand and backhand options."
                ],
                [
                    'assessment_value_id' => 102,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically are deliberate in their passing and will hit their target in most cases. They shoot with power and react appropriately under pressure yet find the most success when they have time to calculate their technique."
                ],
                [
                    'assessment_value_id' => 103,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically are strong on the puck and shoot with power but may not consistently achieve the end goal with their aim. Nevertheless, they are an asset to their line when they take risks to be successful."
                ],
                [
                    'assessment_value_id' => 104,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are varied in their ability to hit a target. Their shot is somewhat accurate but could increase in power and quick-release."
                ],
                [
                    'assessment_value_id' => 105,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could strengthen their shot by improving positioning and keeping their head up and aimed at the target. Overall, strengthening exercises would help to bring power to their shot."
                ],
                [
                    'assessment_value_id' => 106,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to shoot with confidence while building foundational shooting techniques. Repetition in shooting at varied targets is suggested."
                ],
                [
                    'assessment_value_id' => 107,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could improve their accuracy with foundational strength in their legs and core. Repetition is necessary with weighted and unweighted pucks."
                ],
                [
                    'assessment_value_id' => 108,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to work on basic puck handling and shooting skills while keeping their fhead up and focused on the intended goal. Added strength will bring overall confidence to their shooting success."
                ],
                [
                    'assessment_value_id' => 109,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display effortless edge control and mobility. Their lateral post-to-post movement is precise, and they can drop and rise with power, seemingly without effort. They snap immediately back following saves, and they are balanced and in control."
                ],
                [
                    'assessment_value_id' => 110,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display edge control that is precise and on point. Their lateral post-to-post movement is swift, and they can drop or rise quickly with power. There is hardly any delay following post saves."
                ],
                [
                    'assessment_value_id' => 111,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display proper edge control most of the time. Their lateral post-to-post movement is effective, and they are consistent in drops, rises, and post save recoveries while having minimal delay."
                ],
                [
                    'assessment_value_id' => 112,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display some smoothness when using variable edge selection. They can move from post to post with enough efficiency to remain mostly balanced and in control. They occasionally show some delay in save reaction depending on the play."
                ],
                [
                    'assessment_value_id' => 113,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display variable balance, control, and mobility depending on the gameplay situation. They can drop and rise with power but may be delayed due to fatigue or balance issues."
                ],
                [
                    'assessment_value_id' => 114,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating can possibly be seen as inconsistently using balance, mobility, and edge selection to their advantage. Their drops and rises ,when saving, could be more powerful with overall strength training exercises. They can improve their save recovery with focus and reaction time recovery concentration."
                ],
                [
                    'assessment_value_id' => 115,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could benefit from edgework practice inside and out of the net. Additionally, they could improve their lateral post-to-post speed by gaining control and balance through repeating proper techniques and eliminating delay."
                ],
                [
                    'assessment_value_id' => 116,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically are encouraged to pursue balance, and edge control selection in and out of all game situations. Additionally, these players are encouraged to strengthen their lateral post-to-post movement to prevent delay and net coverage loss."
                ],
                [
                    'assessment_value_id' => 117,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically would benefit from foundational skills in edge selection, balance, and control. These players can explore rudimentary lateral post-to-post movement along with quick drops and rises to increase overall efficiency."
                ],
                [
                    'assessment_value_id' => 109,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Precise edge usage, smooth mobility, explosive power that drops hard and rises with spring, post save recoveries are immediate, balance and control is fluid, no delay in reaction"
                ],
                [
                    'assessment_value_id' => 110,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Excellent  edge control with fluid mobility . Powerful drops and rises for a fluid control and balance and little delay in reaction"
                ],
                [
                    'assessment_value_id' => 111,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Proper edge choice with smooth mobility. Powerful drops and rises for save recoveries, creating a fluid balanced movement with minor delays"
                ],
                [
                    'assessment_value_id' => 112,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Acceptable edge control while moving with ease for drops and rises for adequate save recoveries. Fairly balanced with some delay in reaction "
                ],
                [
                    'assessment_value_id' => 113,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Edge selection proper 50% of the time creating choppy mobility and delayed drops and rises for save recoveries. Balance and delay reaction are weakened"
                ],
                [
                    'assessment_value_id' => 114,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Inadequate edge control choices creating balance issues and slow rises and drops with slower than average reaction time"
                ],
                [
                    'assessment_value_id' => 115,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Inappropriate edge selection creating slow drops and rises with 20% of available mobility. Slow reaction and weak balance"
                ],
                [
                    'assessment_value_id' => 116,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Improper edges, control and balance which limits drops, rises and reaction times"
                ],
                [
                    'assessment_value_id' => 117,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Unaware of edges which disables mobility in drops, rises, post recovery, control and efficiency "
                ],
                [
                    'assessment_value_id' => 109,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display precise edge control and have such smooth mobility that they may seem to be floating on the ice. These players demonstrate explosive power in dropping hard and rising with spring. Their post save recoveries are immediate, and their balance and control are fluid while refusing to show a delay in reaction."
                ],
                [
                    'assessment_value_id' => 110,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display excellent edge usage, and their mobility is highly fluid while maintaining balance and control. They drop and rise with ease, and their post save recoveries contain minimal delay."
                ],
                [
                    'assessment_value_id' => 111,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display proper edge choice with smooth mobility. They drop and rise with ample power and recover from saves efficiently. They create a fluid, balanced movement of the body and show minor delays."
                ],
                [
                    'assessment_value_id' => 112,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display acceptable edge selection while moving with some ease from post-to-post and front to back. Their drops and rises are sufficient and they can recover from most saves in appropriate time. They are relatively balanced and in control and show some delay in overall reaction and performance."
                ],
                [
                    'assessment_value_id' => 113,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display proper edge selection half of the time, and they may present choppy mobility and delayed drops and rises for save recoveries. Their variable balance may cause a delay in their reaction to saves."
                ],
                [
                    'assessment_value_id' => 114,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display a lack of efficient edge selection at all times. They could benefit from skating drills outside the net to improve effective edge usage along with strengthening balance and control skill. Overall strength-building would enhance more powerful drops and rises along with save recovery timing."
                ],
                [
                    'assessment_value_id' => 115,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to select inconsistent edge selection. More consistency in this skill would aid in preventing delay in save recovery and post-to-post lateral movements. These players could implement more power into their drops and raises through strengthening and reaction time exercises."
                ],
                [
                    'assessment_value_id' => 116,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display improper balance, control, and edge selection. Post-to-post movement is labored, and drops and rises are delayed. Overall skating technique practice and strengthening exercises are highly encouraged to increase efficiency within the net."
                ],
                [
                    'assessment_value_id' => 117,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating will benefit from comprehensive foundational training in control, balance, edge selection, and lateral post-movement efficiency. Delayed save reaction and overall lack of mobility can be strengthened with skill work."
                ],
                [
                    'assessment_value_id' => 118,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Swift and precise movement, do to excellent tracking. Set square with ideal depth and angles"
                ],
                [
                    'assessment_value_id' => 119,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Properly executed movements do to great tracking. Depth control, remaining set and proper depth for excellent positioning"
                ],
                [
                    'assessment_value_id' => 120,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Deliberate movement from effective tracking. Set square with proper angles and depth"
                ],
                [
                    'assessment_value_id' => 121,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Adequate tracking for movements  allowing square setup and decent depth while maintaining set"
                ],
                [
                    'assessment_value_id' => 122,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Average movement through mostly effective tracking. 50% of the time angles and depth are proper"
                ],
                [
                    'assessment_value_id' => 123,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Below average tracking skills hindering movements, depth and angles"
                ],
                [
                    'assessment_value_id' => 124,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Weak tracking movements, angles and depth are off most of the time"
                ],
                [
                    'assessment_value_id' => 125,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Inadequate tracking ability causing movement, depth and angle issues "
                ],
                [
                    'assessment_value_id' => 126,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "In affective movement, due to improper tracking. Creating bad angles and wrong depth in regards to net and shooter"
                ],
                [
                    'assessment_value_id' => 118,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display proper orientation and depth at all times and track with ideal efficiency. They always line up on the puck with swift precision and excellent angles. They model outstanding and consistent lateral mobility."
                ],
                [
                    'assessment_value_id' => 119,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display proper orientation and depth at most times. Their tracking of the puck is excellent during play. They line up on the puck with precision and model excellent and consistent lateral mobility."
                ],
                [
                    'assessment_value_id' => 120,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating frequently display proper orientation and depth. Their tracking is predominately successful. They line up on the puck well, with appropriate angels, and are consistently effective with lateral mobility."
                ],
                [
                    'assessment_value_id' => 121,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically generally display proper orientation and depth. Their tracking is consistently successful. They line up on the puck adequately and are effective with lateral mobility and speed most of the time."
                ],
                [
                    'assessment_value_id' => 122,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display average orientation and depth perception. Their tracking and angels are effective half of the time. They line up on the puck occasionally and show average lateral mobility and speed."
                ],
                [
                    'assessment_value_id' => 123,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display some proper orientation and angles, with occasional successful puck alignment. Strengthening lateral ability would be beneficial to gameplay as well as working on building tracking skills and depth perception."
                ],
                [
                    'assessment_value_id' => 124,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could improve their orientation and puck alignment by working on angles and spacing within the net. Additional skill-building in lateral mobility and tracking is encouraged."
                ],
                [
                    'assessment_value_id' => 125,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically would benefit from increasing skills in basic orientation and angling techniques, puck alignment, tracking, and lateral mobility. They are encouraged to practice these skills repeatedly. "
                ],
                [
                    'assessment_value_id' => 126,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically can begin rudimentary skill-building in orientation, depth perception, and puck alignment and begin the essential skill of lateral movements. With this practice will come confidence and efficiency."
                ],
                [
                    'assessment_value_id' => 118,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display swift and precise movements that are always on target and beneficial to their gameplay. Their tracking is meticulous, and they are set square with consistent ideal depth and angles."
                ],
                [
                    'assessment_value_id' => 119,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are highly efficient in their movement and tracking abilities. They can predict proper depth and execute their stance appropriately. Their angles are excellent, and they predominantly are set square."
                ],
                [
                    'assessment_value_id' => 120,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically are swift in their movement and have mostly successful tracking skills. They angle their body well and can calculate appropriate depth in most gameplays."
                ],
                [
                    'assessment_value_id' => 121,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically move with some speed and can track the puck adequately. Their angles are mostly effective, and they will calculate proper depth when not under pressure."
                ],
                [
                    'assessment_value_id' => 122,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating possess positioning skills that are moderately successful for deflection, net protection, and blocking. At times, they may lose sight of the puck and will occasionally need to adjust focus to maintain dominance. These players move adequately, and their angles and depth indicate some area for growth."
                ],
                [
                    'assessment_value_id' => 123,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating show an opportunity to strengthen already established tracking skills. Their net awareness and use of angles is sufficient, but these skills could be maximized over time with practice."
                ],
                [
                    'assessment_value_id' => 124,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating display tracking skills that could be sharpened through exercises off the ice. Their angles and depth cause some holes in coverage, and they can strengthen their lateral movement through primary muscle building, which would be beneficial to them in their overall game."
                ],
                [
                    'assessment_value_id' => 125,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to leverage basic fundamental skills in both angles and tracking. Muscle building is essential in lateral movement to be able to cover as much net as possible. These player's depth perception could be strengthened through an understanding of the purpose of each depth perspective."
                ],
                [
                    'assessment_value_id' => 126,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to explore primary elements of net coverage and puck deflections through a basic understanding of angles, depth placement, lateral movement, and tracking. They are encouraged to practice in and out of the net to raise performance as well as increase speed and strength."
                ],
                [
                    'assessment_value_id' => 127,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "100% percent effective in all situations"
                ],
                [
                    'assessment_value_id' => 128,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "90% percent effective in all situations"
                ],
                [
                    'assessment_value_id' => 129,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "80% percent effective in all situations"
                ],
                [
                    'assessment_value_id' => 130,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "60% percent effective in all situations"
                ],
                [
                    'assessment_value_id' => 131,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "50% percent effective in all situations"
                ],
                [
                    'assessment_value_id' => 132,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "45% percent effective in all situations"
                ],
                [
                    'assessment_value_id' => 133,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "30% percent effective in all situations"
                ],
                [
                    'assessment_value_id' => 134,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "20% percent effective in all situations"
                ],
                [
                    'assessment_value_id' => 135,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "10% percent effective in all situations"
                ],
                [
                    'assessment_value_id' => 136,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Absorbing body that cradles the puck. Swift catcher and blocker that deflects the puck to the pads. Perfect use of stick to gather in pucks"
                ],
                [
                    'assessment_value_id' => 137,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Sponge like ability on puck reception with a fast glove and precise block to deflect pucks to pads.Good stick to gather pucks in"
                ],
                [
                    'assessment_value_id' => 138,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Soft body cradling the puck, quick catcher and blocker controlling the puck, appropriate use of stick for loose pucks"
                ],
                [
                    'assessment_value_id' => 139,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Cradling body with ability to use effective glove and blocker to control missed played shots. Ability to gather pucks with stick"
                ],
                [
                    'assessment_value_id' => 140,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Average use of body to absorb and cradle the puck.50% efficient use of glove and blocker. Occasional use of stick to gather pucks"
                ],
                [
                    'assessment_value_id' => 141,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Occasional ability to absorb, catch or blocker pucks through proper sources. Little use of stick to gather pucks"
                ],
                [
                    'assessment_value_id' => 142,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Firm body creating little cradling of the puck, inconsistency catcher and rare deflection of pucks to the pads. Rare use of stick to gather pucks"
                ],
                [
                    'assessment_value_id' => 143,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Stiff body for cradling pucks, with sloppy glove and blocker control and no obvious use of stick to assist in gathering pucks"
                ],
                [
                    'assessment_value_id' => 144,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Rigid body creating loose pucks. Glove and blocker placement improper for save selection. Stick use if at all for gathering pucks"
                ],
                [
                    'assessment_value_id' => 136,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to make the puck disappear with absorption skills within any part of their body. They are phenomenally swift with their catcher, and their blocker constantly deflects the puck to their pads. They effectively and efficiently use the stick to gather and clear pucks."
                ],
                [
                    'assessment_value_id' => 137,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are excellent at absorbing the puck whenever it is shot towards the net. They are quick with their catcher, and their blocker deflects the puck to their pads consistently. These players use their stick to gather pucks well."
                ],
                [
                    'assessment_value_id' => 138,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating absorb the puck but with more of a soft body. They work well with their catcher and blocker when attempting to control where they want the puck to go. They tend to use their stick appropriately when gathering loose pucks."
                ],
                [
                    'assessment_value_id' => 139,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to absorb the puck with a soft body on a variable basis. Their catcher and blocker are quick, and they can control the puck when not under pressure. Their stick can be a successful tool for gathering pucks."
                ],
                [
                    'assessment_value_id' => 140,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating use average skills in using the body to absorb and cradle the puck. Their consistency can vary when using the glove and blocker. They occasionally use their stick to gather pucks."
                ],
                [
                    'assessment_value_id' => 141,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating can build on an already strong base of skills in absorbing pucks. Cradling, catching by the glove, and deflecting by the blocker are present abilities. However, these players can expand upon these skills. The more frequent use of the stick to gather pucks is encouraged."
                ],
                [
                    'assessment_value_id' => 142,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to attempt a more soft cradling body when absorbing pucks. Their catcher and blocker are variably successful and could be used more consistently. Essential stick work is encouraged."
                ],
                [
                    'assessment_value_id' => 143,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to build on foundational skills of puck absorption, catching with the glove, and deflecting with the blocker. Stick gathering and sweeping are encouraged."
                ],
                [
                    'assessment_value_id' => 144,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating can work on elementary puck absorption, catching, deflecting, sweeping, and blocking. Repetitive basic blocking exercises are encouraged."
                ],
                [
                    'assessment_value_id' => 136,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display a magnetic force that draws the puck to their blocking ability. They are lightning-fast with their moves, and they protect the net with controlled speed and constantly master the trajectory of a rebound. They use the cradling of their body, glove catches, blocker movement, and gathering of the puck by their stick in an ideal form of defense against their opponent."
                ],
                [
                    'assessment_value_id' => 137,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display an accelerated ability to absorb pucks rather than lose them. They use the cradling of their body, glove catches, blocker movement, and the gathering of pucks by their stick in an excellent form of defense against their opponent."
                ],
                [
                    'assessment_value_id' => 138,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display a semi-soft body while absorbing the puck. They use the cradling of their body, glove catches, blocker movement, and puck gathering in a strong form of defense against their opponent."
                ],
                [
                    'assessment_value_id' => 139,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to catch many pucks by absorbing the trajectory with their body. They use the cradling of their torso, glove catches, blocker movement, and stick in a variable form of defense against their opponent."
                ],
                [
                    'assessment_value_id' => 140,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating have an average advantage of being able to absorb pucks with their torso. They use the cradling of their body, glove catches, blocker movement, and stick gathering ability in a partly successful form of defense against their opponent."
                ],
                [
                    'assessment_value_id' => 141,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display a variable ability to absorb pucks. They use the cradling of their body, glove catches, blocker movement, and stick in an acceptable form of defense against their opponent."
                ],
                [
                    'assessment_value_id' => 142,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating could improve their puck absorbing and deflecting abilities by softening their absorption. Strengthening skills in the cradling of their body, the usage of glove catches, blocker movement, and stick placement are encouraged."
                ],
                [
                    'assessment_value_id' => 143,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating can build their body mechanic skills which help to absorb and deflect pucks with more consistency. Skill-building in the usage of catcher, pads, and stick is essential."
                ],
                [
                    'assessment_value_id' => 144,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating can understand and practice basic body-cradling skills. Primary rebound practice is suggested as well as learning about the usage of gloves, pads and stick placement."
                ],
                [
                    'assessment_value_id' => 145,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "100% proper placement and playing the puck, ideal handling ability, intentional placement of the puck of the entire game "
                ],
                [
                    'assessment_value_id' => 146,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Shows confidence with deliberate puck handling ability and sees the proper options that are available "
                ],
                [
                    'assessment_value_id' => 147,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Proper playing and placement of pucks, 80% handling ability, makes good decisions on clearing pucks"
                ],
                [
                    'assessment_value_id' => 148,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Good puck playing and handling ability with a high probability of outcome cause of proper discernment "
                ],
                [
                    'assessment_value_id' => 149,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Average execution of puck handling, options that are mildly effective cause lacking urgency and vision "
                ],
                [
                    'assessment_value_id' => 150,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Minor execution of pucking handling showing a lack of confidence and urgency "
                ],
                [
                    'assessment_value_id' => 151,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Rarely playing the puck both inside and outside of the crease, leaves little options for transition play"
                ],
                [
                    'assessment_value_id' => 152,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "No obvious skill in puck handle creating few options to handle the puck"
                ],
                [
                    'assessment_value_id' => 153,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Not leaving the net or handling the puck leaving little options"
                ],
                [
                    'assessment_value_id' => 145,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to have extraordinary puck placement skills. They place or land the puck with rebounds that are always beneficial to their team. Their puck handling is superb, and they appear incredibly confident in their position."
                ],
                [
                    'assessment_value_id' => 146,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to have excellent puck placement and clearing skills. They purposely achieve rebounding the puck in such a way that the rebound is in their favor. They are skilled puck handlers, and they display a confident stance in the net."
                ],
                [
                    'assessment_value_id' => 147,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to have accelerated puck placement skills. They display vision and aim. They are progressive stick handlers and clear pucks well with solid confidence."
                ],
                [
                    'assessment_value_id' => 148,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to place the puck efficiently in their favor. Their puck handling skills are above average, and they make wise decisions about clearing the puck."
                ],
                [
                    'assessment_value_id' => 149,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating place the puck in their favor some of the time. Their puck handling skills are average, and they clear the puck effectively when not under pressure."
                ],
                [
                    'assessment_value_id' => 150,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could strengthen the skill of manipulating the puck with their stick and pads to create favorable rebounds. While stick handling is one of their strengths, they are encouraged to build their ability under pressure."
                ],
                [
                    'assessment_value_id' => 151,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to be mindful of puck trajectory when blocking or placing the puck with their stick or pads. Stick handling can be strengthened, and they can be reminded to clear the puck with strength and confidence."
                ],
                [
                    'assessment_value_id' => 152,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating can work on basic puck movement and manipulation drills to gain control of the puck within and outside of the net. Once they strengthen these skills, they will find more options for transitional play."
                ],
                [
                    'assessment_value_id' => 153,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to stay within their net and do not interact with the puck at all. Basic puck handling drills are encouraged to learn how to manipulate the puck with strength and precision, and clear the puck with confidence."
                ],
                [
                    'assessment_value_id' => 145,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display precise and proper placement and playing of the puck. They show superior puck handling ability in a quiet and controlled manner, and they clear the puck successfully at all times."
                ],
                [
                    'assessment_value_id' => 146,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating have excellent puck placement, and they manipulate the puck in a decisive fashion. They consistently place the puck well throughout game situations and can clear the puck efficiently."
                ],
                [
                    'assessment_value_id' => 147,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display effective, proper placement and playing of the puck most of the time. They have strong stick handling skills, and they make wise decisions on clearing pucks."
                ],
                [
                    'assessment_value_id' => 148,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating show variable proper placement and play the puck. They stick handle well and are successful at clearing pucks most of the time."
                ],
                [
                    'assessment_value_id' => 149,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display intermediate skill with placement and playing of the puck. These players usually clear the puck half of the time during gameplay."
                ],
                [
                    'assessment_value_id' => 150,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to build on their emerging skills of placing and handling the puck. They can develop their confidence in the success of clearing pucks repetitively."
                ],
                [
                    'assessment_value_id' => 151,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could benefit from playing the puck both inside and outside of the crease, increasing options for transition play."
                ],
                [
                    'assessment_value_id' => 152,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically are encouraged to increase puck placement skills by working within their net and practicing puck handling outside of the crease. They will be more successful in clearing the puck with more strengthening exercises."
                ],
                [
                    'assessment_value_id' => 153,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could benefit from foundational training in net navigational play and rudimentary puck handling. They are encouraged to move farther from their net and become more engaged in the play."
                ],
                [
                    'assessment_value_id' => 154,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Uncanny ability to maintain visual contact around screens to find pucks while setting perfect depth and holds ideal stance"
                ],
                [
                    'assessment_value_id' => 155,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Excellent ability to hold visual contact around screens, while finding pucks with proper depth maintaining set stance"
                ],
                [
                    'assessment_value_id' => 156,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "80% of visual contact around screens with proper depth and holds stance"
                ],
                [
                    'assessment_value_id' => 157,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Good visual contact around screens with efficient stance and depth control"
                ],
                [
                    'assessment_value_id' => 158,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Average visual contact around screens. Proper depth and stance proper 50% of the time"
                ],
                [
                    'assessment_value_id' => 159,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Difficulty find pucks around screens creating poor depth and stance"
                ],
                [
                    'assessment_value_id' => 160,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Weak visual contact around screen and not maintaining stance. Proper depth inconsistent"
                ],
                [
                    'assessment_value_id' => 161,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Guessing where pucks will be around screens leaving little depth or stance control"
                ],
                [
                    'assessment_value_id' => 162,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Little visual contact around screens and inadequate depth and stance"
                ],
                [
                    'assessment_value_id' => 154,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to maintain exceptional visual contact around screens and keep their eyes on the puck at all times possible. They maintain a perfect depth in the net and within the crease, and hold an ideal stance at all times."
                ],
                [
                    'assessment_value_id' => 155,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to maintain strong visual contact around screens and can keep the puck in their sight most of the time. Their ratio of depth to crease is excellent, and they hold a solid stance, even under pressure."
                ],
                [
                    'assessment_value_id' => 156,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to excel with visual contact when keeping sight of its location around screens. They are wise when choosing a depth within the net and the crease. Their stance predominately leads to success."
                ],
                [
                    'assessment_value_id' => 157,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to maintain visual contact with the puck in most screens. They can find success within their depth choice in and out of the net and crease. Their stance and posturing only require minor adjustments."
                ],
                [
                    'assessment_value_id' => 158,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating have average puck tracking skills when visually maneuvering around screens. Their depth choice within the net could use adjusting to find further success. Modification of stance may be required to strengthen blocking."
                ],
                [
                    'assessment_value_id' => 159,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating could improve puck tracking in various situations with skill-building exercises. Expanded knowledge of depth management would bolster an already growing skill set on the use of depth within the net. Obtaining maximum coverage and blocking ability would come with stance improvement."
                ],
                [
                    'assessment_value_id' => 160,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display weaker visual contact with the puck, especially around screens. They are encouraged to learn more about depth perception and the benefits of how a proper stance can maximize their play."
                ],
                [
                    'assessment_value_id' => 161,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to build tracking skills with or without screens. Based on visual cues, reaction time will be improved over time, and depth management can be implemented with success."
                ],
                [
                    'assessment_value_id' => 162,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to build general foundational skills in visual spotting and tracking. Understanding the usage of depth within the net can build motivation to develop appropriate kinesthetic responses during gameplay."
                ],
                [
                    'assessment_value_id' => 154,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display precise and proper placement and playing of the puck. They show superior puck handling ability in a quiet and controlled manner, and they clear the puck successfully at all times."
                ],
                [
                    'assessment_value_id' => 155,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating have excellent puck placement, and they manipulate the puck in a decisive fashion. They consistently place the puck well throughout game situations and can clear the puck efficiently."
                ],
                [
                    'assessment_value_id' => 156,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display effective, proper placement and playing of the puck most of the time. They have strong stick handling skills, and they make wise decisions on clearing pucks."
                ],
                [
                    'assessment_value_id' => 157,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating show variable proper placement and play the puck. They stick handle well and are successful at clearing pucks most of the time."
                ],
                [
                    'assessment_value_id' => 158,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display intermediate skill with placement and playing of the puck. These players usually clear the puck half of the time during gameplay."
                ],
                [
                    'assessment_value_id' => 159,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to build on their emerging skills of placing and handling the puck. They can develop their confidence in the success of clearing pucks repetitively."
                ],
                [
                    'assessment_value_id' => 160,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could benefit from playing the puck both inside and outside of the crease, increasing options for transition play."
                ],
                [
                    'assessment_value_id' => 161,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically are encouraged to increase puck placement skills by working within their net and practicing puck handling outside of the crease. They will be more successful in clearing the puck with more strengthening exercises."
                ],
                [
                    'assessment_value_id' => 162,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could benefit from foundational training in net navigational play and rudimentary puck handling. They are encouraged to move farther from their net and become more engaged in the play."
                ],
                [
                    'assessment_value_id' => 163,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Relentless compete on broken plays with proper structure and battles hard for pucks"
                ],
                [
                    'assessment_value_id' => 164,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Tireless compete for pucks while maintaining proper structure on brokers plays, and excellent reaction in response "
                ],
                [
                    'assessment_value_id' => 165,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Anticipation of puck trajectory, ability to read shooter and find pucks in scramble 80% of the time,"
                ],
                [
                    'assessment_value_id' => 166,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Good ability to react with proper structure in puck battles and broken plays"
                ],
                [
                    'assessment_value_id' => 167,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Average skill in anticipation of puck trajectory, 50% success in reading shooter, find puck half of the time in scramble"
                ],
                [
                    'assessment_value_id' => 168,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Occasional anticipation of puck trajectory, minimal success with reading shooter, limited success finding puck in scramble"
                ],
                [
                    'assessment_value_id' => 169,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Subpar anticipation of puck trajectory, little success of reading shooter, loses must pucks in scramble"
                ],
                [
                    'assessment_value_id' => 170,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Weak ability to read puck trajectory and  losing the majority of scrambles"
                ],
                [
                    'assessment_value_id' => 171,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Total disregard of future possible plays, misreads all shooters shots, puck is always lost in scramble"
                ],

                [
                    'assessment_value_id' => 163,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to have radical, relentless compete against all shooters. They consistently react with phenomenal success on broken plays and maintain ideal structure even during rapid and pressure-filled game situations. They battle fiercely for the puck and refuse to allow an opportunity for the competition."
                ],
                [
                    'assessment_value_id' => 164,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to compete against all shooters and battle hard for the puck. They react with success to broken plays and maintain excellent structure when faced with opposition."
                ],
                [
                    'assessment_value_id' => 165,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically can anticipate the trajectory of the puck with consistent success. They can read the shooter well and find pucks in a scramble most of the time."
                ],
                [
                    'assessment_value_id' => 166,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating excel at predicting the trajectory of the puck most of the time. They will usually read the shooter well and will react to both regular and broken plays with variable success. Finding pucks in the scramble is one of their displayed strengths."
                ],
                [
                    'assessment_value_id' => 167,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating can predict and react to the trajectory of the puck half of the time. They successfully react to broken plays inconsistently and can find pucks in a scramble when there is a lack of pressure."
                ],
                [
                    'assessment_value_id' => 168,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating can occasionally anticipate the puck's trajectory and, as a result, will block the net with success. Sometimes they can read the shooter and react appropriately. They will have limited success finding the puck in a scramble, however they will make every effort to do so."
                ],
                [
                    'assessment_value_id' => 169,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating can build on their present skill of predicting puck trajectory. They are encouraged to watch film to build the skill of reading the shooter successfully. Additionally, they can improve upon visual tracking, especially within high-pressure situations."
                ],
                [
                    'assessment_value_id' => 170,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to increase their reaction time to various puck trajectory scenarios. They can explore exercises that raise awareness of shooter options within various gameplay situations. Visual tracking skills can be increased with practice."
                ],
                [
                    'assessment_value_id' => 171,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to build their repertoire of understanding basic plays and how these plays tend to unfold in front of the net. They can also grow foundational techniques in responding to a shooter as well as begin to strengthen basic tracking skills."
                ],
                [
                    'assessment_value_id' => 163,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display ideal positioning at all times. Their reaction to a change in the direction of the puck or play is precise and swiftly calculated. The player demonstrates extensive knowledge of opposing shooter options."
                ],
                [
                    'assessment_value_id' => 164,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display aggressive competition to the shooter and follow any change in the shooter's countenance to prevent a possible goal. They show quick reactions to gameplays and consistently maintain excellent structure."
                ],
                [
                    'assessment_value_id' => 165,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display effective positioning most of the time. Their reaction to a change in the direction of the puck or play is quick and usually calculated. The player demonstrates ample knowledge of opposing shooter options."
                ],
                [
                    'assessment_value_id' => 166,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display sufficient positioning. They are variably quick to react to a shooter's options. They can predict shot placement most of the time."
                ],
                [
                    'assessment_value_id' => 167,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display intermediate positioning skills throughout most of the play. Their reaction to a change in the direction of the puck or play is acceptable and often calculated. The player typically demonstrates some knowledge of opposing shooter options."
                ],
                [
                    'assessment_value_id' => 168,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to note the success they find when displaying favorable positioning. Reaction time and swift movements are visible; however, these skills can be improved upon. These players are encouraged to study gameplay of possible shooter options to predict goalie reaction in order to implement into their own knowledge bank."
                ],
                [
                    'assessment_value_id' => 169,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could benefit from skill-building exercises related to positioning. Their reaction to a change in the direction of the puck or play could be more precise. The player is encouraged to observe gameplay where multiple shooter examples are reviewed."
                ],
                [
                    'assessment_value_id' => 170,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically will want to build positional understanding and how it relates to blocking, deflecting, and rebounding the puck. They are encouraged to continue to work on reaction time to all potential plays."
                ],
                [
                    'assessment_value_id' => 171,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could benefit from rudimentary training in positioning skills. Foundational growth could be built regarding their reaction to a change in the puck's direction or play. The player typically would benefit from observation of gameplay where multiple shooter examples are reviewed."
                ],
                [
                    'assessment_value_id' => 172,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Ideal deliberate use of the body through flexibility, precise balance and use of reactions(reflexes)"
                ],
                [
                    'assessment_value_id' => 173,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Precise use of body through flexibility, balance and use of reflexes"
                ],
                [
                    'assessment_value_id' => 174,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Deliberate use of the body through controlled balance and flexibility while using reflexes to support"
                ],
                [
                    'assessment_value_id' => 175,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Excellent use of body with good flexibility, controlled balance and reflexes"
                ],
                [
                    'assessment_value_id' => 176,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Average balance control, flexibility and reaction time with reflexes"
                ],
                [
                    'assessment_value_id' => 177,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Balance, flexibility and reaction time (using reflexes) are weak"
                ],
                [
                    'assessment_value_id' => 178,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Little balance control, use of reflexes and flexibility are inadequate "
                ],
                [
                    'assessment_value_id' => 179,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Stumbling balance and flexibility with no obvious reflexes"
                ],
                [
                    'assessment_value_id' => 180,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Poor balance, not flexible and little reflex"
                ],
                [
                    'assessment_value_id' => 172,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display deliberate, ideal use of the body through flexibility and precise balance. They consistently show exceptional reflexes and reaction time."
                ],
                [
                    'assessment_value_id' => 173,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display excellent flexibility and balance. Their reflexes and reaction time are markedly above their peers."
                ],
                [
                    'assessment_value_id' => 174,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating display deliberate use of the body through controlled balance and flexibility. They use their reflexes and reaction time to support their success."
                ],
                [
                    'assessment_value_id' => 175,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display adequate balance and flexibility. Their reflexes are developed, and their reaction time is maximized through visual tracking."
                ],
                [
                    'assessment_value_id' => 176,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically have average balance and control. They are visibly flexible, however, they have room to expand their dexterity range. Their reflexes and reaction time could be easily built upon with fundamental concentration."
                ],
                [
                    'assessment_value_id' => 177,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating can expand on their balance and control when shifting quickly to react to plays. Flexibility and dexterity may be limited but can be improved over time. Players are encouraged to build upon their already established reflexes and reaction time."
                ],
                [
                    'assessment_value_id' => 178,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to work on balance skills off the ice to transfer to gameplay. They can build  reaction time swiftness through repetitive practice."
                ],
                [
                    'assessment_value_id' => 179,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to understand the benefit of building strength to benefit balance and control. Reaction time expansion is imperative to successful blocking and rebounding."
                ],
                [
                    'assessment_value_id' => 180,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to learn elementary skills and reasoning behind balance and control and how it pertains to their gameplay. Basic flexibility exercises would be beneficial to reaction options."
                ],
                [
                    'assessment_value_id' => 172,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display extremely fluid movements along with precise, rapid reaction time and dexterity."
                ],
                [
                    'assessment_value_id' => 173,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display fluid movements along with swift reaction time to most scenarios. Their movements are highly controlled, and they master the trajectory of the puck exceptionally well."
                ],
                [
                    'assessment_value_id' => 174,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display easy movements with effective reaction time with sufficient dexterity. Predominantly they move with speed, and control most rebounds."
                ],
                [
                    'assessment_value_id' => 175,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display appropriate movements and accelerated reaction time. Their reactions are controlled, and they often are able to directly place rebounds where desired."
                ],
                [
                    'assessment_value_id' => 176,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display average movement fluidity and reaction time with sufficient dexterity. They demonstrate enough speed to contribute to approximately half of the gameplay experiences, occasionally controlling rebounds."
                ],
                [
                    'assessment_value_id' => 177,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could strengthen delayed movements and reaction times. With repeated practice, their speed and control could be sharpened. These players are encouraged to be mindful of frontal net rebounds."
                ],
                [
                    'assessment_value_id' => 178,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating could shorten lag in reaction time and strengthen movement fluidity and dexterity. They are encouraged to avoid creating rebounds in front of the net."
                ],
                [
                    'assessment_value_id' => 179,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to work on foundational reaction time skills. Precision in movements is vital to improve success and mindfulness of their rebound trajectory."
                ],
                [
                    'assessment_value_id' => 180,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating could work on exercises to improve reaction time, and they would benefit from large and small movement kinesthetic conditioning."
                ],
                [
                    'assessment_value_id' => 181,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Perfect stance through all structural components of the body and physical presence to control access and close on pucks"
                ],
                [
                    'assessment_value_id' => 182,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Proper stance through all structural components of the body. Physical presence to access and close on pucks are on point"
                ],
                [
                    'assessment_value_id' => 183,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Excellent stance through structural components. The physical presence with control to access and close on pucks is on point"
                ],
                [
                    'assessment_value_id' => 184,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Majority of the stance through structural components are consistent . The physical presence with control to access and close on pucks is consistent"
                ],
                [
                    'assessment_value_id' => 185,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Average stance through structural components and physical presence with control to access and close on pucks"
                ],
                [
                    'assessment_value_id' => 186,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Stance and structural  components are inadequate. Control to access and close on pucks due to inconsistent play"
                ],
                [
                    'assessment_value_id' => 187,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Weak stance through all structural components and physical presence with control to access and close on pucks"
                ],
                [
                    'assessment_value_id' => 188,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Improper structure through majority of components and physical presence with control to access and close on pucks is minimal "
                ],
                [
                    'assessment_value_id' => 189,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Sloppy stance through structural components and physical presence with control to access and close on pucks is none existent "
                ],
                [
                    'assessment_value_id' => 181,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically retain an ideal stance through all upper and lower body structural components. They project a physical presence that is highly challenging to their opponent and this presence prevents all access to the net. They can close on pucks with lightning speed."
                ],
                [
                    'assessment_value_id' => 182,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically retain excellent stances through all structural components of the upper and lower body. They have a strong physical presence that is challenging to their opponent, which predominantly prevents access to the net. These players can close on pucks swiftly."
                ],
                [
                    'assessment_value_id' => 183,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically retain stance well through all structural components of the upper and lower body. Their physical presence is established, and they tend to provide strong competition against their opponent. The opposing shooter rarely has access to the net and these players can close on puck quickly in most cases."
                ],
                [
                    'assessment_value_id' => 184,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically retain stance well through most parts of either the upper or lower body. They exert variable physical presence to their opponents and at times provide net access to their competition. These players close on most pucks within their reach."
                ],
                [
                    'assessment_value_id' => 185,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display average stance through most parts of their body. Their physical presence tends to be dependant upon the level of opponent pressure. Shooters may have variable access to the net, and this player will close on pucks half of the time when the puck is within their reach."
                ],
                [
                    'assessment_value_id' => 186,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically can strengthen their stance and structural components to find more success within the net. They allow more access to the shooter than is ideal, which can be corrected through structure alignment and improved presence. These players are encouraged to close on pucks more frequently when it is in their control to do so."
                ],
                [
                    'assessment_value_id' => 187,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically require structural alignment to find success in blocking, rebounding, and limiting net access. Additionally, puck closure techniques can be learned through instruction and practice."
                ],
                [
                    'assessment_value_id' => 188,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically can build upon emerging strength and understanding of effective and powerful stance and aligned structure. Practicing body alignment within or outside the net is critical to gain the ability to cover the net and close on pucks in all gameplay situations."
                ],
                [
                    'assessment_value_id' => 189,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to build muscle strength and physical endurance to hold proper stance and alignment. They can work on learning how to close on pucks and clear the net with power."
                ],
                [
                    'assessment_value_id' => 181,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically retain an ideal alert and focused stance when both static and during high-speed plays. Their butterfly is compact and square when this structure is necessary, and their presence is extremely powerful, with all body parts aligned. They close on pucks at all times to prevent any opportunities for their opponents."
                ],
                [
                    'assessment_value_id' => 182,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically retain an excellent stance when static and during positional shifts. They display only minuscule deviation from the ready position in movements within a play, and, when necessary, their butterly tends to be compact and square. Their stance is strong, with predominately all body parts aligned. They close on pucks exceptionally well."
                ],
                [
                    'assessment_value_id' => 183,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically retain their stance when static and during movement, when they are focused. Their butterfly is compact and mostly proportionate. The structure of their position is effective with most body parts aligned, and their presence is displayed well as they close on pucks efficiently."
                ],
                [
                    'assessment_value_id' => 184,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could strengthen their stance technique and overall structure within positions. Readiness positioning within movements is encouraged. Their butterfly is compact yet can become more square. Their presence is adequate, and they close on pucks well when not under pressure."
                ],
                [
                    'assessment_value_id' => 185,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically retain a proper stance while static half of the time, and could work on being consistently aligned during movements. Their butterfly position is occasionally on point when not under pressure, and there is some misalignment in either upper or lower body parts in other positions. They present adequate presence and control to close on pucks well."
                ],
                [
                    'assessment_value_id' => 186,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically retain a variably successful stance while static only when they are focused on the play at hand. They are encouraged to increase awareness and readiness at all times within the net. The components of a successful butterfly structure can be built upon while maintaining more of a dominating presence. Consistency is suggested regarding closing on pucks."
                ],
                [
                    'assessment_value_id' => 187,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating occasionally display proper static stance and could benefit from incorporating more alignment within movements. They are encouraged to strengthen the proportion of their butterfly position and practice bringing more presence to their net space. Closing on pucks is an essential skill they are encouraged to strengthen."
                ],
                [
                    'assessment_value_id' => 188,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to understand the value of proper stance at all times within their positioning. Their alignment, regarding the butterfly and other positions, can be adjusted to bring more success. An increasing powerful presence in the net will bring confidence and also prevent unnecessary gaps in net coverage."
                ],
                [
                    'assessment_value_id' => 189,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically benefit from basic stance and alignment structuring techniques during standstill and movement throughout the play. Further instruction and skill-building are encouraged in placing the upper and lower body in the basic butterfly and other positions."
                ],
                [
                    'assessment_value_id' => 190,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Ideal situational awareness, with perfect depth adjustment and ready position early"
                ],
                [
                    'assessment_value_id' => 191,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Precise depth adjustments, ready early while identifying threats and complete situational awareness "
                ],
                [
                    'assessment_value_id' => 192,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "High ice awareness to identify threats, making proper depth transitions and ready early"
                ],
                [
                    'assessment_value_id' => 193,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Excellent ice awareness to identify threats while being ready early and proper depth transitions "
                ],
                [
                    'assessment_value_id' => 194,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Average situational awareness to identify threats while maintaining proper depth adjustments and being constantly ready early"
                ],
                [
                    'assessment_value_id' => 195,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Inadequate situational awareness to identify threats while maintaining proper depth and being ready early "
                ],
                [
                    'assessment_value_id' => 196,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Weak situational ice awareness with depth overcorrection and rarely ready early"
                ],
                [
                    'assessment_value_id' => 197,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Little ice awareness creating depth problems and not being ready early "
                ],
                [
                    'assessment_value_id' => 198,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "No situational awareness and depth control. Occasional ready early"
                ],
                [
                    'assessment_value_id' => 190,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display ideal situational awareness. They adjust to the most exceptional depth within the net, without delay or hesitation. They become instantly ready after each maneuver to be prepared for the next oppositional threat."
                ],
                [
                    'assessment_value_id' => 191,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display exceptional situational awareness. They adjust to the ideal depth within their net with limited delay. They are swiftly ready for any oppositional threat after maneuvering in and out of position."
                ],
                [
                    'assessment_value_id' => 192,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display excellent situational awareness. They quickly adjust to depth within their net. They are predominantly ready for all oppositional threats."
                ],
                [
                    'assessment_value_id' => 193,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display good situational awareness in most situations. They can identify threats and adjust to a depth perception that will bring opportunity when focused. These players typically are ready for most oppositional threats."
                ],
                [
                    'assessment_value_id' => 194,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display average situational awareness. They can maintain proper depth, but may hesitate between depth transitions. Players with this rating are ready half of the time when presented with a potential threat."
                ],
                [
                    'assessment_value_id' => 195,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating could expand their already present situational awareness through focus and visual cue training. Depth transition could be expedited through strength building and reaction time practice. These players are challenged to adjust to their readiness stance more quickly."
                ],
                [
                    'assessment_value_id' => 196,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating could build upon growing situational awareness. They will want to learn how to avoid depth adjustment overcorrection. Being ready in time for an oppositional threat is something they display, but the skill could be shown more frequently."
                ],
                [
                    'assessment_value_id' => 197,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to build on foundational skills of situational awareness along with swift depth adjustments within the net. These players can improve upon readiness skills to prevent access to the net."
                ],
                [
                    'assessment_value_id' => 198,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to understand the value of situational awareness and depth control. Elementary strength-building along with foundational depth control practice will improve the overall success of their game. Choosing to be ready at all times will enable the player to keep this as a priority during gameplay."
                ],
                [
                    'assessment_value_id' => 190,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically are precise in their anticipation of the puck trajectory. They can read the shooter with extreme accuracy and can find the puck in every scramble. They seem to know and create the ideal depth for themselves at all times. They are already in the ready position early enough to stop a threat."
                ],
                [
                    'assessment_value_id' => 191,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically are excellent at predicting puck trajectory. They can read the shooter well and find the puck in almost every scramble. They read and adjust to the depths that present the most opportunity. They are in the ready position early and predominantly find pucks in scrambles."
                ],
                [
                    'assessment_value_id' => 192,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display excellent anticipation of the puck trajectory. They can read the shooter consistently and find the puck in most scrambles. They adjust to the depths that present the most opportunity most of the time. They tend to be ready early to most threats."
                ],
                [
                    'assessment_value_id' => 193,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display variable anticipation of the puck trajectory. They can read the shooter most of the time and will find the puck in scrambles when focused. They often read and adjust to the depths that present the most opportunity. These players are ready on time to several threats."
                ],
                [
                    'assessment_value_id' => 194,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display average anticipation skills regarding puck trajectory. They read the shooter half of the time and sometimes find the puck in a scramble. They occasionally read and adjust to the depths that present the most opportunity. These players may or may not be ready on time, depending on focus."
                ],
                [
                    'assessment_value_id' => 195,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating display occasional anticipation skills regarding puck trajectory. They read the shooter at times and can find the puck in a scramble, with effort. These players only infrequently read and adjust to the depths that present the most opportunity. They are encouraged to work on becoming ready earlier to defuse a threat."
                ],
                [
                    'assessment_value_id' => 196,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could benefit from anticipation and prediction skill training. Visual cue skills could be explored to spot pucks faster in a scramble. These players can build skills to read and adjust to the depths that present the most opportunity. They are encouraged to understand the crucial skill of becoming and being ready at all times."
                ],
                [
                    'assessment_value_id' => 197,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically are encouraged to build upon anticipation and prediction skills through studying and building a repertoire of visual cues. These skills will also help to create success when spotting pucks in scrambles. These players are challenged to learn about the benefit of the readiness stance and the benefit of this position to their play."
                ],
                [
                    'assessment_value_id' => 198,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically would benefit from foundational training in anticipation, prediction, and visual cues. They can benefit from a basic understanding of using and adjusting to depth for their benefit. Focus and increased reactionary time will strengthen their ability to be ready for an oppositional threat."
                ],
                [
                    'assessment_value_id' => 199,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Perfect body patience and holds feet position, precise tracking movement, and 100% accurate use of blocking pucks"
                ],
                [
                    'assessment_value_id' => 200,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Patient and holds feet while tracking and accurate use of blocking pucks"
                ],
                [
                    'assessment_value_id' => 201,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Body is at patient and proper feet positioning, strong effective tracking and proper blocking of pucks"
                ],
                [
                    'assessment_value_id' => 202,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Good patience and holding feet in position, while staying on pucks (tracking) and blocking of pucks"
                ],
                [
                    'assessment_value_id' => 203,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Average body patience, tracking and blocking pucks is on point half of the game play"
                ],
                [
                    'assessment_value_id' => 204,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Lacking patience and ability to track pucks creates excessive feet movements and improper blocking actions"
                ],
                [
                    'assessment_value_id' => 205,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Weak body patience and active feet movement, hard time finding pucks and blocking appropriately "
                ],
                [
                    'assessment_value_id' => 206,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Patience and not holding feet in position are predominant throughout, tracking and blocking not effective "
                ],
                [
                    'assessment_value_id' => 207,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Little body patience and significant feet movement, visually cannot find pucks which causes wrong blocking choices "
                ],
                [
                    'assessment_value_id' => 199,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to have perfect patience when facing an opponent who is capitalizing on an opportunity. They refuse to cheat themselves of an ideal stance that serves them well. These players track pucks in an exemplary fashion and are always in position to block a shot."
                ],
                [
                    'assessment_value_id' => 200,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating hold their feet exceptionally well while consistently and successfully tracking the puck. They are frequently in a position of opportunity for blocking shots. These players tend to be extremely patient, and they do not cheat themselves on structure or stance while refusing to give the shooter the upper hand."
                ],
                [
                    'assessment_value_id' => 201,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to hold proper foot placement most of the time and have a high level of success tracking pucks. These players are often in the appropriate position for blocking the net. However, their placement can be improved with practice."
                ],
                [
                    'assessment_value_id' => 202,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to hold their feet in an acceptable placement while tracking pucks. They display sufficient positioning for blocking shots and can be variably patient, yet they may sacrifice stance in attempting a save."
                ],
                [
                    'assessment_value_id' => 203,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating hold their feet appropriately half of their gameplay while tracking the puck. They display average positioning for blocking shots and are occasionally patient, but will compromise their stance and structure to achieve a save."
                ],
                [
                    'assessment_value_id' => 204,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating may lack some patience and occasionally allow the shooter to score due to hurried moves. These players are encouraged to build on proper blocking techniques to prevent giving up secondhand rebounds."
                ],
                [
                    'assessment_value_id' => 205,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are challenged to become more patient within the net and during high-pressure situations. Their feet can become more controlled and purposely placed. These players may find themselves out of position for shots and perhaps let stoppable pucks slip by."
                ],
                [
                    'assessment_value_id' => 206,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to limit compromising their patience and feet position when it comes to high-pressure plays. This will help prevent giving off wrongly placed rebounds when they are out of position for a shot. These players can build on basic tracking skills to be able to block pucks effectively."
                ],
                [
                    'assessment_value_id' => 207,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating show little to no patience with the puck. They are encouraged to learn elementary skills in tracking while being mindful of their position within the net to create opposition and better blocking in front of the shooter."
                ],
                [
                    'assessment_value_id' => 199,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display ideal patient and deliberate use of the body. They are precise with the positioning of their feet, blocker, and catcher. They demonstrate an aggressive ability to challenge and block pucks."
                ],
                [
                    'assessment_value_id' => 200,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display patient execution when using their bodies. They are excellent at the positioning of their feet, blocker, and catcher. They demonstrate a solid ability to challenge and block pucks."
                ],
                [
                    'assessment_value_id' => 201,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are relatively patient and typically display a good selection of body choices. Feet, blocker, and catcher positioning are mostly accurate. These players consistently aggressively challenge most pucks."
                ],
                [
                    'assessment_value_id' => 202,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are often patient and typically display variable successful body choices. Feet, blocker, and catcher positioning can be accurate when not under pressure. These players can challenge pucks effectively."
                ],
                [
                    'assessment_value_id' => 203,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display patience and proper positioning half of gameplay. Their feet, blocker, and catcher are in a position that leads to success some of the time. They are only partially demonstrative of challenging an opponent."
                ],
                [
                    'assessment_value_id' => 204,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically can learn to maximize their patience with the puck. They can improve upon already emerging positioning skills. Their feet, blocker, and catcher can be adjusted to lead to more consistent success. Although they can be challenging to their opponent at times, these players are encouraged to block their opponent's tactics more frequently."
                ],
                [
                    'assessment_value_id' => 205,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically could benefit from skill work that teaches more effective body placement and patience within the placement. Positioning of the feet, blocker, and catcher could be modified to create more success. Opportunities would come with more aggression and the challenging of their opponents."
                ],
                [
                    'assessment_value_id' => 206,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to explore the importance of effective body placement and being patient with choices regarding puck handling. This knowledge and practice will likely cause the player to display more confident aggression within their game. Their feet, blocker, and catcher placement will improve with elementary skill-building in positioning and strength conditioning."
                ],
                [
                    'assessment_value_id' => 207,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically would benefit from basic instruction of body placement, intentional blocker and catcher skill work, and an overall increase of aggression in all aspects of the position."
                ],
                [
                    'assessment_value_id' => 208,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Ideal ability to make key saves while performing under pressure displaying confidence in all areas"
                ],
                [
                    'assessment_value_id' => 209,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Shows confidence with an ability to perform under pressure and make key saves"
                ],
                [
                    'assessment_value_id' => 210,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Majority of key saves made, strong performance under pressure, displays confidence in majority of areas"
                ],
                [
                    'assessment_value_id' => 211,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Great ability to recover under pressure and to compete in making key safes. Areas of displaying confidence could be approved on"
                ],
                [
                    'assessment_value_id' => 212,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Makes half of key saves needed, intimidation displayed under pressure half the time, inconsistent in confidence"
                ],
                [
                    'assessment_value_id' => 213,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Displays a lack of confidence under pressure which allows for some key saves being missed"
                ],
                [
                    'assessment_value_id' => 214,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Makes some key saves, unable to compete under pressure, negative body language"
                ],
                [
                    'assessment_value_id' => 215,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Poor body language with little ability to compete under pressure or effort to make key saves"
                ],
                [
                    'assessment_value_id' => 216,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Ineffective at making any saves, falls apart under any pressure, models fear and intimidation"
                ],
                [
                    'assessment_value_id' => 208,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to make key saves in high-performance situations, giving their team an overall advantage. These players have radical confidence and bring overall contagious energy to the ice. They generate trust and confidence in those that play in front of them."
                ],
                [
                    'assessment_value_id' => 209,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to show strong confidence in their ability to stop a puck, and they always make key saves in difficult positions. Players with this rating build confidence within their teammates by being reliable and energetic."
                ],
                [
                    'assessment_value_id' => 210,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating make key saves during challenging situations and will show confidence, often, during gameplay. They are an important component to the success of their team."
                ],
                [
                    'assessment_value_id' => 211,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "This player can recover under pressure and make key saves. However, their confidence can be seen as variable. These players seem to recover well under pressure, which is imperative to their position."
                ],
                [
                    'assessment_value_id' => 212,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to make half of the key saves, showing intimidation in high-pressure situations. They can become more consistent in responding to the pressure of the opposing team's skill."
                ],
                [
                    'assessment_value_id' => 213,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to lack confidence under pressure, which allows key saves to be missed. These players are encouraged to build upon focus and calming techniques to bring this benefit to the net."
                ],
                [
                    'assessment_value_id' => 214,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are beginning to find success with some saves, when performing in the absence of pressure. They are encouraged to build their confidence, over time, with more practice in front of challenging shooters."
                ],
                [
                    'assessment_value_id' => 215,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to find drills that will build their overall confidence in high-pressure situations. As a result, they will bring confidence to their supporting team. These players can improve overall body language to show control and composure."
                ],
                [
                    'assessment_value_id' => 216,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to practice and learn the mechanics of the save before they are put in high-pressure situations. A priority for these players would be to control negative emotions when the game shifts from an ideal situation."
                ],
                [
                    'assessment_value_id' => 208,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically make every key save. They perform phenomenally under pressure and display strong confidence in all areas."
                ],
                [
                    'assessment_value_id' => 209,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically make most key saves. They perform excellently under pressure and display confidence in all areas."
                ],
                [
                    'assessment_value_id' => 210,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically make key saves and perform well under pressure. Their confidence is strong during successful gameplay."
                ],
                [
                    'assessment_value_id' => 211,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating variably make saves and perform adequately under pressure. Their confidence is visible during successful gameplay."
                ],
                [
                    'assessment_value_id' => 212,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically save half of the shot attempts and are only intimidated by key players. These players can display confidence when supported by teammates."
                ],
                [
                    'assessment_value_id' => 213,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically will make some key saves, however they may show inconsistency based on their opponent's aggression."
                ],
                [
                    'assessment_value_id' => 214,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically make occasional saves. They could benefit from patience under pressure and are encouraged to avoid negative body language."
                ],
                [
                    'assessment_value_id' => 215,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are typically encouraged to evaluate their positioning alignment, depth, and aggression when they make a save, in order to note the elements that led to success. They will want to avoid negative body language including obvious signs of fatigue."
                ],
                [
                    'assessment_value_id' => 216,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically would benefit from basic save techniques. With skill-building, confidence has the potential to grow and therefore, the player will find more success under pressure."
                ],
                [
                    'assessment_value_id' => 217,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Body language on point at all times, with calm demeanor and, radically mentally engaged to take away options"
                ],
                [
                    'assessment_value_id' => 218,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Flawless body language, calm demeanor and mentally present to take appropriate action"
                ],
                [
                    'assessment_value_id' => 219,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Alert most of the time, calm demeanor , concentration and body language is faultless"
                ],
                [
                    'assessment_value_id' => 220,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Excellent concentration and body control with a calm demeanor"
                ],
                [
                    'assessment_value_id' => 221,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Alert half of playing time, concentration on and off and displays some body language issues"
                ],
                [
                    'assessment_value_id' => 222,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Lacking self control and concentration which creates an agitated demeanor "
                ],
                [
                    'assessment_value_id' => 223,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Weak ability to maintain self control, concentration weak and lacks self control"
                ],
                [
                    'assessment_value_id' => 224,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Caught off guard a high percentage of time, concentration weak and lacks self control"
                ],
                [
                    'assessment_value_id' => 225,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Completely inattentive, unaware of body language and mostly lack of focus"
                ],
                [
                    'assessment_value_id' => 217,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically are radically alert. They tend to have a calm demeanor at all times, and their concentration is ideal. Their body language shows that they are confident and a considerable threat to their opponent."
                ],
                [
                    'assessment_value_id' => 218,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically display excellent alertness at all times. They tend to have a calm demeanor most of the game, and their concentration is superb. Their body language is strong and confident."
                ],
                [
                    'assessment_value_id' => 219,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically are predominately alert and show a calm demeanor under most pressure situations. Their concentration is mostly on point, and their body language shows strong confidence in situations that lack pressure."
                ],
                [
                    'assessment_value_id' => 220,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to concentrate well during less pressure plays. They have a calmness about their net position adjustments and variably show confidence during gameplay."
                ],
                [
                    'assessment_value_id' => 221,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are generally alert half of gameplay. They display average demeanor, and they could benefit from more consistent concentration."
                ],
                [
                    'assessment_value_id' => 222,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating tend to display some lack of self-control and focus. Their body language would be more beneficial to their gameplay if they avoid displaying an agitated demeanor."
                ],
                [
                    'assessment_value_id' => 223,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically would benefit from reinforcement in concentration and alertness training. Puck tracking skills would create more opportunities within gameplay."
                ],
                [
                    'assessment_value_id' => 224,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically seem to be caught off guard; therefore, concentration and focus skills can be built upon. They are encouraged to display body language that shows they are composed and are controlling their emotions to remain focused during gameplay."
                ],
                [
                    'assessment_value_id' => 225,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to understand and build on primary skills of focusing on the present play and practicing attentiveness while on the ice. Being aware of body language and its impact on their play would likely bring immediate success to their game."
                ],
                [
                    'assessment_value_id' => 217,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically are radically alert at all times. They tend to follow the puck precisely, and their concentration is ideal. They mentally engage 100% to take away shooter options."
                ],
                [
                    'assessment_value_id' => 218,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically are predominately alert during the entire gameplay. They follow the puck extremely well, and their concentration is admirable. They tend to mentally engage most of the time to take away shooter options."
                ],
                [
                    'assessment_value_id' => 219,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are typically extremely alert, most of the time. They follow the puck well, and they tend to display excellent concentration. Their mental engagement takes away several shooter options."
                ],
                [
                    'assessment_value_id' => 220,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are strongly, yet variably, alert through most gameplay. They follow the puck adequately and have good concentration. Their mental engagement is sufficient to take away some shooter options."
                ],
                [
                    'assessment_value_id' => 221,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are typically alert half of gameplay. They could benefit from more consistent concentration and mental adjustments to take away more shooter options."
                ],
                [
                    'assessment_value_id' => 222,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating can expand on their awareness during gameplay. They can build on their concentration and mental focus to take away shooter options."
                ],
                [
                    'assessment_value_id' => 223,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically would benefit from reinforcement in concentration and alertness training. Puck tracking skills would create more opportunities within gameplay and build upon net positioning awareness."
                ],
                [
                    'assessment_value_id' => 224,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating are encouraged to practice concentration and mental alertness drills to find success that may be missing from their game. Tracking and focus will enable the player to limit their opponent's shooting options."
                ],
                [
                    'assessment_value_id' => 225,
                    'created_at'          => $timestamp,
                    'updated_at'          => $timestamp,
                    'statement'           => "Players with this rating typically benefit from foundational training in attentiveness, engagement, and focus building. These skills will help encourage them to find success in all aspects of their gameplay."
                ],
            ]);
        }
    }
}
