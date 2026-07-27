<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

/**
 * GET /api/announcements
 * Consumed by AnnouncementsScreen.kt (Task 11).
 *
 * Response shape MUST match Models.kt -> List<Announcement>:
 *   [{ id, title, content, audience, postedDate, expiryDate }, ...]
 *
 * Filters: only published, non-expired announcements, and only
 * those matching the logged-in user's audience (or "All").
 */
class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $roleName = $user->role->role_name ?? 'Student';

        // Map role -> audience value used in the `announcements` table.
        $audience = match ($roleName) {
            'Student' => 'Students',
            'Parent' => 'Parents',
            'Admin', 'Teacher', 'Bursar' => 'Staff',
            default => 'Students',
        };

        $today = now()->toDateString();

        $announcements = Announcement::where('is_published', true)
            ->where(function ($q) use ($audience) {
                $q->where('audience', 'All')->orWhere('audience', $audience);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', $today);
            })
            ->orderByDesc('posted_date')
            ->get()
            ->map(fn (Announcement $a) => [
                'id' => $a->announcement_id,
                'title' => $a->title,
                'content' => $a->content,
                'audience' => $a->audience,
                'postedDate' => $a->posted_date,
                'expiryDate' => $a->expiry_date,
            ]);

        return response()->json($announcements);
    }
}
