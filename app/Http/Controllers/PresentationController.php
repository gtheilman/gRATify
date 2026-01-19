<?php

namespace App\Http\Controllers;

use App\Http\Resources\PublicAssessmentResource;
use App\Http\Resources\PublicPresentationResource;
use App\Models\Assessment;
use App\Models\Attempt;
use App\Models\Presentation;
use App\Models\Question;
use App\Models\User;
use App\Services\Scoring\ScoringManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class PresentationController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(private ScoringManager $scoringManager)
    {
        $this->middleware('auth:web', ['except' => ['store', 'show', 'getAssessment', 'scoreByCredentials']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return
     */
    public function store($password, $user_id)
    {

        $assessment = Assessment::with('questions.answers')->firstWhere('password', $password);
        if (! $assessment) {
            return response()->json(['message' => 'Not found'], 404);
        }
        if (!$assessment->active) {
            return response()->json(['status' => 'Forbidden'], 403);
        }
        $assessment->setRelation(
            'questions',
            $assessment->questions
                ->sortBy(fn ($question) => $question->sequence)
                ->values()
                ->map(function ($question) {
                    $question->setRelation(
                        'answers',
                        $question->answers->sortBy(fn ($answer) => $answer->sequence)->values()
                    );

                    return $question;
                })
        );

        $presentationCreated = false;
        $presentation = Presentation::where('assessment_id', $assessment->id)
            ->where('user_id', $user_id)
            ->first();

        if (! $presentation) {
            $presentation = new Presentation;
            $presentation->user_id = $user_id;
            $presentation->assessment_id = $assessment->id;
            $presentation->save();
            $presentationCreated = true;
        }
        $presentation->assessment = $assessment;
        $attempts = Attempt::with('answer')
            ->where('presentation_id', $presentation->id)
            ->get();

        $presentation->setRelation('assessment', $assessment);
        $presentation->setRelation('attempts', $attempts);

        return (new PublicPresentationResource($presentation))
            ->response()
            ->setStatusCode($presentationCreated ? 201 : 200);
    }


    /**
     * Score a presentation.
     *
     * @param string $password
     * @param string $user_id
     * @return
     */
    public function scoreByCredentials(\App\Http\Requests\ScoreByCredentialsRequest $request)
    {
        $password = $request->route('password');
        $user_id = $request->route('user_id');

        $assessment = Assessment::with('questions')->firstWhere('password', $password);
        if (! $assessment) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $assessment->questions = $assessment->questions->sortBy(function ($question) {
            return $question->sequence;
        });

        $presentation = Presentation::where('assessment_id', $assessment->id)
            ->where('user_id', $user_id)
            ->first();

        if (!$presentation) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $presentation->assessment = $assessment;
        $attempts = Attempt::with('answer')->where('presentation_id', $presentation->id)->get();

        $questions = collect([]);

        foreach ($assessment->questions as $question) {
            $questionAttempts = collect([]);
            foreach ($attempts as $attempt) {
                if ($attempt->answer->question_id === $question->id) {
                    $questionAttempts->push($attempt);
                }
            }

            $question->attempts = $questionAttempts;
            $question->attempts = $question->attempts->sortBy(function ($attempts) {
                return $attempts->created_at;
            });
            // Ensure consumers see the actual stem instead of generic titles
            $question->title = $question->stem;
            $questions->push($question);
        }

        $presentation->assessment->questions = $questions;

        $scoring = $this->scoringManager
            ->forScheme(config('scoring.default', 'geometric-decay'))
            ->scoreQuestions($presentation->assessment->questions);

        foreach ($presentation->assessment->questions as $question) {
            $question->score = $scoring['questionScores'][$question->id] ?? 0;
        }
        $presentation->score = $scoring['total'];
        return response()->json($presentation->score);
    }

    /**
     * Score   presentations by assessment_id
     *
     * @param integer $assessment_id
     * @return
     */
    public function scoreByAssessmentId(Request $request, int $assessment_id)
    {

        $assessment = Assessment::findOrFail($assessment_id);
        $this->authorize('viewForAssessment', [Presentation::class, $assessment]);

        $presentations = Presentation::where('assessment_id', $assessment_id)->get();

        $scoredPresentations = collect([]);

        $scheme = $request->query('scheme', config('scoring.default', 'geometric-decay'));

        foreach ($presentations as $presentation) {
            $scoredPresentation = $this->scoreByPresentationId($presentation->id, $scheme);
            $scoredPresentations->push($scoredPresentation);
        }
        return response()->json($scoredPresentations);
    }


    /**
     * Score a presentation by presentation_id
     *
     * @param integer $presentation_id
     * @param string|null $scheme
     * @return
     */
    private function scoreByPresentationId(int $presentation_id, ?string $scheme = null)
    {
        $presentation = Presentation::find($presentation_id);
        $assessment = Assessment::find($presentation->assessment_id);
        $assessment->questions = $assessment->questions->sortBy(function ($question) {
            return $question->sequence;
        });


        $presentation->assessment = $assessment;
        $attempts = Attempt::with('answer')->where('presentation_id', $presentation->id)->get();

        $assessmentSum = 0;
        $questionCount = 0;
        $questions = collect([]);

        foreach ($assessment->questions as $question) {
            $questionAttempts = collect([]);
            foreach ($attempts as $attempt) {
                if ($attempt->answer->question_id === $question->id) {
                    $questionAttempts->push($attempt);
                }
            }

            $question->attempts = $questionAttempts;
            $question->attempts = $question->attempts->sortBy(function ($attempts) {
                return $attempts->created_at;
            });
            $questions->push($question);
        }

        $presentation->assessment->questions = $questions;

        $scoring = $this->scoringManager
            ->forScheme($scheme ?? config('scoring.default', 'geometric-decay'))
            ->scoreQuestions($presentation->assessment->questions);

        foreach ($presentation->assessment->questions as $question) {
            $question->score = $scoring['questionScores'][$question->id] ?? 0;
        }
        $presentation->score = $scoring['total'];
        $presentation->user_id = $presentation->user_id;

        return $presentation;
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $assessment = Assessment::with('questions.answers')->where('password', $id);
        $assessment->questions = $assessment->questions->sortBy(function ($question) {
            return $question->sequence;
        });


        return response()->json($assessment);
    }


    /**
     * Get the assessment questions
     *
     * @param string $presentation_id
     * @return
     */
    public function getAssessment($presentation_id)
    {
        $presentation = Presentation::find($presentation_id);
        if (! $presentation) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $assessment_id = $presentation->assessment_id;

        $assessment = Assessment::with('questions.answers')->find($assessment_id);
        $assessment->setRelation(
            'questions',
            $assessment->questions
                ->sortBy(fn ($question) => $question->sequence)
                ->values()
                ->map(function ($question) {
                    $question->setRelation(
                        'answers',
                        $question->answers->sortBy(fn ($answer) => $answer->sequence)->values()
                    );

                    return $question;
                })
        );

        $presentation->setRelation('assessment', $assessment);

        return new PublicAssessmentResource($assessment);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * List asssements that have been taken
     *
     *
     * @return string
     */
    public function completed()
    {
        $this->authorize('viewAny', Presentation::class);

        $collection = collect([]);
        $presentations = Presentation::all();
        foreach ($presentations as $presentation) {
            try {
                $assessment = Assessment::find($presentation->assessment_id);
                $assessment->user = User::find($assessment->user_id);
                $presentation->title = $assessment->title;
                $presentation->name = $assessment->user->name;
                $presentation->assessment = $assessment;
                if ($presentation->assessment->user_id != config('grat.admin_id')) {
                    $collection->push($presentation);
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        return response()->json($collection);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
