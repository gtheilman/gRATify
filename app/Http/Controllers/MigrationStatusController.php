<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrationStatusController extends Controller
{
    private const REQUIRED_MIGRATIONS = [
        '2026_01_25_200142_add_unique_attempts_index',
        '2026_01_25_203000_add_foreign_key_indexes',
        '2026_01_25_205500_add_presentation_lookup_index',
        '2026_01_25_211000_add_attempts_presentation_created_index',
        '2026_01_25_211100_add_assessments_user_created_index',
        '2026_01_25_213000_add_presentations_assessment_created_index',
        '2026_01_25_220000_fix_presentations_assessment_user_index',
        '2026_01_25_221000_add_foreign_keys',
    ];

    public function status(): JsonResponse
    {
        $user = auth('web')->user();
        $role = $user?->role;
        $normalizedRole = $role === 'poobah' ? 'admin' : $role;

        if (! $user || $normalizedRole !== 'admin') {
            return $this->errorResponse('forbidden', null, 403);
        }

        return response()->json($this->buildStatusPayload());
    }

    public function publicStatus(): JsonResponse
    {
        return response()->json($this->buildStatusPayload());
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStatusPayload(): array
    {
        if (! Schema::hasTable('migrations')) {
            return [
                'ok' => false,
                'missing' => self::REQUIRED_MIGRATIONS,
                'required' => self::REQUIRED_MIGRATIONS,
                'hasMigrationsTable' => false,
            ];
        }

        $applied = DB::table('migrations')->pluck('migration')->all();
        $missing = array_values(array_diff(self::REQUIRED_MIGRATIONS, $applied));

        return [
            'ok' => empty($missing),
            'missing' => $missing,
            'required' => self::REQUIRED_MIGRATIONS,
            'hasMigrationsTable' => true,
        ];
    }
}
