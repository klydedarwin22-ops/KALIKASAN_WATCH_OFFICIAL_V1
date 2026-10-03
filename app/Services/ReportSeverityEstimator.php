<?php

namespace App\Services;

class ReportSeverityEstimator
{
    public function estimate(string $category, string $description): string
    {
        $description = mb_strtolower($description);

        if (preg_match('/\b(severe|critical|emergency|life-threatening|immediate danger|widespread|large-scale|massive|overflowing|submerged|toxic|chemical|contaminated|collapse)\b/u', $description)) {
            return 'high';
        }

        if (preg_match('/\b(minor|small|localized|contained|limited|slight|shallow|few items)\b/u', $description)) {
            return 'low';
        }

        if (preg_match('/\b(persistent|repeated|spreading|significant|moderate|multiple|several|blocked drain|large amount|large pile)\b/u', $description)) {
            return 'medium';
        }

        return match ($category) {
            'flooding' => 'high',
            'illegal_dumping', 'soil_erosion' => 'medium',
            default => 'low',
        };
    }
}