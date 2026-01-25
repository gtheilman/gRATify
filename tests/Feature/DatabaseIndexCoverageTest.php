<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function indexNamesFor(string $table): array
{
    $driver = DB::connection()->getDriverName();

    if ($driver === 'sqlite') {
        return collect(DB::select("PRAGMA index_list('$table')"))
            ->pluck('name')
            ->all();
    }

    if ($driver === 'mysql') {
        $schema = DB::connection()->getDatabaseName();
        return collect(DB::select(
            'SELECT INDEX_NAME AS name FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$schema, $table]
        ))->pluck('name')->all();
    }

    test()->markTestSkipped("Index introspection not implemented for {$driver}");
    return [];
}

it('adds foreign key and ordering indexes for core tables', function () {
    $expected = [
        'assessments' => [
            'assessments_user_id_index',
            'assessments_user_created_index',
        ],
        'attempts' => [
            'attempts_presentation_created_index',
        ],
        'questions' => [
            'questions_assessment_id_index',
            'questions_assessment_sequence_index',
        ],
        'answers' => [
            'answers_question_id_index',
            'answers_question_sequence_index',
        ],
        'presentations' => [
            'presentations_assessment_id_index',
            'presentations_assessment_user_index',
            'presentations_assessment_created_index',
        ],
    ];

    foreach ($expected as $table => $indexes) {
        $names = indexNamesFor($table);
        foreach ($indexes as $index) {
            expect($names)->toContain($index);
        }
    }
});
