<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\View\View;

/**
 * Controller for the map visualization page.
 * Returns all reports with geolocation data for Leaflet.js rendering.
 */
class MapController extends Controller
{
    /**
     * Display the map view with all reports as markers.
     */
    public function index(): View
    {
        $reports = Report::with('user')
            ->select(['id', 'title', 'category', 'severity', 'status', 'latitude', 'longitude', 'created_at', 'user_id'])
            ->where('status', '!=', 'rejected')
            ->latest()
            ->get();

        return view('map.index', [
            'reports' => $reports,
            'categories' => Report::CATEGORIES,
        ]);
    }
}
