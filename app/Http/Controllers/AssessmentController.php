<?php

namespace App\Http\Controllers;

use App\Http\Resources\AssessmentResource;
use App\Models\Assessment;
use App\Models\Presentation;
use App\Http\Requests\StoreAssessmentRequest;
use App\Http\Requests\UpdateAssessmentRequest;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Bitly;


class AssessmentController extends Controller
{
    /**
     * database id of the assessment
     *
     * @var integer $id
     */
    public $id = null;
    /**
     * user id of the owner of the assessment
     *
     * @var integer $user_id ;
     */
    public $user_id = null;
    /**
     * Title  of the assessment
     *
     * @var string $title ;
     */
    public $title = null;
    /**
     * Day time the assessment was created
     *
     * @var integer $created_at ;
     */
    public $created_at = null;
    /**
     * Day time the assessment was last updated
     *
     * @var object $updated_at ;
     */
    public $updated_at = null;
    /**
     * Number of minutes allowed to complete quiz
     *
     * @var integer $time_limit ;
     */
    public $time_limit = null;
    /**
     * Name of the course the quiz belongs to
     *
     * @var string $course ;
     */
    public $course = null;
    /**
     * Day time the assessment is planned to be scheduled
     *
     * @var object $scheduled_at ;
     */
    public $scheduled_at = null;
    /**
     * Misc information about the quiz
     *
     * @var string $memo ;
     */
    public $memo = null;
    /**
     * URl given to students manually accessing the quiz
     *
     * @var string $short_url ;
     */
    public $short_url = null;
    /**
     * password to open the quiz
     *
     * @var string $password ;
     */
    public $password = null;
    /**
     * Questions associated with the quiz
     *
     * @var array $questions ;
     */
    public $questions = array();
    /**
     * Extension of the uploaded file
     *
     * @var string $extension ;
     */
    public $extension = null;
    /**
     * Location of uploaded file
     *
     * @var string $path
     */
    public $path = null;

    /**
     * Location exam information inside zipped QTI file
     *
     * @var string $path
     */
    public $xmlFileLocation = null;
    /**
     * Name of the xml file exam information inside zipped QTI file (without xml extension)
     *
     * @var string $path
     */
    public $xmlFileName = null;

    /**
     * QTI Version
     *
     * @var string $path
     */
    public $qtiVersion = null;


    /**
     * Class constructor.
     *
     * @param
     * @param
     * @param
     */
    public function __construct()
    {
        $this->authorizeResource(Assessment::class, 'assessment');
        /*
                $this->initialize();


                $this->middleware('auth:api', ['except' => ['initialize']]);*/


    }

    /**
     * Initialise the assessment.
     */
    public function initialize()
    {
        $this->id = null;
        $this->user_id = Auth::id();
        $this->title = null;
        //$this->updated_at = null;
        $this->time_limit = null;
        $this->course = null;
        $this->scheduled_at = null;
        $this->memo = null;
        $this->password = null;
        $this->extension = null;
        //$this->path = null;
        $this->questions = array();
    }


    /**
     * Display a listing of the resource.
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function index(Request $request)
    {
        try {
            $userId = auth('web')->id();
        } catch (\Exception $e) {
            return response()->json(['status' => 'Not Logged In'], 401);

        }

        $baseQuery = Assessment::query()
            ->with(['user:id,username'])
            ->withCount('presentations')
            ->orderByDesc('created_at');

        $user = auth('web')->user();

        if ($userId == config('grat.admin_id') || ($user && $user->role === 'admin')) {
            $assessments = $baseQuery->get();
        } else {
            $assessments = $baseQuery
                ->where('user_id', $userId)
                ->get();
        }


        if (isset($assessments->scheduled_at)) {
            $assessments->scheduled_at = date_format($assessments->scheduled_at, 'Y-m-d');
        }

        return AssessmentResource::collection($assessments);

    }


    /**
     * Display a listing of the resource per user.
     * @param int $user_id
     * @return string
     */
    public function listAssessmentsByUser($user_id)
    {
        $user = auth('web')->user();

        if (auth('web')->id() == config('grat.admin_id') || ($user && $user->role === 'admin')) {
            $assessments = Assessment::query()
                ->where('user_id', $user_id)
                ->with(['user:id,username'])
                ->orderByDesc('created_at')
                ->get();
            return AssessmentResource::collection($assessments);
        } else {
            return response()->json(['status' => 'Forbidden'], 403);
        }


    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('assessments.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAssessmentRequest $request)
    {


        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'Not Logged In'], 401);
        }
        $this->authorize('create', Assessment::class);

        $assessment = new Assessment([
            'title' => $request->get('title'),
            'time_limit' => $request->get('time_limit'),
            'course' => $request->get('course'),
            //'scheduled_at' => $request->get('scheduled_at'),
            'penalty_method' => $request->get('penalty_method'),
            'memo' => $request->get('memo'),
            'active' => $request->get('active'),
        ]);

        $assessment->user_id = $user->id;
        $assessment->password = bin2hex(openssl_random_pseudo_bytes(4));

        // Build the student client URL from the current host; no separate env needed now that client lives with server.
        $clientUrl = rtrim($request->getSchemeAndHttpHost(), '/') . '/client/' . $assessment->password;
        $preferredProvider = $request->get('shortlink_provider');
        [$shortUrl, $shortError] = $this->generateShortUrl($clientUrl, $preferredProvider);
        $assessment->short_url = $shortUrl;
        $assessment->bitly_error = $shortError;
        $assessment->save();
        return response()->json($assessment, 201);

    }

    /**
     * Display the specified resource.
     *
     *
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *  Put the nested array of assessment, questions and answers here
     * @param integer $assessment_id
     * @return string
     */
    public function edit(Assessment $assessment)
    {
        $assessment->load('questions.answers');
        // Keep relations sorted without turning them into dirty attributes.
        $assessment->setRelation(
            'questions',
            $assessment->questions->sortBy(fn ($question) => $question->sequence)->values()
        );

        $presentations = Presentation::with('attempts')
            ->where('assessment_id', $assessment->id)
            ->get()
            ->map(function ($presentation) {
                $presentation->group_label = $presentation->user_id;

                return $presentation;
            });

        $assessment->setRelation(
            'presentations',
            $presentations->sortBy(fn ($presentation) => $presentation->id)->values()
        );

        // Ensure short URL exists (or fallback) and surface Bitly errors for the UI to show a dismissible warning.
        $request = request();
        $clientUrl = rtrim($request->getSchemeAndHttpHost(), '/') . '/client/' . ($assessment->password ?? '');
        $bitlyError = null;
        $preferredProvider = $request->query('shortlink_provider');
        $newShort = $assessment->short_url;
        if (! $newShort) {
            [$newShort, $bitlyError] = $this->generateShortUrl($clientUrl, $preferredProvider);
        }
        if ($newShort !== $assessment->short_url) {
            $assessment->short_url = $newShort;
            $assessment->save();
        }
        $assessment->bitly_error = $bitlyError;

        return response()->json($assessment);


    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param integer $assessment_id
     * @return string
     */
    public function update(\App\Http\Requests\UpdateAssessmentInlineRequest $request, Assessment $assessment)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'Not Logged In'], 401);
        }

        $assessment->title = $request->get('title');
        $assessment->time_limit = $request->get('time_limit') ?? null;
        $assessment->course = $request->get('course') ?? null;
        $assessment->penalty_method = $request->get('penalty_method') ?? null;
        $assessment->active = $request->get('active') ?? true;
        $scheduledAt = $request->get('scheduled_at');
        if (is_string($scheduledAt) && str_contains($scheduledAt, 'T')) {
            $scheduledAt = substr($scheduledAt, 0, strpos($scheduledAt, 'T'));
        }
        $assessment->scheduled_at = $scheduledAt;

        $assessment->memo = $request->get('memo') ?? null;
        $this->authorize('update', $assessment);

        $assessment->save();
        return response()->json($assessment);
    }

    /**
     * Bulk update assessment, questions, and answers in one request.
     */
    public function bulkUpdate(Request $request, Assessment $assessment)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'Not Logged In'], 401);
        }

        $this->authorize('update', $assessment);

        $hasResponses = Presentation::where('assessment_id', $assessment->id)->exists();
        if ($hasResponses) {
            return response()->json(['status' => 'Locked'], 403);
        }

        $validated = $request->validate([
            'assessment' => ['required', 'array'],
            'assessment.title' => ['required', 'string'],
            'assessment.course' => ['nullable', 'string'],
            'assessment.memo' => ['nullable', 'string'],
            'assessment.scheduled_at' => ['nullable', 'string'],
            'assessment.time_limit' => ['nullable'],
            'assessment.penalty_method' => ['nullable', 'string'],
            'assessment.active' => ['nullable', 'boolean'],
            'questions' => ['array'],
            'questions.*.id' => ['required', 'integer'],
            'questions.*.title' => ['nullable', 'string'],
            'questions.*.stem' => ['required', 'string'],
            'questions.*.sequence' => ['required', 'integer'],
            'questions.*.answers' => ['array'],
            'questions.*.answers.*.id' => ['required', 'integer'],
            'questions.*.answers.*.answer_text' => ['required', 'string'],
            'questions.*.answers.*.correct' => ['nullable', 'boolean'],
            'questions.*.answers.*.sequence' => ['required', 'integer'],
        ]);

        $assessmentData = $validated['assessment'];
        $questionPayloads = $validated['questions'] ?? [];

        DB::transaction(function () use ($assessment, $assessmentData, $questionPayloads) {
            $assessment->title = $assessmentData['title'];
            $assessment->time_limit = $assessmentData['time_limit'] ?? null;
            $assessment->course = $assessmentData['course'] ?? null;
            $assessment->penalty_method = $assessmentData['penalty_method'] ?? null;
            $assessment->active = $assessmentData['active'] ?? true;
            if (!empty($assessmentData['scheduled_at']) && strpos($assessmentData['scheduled_at'], 'T') !== false) {
                $assessment->scheduled_at = substr($assessmentData['scheduled_at'], 0, strpos($assessmentData['scheduled_at'], 'T'));
            } else {
                $assessment->scheduled_at = $assessmentData['scheduled_at'] ?? null;
            }
            $assessment->memo = $assessmentData['memo'] ?? null;
            $assessment->save();

            $questionIds = collect($questionPayloads)->pluck('id')->filter()->values();
            if ($questionIds->isNotEmpty()) {
                $validQuestionIds = Question::where('assessment_id', $assessment->id)
                    ->whereIn('id', $questionIds)
                    ->pluck('id');
                if ($validQuestionIds->count() !== $questionIds->count()) {
                    throw ValidationException::withMessages([
                        'questions' => ['Question does not belong to assessment.'],
                    ]);
                }
            }

            $answerPayloads = [];
            foreach ($questionPayloads as $questionData) {
                foreach ($questionData['answers'] ?? [] as $answerData) {
                    $answerPayloads[] = [
                        'question_id' => $questionData['id'],
                        'answer' => $answerData,
                    ];
                }
            }

            $answerIds = collect($answerPayloads)->pluck('answer.id')->filter()->values();
            if ($answerIds->isNotEmpty()) {
                $validAnswerIds = Answer::whereIn('question_id', $questionIds)
                    ->whereIn('id', $answerIds)
                    ->pluck('id');
                if ($validAnswerIds->count() !== $answerIds->count()) {
                    throw ValidationException::withMessages([
                        'answers' => ['Answer does not belong to question.'],
                    ]);
                }
            }

            if ($questionIds->isNotEmpty()) {
                $questionRows = collect($questionPayloads)->map(function ($questionData) use ($assessment) {
                    return [
                        'id' => $questionData['id'],
                        'assessment_id' => $assessment->id,
                        'title' => $questionData['title'] ?? $questionData['stem'],
                        'stem' => $questionData['stem'],
                        'sequence' => $questionData['sequence'],
                        'updated_at' => now(),
                    ];
                })->all();
                DB::table('questions')->upsert(
                    $questionRows,
                    ['id'],
                    ['title', 'stem', 'sequence', 'updated_at']
                );
            }

            if (!empty($answerPayloads)) {
                $answerRows = collect($answerPayloads)->map(function ($item) {
                    $answerData = $item['answer'];
                    return [
                        'id' => $answerData['id'],
                        'question_id' => $item['question_id'],
                        'answer_text' => $answerData['answer_text'],
                        'sequence' => $answerData['sequence'],
                        'correct' => (bool) ($answerData['correct'] ?? false),
                        'updated_at' => now(),
                    ];
                })->all();
                DB::table('answers')->upsert(
                    $answerRows,
                    ['id'],
                    ['answer_text', 'sequence', 'correct', 'updated_at']
                );
            }
        });

        return response()->json(['status' => 'saved']);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param integer $assessment_id
     * @return string
     */
    public function destroy(Request $request, Assessment $assessment)
    {

        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'Not Logged In'], 401);
        }
        $this->authorize('delete', $assessment);

        $assessment->delete();
        return response()->noContent();


    }

    /**
     * Show the student password for starting the assessment
     * @param integer $assessment_id
     * @return \Illuminate\Http\Response
     */
    public function showPassword($assessment_id)
    {

        $assessment = Assessment::where('id', $assessment_id)->with(['questions.answers'])->get();

        return view('assessments.showPassword', compact('assessment'));

    }


    /**
     * Return the attempts for a given assessment
     *
     * @param int $assessment_id
     * @return string
     */
    public function assessmentAttempts(int $assessment_id)
    {
        $assessment = Assessment::with('questions.answers')->find($assessment_id);
        if (!$assessment) {
            return response()->json(['status' => 'Not Found'], 404);
        }

        $assessment->questions = $assessment->questions->sortBy(function ($question) {
            return $question->sequence;
        })->values();

        $presentations = Presentation::with('attempts')
            ->where('assessment_id', $assessment_id)
            ->get();

        $assessment->presentations = Presentation::with('attempts')
            ->where('assessment_id', $assessment_id)
            ->get()
            ->map(function ($presentation) {
                $presentation->group_label = $presentation->user_id;

                return $presentation;
            });

        return response()->json($assessment);
    }

    /**
     * Generate a short URL using Bitly first, then TinyURL, else fall back to the original.
     *
     * @return array{0:string,1:?string}
     */
    private function generateShortUrl(string $clientUrl, ?string $preferredProvider = null): array
    {
        $errors = [];
        $preferred = strtolower((string) $preferredProvider);
        $providerOrder = $preferred === 'tinyurl' ? ['tinyurl', 'bitly'] : ['bitly', 'tinyurl'];

        foreach ($providerOrder as $provider) {
            if ($provider === 'bitly' && config('bitly.accesstoken')) {
                try {
                    $bitly = Bitly::getURL($clientUrl);
                    if ($bitly) {
                        return [$bitly, $errors ? implode(' | ', $errors) : null];
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Bitly: ' . $e->getMessage();
                }
            }

            if ($provider === 'tinyurl' && config('services.tinyurl.token')) {
                try {
                    $response = Http::withToken(config('services.tinyurl.token'))
                        ->acceptJson()
                        ->post('https://api.tinyurl.com/create', [
                            'url' => $clientUrl,
                            'domain' => config('services.tinyurl.domain', 'tinyurl.com'),
                        ]);

                    if ($response->successful()) {
                        $tiny = data_get($response->json(), 'data.tiny_url');
                        if ($tiny) {
                            return [$tiny, $errors ? implode(' | ', $errors) : null];
                        }
                    }

                    $errors[] = 'TinyURL: ' . ($response->body() ?: 'unknown error');
                } catch (\Throwable $e) {
                    $errors[] = 'TinyURL: ' . $e->getMessage();
                }
            }
        }

        return [$clientUrl, $errors ? implode(' | ', $errors) : null];
    }

}
