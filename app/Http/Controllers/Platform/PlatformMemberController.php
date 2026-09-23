<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMemberRequest;
use App\Models\Member;
use App\Models\PlatformUser;
use App\Services\TenantConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\MemberWelcomeMail;
use App\Services\AdminActionLogger;
use RuntimeException;
use App\Http\Requests\BulkMemberImportRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\Platform\BulkMemberImportService;
use App\Exceptions\BulkMemberImportException;

class PlatformMemberController extends Controller
{


    public function store(
        StoreMemberRequest $request,
        TenantConnectionService $tenantConnectionService
    ): JsonResponse {
        $user = $request->user();

        if (!$user instanceof PlatformUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your platform account is not active.',
            ], 403);
        }

        if ($user->role !== 'setup_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only a setup administrator can add members.',
            ], 403);
        }

        $church = $user->church;

        if (!$church) {
            return response()->json([
                'success' => false,
                'message' => 'No church is associated with your account.',
            ], 422);
        }

        if ($church->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your church is not active.',
            ], 422);
        }

        try {
            /*
             * Connect to this setup administrator's tenant.
             *
             * No database credentials are exposed to the frontend.
             */
            $tenantConnectionService->connect($church);

            $data = $request->validated();

            /*
             * Handle profile photo.
             */
            if ($request->hasFile('profile_photo')) {
                $data['profile_photo'] = $request
                    ->file('profile_photo')
                    ->store('members/photos', 'public');
            }

            /*
             * Handle couple photo.
             */
            if ($request->hasFile('couple_pic')) {
                $data['couple_pic'] = $request
                    ->file('couple_pic')
                    ->store('members/couplepics', 'public');
            }

            /*
             * New members are active by default.
             */
            if (!array_key_exists('status_flag', $data)) {
                $data['status_flag'] = true;
            }

            $member = Member::create($data);

            /*
             * Send welcome email if an email address exists.
             */
            if (!empty($member->email)) {
                try {
                    Mail::to($member->email)
                        ->send(new MemberWelcomeMail($member));
                } catch (\Throwable $mailEx) {
                    Log::warning(
                        'Member welcome email failed.',
                        [
                            'member_id' => $member->id,
                            'email' => $member->email,
                            'error' => $mailEx->getMessage(),
                        ]
                    );
                }
            }

            AdminActionLogger::log(
                action: 'member.create',
                description: "Member created '{$member->first_name}'",
                modelType: Member::class,
                modelId: $member->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Member created successfully.',
                'data' => [
                    'member' => [
                        ...$member->toArray(),
                        'date_of_birth' => $member->date_of_birth?->format('Y-m-d'),
                        'wedding_date' => $member->wedding_date?->format('Y-m-d'),
                    ],
                ],
            ], 201);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error(
                'Platform member creation failed.',
                [
                    'platform_user_id' => $user->id,
                    'church_id' => $church->id,
                    'error' => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Unable to create the member.',
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user instanceof PlatformUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->status !== 'active' || $user->role !== 'setup_admin') {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to manage members.',
            ], 403);
        }

        try {
            $perPage = (int) $request->query('per_page', 20);
            $perPage = min(max($perPage, 1), 100);

            $query = Member::query()
                ->where('status_flag', true);

            if ($request->filled('search')) {
                $search = trim((string) $request->query('search'));

                $query->where(function ($q) use ($search) {
                    if (ctype_digit($search)) {
                        $q->orWhere('id', (int) $search);
                    }

                    $q->orWhere('family_name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile_number', 'like', "%{$search}%");
                });
            }

            $members = $query
                ->orderByDesc('created_at')
                ->paginate($perPage)
                ->withQueryString();

            $members->getCollection()->transform(function (Member $member) {
                $member->setAttribute(
                    'date_of_birth',
                    $member->date_of_birth?->format('Y-m-d')
                );

                $member->setAttribute(
                    'wedding_date',
                    $member->wedding_date?->format('Y-m-d')
                );

                return $member;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'members' => $members,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Platform member list failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load members.',
            ], 500);
        }
    }


    public function importTemplate(
        BulkMemberImportService $importService
    ): \Symfony\Component\HttpFoundation\StreamedResponse {
        return $importService->downloadTemplate();
    }


    public function importPreview(
        BulkMemberImportRequest $request,
        BulkMemberImportService $importService
    ): JsonResponse {
        try {
            $preview = $importService->preview(
                $request->file('file')
            );

            return response()->json([
                'success' => true,
                'message' => 'CSV preview generated successfully.',
                'data' => $preview,
            ]);
        } catch (BulkMemberImportException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [
                    'rows' => $e->rows(),
                ],
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Member CSV preview failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to process the CSV file.',
            ], 500);
        }
    }

    public function import(
        BulkMemberImportRequest $request,
        BulkMemberImportService $importService
    ): JsonResponse {
        try {
            $result = $importService->import(
                $request->file('file')
            );

            return response()->json([
                'success' => true,
                'message' =>
                "{$result['created']} members imported successfully.",
                'data' => $result,
            ], 201);
        } catch (BulkMemberImportException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [
                    'rows' => $e->rows(),
                ],
            ], 422);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Bulk member import database constraint failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' =>
                'A member with the same email or mobile number was created while this import was being processed. No members were imported.',
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Bulk member import failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' =>
                'Unable to import members. No members were imported.',
            ], 500);
        }
    }
}
